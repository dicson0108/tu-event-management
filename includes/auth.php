<?php
require_once __DIR__ . '/../config/db.php';

function start_session_safe(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_samesite', 'Lax');
        session_start();
    }
}

function is_valid_taylors_email(string $email): bool {
    $email = strtolower(trim($email));
    return (bool) preg_match('/^[a-zA-Z0-9._%+-]+@(sd\.)?taylors\.edu\.my$/', $email);
}

function is_logged_in(): bool {
    start_session_safe();
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    start_session_safe();
    if (!is_logged_in()) {
        return null;
    }
    
    // Cache user in static variable during request
    static $cached_user = null;
    if ($cached_user !== null && $cached_user['id'] === (int)$_SESSION['user_id']) {
        return $cached_user;
    }

    $db = get_db_connection();
    $stmt = $db->prepare("SELECT id, name, preferred_name, email, role, status, avatar, phone, faculty, created_at FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] === 'suspended') {
        logout_user();
        return null;
    }

    $cached_user = $user;
    return $user;
}

function login_user(array $user): void {
    start_session_safe();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
}

function logout_user(): void {
    start_session_safe();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

function require_login(?string $redirect = null): array {
    if (!is_logged_in()) {
        $target = $redirect ?? $_SERVER['REQUEST_URI'] ?? '/';
        header("Location: " . app_url("/login.php?redirect=") . urlencode($target));
        exit;
    }
    $user = current_user();
    if (!$user) {
        header("Location: " . app_url("/login.php?msg=") . urlencode("Account inactive or not found"));
        exit;
    }
    return $user;
}

function require_admin(): array {
    $user = require_login();
    if ($user['role'] !== 'admin') {
        render_error_page(403);
    }
    return $user;
}
