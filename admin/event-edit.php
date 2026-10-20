<?php
require_once __DIR__ . '/sidebar.php';

$db = get_db_connection();
$event_id = (int)($_GET['id'] ?? 0);
$is_new = ($event_id === 0);

$event = [
    'title' => '',
    'description' => '',
    'category_id' => 1,
    'event_date' => date('Y-m-d', strtotime('+7 days')),
    'start_time' => '10:00:00',
    'end_time' => '17:00:00',
    'format' => 'physical',
    'venue' => 'The Grand Hall, Taylor\'s Lakeside Campus',
    'online_link' => '',
    'capacity' => 100,
    'poster_theme' => 'purple',
    'status' => 'published',
    'allow_waitlist' => 1,
    'organiser_name' => 'Taylor\'s Design School',
    'organiser_email' => 'designschool@taylors.edu.my',
];

if (!$is_new) {
    $stmt = $db->prepare("SELECT * FROM events WHERE id = :id");
    $stmt->execute([':id' => $event_id]);
    $existing = $stmt->fetch();
    if ($existing) {
        $event = $existing;
    } else {
        header("Location: " . app_url("/admin/events.php"));
        exit;
    }
}

$seats = !$is_new ? get_event_seat_info($event_id) : ['confirmed' => 0, 'waitlisted' => 0];

// Save changes
$msg = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 1);
    $event_date = trim($_POST['event_date'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');
    $format = trim($_POST['format'] ?? 'physical');
    $venue = trim($_POST['venue'] ?? '');
    $online_link = trim($_POST['online_link'] ?? '');
    $capacity = max(1, (int)($_POST['capacity'] ?? 50));
    $allow_waitlist = !empty($_POST['allow_waitlist']) ? 1 : 0;
    $organiser_name = trim($_POST['organiser_name'] ?? "Taylor's University");
    $organiser_email = trim($_POST['organiser_email'] ?? '');
    $poster_theme = trim($_POST['poster_theme'] ?? 'purple');
    $status = trim($_POST['status'] ?? 'published');

    // Poster upload handle
    $poster_path = $event['poster_path'] ?? null;
    if (!empty($_FILES['poster']['name'])) {
        $file = $_FILES['poster'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) && $file['size'] <= 5 * 1024 * 1024) {
            $dest_dir = __DIR__ . '/../assets/uploads/';
            if (!is_dir($dest_dir)) mkdir($dest_dir, 0755, true);
            $new_name = 'poster_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $dest_dir . $new_name)) {
                $poster_path = '/assets/uploads/' . $new_name;
            }
        }
    }

    if (empty($title) || empty($event_date)) {
        $error = "Title and date are required fields.";
    } elseif (!$is_new && $capacity < $seats['confirmed']) {
        $error = "Capacity cannot be lower than the current {$seats['confirmed']} confirmed registrations.";
    } else {
        $params = [
            ':t' => $title, ':d' => $description, ':c' => $category_id, ':ed' => $event_date,
            ':st' => $start_time, ':et' => $end_time, ':f' => $format, ':v' => $venue,
            ':ol' => $online_link, ':cap' => $capacity, ':pp' => $poster_path, ':pt' => $poster_theme,
            ':s' => $status, ':aw' => $allow_waitlist, ':on' => $organiser_name, ':oe' => $organiser_email,
        ];
        if ($is_new) {
            $stmt = $db->prepare("
                INSERT INTO events (title, description, category_id, event_date, start_time, end_time, format, venue, online_link, capacity, poster_path, poster_theme, status, allow_waitlist, organiser_name, organiser_email, created_by)
                VALUES (:t, :d, :c, :ed, :st, :et, :f, :v, :ol, :cap, :pp, :pt, :s, :aw, :on, :oe, :cb)
            ");
            $stmt->execute($params + [':cb' => $admin_user['id']]);
            $new_id = $db->lastInsertId();
            header("Location: " . app_url("/admin/event-edit.php?id=") . $new_id . "&saved=1");
            exit;
        } else {
            $stmt = $db->prepare("
                UPDATE events SET 
                    title = :t, description = :d, category_id = :c, event_date = :ed,
                    start_time = :st, end_time = :et, format = :f, venue = :v, online_link = :ol,
                    capacity = :cap, poster_path = :pp, poster_theme = :pt, status = :s,
                    allow_waitlist = :aw, organiser_name = :on, organiser_email = :oe, updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute($params + [':id' => $event_id]);
            $msg = "Event updated successfully.";
            // Reload
            $stmt = $db->prepare("SELECT * FROM events WHERE id = :id");
            $stmt->execute([':id' => $event_id]);
            $event = $stmt->fetch();
        }
    }
}

if (isset($_GET['saved'])) {
    $msg = "Event created successfully.";
}

$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<!-- Breadcrumbs -->
<div style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;">
    <a href="<?= APP_BASE_PATH ?>/admin/events.php" style="color:var(--text-secondary);">Manage events</a> /
    <?php if (!$is_new): ?>
        <span><?= e($event['title']) ?></span> / 
    <?php endif; ?>
    <span style="color:var(--text-main); font-weight:600;"><?= $is_new ? 'Create event' : 'Edit event' ?></span>
</div>

<div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom: 28px;">
    <div>
        <div style="font-size: 11px; font-weight: 700; color: var(--color-primary); letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 6px;">
            EVENT EDITOR
        </div>
        <h1 style="font-size: 34px; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
            <?= $is_new ? 'Create event' : 'Edit event' ?>
        </h1>
        <p style="font-size: 14.5px; color: var(--text-secondary);">
            Keep the details clear, current and ready for your community.
        </p>
    </div>

    <a href="<?= APP_BASE_PATH ?>/admin/events.php" class="btn-secondary">
        ← Back to events
    </a>
</div>

<?php if ($msg): ?>
    <div class="status-alert-box success" style="margin-bottom: 24px;">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
        <div><?= e($msg) ?></div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="status-alert-box danger" style="margin-bottom: 24px;">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
        <div><?= e($error) ?></div>
    </div>
<?php endif; ?>

<form method="POST" action="<?= APP_BASE_PATH ?>/admin/event-edit.php<?= !$is_new ? '?id=' . $event_id : '' ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div style="display:grid; grid-template-columns: 1fr 340px; gap: 32px; align-items:start;">
        <!-- Left Forms -->
        <div>
            <!-- Card 1: The essentials -->
            <div class="info-card-box">
                <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 20px;">The essentials</h3>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Event title *</label>
                    <input type="text" name="title" class="form-input" value="<?= e($event['title']) ?>" required placeholder="e.g. Design Futures Festival">
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Description *</label>
                    <textarea name="description" rows="4" class="form-input" required placeholder="Describe what attendees can expect..." style="resize:vertical;"><?= e($event['description']) ?></textarea>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <select name="category_id" class="form-select">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $event['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Organiser *</label>
                        <input type="text" name="organiser_name" class="form-input" value="<?= e($event['organiser_name']) ?>" required placeholder="e.g. Taylor's Design School">
                    </div>
                </div>
            </div>

            <!-- Card 2: When & where -->
            <div class="info-card-box">
                <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 20px;">When & where</h3>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Event date *</label>
                    <input type="date" name="event_date" class="form-input" value="<?= e($event['event_date']) ?>" required>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 8px;">
                    <div class="form-group">
                        <label class="form-label">Start time *</label>
                        <input type="time" name="start_time" class="form-input" value="<?= e(substr($event['start_time'], 0, 5)) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">End time *</label>
                        <input type="time" name="end_time" class="form-input" value="<?= e(substr($event['end_time'], 0, 5)) ?>" required>
                    </div>
                </div>
                <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 20px;">
                    All times are in Malaysia Time (MYT, UTC+8).
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label">Event format *</label>
                    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                        <label style="border: 1px solid <?= $event['format'] === 'physical' ? 'var(--color-primary)' : 'var(--border-light)' ?>; background: <?= $event['format'] === 'physical' ? '#fff5f5' : '#fff' ?>; border-radius: var(--radius-sm); padding: 12px; display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="radio" name="format" value="physical" <?= $event['format'] === 'physical' ? 'checked' : '' ?> style="accent-color:var(--color-primary);">
                            <span style="font-size:14px; font-weight:600;">Physical</span>
                        </label>
                        <label style="border: 1px solid <?= $event['format'] === 'online' ? 'var(--color-primary)' : 'var(--border-light)' ?>; background: <?= $event['format'] === 'online' ? '#fff5f5' : '#fff' ?>; border-radius: var(--radius-sm); padding: 12px; display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="radio" name="format" value="online" <?= $event['format'] === 'online' ? 'checked' : '' ?> style="accent-color:var(--color-primary);">
                            <span style="font-size:14px; font-weight:600;">Online</span>
                        </label>
                        <label style="border: 1px solid <?= $event['format'] === 'hybrid' ? 'var(--color-primary)' : 'var(--border-light)' ?>; background: <?= $event['format'] === 'hybrid' ? '#fff5f5' : '#fff' ?>; border-radius: var(--radius-sm); padding: 12px; display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="radio" name="format" value="hybrid" <?= $event['format'] === 'hybrid' ? 'checked' : '' ?> style="accent-color:var(--color-primary);">
                            <span style="font-size:14px; font-weight:600;">Hybrid</span>
                        </label>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Venue *</label>
                    <input type="text" name="venue" class="form-input" value="<?= e($event['venue']) ?>" placeholder="The Grand Hall, Taylor's Lakeside Campus">
                </div>

                <div class="form-group" style="margin-bottom: 8px;">
                    <label class="form-label">Online event link</label>
                    <input type="url" name="online_link" class="form-input" value="<?= e($event['online_link']) ?>" placeholder="https://teams.microsoft.com/...">
                </div>
                <div style="font-size: 12px; color: var(--text-muted);">
                    For online or hybrid events, add a valid meeting URL. Both venue and link are required for hybrid.
                </div>
            </div>

            <!-- Card 3: Attendance -->
            <div class="info-card-box">
                <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 20px;">Attendance</h3>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 8px;">
                    <div class="form-group">
                        <label class="form-label">Capacity *</label>
                        <input type="number" name="capacity" class="form-input" value="<?= (int)$event['capacity'] ?>" min="1" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact email *</label>
                        <input type="email" name="organiser_email" class="form-input" value="<?= e($event['organiser_email']) ?>" placeholder="designschool@taylors.edu.my">
                    </div>
                </div>
                <?php if (!$is_new): ?>
                    <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 20px;">
                        Cannot be lower than <?= $seats['confirmed'] ?> current registrations.
                    </div>
                <?php endif; ?>

                <div style="margin-top: 16px;">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-size:14px; font-weight:500;">
                        <input type="checkbox" name="allow_waitlist" value="1" <?= !empty($event['allow_waitlist']) ? 'checked' : '' ?> style="accent-color:var(--color-primary); width:18px; height:18px;">
                        <span>Allow a waitlist when the event is full</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Right Sidebar Controls -->
        <div>
            <!-- Publication Box -->
            <div class="info-card-box" style="margin-bottom: 24px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 12px;">
                    <span style="font-size:14px; font-weight:700;">Publication</span>
                    <span class="badge <?= $event['status'] === 'published' ? 'badge-open' : 'badge-draft' ?>">
                        <?= ucfirst($event['status']) ?>
                    </span>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <select name="status" class="form-select" style="font-size:13px;">
                        <option value="published" <?= $event['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="draft" <?= $event['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="completed" <?= $event['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $event['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <p style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 16px; line-height: 1.4;">
                    <?= $event['status'] === 'published' ? 'This approved event is visible to the campus community.' : 'Draft events are visible only to administrators.' ?>
                </p>

                <button type="submit" class="btn-primary" style="width:100%; justify-content:center; padding:11px; margin-bottom:12px;">
                    Save changes
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                </button>

                <?php if (!$is_new): ?>
                    <a href="<?= APP_BASE_PATH ?>/event-detail.php?id=<?= $event['id'] ?>" target="_blank" class="btn-secondary" style="width:100%; justify-content:center; padding:10px; font-size:13px; margin-bottom:16px;">
                        Preview event ↗
                    </a>
                <?php endif; ?>

                <div style="font-size: 11.5px; color: var(--text-muted); line-height: 1.4;">
                    Changes to the date or venue should be shared with registered attendees.
                </div>
            </div>

            <!-- Event Poster Box -->
            <div class="info-card-box" style="margin-bottom: 24px;">
                <h4 style="font-size: 15px; font-weight: 700; margin-bottom: 14px;">Event poster</h4>

                <div style="border-radius: var(--radius-sm); overflow:hidden; margin-bottom: 16px;">
                    <?= render_event_poster($event) ?>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label">Art Theme</label>
                    <select name="poster_theme" class="form-select" style="font-size:13px;">
                        <option value="purple" <?= ($event['poster_theme'] ?? '') === 'purple' ? 'selected' : '' ?>>Design Futures (Purple)</option>
                        <option value="dark" <?= ($event['poster_theme'] ?? '') === 'dark' ? 'selected' : '' ?>>After Hours (Dark)</option>
                        <option value="yellow" <?= ($event['poster_theme'] ?? '') === 'yellow' ? 'selected' : '' ?>>Build/Break (Yellow)</option>
                        <option value="blue" <?= ($event['poster_theme'] ?? '') === 'blue' ? 'selected' : '' ?>>Next Chapter (Blue)</option>
                        <option value="green" <?= ($event['poster_theme'] ?? '') === 'green' ? 'selected' : '' ?>>Green Campus (Green)</option>
                        <option value="peach" <?= ($event['poster_theme'] ?? '') === 'peach' ? 'selected' : '' ?>>Sunrise Run (Peach)</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label">Upload Custom Image</label>
                    <input type="file" name="poster" class="form-input" accept="image/png, image/jpeg" style="font-size:12px; padding:6px;">
                </div>

                <div style="font-size: 11.5px; color: var(--text-muted); line-height: 1.4;">
                    Upload JPG or PNG, up to 5 MB. Recommended: 1600 × 1000 px. Keep key details within the centre.
                </div>
            </div>

            <?php if (!$is_new): ?>
                <!-- Event Management Actions -->
                <div class="info-card-box">
                    <h4 style="font-size: 15px; font-weight: 700; margin-bottom: 10px;">Event management</h4>
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 16px; line-height: 1.4;">
                        <?= $seats['confirmed'] ?> registered attendees. <?= $seats['waitlisted'] ?> waitlisted. Consider cancellation instead of deleting a published event.
                    </p>

                    <a href="<?= APP_BASE_PATH ?>/admin/attendees.php?id=<?= $event['id'] ?>" class="btn-secondary" style="width:100%; justify-content:center; margin-bottom:12px; font-size:13px;">
                        Manage attendees
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
