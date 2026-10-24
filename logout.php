<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Use the log out button to end your session.');
}
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(400);
    exit('Invalid or expired CSRF token.');
}
logout_user();
header("Location: " . app_url("/login.php"));
exit;
