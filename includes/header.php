<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/helpers.php';

$current_user = current_user();
$current_page = basename($_SERVER['PHP_SELF']);

$css_map = [
    'index.php' => 'home.css',
    'events.php' => 'events.css',
    'event-detail.php' => 'event-detail.css',
    'my-events.php' => 'my-events.css',
    'past-event.php' => 'my-events.css',
    'profile.php' => 'profile.css',
    'login.php' => 'auth.css',
    'register.php' => 'auth.css',
    'privacy.php' => 'legal.css',
    'terms.php' => 'legal.css',
];
$page_css = $extra_css ?? $css_map[$current_page] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= isset($page_title) ? e($page_title) . " · Taylor's Campus Events" : "Taylor's Campus Events · Go beyond the classroom" ?></title>
    <!-- Main Core Stylesheet -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <!-- Page Specific Stylesheet -->
    <?php if ($page_css): ?>
        <link rel="stylesheet" href="/assets/css/<?= e($page_css) ?>">
    <?php endif; ?>
    <link rel="icon" type="image/png" href="/assets/images/logo/logo.png">
</head>
<body>

<a href="#main-content" class="skip-link">Skip to main content</a>

<header class="site-header">
    <div class="header-inner">
        <a href="/index.php" class="brand-group" aria-label="Taylor's Campus Events Home">
            <?= get_site_logo_html() ?>
            <div class="brand-text">
                <span class="brand-title">Taylor's</span>
                <span class="brand-subtitle">CAMPUS EVENTS</span>
            </div>
        </a>

        <nav class="nav-links" aria-label="Main Navigation">
            <a href="/index.php" class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>">Home</a>
            <a href="/events.php" class="nav-link <?= in_array($current_page, ['events.php', 'event-detail.php']) ? 'active' : '' ?>">Explore events</a>
            <a href="/my-events.php" class="nav-link <?= in_array($current_page, ['my-events.php', 'past-event.php']) ? 'active' : '' ?>">My events</a>
        </nav>

        <div class="nav-actions">
            <?php if ($current_user): ?>
                <div class="user-menu-pill" tabindex="0" role="button" aria-haspopup="true" aria-expanded="false" aria-label="User menu for <?= e($current_user['preferred_name'] ?? $current_user['name']) ?>">
                    <?= render_user_avatar($current_user, 32) ?>
                    <span class="user-name-text"><?= e($current_user['preferred_name'] ?? $current_user['name']) ?></span>
                    <svg class="dropdown-arrow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>

                    <div class="user-dropdown" role="menu">
                        <div class="user-dropdown-header">
                            <strong><?= e($current_user['name']) ?></strong>
                            <span><?= e($current_user['email']) ?></span>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="/profile.php" class="dropdown-item" role="menuitem">My Profile</a>
                        <a href="/my-events.php" class="dropdown-item" role="menuitem">My Registrations</a>
                        <?php if ($current_user['role'] === 'admin'): ?>
                            <div class="dropdown-divider"></div>
                            <a href="/admin/index.php" class="dropdown-item admin-link" role="menuitem">
                                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                                Admin Workspace
                            </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="/logout.php" class="dropdown-item text-danger" role="menuitem">Log out</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="/login.php" class="btn-link">Log in</a>
                <a href="/register.php" class="btn-primary">Register</a>
            <?php endif; ?>

            <!-- Mobile Hamburger Toggle Button -->
            <button type="button" class="mobile-menu-btn" aria-label="Toggle navigation menu" aria-expanded="false">
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
            </button>
        </div>
    </div>
</header>

<!-- Mobile Navigation Drawer -->
<div class="mobile-drawer-backdrop" aria-hidden="true"></div>
<aside class="mobile-drawer" aria-label="Mobile Navigation Drawer">
    <div class="mobile-drawer-header">
        <a href="/index.php" class="brand-group">
            <?= get_site_logo_html() ?>
            <div class="brand-text">
                <span class="brand-title">Taylor's</span>
                <span class="brand-subtitle">CAMPUS EVENTS</span>
            </div>
        </a>
        <button type="button" class="mobile-drawer-close" aria-label="Close navigation menu">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
    </div>

    <nav class="mobile-drawer-nav">
        <a href="/index.php" class="mobile-nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>">Home</a>
        <a href="/events.php" class="mobile-nav-link <?= in_array($current_page, ['events.php', 'event-detail.php']) ? 'active' : '' ?>">Explore events</a>
        <a href="/my-events.php" class="mobile-nav-link <?= in_array($current_page, ['my-events.php', 'past-event.php']) ? 'active' : '' ?>">My events</a>
        <?php if ($current_user): ?>
            <a href="/profile.php" class="mobile-nav-link <?= $current_page === 'profile.php' ? 'active' : '' ?>">My profile</a>
            <?php if ($current_user['role'] === 'admin'): ?>
                <a href="/admin/index.php" class="mobile-nav-link" style="color:var(--color-primary); font-weight:700;">Admin Workspace ↗</a>
            <?php endif; ?>
            <div class="mobile-drawer-footer">
                <a href="/logout.php" class="btn-secondary" style="width:100%; justify-content:center; color:#dc2626;">Log out</a>
            </div>
        <?php else: ?>
            <div class="mobile-drawer-auth">
                <a href="/login.php" class="btn-secondary" style="width:100%; justify-content:center;">Log in</a>
                <a href="/register.php" class="btn-primary" style="width:100%; justify-content:center;">Register</a>
            </div>
        <?php endif; ?>
    </nav>
</aside>

<main id="main-content">
