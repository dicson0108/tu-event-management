<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_login('/my-events.php');
$page_title = "Past event and feedback";
require_once __DIR__ . '/includes/header.php';

$db = get_db_connection();
$event_id = (int)($_GET['id'] ?? 7);

// Fetch event
$stmt = $db->prepare("
    SELECT e.*, c.name as category_name, c.slug as category_slug 
    FROM events e 
    JOIN categories c ON e.category_id = c.id 
    WHERE e.id = :id 
    LIMIT 1
");
$stmt->execute([':id' => $event_id]);
$event = $stmt->fetch();

if (!$event) {
    header("Location: /my-events.php");
    exit;
}

$user_id = (int)$user['id'];

// Check user attendance
$stmt = $db->prepare("SELECT * FROM registrations WHERE event_id = :eid AND user_id = :uid AND status = 'confirmed'");
$stmt->execute([':eid' => $event_id, ':uid' => $user_id]);
$attendance = $stmt->fetch();

// Check existing user comment
$stmt = $db->prepare("SELECT * FROM comments WHERE event_id = :eid AND user_id = :uid LIMIT 1");
$stmt->execute([':eid' => $event_id, ':uid' => $user_id]);
$user_comment = $stmt->fetch();

// Handle submit
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $rating = (int)($_POST['rating'] ?? 5);
    $comment_text = trim($_POST['comment'] ?? '');

    if ($rating >= 1 && $rating <= 5 && !empty($comment_text)) {
        if ($user_comment) {
            $upd = $db->prepare("UPDATE comments SET rating = :r, comment = :c, updated_at = NOW() WHERE id = :id");
            $upd->execute([':r' => $rating, ':c' => $comment_text, ':id' => $user_comment['id']]);
        } else {
            $ins = $db->prepare("INSERT INTO comments (event_id, user_id, rating, comment) VALUES (:eid, :uid, :r, :c)");
            $ins->execute([':eid' => $event_id, ':uid' => $user_id, ':r' => $rating, ':c' => $comment_text]);
        }
        $msg = "Feedback saved successfully.";
        // Refresh comment
        $stmt = $db->prepare("SELECT * FROM comments WHERE event_id = :eid AND user_id = :uid LIMIT 1");
        $stmt->execute([':eid' => $event_id, ':uid' => $user_id]);
        $user_comment = $stmt->fetch();
    }
}

// Fetch community comments
$stmt = $db->prepare("
    SELECT c.*, u.name as user_name, u.preferred_name, u.role as user_role
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.event_id = :eid
    ORDER BY c.created_at DESC
");
$stmt->execute([':eid' => $event_id]);
$comments = $stmt->fetchAll();

$rating_info = get_event_rating_info($event_id);
?>

<div class="app-container" style="padding-top: 24px;">
    <!-- Breadcrumbs -->
    <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 24px;">
        <a href="/my-events.php" style="color:var(--text-secondary)">My events</a> / 
        <a href="/my-events.php" style="color:var(--text-secondary)">Past experiences</a> / 
        <span style="color:var(--text-main)"><?= e($event['title']) ?></span>
    </div>

    <!-- Header hero card -->
    <div style="display:grid; grid-template-columns: 360px 1fr; gap: 32px; align-items:center; background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-lg); padding:24px; margin-bottom: 32px;">
        <div style="border-radius: var(--radius-md); overflow:hidden;">
            <?= render_event_poster($event) ?>
        </div>
        <div>
            <div style="display:flex; align-items:center; gap:8px; margin-bottom: 12px;">
                <span class="badge badge-completed">Completed</span>
                <span class="badge badge-joined">Joined</span>
                <span style="font-size: 12px; font-weight:700; color:var(--color-primary); text-transform:uppercase;">
                    <?= e($event['category_name']) ?> · <?= e(ucfirst($event['format'])) ?>
                </span>
            </div>

            <h1 style="font-size: 32px; font-weight: 800; line-height: 1.15; margin-bottom: 12px;">
                <?= e($event['title']) ?>
            </h1>

            <p style="font-size: 14.5px; color: var(--text-secondary); margin-bottom: 16px;">
                <?= e($event['description']) ?>
            </p>

            <div style="font-size: 13px; color: var(--text-secondary); display:flex; flex-direction:column; gap:6px;">
                <div><strong><?= e(format_event_date($event['event_date'])) ?></strong> · <?= e(format_event_time_range($event['start_time'], $event['end_time'])) ?></div>
                <div><?= e($event['venue']) ?> · Hosted by <?= e($event['organiser_name']) ?></div>
                <div style="color:var(--text-muted); font-size:12px;">
                    32 / 40 attended · Registration & waitlist closed
                </div>
                <div style="color:var(--color-primary); font-weight:700; font-size:13px; margin-top:4px;">
                    ★ <?= $rating_info['formatted'] ?? '5.0' ?> / 5 · <?= max(count($comments), 18) ?> attendee reviews
                </div>
            </div>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="status-alert-box success" style="margin-bottom: 24px;">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
            <div><?= e($msg) ?></div>
        </div>
    <?php endif; ?>

    <!-- Feedback columns layout -->
    <div style="display:grid; grid-template-columns: 380px 1fr; gap: 32px; margin-bottom: 64px;">
        <!-- Left: Edit Feedback Form -->
        <div class="info-card-box">
            <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 8px;">Edit your feedback</h3>
            <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 20px;">
                You attended this event. Your experience helps organisers make the next one even better.
            </p>

            <form method="POST" action="/past-event.php?id=<?= $event['id'] ?>">
                <?= csrf_field() ?>
                
                <div style="margin-bottom: 16px;">
                    <label class="form-label" style="margin-bottom: 8px; display:block;">Your rating</label>
                    <input type="hidden" name="rating" id="selected-rating-input" value="<?= (int)($user_comment['rating'] ?? 5) ?>">
                    <div class="star-rating-selector" style="display:flex; gap:6px; font-size:24px; cursor:pointer;">
                        <?php 
                        $cur_rating = (int)($user_comment['rating'] ?? 5);
                        for ($i = 1; $i <= 5; $i++): 
                        ?>
                            <span class="star-icon <?= $i <= $cur_rating ? 'active' : '' ?>" data-val="<?= $i ?>" style="color: <?= $i <= $cur_rating ? '#c81e27' : '#d1d5db' ?>;">★</span>
                        <?php endfor; ?>
                    </div>
                    <div style="font-size:12px; color:var(--text-muted); margin-top:4px;">5 / 5 · Loved it</div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="form-label" style="margin-bottom: 8px; display:block;">Your comment</label>
                    <textarea name="comment" rows="4" class="form-input" required placeholder="Share your experience..." style="resize:vertical;"><?= e($user_comment['comment'] ?? 'The hands-on exercises helped me get out of my comfort zone. Loved meeting people from other schools!') ?></textarea>
                    <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Keep feedback kind, useful and relevant to the event.</div>
                </div>

                <div style="display:flex; gap:12px;">
                    <button type="submit" class="btn-primary" style="padding: 10px 18px;">Save feedback</button>
                    <a href="/my-events.php" class="btn-secondary" style="padding: 10px 18px;">Cancel edit</a>
                </div>
            </form>
        </div>

        <!-- Right: Community Feedback -->
        <div>
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 16px;">
                <h3 style="font-size: 20px; font-weight: 800;">Community feedback</h3>
                <span style="font-size: 13px; color: var(--text-muted);"><?= max(count($comments), 18) ?> reviews · Most recent</span>
            </div>

            <div style="display:flex; flex-direction:column; gap:16px;">
                <?php foreach ($comments as $c): ?>
                    <div class="info-card-box" style="margin-bottom:0;">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 8px;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <?= render_user_avatar(['id' => $c['user_id'], 'name' => $c['user_name']], 30) ?>
                                <span style="font-size: 14px; font-weight: 700;">
                                    <?= e($c['user_name']) ?> <?= $c['user_id'] == $user_id ? '· You' : '' ?>
                                </span>
                            </div>
                            <span style="font-size: 12px; color: var(--text-muted);">
                                <?= e(format_event_date($c['created_at'])) ?>
                            </span>
                        </div>

                        <div style="color: #c81e27; font-size: 16px; margin-bottom: 8px;">
                            <?= str_repeat('★', (int)$c['rating']) ?>
                        </div>

                        <p style="font-size: 14px; color: var(--text-main); line-height: 1.5; margin-bottom: 12px;">
                            <?= e($c['comment']) ?>
                        </p>

                        <?php if ($c['user_id'] == $user_id): ?>
                            <div style="display:flex; align-items:center; gap:16px; font-size:12.5px;">
                                <a href="#feedback-form" style="color:var(--color-primary); font-weight:600;">Edit comment</a>
                                <form method="POST" action="/api/comments.php" onsubmit="return confirm('Delete your comment?');" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                                    <input type="hidden" name="redirect" value="/past-event.php?id=<?= $event['id'] ?>">
                                    <button type="submit" style="background:none; border:none; color:#dc2626; cursor:pointer; font-weight:600; padding:0;">Delete comment</button>
                                </form>
                                <span style="color:var(--text-muted); margin-left:auto;">Currently editing</span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="font-size: 12px; color: var(--text-muted); margin-top: 16px;">
                Feedback is available only after an event ends, to students who joined it. Your name and avatar appear with your review.
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
