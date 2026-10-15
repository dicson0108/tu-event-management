<?php
$admin_page_title = "Manage Events";
$admin_breadcrumb = "Manage events";
require_once __DIR__ . '/sidebar.php';

$db = get_db_connection();

// Handle cancellation or deletion
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $event_id = (int)($_POST['event_id'] ?? 0);

    if ($action === 'cancel' && $event_id > 0) {
        $reason = trim($_POST['reason'] ?? 'Event cancelled by administration.');
        $stmt = $db->prepare("UPDATE events SET status = 'cancelled', cancellation_reason = :r WHERE id = :id");
        $stmt->execute([':r' => $reason, ':id' => $event_id]);
        $msg = "Event cancelled successfully.";
    } elseif ($action === 'delete' && $event_id > 0) {
        $stmt = $db->prepare("DELETE FROM events WHERE id = :id");
        $stmt->execute([':id' => $event_id]);
        $msg = "Event deleted successfully.";
    }
}

// Filters
$tab = $_GET['tab'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$cat_id = (int)($_GET['category_id'] ?? 0);
$status_filter = trim($_GET['status'] ?? '');

$where = ["1=1"];
$params = [];

if ($tab === 'upcoming') {
    $where[] = "e.status = 'published' AND e.event_date >= '2026-10-15'";
} elseif ($tab === 'completed') {
    $where[] = "(e.status = 'completed' OR (e.status = 'published' AND e.event_date < '2026-10-15'))";
} elseif ($tab === 'draft') {
    $where[] = "e.status = 'draft'";
}

if ($search !== '') {
    $where[] = "(e.title LIKE :s OR e.organiser_name LIKE :s)";
    $params[':s'] = "%{$search}%";
}

if ($cat_id > 0) {
    $where[] = "e.category_id = :cat";
    $params[':cat'] = $cat_id;
}

if ($status_filter !== '') {
    $where[] = "e.status = :status";
    $params[':status'] = $status_filter;
}

$where_clause = implode(" AND ", $where);
$stmt = $db->prepare("
    SELECT e.*, c.name as category_name
    FROM events e
    JOIN categories c ON e.category_id = c.id
    WHERE {$where_clause}
    ORDER BY e.event_date ASC
");
$stmt->execute($params);
$events = $stmt->fetchAll();

$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<!-- Title Row -->
<div style="display:flex; align-items:flex-end; justify-content:space-between; margin-bottom: 28px;">
    <div>
        <div style="font-size: 11px; font-weight: 700; color: var(--color-primary); letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 6px;">
            EVENT ADMINISTRATION
        </div>
        <h1 style="font-size: 34px; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
            Make campus happen.
        </h1>
        <p style="font-size: 14.5px; color: var(--text-secondary);">
            Review, publish and manage every campus experience.
        </p>
    </div>

    <a href="/admin/event-edit.php" class="btn-primary" style="padding: 10px 20px; font-size: 14px;">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Create event
    </a>
</div>

<?php if ($msg): ?>
    <div class="status-alert-box success" style="margin-bottom: 24px;">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
        <div><?= e($msg) ?></div>
    </div>
<?php endif; ?>

<!-- 4 Metrics Counters -->
<div class="admin-metrics-grid">
    <div class="info-card-box" style="margin-bottom:0;">
        <div style="font-size: 36px; font-weight: 800; line-height: 1; margin-bottom: 6px;">24</div>
        <div style="font-size: 13px; color: var(--text-secondary);">All events</div>
    </div>
    <div class="info-card-box" style="margin-bottom:0;">
        <div style="font-size: 36px; font-weight: 800; line-height: 1; margin-bottom: 6px;">18</div>
        <div style="font-size: 13px; color: var(--text-secondary);">Upcoming</div>
    </div>
    <div class="info-card-box" style="margin-bottom:0;">
        <div style="font-size: 36px; font-weight: 800; line-height: 1; margin-bottom: 6px;">5</div>
        <div style="font-size: 13px; color: var(--text-secondary);">Completed</div>
    </div>
    <div class="info-card-box" style="margin-bottom:0;">
        <div style="font-size: 36px; font-weight: 800; line-height: 1; margin-bottom: 6px;">1</div>
        <div style="font-size: 13px; color: var(--text-secondary);">Draft</div>
    </div>
</div>

<!-- Table Card Container -->
<div class="info-card-box" style="padding:0; overflow:hidden;">
    <!-- Tabs Header -->
    <div style="padding: 16px 24px 0 24px; border-bottom: 1px solid var(--border-light); display:flex; gap:28px;">
        <a href="?tab=all" class="tab-item <?= $tab === 'all' ? 'active' : '' ?>">All events (24)</a>
        <a href="?tab=upcoming" class="tab-item <?= $tab === 'upcoming' ? 'active' : '' ?>">Upcoming (18)</a>
        <a href="?tab=completed" class="tab-item <?= $tab === 'completed' ? 'active' : '' ?>">Completed (5)</a>
        <a href="?tab=draft" class="tab-item <?= $tab === 'draft' ? 'active' : '' ?>">Draft (1)</a>
    </div>

    <!-- Search & Filters Strip -->
    <form method="GET" action="/admin/events.php" style="padding: 16px 24px; display:flex; gap:16px; align-items:center;">
        <input type="hidden" name="tab" value="<?= e($tab) ?>">
        <div style="flex-grow:1; position:relative; display:flex; align-items:center;">
            <svg style="position:absolute; left:12px; width:16px; height:16px; color:var(--text-muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search events or organisers" class="form-input" style="padding-left:36px;">
        </div>

        <select name="category_id" class="form-select" style="width:180px;" onchange="this.form.submit()">
            <option value="">All categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $cat_id == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="status" class="form-select" style="width:160px;" onchange="this.form.submit()">
            <option value="">All statuses</option>
            <option value="published" <?= $status_filter === 'published' ? 'selected' : '' ?>>Published</option>
            <option value="draft" <?= $status_filter === 'draft' ? 'selected' : '' ?>>Draft</option>
            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
            <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>

        <a href="/admin/attendees.php?export=all" class="btn-secondary" style="font-size:13px; padding:9px 14px;">
            Export
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        </a>
    </form>

    <!-- Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th>EVENT / CATEGORY</th>
                <th>DATE / FORMAT</th>
                <th>REGISTERED</th>
                <th>WAITLIST</th>
                <th>STATUS</th>
                <th style="text-align:right;">ACTIONS</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($events as $ev): 
                $seats = get_event_seat_info((int)$ev['id']);
                $badge_class = 'badge-open';
                $badge_text = 'Open';
                if ($ev['status'] === 'draft') {
                    $badge_class = 'badge-draft';
                    $badge_text = 'Draft';
                } elseif ($ev['status'] === 'completed') {
                    $badge_class = 'badge-completed';
                    $badge_text = 'Completed';
                } elseif ($ev['status'] === 'cancelled') {
                    $badge_class = 'badge-full';
                    $badge_text = 'Cancelled';
                } elseif ($seats['is_full']) {
                    $badge_class = 'badge-full';
                    $badge_text = 'Full';
                }
            ?>
                <tr>
                    <td>
                        <strong style="font-size:14px;"><?= e($ev['title']) ?></strong>
                        <div style="font-size:12px; color:var(--text-muted);"><?= e($ev['category_name']) ?></div>
                    </td>
                    <td>
                        <div><?= e(format_event_date($ev['event_date'])) ?></div>
                        <div style="font-size:12px; color:var(--text-muted);"><?= e(ucfirst($ev['format'])) ?></div>
                    </td>
                    <td>
                        <?= e($seats['confirmed']) ?> / <?= e($seats['capacity']) ?>
                    </td>
                    <td>
                        <?= e($seats['waitlisted']) ?>
                    </td>
                    <td>
                        <span class="badge <?= $badge_class ?>"><?= $badge_text ?></span>
                    </td>
                    <td style="text-align:right;">
                        <div style="display:inline-flex; align-items:center; gap:8px;">
                            <a href="/admin/attendees.php?id=<?= $ev['id'] ?>" class="action-btn-link">Attendees</a>
                            <a href="/admin/event-edit.php?id=<?= $ev['id'] ?>" class="action-btn-link" style="color:var(--color-primary); font-weight:600;">Edit</a>

                            <button type="button" class="action-btn-link <?= $ev['status'] === 'draft' ? 'action-btn-danger' : '' ?>" 
                                    onclick="openDeleteModal(<?= $ev['id'] ?>, '<?= e(addslashes($ev['title'])) ?>')">
                                Delete
                            </button>

                            <?php if ($ev['status'] === 'published'): ?>
                                <button type="button" class="action-btn-danger" 
                                        onclick="openCancelModal(<?= $ev['id'] ?>, '<?= e(addslashes($ev['title'])) ?>')">
                                    Cancel event
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="padding:16px 24px; font-size:12px; color:var(--text-muted); border-top:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center;">
        <span>Showing 1–<?= count($events) ?> of <?= count($events) ?> events</span>
    </div>
</div>

<div style="font-size:12px; color:var(--text-muted); margin-top:16px;">
    Published events appear in Explore events. Cancelling an event notifies registered and waitlisted attendees; deleting removes its record. Confirmation is required for both.
</div>

<!-- Modal 1: Cancel Event Dialog (Matches Registration and confirmation states.png) -->
<div id="cancel-event-modal" class="modal-backdrop">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title" id="cancel-modal-title">Cancel Event?</h3>
            <button type="button" class="modal-close-btn" data-modal-close>&times;</button>
        </div>

        <p style="font-size:13.5px; color:var(--text-secondary); line-height:1.5; margin-bottom:16px;">
            Registered attendees will be notified. The event will be marked Cancelled and will no longer accept registrations. The record and feedback stay available.
        </p>

        <form method="POST" action="/admin/events.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="event_id" id="cancel-event-id" value="">

            <div class="form-group" style="margin-bottom:24px;">
                <label class="form-label">Reason shown to attendees</label>
                <textarea name="reason" rows="2" class="form-input" style="resize:vertical;">The festival is postponed due to venue maintenance.</textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <button type="button" class="btn-secondary" data-modal-close>Keep event</button>
                <button type="submit" class="btn-primary" style="background:#c81e27;">Confirm cancellation</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Delete Draft Dialog (Matches Registration and confirmation states.png) -->
<div id="delete-event-modal" class="modal-backdrop">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title" id="delete-modal-title">Delete Event?</h3>
            <button type="button" class="modal-close-btn" data-modal-close>&times;</button>
        </div>

        <div class="status-alert-box danger" style="margin-bottom:16px;">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
            <div>
                <strong>This cannot be undone.</strong>
                <div>Deleting removes the event, its details and its poster.</div>
            </div>
        </div>

        <p style="font-size:13px; color:var(--text-secondary); margin-bottom:24px;">
            Want to keep it for later? Leave it as a draft instead.
        </p>

        <form method="POST" action="/admin/events.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="event_id" id="delete-event-id" value="">

            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <button type="button" class="btn-secondary" data-modal-close>Keep draft</button>
                <button type="submit" class="btn-primary" style="background:#c81e27;">
                    Delete event
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCancelModal(id, title) {
    document.getElementById('cancel-event-id').value = id;
    document.getElementById('cancel-modal-title').textContent = 'Cancel ' + title + '?';
    document.getElementById('cancel-event-modal').classList.add('show');
}
function openDeleteModal(id, title) {
    document.getElementById('delete-event-id').value = id;
    document.getElementById('delete-modal-title').textContent = 'Delete ' + title + '?';
    document.getElementById('delete-event-modal').classList.add('show');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
