<?php
require_once __DIR__ . '/includes/header.php';

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

if (!$event) {
    echo '<div class="app-container" style="padding: 64px 0; text-align:center;"><h2>Event not found</h2><p><a href="/events.php" class="btn-primary" style="margin-top:16px;">Back to events</a></p></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$page_title = $event['title'];
$seats = get_event_seat_info($event_id);
$user_status = $user ? get_user_event_status($event_id, (int)$user['id']) : null;
$rating_info = get_event_rating_info($event_id);

$is_past = strtotime($event['event_date']) < strtotime(date('Y-m-d'));
$day_of_week = date('l', strtotime($event['event_date']));

// Schedule mockup items
$schedule_items = [
    ['time' => '10:00 AM', 'desc' => 'Doors open & student showcase'],
    ['time' => '11:00 AM', 'desc' => 'Future by design · keynote with guest speakers'],
    ['time' => '2:00 PM', 'desc' => 'Hands-on studio sessions & interactive workshops'],
    ['time' => '4:00 PM', 'desc' => 'Creative connections & closing showcase']
];
?>

<div class="app-container" style="padding-top: 24px;">
    <!-- Breadcrumbs -->
    <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 24px;">
        <a href="/events.php" style="color:var(--text-secondary)">Explore events</a> / 
        <a href="/events.php?category=<?= urlencode($event['category_slug']) ?>" style="color:var(--text-secondary)"><?= e($event['category_name']) ?></a> / 
        <span style="color:var(--text-main)"><?= e($event['title']) ?></span>
    </div>

    <div class="event-detail-layout">
        <!-- Main Column -->
        <div class="event-detail-main">
            <div class="event-detail-poster-wrap">
                <?= render_event_poster($event) ?>
            </div>

            <div style="font-size: 12px; font-weight: 700; color: var(--color-primary); letter-spacing: 0.08em; text-transform: uppercase;">
                <?= e(strtoupper($event['category_name'])) ?> · <?= e(strtoupper($event['format'])) ?> EVENT
            </div>

            <h1 class="event-detail-title"><?= e($event['title']) ?></h1>

            <p class="event-detail-lead"><?= nl2br(e($event['description'])) ?></p>

            <!-- Schedule section -->
            <div class="info-card-box">
                <h3 class="info-card-title">The day at a glance</h3>
                <div class="schedule-timeline">
                    <?php foreach ($schedule_items as $item): ?>
                        <div class="timeline-row">
                            <div class="timeline-time"><?= e($item['time']) ?></div>
                            <div class="timeline-desc"><?= e($item['desc']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Organiser updates -->
            <div style="margin-top: 24px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 16px;">
                    <h3 style="font-size: 20px; font-weight: 800;">Before you go</h3>
                    <span style="font-size: 12px; color: var(--text-muted);">2 organiser updates</span>
                </div>

                <div class="info-card-box">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom: 12px;">
                        <div class="avatar-initials" style="width:28px; height:28px; font-size:11px;">
                            <?= e(get_initials($event['organiser_name'])) ?>
                        </div>
                        <span style="font-size: 13.5px; font-weight: 700;"><?= e($event['organiser_name']) ?></span>
                        <span class="badge" style="background:#fee2e2; color:var(--color-primary); font-size:11px;">Organiser</span>
                        <span style="font-size: 12px; color: var(--text-muted); margin-left:auto;">12 Oct 2026</span>
                    </div>
                    <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 16px; line-height: 1.5;">
                        Studio materials are provided. Bring a reusable water bottle and arrive 15 minutes early to check in at the Grand Hall foyer.
                    </p>
                    <hr style="border:none; border-top:1px solid var(--border-subtle); margin: 16px 0;">
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5;">
                        The student showcase is open all day. You're welcome to move between sessions at your own pace.
                    </p>
                </div>

                <p style="font-size: 12.5px; color: var(--text-muted); margin-top: 8px;">
                    Ratings and feedback open after the event for joined attendees. For questions now, contact the organiser above.
                </p>
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
                            <div style="font-weight:600;"><?= e($seats['confirmed']) ?> / <?= e($seats['capacity']) ?> registered</div>
                            <div style="font-size:12px; color:var(--text-muted);">
                                <?= e($seats['seats_left']) ?> seats left · <?= e($seats['waitlisted']) ?> waitlisted
                            </div>
                        </div>
                    </div>

                    <div class="reg-meta-item">
                        <svg class="reg-meta-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <div>
                            <div style="font-weight:600;"><?= $rating_info['formatted'] ? $rating_info['formatted'] . ' / 5 organiser rating' : '4.8 / 5 organiser rating' ?></div>
                            <div style="font-size:12px; color:var(--text-muted);">Based on past attendee reviews</div>
                        </div>
                    </div>
                </div>

                <div class="reg-action-area">
                    <?php if (!$user): ?>
                        <a href="/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn-primary" style="width:100%;">
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
                        <button type="button" class="btn-secondary" style="width:100%;" data-action-event="leave" data-event-id="<?= $event['id'] ?>">
                            Leave event
                        </button>
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
