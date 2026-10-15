<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/helpers.php';

$current_user = current_user();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= isset($page_title) ? e($page_title) . " · Taylor's Campus Events" : "Taylor's Campus Events · Go beyond the classroom" ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='15' fill='%23C81E27'/><text x='50%' y='68%' dominant-baseline='middle' text-anchor='middle' font-family='serif' font-weight='900' font-size='65' fill='white'>t</text></svg>">
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <a href="/index.php" class="brand-group">
            <div class="brand-logo-icon">t</div>
            <div class="brand-text">
                <span class="brand-title">Taylor's</span>
                <span class="brand-subtitle">CAMPUS EVENTS</span>
            </div>
        </a>

        <nav class="nav-links">
            <a href="/index.php" class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>">Home</a>
            <a href="/events.php" class="nav-link <?= in_array($current_page, ['events.php', 'event-detail.php']) ? 'active' : '' ?>">Explore events</a>
            <a href="/my-events.php" class="nav-link <?= $current_page === 'my-events.php' ? 'active' : '' ?>">My events</a>
        </nav>

        <div class="nav-actions">
            <?php if ($current_user): ?>
                <!-- Notification bell icon -->
                <button type="button" aria-label="Notifications" style="background:none; border:none; cursor:pointer; color:#4b5563; padding:6px; display:flex; align-items:center;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                </button>

                <div class="user-menu-pill">
                    <div class="avatar-initials"><?= e(get_initials($current_user['preferred_name'] ?? $current_user['name'])) ?></div>
                    <span class="user-name-text"><?= e($current_user['preferred_name'] ?? $current_user['name']) ?></span>
                    <svg class="dropdown-arrow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>

                    <div class="user-dropdown">
                        <a href="/profile.php" class="dropdown-item">My Profile</a>
                        <a href="/my-events.php" class="dropdown-item">My Registrations</a>
                        <?php if ($current_user['role'] === 'admin'): ?>
                            <div class="dropdown-divider"></div>
                            <a href="/admin/index.php" class="dropdown-item" style="color:var(--color-primary); font-weight:600;">Admin Workspace</a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="/logout.php" class="dropdown-item" style="color:#dc2626;">Log out</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="/login.php" class="btn-link">Log in</a>
                <a href="/register.php" class="btn-primary">Register</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main>
