<?php
// Run only against a disposable local MariaDB/MySQL server on a non-default port.
$port = (int)($argv[1] ?? 0);
if ($port < 1024 || $port === 3306) {
    fwrite(STDERR, "Usage: php tests/database-setup.php <disposable-server-port>\n");
    exit(1);
}
if (!is_file(__DIR__ . '/../config/setup.php')) {
    fwrite(STDERR, "FAIL: automatic database setup is not implemented.\n");
    exit(1);
}
require __DIR__ . '/../config/setup.php';
$db = new PDO("mysql:host=127.0.0.1;port=$port;charset=utf8mb4", 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
if (($argv[2] ?? '') === '--worker') {
    $name = $argv[3] ?? '';
    if (!preg_match('/^tu_setup_test_[a-f0-9]{10}_concurrent$/', $name)) exit(1);
    initialize_database($db, $name);
    exit(0);
}
function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
$prefix = 'tu_setup_test_' . bin2hex(random_bytes(5));
$databases = [];
try {
    $name = $prefix . '_fresh';
    $databases[] = $name;
    initialize_database($db, $name);
    check((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 6, 'Fresh setup must seed users.');
    check((int)$db->query('SELECT COUNT(*) FROM events')->fetchColumn() === 8, 'Fresh setup must seed events.');
    check(password_verify('Password123!', $db->query('SELECT password_hash FROM users WHERE id = 1')->fetchColumn()), 'Seed login must work.');
    $db->exec("UPDATE users SET name = 'Preserved name' WHERE id = 1");
    $db->exec('DELETE FROM users WHERE id = 6');
    initialize_database($db, $name);
    check($db->query('SELECT name FROM users WHERE id = 1')->fetchColumn() === 'Preserved name', 'Repeat setup must preserve edits.');
    check((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 5, 'Repeat setup must not recreate deleted demo records.');
    // Installations created before automatic setup have no setup metadata.
    $db->exec('DROP TABLE app_setup');
    initialize_database($db, $name);
    check((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 5, 'Legacy installations must not be reseeded.');
    echo "PASS: new/custom database, demo login, repeat requests and legacy data preservation.\n";

    $name = $prefix . '_empty';
    $databases[] = $name;
    $db->exec("CREATE DATABASE `$name`");
    initialize_database($db, $name);
    check((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 6, 'Existing empty database must initialize.');
    echo "PASS: existing empty database.\n";

    $name = $prefix . '_partial';
    $databases[] = $name;
    $db->exec("CREATE DATABASE `$name`");
    $db->exec("USE `$name`");
    $db->exec('CREATE TABLE users (id INT PRIMARY KEY)');
    $db->exec('INSERT INTO users VALUES (99)');
    $rejected = false;
    try { initialize_database($db, $name); } catch (RuntimeException $e) { $rejected = true; }
    check($rejected, 'An unknown partial database must be rejected.');
    check((int)$db->query('SELECT id FROM users')->fetchColumn() === 99, 'Partial database records must be preserved.');
    echo "PASS: partial database is rejected without deleting data.\n";

    $name = $prefix . '_retry';
    $databases[] = $name;
    initialize_database($db, $name);
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['comments', 'registrations', 'events', 'categories', 'users'] as $table) $db->exec("TRUNCATE TABLE `$table`");
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    $db->exec('UPDATE app_setup SET completed = 0');
    $db->exec("CREATE TRIGGER fail_seed BEFORE INSERT ON comments FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Test seed failure'");
    $failed = false;
    try { initialize_database($db, $name); } catch (PDOException $e) { $failed = true; }
    check($failed, 'Seed failure must be reported.');
    check((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0, 'Failed seed must roll back.');
    check((int)$db->query('SELECT completed FROM app_setup WHERE id = 1')->fetchColumn() === 0, 'Failed seed must not mark setup complete.');
    $db->exec('DROP TRIGGER fail_seed');
    initialize_database($db, $name);
    check((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 6, 'Interrupted setup must recover.');
    echo "PASS: failed seed rolls back and setup can recover.\n";

    $db->exec('DELETE FROM app_setup');
    initialize_database($db, $name);
    check((int)$db->query('SELECT completed FROM app_setup WHERE id = 1')->fetchColumn() === 1, 'An interrupted setup marker must recover.');
    echo "PASS: missing setup marker row recovers.\n";

    $name = $prefix . '_concurrent';
    $databases[] = $name;
    $workers = [];
    for ($i = 0; $i < 2; $i++) {
        $process = proc_open([PHP_BINARY, __FILE__, (string)$port, '--worker', $name], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        check(is_resource($process), 'Could not start concurrent setup test.');
        fclose($pipes[0]);
        $workers[] = [$process, $pipes];
    }
    foreach ($workers as [$process, $pipes]) {
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        check(proc_close($process) === 0, 'Concurrent setup failed: ' . $output);
    }
    $db->exec("USE `$name`");
    check((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 6, 'Concurrent setup must seed once.');
    check((int)$db->query('SELECT COUNT(*) FROM comments')->fetchColumn() === 3, 'Concurrent setup must not duplicate reviews.');
    echo "PASS: simultaneous first requests initialize once.\n";
} finally {
    foreach ($databases as $name) $db->exec("DROP DATABASE IF EXISTS `$name`");
}
