<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

$admin_user = require_admin();
$current_admin_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= isset($admin_page_title) ? e($admin_page_title) . " · Taylor's Admin Workspace" : "Admin Workspace · Taylor's Campus Events" ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='15' fill='%23C81E27'/><text x='50%' y='68%' dominant-baseline='middle' text-anchor='middle' font-family='serif' font-weight='900' font-size='65' fill='white'>t</text></svg>">
</head>
<body style="background:var(--bg-page);">

<div class="admin-shell">
    <!-- Left Sidebar -->
    <aside class="admin-sidebar">
        <a href="/admin/index.php" class="brand-group">
            <div class="brand-logo-icon">t</div>
            <div class="brand-text">
                <span class="brand-title">Taylor's</span>
                <span class="brand-subtitle">CAMPUS EVENTS / ADMIN</span>
            </div>
        </a>

        <nav class="admin-nav">
            <a href="/admin/index.php" class="admin-nav-item <?= in_array($current_admin_page, ['index.php', 'dashboard.php']) ? 'active' : '' ?>">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                Dashboard
            </a>
            <a href="/admin/events.php" class="admin-nav-item <?= in_array($current_admin_page, ['events.php', 'event-edit.php', 'attendees.php']) ? 'active' : '' ?>">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                Manage events
            </a>
            <a href="/admin/users.php" class="admin-nav-item <?= $current_admin_page === 'users.php' ? 'active' : '' ?>">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Users
            </a>
            <a href="/admin/comments.php" class="admin-nav-item <?= $current_admin_page === 'comments.php' ? 'active' : '' ?>">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Comments
            </a>
            <a href="/admin/categories.php" class="admin-nav-item <?= $current_admin_page === 'categories.php' ? 'active' : '' ?>">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                Categories
            </a>
        </nav>

        <div class="admin-sidebar-bottom">
            <a href="/events.php" class="btn-link" style="font-size:13px; color:var(--text-secondary); display:flex; align-items:center; gap:6px; padding:0; margin-bottom:16px;">
                <span>↗ View campus website</span>
            </a>
            <div style="font-size: 11px; color: var(--text-muted); line-height: 1.4;">
                <strong style="color:var(--text-secondary); display:block; margin-bottom:2px;">Make campus happen.</strong>
                Manage experiences that bring our community together.
            </div>
        </div>
    </aside>

    <!-- Main Admin Body -->
    <div class="admin-main">
        <!-- Top bar -->
        <header class="admin-topbar">
            <div style="font-size: 13px; color: var(--text-muted);">
                Workspace / <span style="color:var(--text-main); font-weight:600;"><?= e($admin_breadcrumb ?? 'Dashboard') ?></span>
            </div>

            <div style="display:flex; align-items:center; gap:16px;">
                <span class="badge" style="background:#fee2e2; color:var(--color-primary); font-size:12px;">Admin</span>
                <button type="button" aria-label="Notifications" style="background:none; border:none; cursor:pointer; color:var(--text-muted); display:flex;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                </button>
                <div style="font-size:13.5px; font-weight:700; color:var(--text-main);">
                    <?= e($admin_user['name']) ?>
                </div>
            </div>
        </header>

        <div class="admin-content-inner">
