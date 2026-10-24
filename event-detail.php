<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';

$db = get_db_connection();
$user = current_user();
$event_id = (int)($_GET['id'] ?? 0);

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

if (!$event || ($event['status'] === 'draft' && (!$user || $user['role'] !== 'admin'))) {
    render_error_page(404);
}

$page_title = $event['title'];
$seats = get_event_seat_info($event_id);
$user_status = $user ? get_user_event_status($event_id, (int)$user['id']) : null;
$rating_info = get_event_rating_info($event_id);

$is_past = strtotime($event['event_date']) < strtotime(date('Y-m-d'));
$day_of_week = date('l', strtotime($event['event_date']));

$user_id = $user ? (int)$user['id'] : 0;

// Check attendance & comment
$attendance = false;
$user_comment = null;
if ($user_id) {
    $stmt = $db->prepare("SELECT * FROM registrations WHERE event_id = :eid AND user_id = :uid AND status = 'confirmed'");
    $stmt->execute([':eid' => $event_id, ':uid' => $user_id]);
    $attendance = (bool)$stmt->fetch();

    $stmt = $db->prepare("SELECT * FROM comments WHERE event_id = :eid AND user_id = :uid LIMIT 1");
    $stmt->execute([':eid' => $event_id, ':uid' => $user_id]);
    $user_comment = $stmt->fetch();
}

// Handle feedback submit
$feedback_msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_feedback') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        exit('Invalid or expired CSRF token.');
    }
    if (!$user_id || !$attendance || !$is_past || !in_array($event['status'], ['published', 'completed'], true)) {
        render_error_page(403);
    }
    $rating = (int)($_POST['rating'] ?? 5);
    $comment_text = trim($_POST['comment'] ?? '');
    if ($rating >= 1 && $rating <= 5 && !empty($comment_text) && $user_id) {
        if ($user_comment) {
            $upd = $db->prepare("UPDATE comments SET rating = :r, comment = :c, updated_at = NOW() WHERE id = :id");
            $upd->execute([':r' => $rating, ':c' => $comment_text, ':id' => $user_comment['id']]);
        } else {
            $ins = $db->prepare("INSERT INTO comments (event_id, user_id, rating, comment) VALUES (:eid, :uid, :r, :c)");
            $ins->execute([':eid' => $event_id, ':uid' => $user_id, ':r' => $rating, ':c' => $comment_text]);
        }
        $feedback_msg = "Feedback saved successfully.";
        $stmt = $db->prepare("SELECT * FROM comments WHERE event_id = :eid AND user_id = :uid LIMIT 1");
        $stmt->execute([':eid' => $event_id, ':uid' => $user_id]);
        $user_comment = $stmt->fetch();
        $rating_info = get_event_rating_info($event_id);
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
require_once __DIR__ . '/includes/header.php';
?>

<div class="app-container" style="padding-top: 24px;">
    <!-- Breadcrumbs -->
    <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 24px;">
        <a href="<?= APP_BASE_PATH ?>/events.php" style="color:var(--text-secondary)">Explore events</a> /
        <a href="<?= APP_BASE_PATH ?>/events.php?category=<?= urlencode($event['category_slug']) ?>" style="color:var(--text-secondary)"><?= e($event['category_name']) ?></a> /
        <span style="color:var(--text-main)"><?= e($event['title']) ?></span>
    </div>

    <div class="event-detail-layout">
        <!-- Main Column -->
        <div class="event-detail-main">
            <div class="event-detail-poster-wrap">
                <?= render_event_poster($event, 'detail') ?>
            </div>

            <div style="font-size: 12px; font-weight: 700; color: var(--color-primary); letter-spacing: 0.08em; text-transform: uppercase;">
                <?= e(strtoupper($event['category_name'])) ?> · <?= e(strtoupper($event['format'])) ?> EVENT
            </div>

            <h1 class="event-detail-title"><?= e($event['title']) ?></h1>

            <p class="event-detail-lead"><?= nl2br(e($event['description'])) ?></p>

            <!-- Time & Venue overview -->
            <div class="info-card-box">
                <h3 class="info-card-title">Event Timing & Venue</h3>
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <div style="display:flex; align-items:center; gap:12px; font-size:14.5px;">
                        <strong>Date:</strong> <span><?= e(format_event_full_date($event['event_date'])) ?></span>
                    </div>
                    <div style="display:flex; align-items:center; gap:12px; font-size:14.5px;">
                        <strong>Time:</strong> <span><?= e(format_event_time_range($event['start_time'], $event['end_time'])) ?></span>
                    </div>
                    <div style="display:flex; align-items:center; gap:12px; font-size:14.5px;">
                        <strong>Location:</strong> <span><?= e($event['venue'] ?: 'Online') ?></span>
                    </div>
                </div>
            </div>

            <!-- Organiser updates -->
            <div style="margin-top: 24px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 16px;">
                    <h3 style="font-size: 20px; font-weight: 800;">Before you go</h3>
                    <span style="font-size: 12px; color: var(--text-muted);">Organiser notes</span>
                </div>

                <div class="info-card-box">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom: 12px;">
                        <?= render_user_avatar(['id' => 1, 'name' => $event['organiser_name']], 28) ?>
                        <span style="font-size: 13.5px; font-weight: 700;"><?= e($event['organiser_name']) ?></span>
                        <span class="badge" style="background:#fee2e2; color:var(--color-primary); font-size:11px;">Organiser</span>
                    </div>
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5;">
                        Please bring your student ID and arrive 10–15 minutes early to check in smoothly. For online sessions, ensure your camera and microphone are ready.
                    </p>
                </div>
            </div>

            <!-- Feedback & Community Reviews Section -->
            <div id="reviews" style="margin-top: 36px;">
                <?php if ($feedback_msg): ?>
                    <div class="status-alert-box success" style="margin-bottom: 20px;">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                        <div><?= e($feedback_msg) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($attendance && $is_past && in_array($event['status'], ['published', 'completed'], true)): ?>
                    <div class="info-card-box" id="feedback-form" style="margin-bottom: 28px;">
                        <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 8px;"><?= $user_comment ? 'Edit your review' : 'Rate this event' ?></h3>
                        <p style="font-size: 13.5px; color: var(--text-secondary); margin-bottom: 20px;">
                            You attended this event. Your experience helps organisers make the next one even better.
                        </p>
                        <form method="POST" action="<?= APP_BASE_PATH ?>/event-detail.php?id=<?= $event['id'] ?>#reviews">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="save_feedback">
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
                            </div>
                            <div style="margin-bottom: 20px;">
                                <label class="form-label" style="margin-bottom: 8px; display:block;">Your feedback</label>
                                <textarea name="comment" rows="3" class="form-input" required placeholder="Share your experience..." style="resize:vertical;"><?= e($user_comment['comment'] ?? '') ?></textarea>
                            </div>
                            <button type="submit" class="btn-primary" style="padding: 10px 20px;">Save feedback</button>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Community Reviews List -->
                <div>
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 16px;">
                        <h3 style="font-size: 20px; font-weight: 800;">Community feedback</h3>
                        <span style="font-size: 13px; color: var(--text-muted);">
                            <?= count($comments) ?> <?= count($comments) === 1 ? 'review' : 'reviews' ?>
                            <?php if ($rating_info['formatted']): ?>
                                · <?= $rating_info['formatted'] ?> / 5 ★
                            <?php endif; ?>
                        </span>
                    </div>

                    <?php if (empty($comments)): ?>
                        <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-lg); padding:28px; text-align:center; color:var(--text-muted); font-size:14px;">
                            No reviews submitted yet for this event.
                        </div>
                    <?php else: ?>
                        <div style="display:flex; flex-direction:column; gap:16px;">
                            <?php foreach ($comments as $c): ?>
                                <div class="info-card-box" style="margin-bottom:0;">
                                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 8px;">
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <?= render_user_avatar(['id' => $c['user_id'], 'name' => $c['user_name']], 30) ?>
                                            <span style="font-size: 14px; font-weight: 700;">
                                                <?= e($c['user_name']) ?> <?= ($user_id && $c['user_id'] == $user_id) ? '· You' : '' ?>
                                            </span>
                                        </div>
                                        <span style="font-size: 12px; color: var(--text-muted);">
                                            <?= e(format_event_date($c['created_at'])) ?>
                                        </span>
                                    </div>
                                    <div style="color: #c81e27; font-size: 15px; margin-bottom: 8px;">
                                        <?= str_repeat('★', (int)$c['rating']) ?>
                                    </div>
                                    <p style="font-size: 14px; color: var(--text-main); line-height: 1.5; margin-bottom: 8px;">
                                        <?= e($c['comment']) ?>
                                    </p>
                                    <?php if ($user_id && $c['user_id'] == $user_id): ?>
                                        <div style="display:flex; align-items:center; gap:14px; font-size:12.5px; margin-top:8px;">
                                            <a href="#feedback-form" style="color:var(--color-primary); font-weight:600;">Edit</a>
                                            <form method="POST" action="<?= APP_BASE_PATH ?>/api/comments.php" onsubmit="return confirm('Delete your comment?');" style="display:inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                                                <input type="hidden" name="redirect" value="<?= APP_BASE_PATH ?>/event-detail.php?id=<?= $event['id'] ?>#reviews">
                                                <button type="submit" style="background:none; border:none; color:#dc2626; cursor:pointer; font-weight:600; padding:0;">Delete</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sticky Sidebar Card -->
        <div>
            <div class="sticky-reg-card">
                <div class="sticky-header-row">
                    <?php 
                    if ($user_status && $user_status['status'] === 'confirmed') {
                        echo '<span class="badge badge-joined">Joined</span>';
                    } elseif ($user_status && $user_status['status'] === 'waitlisted') {
                        echo '<span class="badge badge-waitlisted">Waitlisted</span>';
                    } elseif ($seats['is_full']) {
                        echo '<span class="badge badge-full">Full</span>';
                    } else {
                        echo '<span class="badge badge-open">Open</span>';
                    }
                    ?>
                    <span class="free-tag">Free admission</span>
                </div>

                <div class="reg-card-headline">Your <?= e($day_of_week) ?>, sorted.</div>

                <div class="reg-meta-list">
                    <div class="reg-meta-item">
                        <svg class="reg-meta-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                        <div>
                            <div style="font-weight:600;"><?= e(format_event_full_date($event['event_date'])) ?></div>
                            <div style="font-size:12px; color:var(--text-muted);"><?= e(format_event_time_range($event['start_time'], $event['end_time'])) ?></div>
                        </div>
                    </div>

                    <div class="reg-meta-item">
                        <svg class="reg-meta-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        <div>
                            <div style="font-weight:600;"><?= e($event['venue'] ?: 'Online session') ?></div>
                            <div style="font-size:12px; color:var(--text-muted);">Taylor's Lakeside Campus</div>
                        </div>
                    </div>

                    <div class="reg-meta-item">
                        <svg class="reg-meta-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <div>
                            <div style="font-weight:600;"><?= e($seats['confirmed']) ?> of <?= e($seats['capacity']) ?> places reserved</div>
                            <div style="font-size:12px; color:var(--text-muted);">
                                <?= $seats['seats_left'] > 0 ? e($seats['seats_left']) . ' spots remaining' : 'Event currently full' ?>
                            </div>
                        </div>
                    </div>

                    <div class="reg-meta-item">
                        <svg class="reg-meta-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <div>
                            <div style="font-weight:600;"><?= $rating_info['formatted'] ? $rating_info['formatted'] . ' / 5 organiser rating' : '4.8 / 5 organiser rating' ?></div>
                            <div style="font-size:12px; color:var(--text-muted);">Based on attendee reviews</div>
                        </div>
                    </div>
                </div>

                <div class="reg-action-area">
                    <?php if (!$user): ?>
                        <a href="<?= APP_BASE_PATH ?>/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn-primary" style="width:100%;">
                            Log in to join event ↗
                        </a>
                    <?php elseif ($user_status && $user_status['status'] === 'confirmed'): ?>
                        <div class="status-alert-box success">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                            <div>
                                <strong>You're going!</strong>
                                <div>Your registration is confirmed. Find it in My events.</div>
                            </div>
                        </div>
                        <?php if (!$is_past): ?>
                            <button type="button" class="btn-secondary" style="width:100%;" data-action-event="leave" data-event-id="<?= $event['id'] ?>">
                                Leave event
                            </button>
                        <?php endif; ?>
                    <?php elseif ($user_status && $user_status['status'] === 'waitlisted'): ?>
                        <div class="status-alert-box danger">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                            <div>
                                <strong>You're #<?= e($user_status['waitlist_position']) ?> on the waitlist.</strong>
                                <div>No seat reserved yet. Watch your Taylor's email for updates.</div>
                            </div>
                        </div>
                        <button type="button" class="btn-secondary" style="width:100%;" data-action-event="leave-waitlist" data-event-id="<?= $event['id'] ?>">
                            Leave waitlist
                        </button>
                    <?php elseif ($is_past): ?>
                        <div class="status-alert-box info" style="background:#f1f5f9; color:var(--text-secondary); border:1px solid var(--border-light);">
                            This event has already taken place.
                        </div>
                    <?php elseif ($seats['is_full']): ?>
                        <?php if ($seats['allow_waitlist']): ?>
                            <button type="button" class="btn-primary" style="width:100%;" data-action-event="join-waitlist" data-event-id="<?= $event['id'] ?>">
                                Join waitlist
                            </button>
                            <p style="font-size:12px; color:var(--text-muted); text-align:center;">Places are offered in order. We'll email you if a seat opens.</p>
                        <?php else: ?>
                            <button type="button" class="btn-secondary" style="width:100%;" disabled>Event Full</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <button type="button" class="btn-primary" style="width:100%;" data-action-event="join" data-event-id="<?= $event['id'] ?>">
                            Join event ↗
                        </button>
                        <p style="font-size:12px; color:var(--text-muted); text-align:center;">Your place is reserved when you join. You can cancel anytime from My events.</p>
                    <?php endif; ?>

                    <button type="button" class="btn-secondary" style="width:100%;" onclick="navigator.clipboard.writeText(window.location.href); alert('Event link copied to clipboard!');">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"/><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"/></svg>
                        Share event
                    </button>
                </div>

                <hr style="border:none; border-top:1px solid var(--border-subtle); margin: 6px 0;">

                <!-- Hosted by card -->
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">HOSTED BY</div>
                    <div style="font-size: 15px; font-weight: 700;"><?= e($event['organiser_name']) ?></div>
                    <div style="font-size: 12.5px; color: var(--text-secondary); margin: 6px 0;">
                        Creating a more thoughtful world, one idea at a time.
                    </div>
                    <?php if (!empty($event['organiser_email'])): ?>
                        <a href="mailto:<?= e($event['organiser_email']) ?>" style="font-size: 12.5px; color: var(--color-primary); font-weight: 600;">
                            <?= e($event['organiser_email']) ?> ↗
                        </a>
                    <?php endif; ?>
                </div>

                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 8px;">
                    Need accessibility support? Contact the organiser before <?= e(format_event_date($event['event_date'])) ?> so we can make your visit comfortable.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
