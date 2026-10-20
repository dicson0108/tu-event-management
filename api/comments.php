<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$user = require_login();
$user_id = (int)$user['id'];
$is_admin = ($user['role'] === 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token.");
    }

    $action = $_POST['action'] ?? '';
    $redirect = $_POST['redirect'] ?? app_url('/my-events.php');
    $db = get_db_connection();

    if ($action === 'delete') {
        $comment_id = (int)($_POST['comment_id'] ?? 0);
        if ($is_admin) {
            $stmt = $db->prepare("DELETE FROM comments WHERE id = :id");
            $stmt->execute([':id' => $comment_id]);
        } else {
            $stmt = $db->prepare("DELETE FROM comments WHERE id = :id AND user_id = :uid");
            $stmt->execute([':id' => $comment_id, ':uid' => $user_id]);
        }
        header("Location: " . $redirect);
        exit;
    }
}

header("Location: " . app_url("/my-events.php"));
exit;
