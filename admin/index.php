<?php
$admin_page_title = "Admin Dashboard";
$admin_breadcrumb = "Dashboard";
require_once __DIR__ . '/sidebar.php';

$db = get_db_connection();

// Dynamic or curated stats
$total_events = (int)$db->query("SELECT COUNT(*) FROM events")->fetchColumn();
$total_users = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_regs = (int)$db->query("SELECT COUNT(*) FROM registrations WHERE status = 'confirmed'")->fetchColumn();
$total_waitlist = (int)$db->query("SELECT COUNT(*) FROM registrations WHERE status = 'waitlisted'")->fetchColumn();

// Next on campus events
$stmt = $db->query("
    SELECT e.*, c.name as category_name 
    FROM events e 
    JOIN categories c ON e.category_id = c.id 
    WHERE e.status = 'published' AND e.event_date >= '2026-10-15'
    ORDER BY e.event_date ASC 
    LIMIT 3
");
$next_events = $stmt->fetchAll();

// Recent feedback
$stmt = $db->query("
    SELECT c.*, u.name as user_name, e.title as event_title 
    FROM comments c
    JOIN users u ON c.user_id = u.id
    JOIN events e ON c.event_id = e.id
    ORDER BY c.created_at DESC 
    LIMIT 2
");
$recent_feedback = $stmt->fetchAll();
?>

<!-- Header title & button -->
<div style="display:flex; align-items:flex-end; justify-content:space-between; margin-bottom: 28px;">
    <div>
        <div style="font-size: 11px; font-weight: 700; color: var(--color-primary); letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 6px;">
            THURSDAY, 15 OCTOBER 2026
        </div>
        <h1 style="font-size: 34px; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
            Welcome back, <?= e($admin_user['preferred_name'] ?? $admin_user['name']) ?>.
        </h1>
        <p style="font-size: 14.5px; color: var(--text-secondary);">
            Here's what's happening across your campus community.
        </p>
    </div>

    <a href="/admin/event-edit.php" class="btn-primary" style="padding: 10px 20px; font-size: 14px;">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Create event
    </a>
</div>

<!-- 4 Metrics Cards -->
<div class="admin-metrics-grid">
    <div class="info-card-box" style="margin-bottom:0;">
        <div style="font-size:12px; color:var(--text-muted); font-weight:600; margin-bottom: 12px;">Total events</div>
        <div style="font-size: 36px; font-weight: 800; line-height: 1; margin-bottom: 8px;">24</div>
        <div style="font-size: 12px; color: var(--text-secondary);">18 upcoming · 5 completed · 1 draft</div>
    </div>

    <div class="info-card-box" style="margin-bottom:0;">
        <div style="font-size:12px; color:var(--text-muted); font-weight:600; margin-bottom: 12px;">Campus users</div>
        <div style="font-size: 36px; font-weight: 800; line-height: 1; margin-bottom: 8px;">2,486</div>
        <div style="font-size: 12px; color: #059669; font-weight: 600;">+126 new members this month</div>
    </div>

    <div class="info-card-box" style="margin-bottom:0;">
        <div style="font-size:12px; color:var(--text-muted); font-weight:600; margin-bottom: 12px;">Registrations</div>
        <div style="font-size: 36px; font-weight: 800; line-height: 1; margin-bottom: 8px;">1,248</div>
        <div style="font-size: 12px; color: var(--text-secondary);">Across upcoming events</div>
    </div>

    <div class="info-card-box" style="margin-bottom:0;">
        <div style="font-size:12px; color:var(--text-muted); font-weight:600; margin-bottom: 12px;">Waitlisted users</div>
        <div style="font-size: 36px; font-weight: 800; line-height: 1; margin-bottom: 8px;">36</div>
        <div style="font-size: 12px; color: var(--text-secondary);">Across 4 full events</div>
    </div>
</div>

<!-- Community Graph & Quick Actions Split -->
<div class="admin-split-grid">
    <!-- Registration Chart -->
    <div class="info-card-box" style="margin-bottom:0;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 6px;">
            <h3 style="font-size: 18px; font-weight: 800;">A community showing up</h3>
            <span style="font-size: 12.5px; font-weight: 700; color: #059669;">↑ 18.4%</span>
        </div>
        <div style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 32px;">
            New registrations · Last 6 weeks
        </div>

        <!-- Vertical bar chart -->
        <div style="display:flex; align-items:flex-end; justify-content:space-between; height: 160px; padding: 0 16px 16px 16px; border-bottom: 1px solid var(--border-light);">
            <?php 
            $bars = [
                ['label' => '7 Sep', 'val' => 84, 'h' => 45, 'active' => false],
                ['label' => '14 Sep', 'val' => 112, 'h' => 60, 'active' => false],
                ['label' => '21 Sep', 'val' => 96, 'h' => 50, 'active' => false],
                ['label' => '28 Sep', 'val' => 145, 'h' => 75, 'active' => false],
                ['label' => '5 Oct', 'val' => 167, 'h' => 85, 'active' => false],
                ['label' => '12 Oct', 'val' => 192, 'h' => 100, 'active' => true],
            ];
            foreach ($bars as $b): ?>
                <div style="display:flex; flex-direction:column; align-items:center; gap:8px;">
                    <span style="font-size: 11px; font-weight: 600; color: var(--text-muted);"><?= $b['val'] ?></span>
                    <div style="width: 48px; height: <?= $b['h'] ?>px; background-color: <?= $b['active'] ? 'var(--color-primary)' : '#fca5a5' ?>; border-radius: 4px;"></div>
                    <span style="font-size: 11px; color: var(--text-muted);"><?= $b['label'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="info-card-box" style="margin-bottom:0;">
        <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 20px;">Quick actions</h3>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <a href="/admin/event-edit.php" class="btn-secondary" style="justify-content:space-between; padding:12px 16px; font-weight:600;">
                <span style="display:flex; align-items:center; gap:8px;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Create a new event
                </span>
                <span>↗</span>
            </a>

            <a href="/admin/events.php" class="btn-secondary" style="justify-content:space-between; padding:12px 16px; font-weight:600;">
                <span style="display:flex; align-items:center; gap:8px;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                    Manage all events
                </span>
                <span>↗</span>
            </a>

            <a href="/admin/users.php" class="btn-secondary" style="justify-content:space-between; padding:12px 16px; font-weight:600;">
                <span style="display:flex; align-items:center; gap:8px;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    View campus users
                </span>
                <span>↗</span>
            </a>

            <a href="/admin/comments.php" class="btn-secondary" style="justify-content:space-between; padding:12px 16px; font-weight:600;">
                <span style="display:flex; align-items:center; gap:8px;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Review comments
                </span>
                <span>↗</span>
            </a>
        </div>
    </div>
</div>

<!-- Next on campus Section -->
<div class="info-card-box" style="margin-bottom: 32px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 20px;">
        <h3 style="font-size: 18px; font-weight: 800;">Next on campus</h3>
        <a href="/admin/events.php" style="font-size: 13px; color: var(--color-primary); font-weight: 600;">
            Manage events →
        </a>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>EVENT</th>
                <th>DATE</th>
                <th>REGISTERED</th>
                <th>STATUS</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($next_events as $ev): 
                $st = get_event_seat_info((int)$ev['id']);
                $badge_cls = $st['is_full'] ? 'badge-full' : 'badge-open';
                $badge_lbl = $st['is_full'] ? 'Full' : 'Open';
            ?>
                <tr>
                    <td>
                        <strong style="font-size: 14px;"><?= e($ev['title']) ?></strong>
                        <div style="font-size: 12px; color: var(--text-muted);"><?= e($ev['venue'] ?: 'Online') ?></div>
                    </td>
                    <td><?= e(format_event_date($ev['event_date'])) ?></td>
                    <td><?= e($st['confirmed']) ?> / <?= e($st['capacity']) ?></td>
                    <td><span class="badge <?= $badge_cls ?>"><?= $badge_lbl ?></span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Recent community feedback -->
<div class="info-card-box">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 20px;">
        <h3 style="font-size: 18px; font-weight: 800;">Recent community feedback</h3>
        <a href="/admin/comments.php" style="font-size: 13px; color: var(--color-primary); font-weight: 600;">
            View all comments →
        </a>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <?php foreach ($recent_feedback as $fb): ?>
            <div style="background:var(--bg-surface-subtle); border-radius:var(--radius-sm); padding:20px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                    <strong style="font-size:14px;"><?= e($fb['user_name']) ?></strong>
                    <span style="font-size:12px; color:var(--text-muted);"><?= e(format_event_date($fb['created_at'])) ?></span>
                </div>
                <div style="font-size:12.5px; color:var(--color-primary); font-weight:600; margin-bottom:8px;">
                    <?= e($fb['event_title']) ?>
                </div>
                <p style="font-size:13.5px; color:var(--text-secondary); line-height:1.5; margin-bottom:10px;">
                    <?= e($fb['comment']) ?>
                </p>
                <div style="color:var(--color-primary); font-size:14px;">★★★★★</div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
