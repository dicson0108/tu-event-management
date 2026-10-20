<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';

$page_title = "Create an account · Taylor's Campus Events";

$name_val = trim($_POST['name'] ?? '');
$email_val = trim($_POST['email'] ?? '');
$errors = [];
$duplicate_email = false;
$success_user = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors['general'] = "Session expired. Please try again.";
    } else {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $terms = !empty($_POST['terms']);

        if (empty($name_val)) {
            $errors['name'] = "Full name is required.";
        }

        if (empty($email_val)) {
            $errors['email'] = "Taylor's email is required.";
        } elseif (!is_valid_taylors_email($email_val)) {
            $errors['email'] = "Use @taylors.edu.my or @sd.taylors.edu.my.";
        }

        if (strlen($password) < 8 || !preg_match('/[0-9]/', $password) || !preg_match('/[a-zA-Z]/', $password)) {
            $errors['password'] = "Use at least 8 characters, a letter and a number.";
        }

        if ($password !== $confirm_password) {
            $errors['confirm_password'] = "Your passwords don't match.";
        }

        if (!$terms) {
            $errors['terms'] = "You must agree to the terms to proceed.";
        }

        if (empty($errors)) {
            $db = get_db_connection();
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email_val]);
            if ($stmt->fetch()) {
                $duplicate_email = true;
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $ins = $db->prepare("
                    INSERT INTO users (name, email, password_hash, role, status) 
                    VALUES (:name, :email, :hash, 'user', 'active')
                ");
                $ins->execute([
                    ':name' => $name_val,
                    ':email' => $email_val,
                    ':hash' => $hash
                ]);
                $success_user = [
                    'name' => $name_val,
                    'email' => $email_val
                ];
            }
        }
    }
}

// Sample poster for left hero: Grow Together
$grow_poster = [
    'id' => 5,
    'title' => 'GROW TOGETHER',
    'poster_theme' => 'green',
    'description' => 'Small acts. A greener campus.',
    'event_date' => '2026-11-14',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="app-base-path" content="<?= APP_BASE_PATH ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <link rel="stylesheet" href="<?= APP_BASE_PATH ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= APP_BASE_PATH ?>/assets/css/auth.css">
</head>
<body style="background: var(--bg-surface-subtle);">

<header class="site-header">
    <div class="header-inner">
        <a href="<?= APP_BASE_PATH ?>/index.php" class="brand-group">
            <?= get_site_logo_html() ?>
            <div class="brand-text">
                <span class="brand-title">Taylor's</span>
                <span class="brand-subtitle">CAMPUS EVENTS</span>
            </div>
        </a>

        <nav class="nav-links">
            <a href="<?= APP_BASE_PATH ?>/index.php" class="nav-link">Home</a>
            <a href="<?= APP_BASE_PATH ?>/events.php" class="nav-link">Explore events</a>
            <a href="<?= APP_BASE_PATH ?>/my-events.php" class="nav-link">My events</a>
        </nav>

        <div class="nav-actions">
            <a href="<?= APP_BASE_PATH ?>/login.php" class="btn-link">Log in</a>
            <a href="<?= APP_BASE_PATH ?>/register.php" class="btn-primary">Register</a>
        </div>
    </div>
</header>

<main class="app-container">
    <div class="auth-split-layout">
        <!-- Left Hero -->
        <div>
            <div class="section-label">YOUR CAMPUS EXPERIENCE STARTS HERE</div>
            <h1 style="font-size: 46px; font-weight: 800; line-height: 1.1; margin-bottom: 16px;">
                A new connection.<br>A new possibility.
            </h1>
            <p style="font-size: 16px; color: var(--text-secondary); margin-bottom: 32px; max-width: 440px;">
                One account for the moments that matter. Discover, join and keep track of your campus life.
            </p>

            <div style="max-width: 420px; border-radius: var(--radius-md); overflow:hidden; margin-bottom:16px;">
                <?= render_event_poster($grow_poster) ?>
            </div>
            <div style="font-size: 12.5px; color: var(--text-muted);">
                Made for the Taylor's community. Made for you.
            </div>
        </div>

        <!-- Right Card -->
        <div class="auth-form-card">
            <?php if ($success_user): ?>
                <!-- Success State (Matches Account validation states.png) -->
                <div style="text-align:left;">
                    <div style="width:48px; height:48px; border-radius:50%; border:2px solid #059669; display:flex; align-items:center; justify-content:center; color:#059669; margin-bottom:20px;">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>

                    <h2 class="auth-card-title">You're part of it.</h2>

                    <div class="status-alert-box success" style="margin: 20px 0;">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                        <div>
                            <strong>Your account is ready.</strong>
                            <div>Welcome to Taylor's Campus Events, <?= e($success_user['name']) ?>.</div>
                        </div>
                    </div>

                    <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 28px; line-height: 1.5;">
                        Log in with <strong><?= e($success_user['email']) ?></strong> and start exploring your campus community.
                    </p>

                    <a href="<?= APP_BASE_PATH ?>/login.php" class="btn-primary" style="width:100%; padding:12px; font-size:15px; justify-content:center;">
                        Continue to login
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                </div>

            <?php elseif ($duplicate_email): ?>
                <!-- Duplicate Email State (Matches Account validation states.png) -->
                <div>
                    <h2 class="auth-card-title" style="margin-bottom: 20px;">Already one of us?</h2>

                    <div class="status-alert-box danger" style="margin-bottom: 24px;">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                        <div>
                            <strong>This email is already registered.</strong>
                            <div>Log in or reset your password to access your account.</div>
                        </div>
                    </div>

                    <div class="form-field">
                        <label class="form-label" style="margin-bottom: 6px; display:block;">Taylor's email</label>
                        <input type="email" value="<?= e($email_val) ?>" class="form-input input-error" readonly>
                    </div>

                    <div style="margin-top: 24px; display:flex; flex-direction:column; gap:16px;">
                        <a href="<?= APP_BASE_PATH ?>/login.php" class="btn-primary" style="padding:12px; font-size:14px; justify-content:center;">
                            Go to login
                        </a>
                        <a href="#" style="color:var(--color-primary); font-size:13.5px; font-weight:600;">
                            Reset password ↗
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- Standard Register Form (Matches Create an account.png) -->
                <div style="display:flex; align-items:baseline; justify-content:space-between; margin-bottom: 6px;">
                    <h2 class="auth-card-title">Create your account</h2>
                </div>
                <p class="auth-card-sub">
                    Already part of the community? <a href="<?= APP_BASE_PATH ?>/login.php" style="color:var(--color-primary); font-weight:600;">Log in</a>
                </p>

                <?php if (!empty($errors['general'])): ?>
                    <div class="status-alert-box danger" style="margin-bottom: 20px;">
                        <div><?= e($errors['general']) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= APP_BASE_PATH ?>/register.php">
                    <?= csrf_field() ?>

                    <div class="form-field">
                        <label class="form-label" style="margin-bottom: 6px; display:block;">Full name</label>
                        <input type="text" name="name" value="<?= e($name_val) ?>" class="form-input <?= isset($errors['name']) ? 'input-error' : '' ?>" required placeholder="Aisha Tan">
                        <?php if (isset($errors['name'])): ?>
                            <div class="field-error-msg"><?= e($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label class="form-label" style="margin-bottom: 6px; display:block;">Taylor's email</label>
                        <input type="email" name="email" value="<?= e($email_val) ?>" class="form-input <?= isset($errors['email']) ? 'input-error' : '' ?>" required placeholder="aisha.tan@sd.taylors.edu.my">
                        <?php if (isset($errors['email'])): ?>
                            <div class="field-error-msg"><?= e($errors['email']) ?></div>
                        <?php else: ?>
                            <div style="font-size:12px; color:var(--text-muted); margin-top:4px;">Use your @taylors.edu.my or @sd.taylors.edu.my email.</div>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label class="form-label" style="margin-bottom: 6px; display:block;">Password</label>
                        <input type="password" name="password" class="form-input <?= isset($errors['password']) ? 'input-error' : '' ?>" required placeholder="••••••••••••">
                        <?php if (isset($errors['password'])): ?>
                            <div class="field-error-msg"><?= e($errors['password']) ?></div>
                        <?php else: ?>
                            <div style="font-size:12px; color:var(--text-muted); margin-top:4px;">At least 8 characters, including a letter and a number.</div>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label class="form-label" style="margin-bottom: 6px; display:block;">Confirm password</label>
                        <input type="password" name="confirm_password" class="form-input <?= isset($errors['confirm_password']) ? 'input-error' : '' ?>" required placeholder="••••••••••••">
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="field-error-msg"><?= e($errors['confirm_password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-field" style="margin-bottom: 24px;">
                        <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer; font-size:13px; color:var(--text-secondary); line-height:1.4;">
                            <input type="checkbox" name="terms" checked required style="accent-color:var(--color-primary); margin-top:2px;">
                            <span>I agree to the Terms of use and Privacy policy, and to keeping our campus community welcoming.</span>
                        </label>
                    </div>

                    <button type="submit" class="btn-primary" style="width:100%; padding:12px; font-size:15px; margin-bottom: 20px;">
                        Create account
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </button>

                    <div style="font-size: 11.5px; color: var(--text-muted); line-height: 1.4;">
                        For Taylor's students and staff. Your email is only used for your campus account and event updates.
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<footer class="site-footer">
    <div class="app-container">
        <div class="footer-bottom-bar">
            <span>© 2026 Taylor's University. All rights reserved.</span>
            <div style="display:flex; gap:20px; align-items:center;">
                <a href="<?= APP_BASE_PATH ?>/privacy.php" class="footer-link">Privacy policy</a>
                <a href="<?= APP_BASE_PATH ?>/terms.php" class="footer-link">Terms of use</a>
                <span style="color:var(--text-muted)">Lakeside Campus, Malaysia</span>
            </div>
        </div>
    </div>
</footer>

<!-- Floating Back To Top Button -->
<button type="button" id="back-to-top" class="back-to-top-btn" aria-label="Back to top" title="Back to top">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>
</button>

<script src="<?= APP_BASE_PATH ?>/assets/js/main.js"></script>
</body>
</html>
