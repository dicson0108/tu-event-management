<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';

if (is_logged_in()) {
    $user = current_user();
    if ($user && $user['role'] === 'admin') {
        header("Location: /admin/index.php");
    } else {
        header("Location: /events.php");
    }
    exit;
}

$page_title = "Log in · Taylor's Campus Events";
$error = null;
$email_val = trim($_POST['email'] ?? '');
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '/events.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Session expired. Please try again.";
    } else {
        $password = $_POST['password'] ?? '';

        if (empty($email_val) || empty($password)) {
            $error = "Email or password is incorrect. Check your details and try again.";
        } else {
            $db = get_db_connection();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email_val]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['status'] === 'suspended') {
                    $error = "Your account has been suspended. Please contact the administrator.";
                } else {
                    login_user($user);
                    if ($user['role'] === 'admin' && empty($_GET['redirect'])) {
                        header("Location: /admin/index.php");
                    } else {
                        header("Location: " . $redirect);
                    }
                    exit;
                }
            } else {
                $error = "Email or password is incorrect. Check your details and try again.";
            }
        }
    }
}

// Sample poster for left hero: Lakeside After Hours
$after_hours_poster = [
    'id' => 2,
    'title' => 'AFTER HOURS',
    'poster_theme' => 'dark',
    'description' => 'Good music. Great company.',
    'event_date' => '2026-10-30',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body style="background: var(--bg-surface-subtle);">

<header class="site-header">
    <div class="header-inner">
        <a href="/index.php" class="brand-group">
            <?= get_site_logo_html() ?>
            <div class="brand-text">
                <span class="brand-title">Taylor's</span>
                <span class="brand-subtitle">CAMPUS EVENTS</span>
            </div>
        </a>

        <nav class="nav-links">
            <a href="/index.php" class="nav-link">Home</a>
            <a href="/events.php" class="nav-link">Explore events</a>
            <a href="/my-events.php" class="nav-link">My events</a>
        </nav>

        <div class="nav-actions">
            <a href="/login.php" class="btn-link" style="color:var(--text-main); font-weight:600;">Log in</a>
            <a href="/register.php" class="btn-primary">Register</a>
        </div>
    </div>
</header>

<main class="app-container">
    <div class="auth-split-layout">
        <!-- Left Hero -->
        <div>
            <div class="section-label">WELCOME BACK TO CAMPUS LIFE</div>
            <h1 style="font-size: 46px; font-weight: 800; line-height: 1.1; margin-bottom: 16px;">
                Your next great<br>moment is waiting.
            </h1>
            <p style="font-size: 16px; color: var(--text-secondary); margin-bottom: 32px; max-width: 440px;">
                Pick up where you left off. Your events, your community, all in one place.
            </p>

            <div style="max-width: 420px; border-radius: var(--radius-md); overflow:hidden;">
                <?= render_event_poster($after_hours_poster) ?>
            </div>
        </div>

        <!-- Right Card -->
        <div class="auth-form-card">
            <div style="font-size: 11px; font-weight: 700; color: var(--color-primary); letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 8px;">
                TAYLOR'S CAMPUS EVENTS
            </div>
            <h2 class="auth-card-title">Good to see you again.</h2>
            <p class="auth-card-sub">Log in with your Taylor's email to save your seat.</p>

            <?php if ($error): ?>
                <div class="status-alert-box danger" style="margin-bottom: 24px;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                    <div>
                        <strong>Email or password is incorrect.</strong>
                        <div>Check your details and try again.</div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="/login.php">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect" value="<?= e($redirect) ?>">

                <div class="form-field">
                    <label class="form-label" style="margin-bottom: 6px; display:block;">Taylor's email</label>
                    <input type="email" name="email" value="<?= e($email_val ?: 'aisha.tan@sd.taylors.edu.my') ?>" 
                           class="form-input <?= $error ? 'input-error' : '' ?>" required placeholder="name@sd.taylors.edu.my">
                </div>

                <div class="form-field">
                    <label class="form-label" style="margin-bottom: 6px; display:block;">Password</label>
                    <input type="password" name="password" class="form-input <?= $error ? 'input-error' : '' ?>" 
                           required value="Password123!" placeholder="••••••••••••">
                </div>

                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 24px; font-size:13px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" name="remember" checked style="accent-color:var(--color-primary);">
                        <span>Remember me</span>
                    </label>
                    <a href="#" style="color:var(--color-primary); font-weight:600;">Forgot password?</a>
                </div>

                <button type="submit" class="btn-primary" style="width:100%; padding:12px; font-size:15px; margin-bottom: 24px;">
                    Log in
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </button>

                <div style="text-align:center; font-size: 13.5px; color: var(--text-secondary); padding-top: 16px; border-top: 1px solid var(--border-subtle);">
                    New to campus events? <a href="/register.php" style="color:var(--color-primary); font-weight:600;">Create an account</a>
                </div>

                <div style="font-size: 11.5px; color: var(--text-muted); text-align:center; margin-top: 12px; line-height: 1.4;">
                    Students and staff use the same login. Admin access is assigned by the university.
                </div>
            </form>
        </div>
    </div>
</main>

<footer class="site-footer">
    <div class="app-container">
        <div class="footer-bottom-bar">
            <span>© 2026 Taylor's University. All rights reserved.</span>
            <div style="display:flex; gap:20px; align-items:center;">
                <a href="/privacy.php" class="footer-link">Privacy policy</a>
                <a href="/terms.php" class="footer-link">Terms of use</a>
                <span style="color:var(--text-muted)">Lakeside Campus, Malaysia</span>
            </div>
        </div>
    </div>
</footer>

<!-- Floating Back To Top Button -->
<button type="button" id="back-to-top" class="back-to-top-btn" aria-label="Back to top" title="Back to top">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>
</button>

<script src="/assets/js/main.js"></script>
</body>
</html>
