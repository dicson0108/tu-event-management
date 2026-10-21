<?php
require_once __DIR__ . '/app.php';
require_once __DIR__ . '/setup.php';
// Set these to your XAMPP/MAMP MySQL settings. The database is created on first use.
// MAMP commonly uses DB_PASS = 'root' and DB_PORT = 8889; check your installation.
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'tu_event_management');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', 3306);

function get_db_connection(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            initialize_database($connection, DB_NAME);
            $pdo = $connection;
        } catch (Throwable $e) {
            http_response_code(500);
            die("Database connection or setup failed. Check config/db.php and ensure your MySQL account can create the database and tables. " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
        }
    }
    return $pdo;
}
