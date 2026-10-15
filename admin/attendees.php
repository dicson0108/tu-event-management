<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

$admin_user = require_admin();
$db = get_db_connection();

$event_id = (int)($_GET['id'] ?? 0);

// Export CSV handler
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=attendees_' . ($event_id ? 'event_' . $event_id : 'all') . '_' . date('Ymd_His') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Event Title', 'Attendee Name', 'Email', 'Role', 'Faculty', 'Registration Status', 'Waitlist Position', 'Registered At']);

    $sql = "
        SELECT e.title as event_title, u.name as user_name, u.email as user_email, u.role, u.faculty, r.status as reg_status, r.waitlist_position, r.created_at
        FROM registrations r
        JOIN events e ON r.event_id = e.id
        JOIN users u ON r.user_id = u.id
    ";
    if ($event_id > 0) {
        $sql .= " WHERE r.event_id = :eid ORDER BY r.status ASC, r.waitlist_position ASC, r.created_at ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':eid' => $event_id]);
    } else {
        $sql .= " ORDER BY e.id ASC, r.status ASC, r.created_at ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
    }

    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['event_title'],
            $row['user_name'],
            $row['user_email'],
            $row['role'],
            $row['faculty'] ?? '',
            $row['reg_status'],
            $row['waitlist_position'] ?? '',
            $row['created_at'],
        ]);
    }
    fclose($output);
    exit;
}

// Action handlers (remove attendee, manual promote)
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $reg_id = (int)($_POST['reg_id'] ?? 0);

    if ($action === 'remove' && $reg_id > 0) {
        // Find registration
        $stmt = $db->prepare("SELECT event_id, status FROM registrations WHERE id = :id");
        $stmt->execute([':id' => $reg_id]);
        $target = $stmt->fetch();

        if ($target) {
            $stmt = $db->prepare("DELETE FROM registrations WHERE id = :id");
            $stmt->execute([':id' => $reg_id]);

            if ($target['status'] === 'confirmed') {
                // Auto promote first in waitlist!
                promote_first_waitlisted((int)$target['event_id']);
            } else {
                reindex_waitlist_positions((int)$target['event_id']);
            }
            $msg = "Attendee removed.";
        }
    } elseif ($action === 'promote' && $reg_id > 0) {
        $stmt = $db->prepare("UPDATE registrations SET status = 'confirmed', waitlist_position = NULL, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $reg_id]);

        // Find event_id to reindex
        $stmt = $db->prepare("SELECT event_id FROM registrations WHERE id = :id");
        $stmt->execute([':id' => $reg_id]);
        $ev_id = (int)$stmt->fetchColumn();
        if ($ev_id) reindex_waitlist_positions($ev_id);

        $msg = "Waitlisted attendee manually promoted to confirmed.";
    }
}

$admin_page_title = "Manage Attendees";
$admin_breadcrumb = "Attendees";
require_once __DIR__ . '/sidebar.php';

// Event details
$event = null;
if ($event_id > 0) {
    $stmt = $db->prepare("SELECT * FROM events WHERE id = :id");
    $stmt->execute([':id' => $event_id]);
    $event = $stmt->fetch();
}

// Fetch list
$sql = "
    SELECT r.id as reg_id, r.status as reg_status, r.waitlist_position, r.created_at,
           u.id as user_id, u.name, u.email, u.phone, u.faculty,
           e.id as event_id, e.title as event_title, e.capacity
    FROM registrations r
    JOIN users u ON r.user_id = u.id
    JOIN events e ON r.event_id = e.id
";
if ($event_id > 0) {
    $sql .= " WHERE r.event_id = :eid ORDER BY FIELD(r.status, 'confirmed', 'waitlisted', 'cancelled'), r.waitlist_position ASC, r.created_at ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute([':eid' => $event_id]);
} else {
    $sql .= " ORDER BY e.event_date DESC, r.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
}
$attendees = $stmt->fetchAll();
?>

<div style="display:flex; align-items:flex-end; justify-content:space-between; margin-bottom: 24px;">
    <div>
        <div style="font-size: 11px; font-weight: 700; color: var(--color-primary); letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 6px;">
            EVENT ATTENDANCE
        </div>
        <h1 style="font-size: 32px; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
            <?= $event ? e($event['title']) . ' · Attendees' : 'All Attendees & Registrations' ?>
        </h1>
        <p style="font-size: 14px; color: var(--text-secondary);">
            <?= $event ? 'Capacity: ' . $event['capacity'] . ' seats' : 'Manage attendees across all campus events.' ?>
        </p>
    </div>

    <div style="display:flex; gap:12px;">
        <a href="/admin/attendees.php?export=1<?= $event_id ? '&id=' . $event_id : '' ?>" class="btn-primary" style="font-size:13px; padding:10px 16px;">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export to CSV
        </a>
        <a href="/admin/events.php" class="btn-secondary" style="font-size:13px;">
            ← Back to events
        </a>
    </div>
</div>

<?php if ($msg): ?>
    <div class="status-alert-box success" style="margin-bottom: 20px;">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
        <div><?= e($msg) ?></div>
    </div>
<?php endif; ?>

<div class="info-card-box" style="padding:0; overflow:hidden;">
    <table class="data-table">
        <thead>
            <tr>
                <th>ATTENDEE</th>
                <th>CONTACT / FACULTY</th>
                <?php if (!$event_id): ?>
                    <th>EVENT</th>
                <?php endif; ?>
                <th>STATUS</th>
                <th>REGISTERED AT</th>
                <th style="text-align:right;">ACTIONS</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($attendees)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding:32px; color:var(--text-muted);">
                        No registrations found.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($attendees as $att): ?>
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div class="avatar-initials" style="width:32px; height:32px; font-size:11px;">
                                    <?= e(get_initials($att['name'])) ?>
                                </div>
                                <div>
                                    <strong style="font-size:14px;"><?= e($att['name']) ?></strong>
                                    <div style="font-size:12px; color:var(--text-muted);"><?= e($att['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-size:13px;"><?= e($att['faculty'] ?: 'Not specified') ?></div>
                            <div style="font-size:12px; color:var(--text-muted);"><?= e($att['phone'] ?: 'No phone') ?></div>
                        </td>
                        <?php if (!$event_id): ?>
                            <td>
                                <a href="/admin/event-edit.php?id=<?= $att['event_id'] ?>" style="color:var(--color-primary); font-weight:600;">
                                    <?= e($att['event_title']) ?>
                                </a>
                            </td>
                        <?php endif; ?>
                        <td>
                            <?php if ($att['reg_status'] === 'confirmed'): ?>
                                <span class="badge badge-joined">Confirmed</span>
                            <?php elseif ($att['reg_status'] === 'waitlisted'): ?>
                                <span class="badge badge-waitlisted">Waitlist #<?= e($att['waitlist_position']) ?></span>
                            <?php else: ?>
                                <span class="badge badge-full">Cancelled</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12.5px; color:var(--text-muted);">
                            <?= e(date('j M Y, g:i A', strtotime($att['created_at']))) ?>
                        </td>
                        <td style="text-align:right;">
                            <div style="display:inline-flex; align-items:center; gap:10px;">
                                <?php if ($att['reg_status'] === 'waitlisted'): ?>
                                    <form method="POST" action="/admin/attendees.php<?= $event_id ? '?id=' . $event_id : '' ?>" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="promote">
                                        <input type="hidden" name="reg_id" value="<?= $att['reg_id'] ?>">
                                        <button type="submit" class="btn-link" style="color:#059669; font-weight:600; font-size:12.5px; padding:0; background:none; border:none; cursor:pointer;">
                                            Promote seat
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST" action="/admin/attendees.php<?= $event_id ? '?id=' . $event_id : '' ?>" onsubmit="return confirm('Remove this attendee? If confirmed, next waitlisted person will be automatically promoted.');" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="reg_id" value="<?= $att['reg_id'] ?>">
                                    <button type="submit" style="background:none; border:none; color:#dc2626; cursor:pointer; font-size:12.5px; font-weight:600; padding:0;">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
