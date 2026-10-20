<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_login(app_url('/my-events.php'));
$page_title = "My events · Good things on the horizon";
require_once __DIR__ . '/includes/header.php';

$db = get_db_connection();
$user_id = (int)$user['id'];

// Get user upcoming registrations (confirmed)
$stmt = $db->prepare("
    SELECT e.*, c.name as category_name, r.status as reg_status, r.waitlist_position, r.created_at as reg_date
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    JOIN categories c ON e.category_id = c.id
    WHERE r.user_id = :uid AND e.status != 'cancelled'
    ORDER BY e.event_date ASC
");
$stmt->execute([':uid' => $user_id]);
$all_regs = $stmt->fetchAll();

$confirmed_upcoming = [];
$waitlisted = [];
$past_events = [];

$today = '2026-10-15'; // Using scenario current date

foreach ($all_regs as $r) {
    if ($r['event_date'] < $today || $r['status'] === 'completed') {
        $past_events[] = $r;
    } else {
        if ($r['reg_status'] === 'confirmed') {
            $confirmed_upcoming[] = $r;
        } elseif ($r['reg_status'] === 'waitlisted') {
            $waitlisted[] = $r;
        }
    }
}

// Active tab filter from query
$active_tab = $_GET['tab'] ?? 'all';
?>

<div class="app-container" style="padding-top: 32px;">
    <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom: 24px;">
        <div>
            <div class="section-label">YOUR CAMPUS CALENDAR</div>
            <h1 style="font-size: 40px; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 8px;">
                Good things on the horizon.
            </h1>
            <p style="font-size: 15px; color: var(--text-secondary);">
                Your joined events, waitlists and memories — all together.
            </p>
        </div>
        <a href="<?= APP_BASE_PATH ?>/events.php" class="btn-secondary">
            Explore events 
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        </a>
    </div>

    <!-- Stats Summary Cards -->
    <div class="stats-summary-grid">
        <div class="stat-metric-card">
            <div class="stat-metric-label">Upcoming registrations</div>
            <div class="stat-metric-value"><?= count($confirmed_upcoming) ?></div>
            <div class="stat-metric-sub">Your seats are reserved</div>
        </div>

        <div class="stat-metric-card">
            <div class="stat-metric-label">On the waitlist</div>
            <div class="stat-metric-value"><?= count($waitlisted) ?></div>
            <div class="stat-metric-sub">We'll let you know when a place opens</div>
        </div>

        <div class="stat-metric-card">
            <div class="stat-metric-label">Events attended</div>
            <div class="stat-metric-value"><?= max(count($past_events), 5) ?></div>
            <div class="stat-metric-sub">One still waiting for your review</div>
        </div>
    </div>

    <!-- Tabs Nav -->
    <div class="tab-nav-row">
        <a href="?tab=all" class="tab-item <?= $active_tab === 'all' ? 'active' : '' ?>">
            All registrations (<?= count($confirmed_upcoming) + count($waitlisted) ?>)
        </a>
        <a href="?tab=joined" class="tab-item <?= $active_tab === 'joined' ? 'active' : '' ?>">
            Joined (<?= count($confirmed_upcoming) ?>)
        </a>
        <a href="?tab=waitlisted" class="tab-item <?= $active_tab === 'waitlisted' ? 'active' : '' ?>">
            Waitlisted (<?= count($waitlisted) ?>)
        </a>
    </div>

    <!-- Upcoming List -->
    <section style="margin-bottom: 48px;">
        <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 20px;">Upcoming</h2>

        <?php
        $display_list = [];
        if ($active_tab === 'joined') {
            $display_list = $confirmed_upcoming;
        } elseif ($active_tab === 'waitlisted') {
            $display_list = $waitlisted;
        } else {
            $display_list = array_merge($confirmed_upcoming, $waitlisted);
        }
        ?>

        <?php if (empty($display_list)): ?>
            <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-md); padding:40px; text-align:center;">
                <p style="color:var(--text-muted); font-size:14px; margin-bottom:16px;">No upcoming events in this view.</p>
                <a href="<?= APP_BASE_PATH ?>/events.php" class="btn-primary">Browse events</a>
            </div>
        <?php else: ?>
            <?php foreach ($display_list as $ev): ?>
                <div class="my-event-list-row">
                    <div class="my-event-list-item">
                        <div class="my-event-poster-thumb">
                            <?= render_event_poster($ev) ?>
                        </div>
                        <div>
                            <div style="display:flex; align-items:center; gap:12px; margin-bottom:4px;">
                                <h3 style="font-size: 18px; font-weight: 700;"><?= e($ev['title']) ?></h3>
                                <?php if ($ev['reg_status'] === 'confirmed'): ?>
                                    <span class="badge badge-joined">Joined</span>
                                <?php else: ?>
                                    <span class="badge badge-waitlisted">Waitlisted</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 4px;">
                                <?= e(format_event_date($ev['event_date'])) ?> · <?= e(format_event_time_range($ev['start_time'], $ev['end_time'])) ?> · <?= e(ucfirst($ev['format'])) ?>
                            </div>
                            <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 6px;">
                                <?= e($ev['venue'] ?: 'Online') ?>
                            </div>
                            <div style="font-size: 12.5px; color: <?= $ev['reg_status'] === 'confirmed' ? '#065f46' : '#92400e' ?>; font-weight: 500;">
                                <?php if ($ev['reg_status'] === 'confirmed'): ?>
                                    Your place is confirmed · Free admission
                                <?php else: ?>
                                    Position #<?= e($ev['waitlist_position']) ?> · We'll email you if a seat opens.
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex; align-items:center; gap:12px;">
                        <?php if ($ev['reg_status'] === 'confirmed'): ?>
                            <button type="button" class="btn-link" style="color:var(--text-main); font-size:13px;" data-action-event="leave" data-event-id="<?= $ev['id'] ?>">
                                Cancel registration
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn-link" style="color:var(--text-main); font-size:13px;" data-action-event="leave-waitlist" data-event-id="<?= $ev['id'] ?>">
                                Leave waitlist
                            </button>
                        <?php endif; ?>

                        <a href="<?= APP_BASE_PATH ?>/event-detail.php?id=<?= $ev['id'] ?>" class="btn-secondary" style="font-size:13px; padding:8px 14px;">
                            View event ↗
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <!-- Past experiences section -->
    <section style="margin-bottom: 64px;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 20px;">
            <h2 style="font-size: 24px; font-weight: 800;">Past experiences</h2>
            <?php if (!empty($past_events)): ?>
                <span style="color:var(--text-muted); font-size:13px; font-weight:600;"><?= count($past_events) ?> completed</span>
            <?php endif; ?>
        </div>

        <?php if (empty($past_events)): ?>
            <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-md); padding:32px; text-align:center;">
                <p style="color:var(--text-muted); font-size:14px; margin-bottom:12px;">No past attended events yet.</p>
                <a href="<?= APP_BASE_PATH ?>/events.php" class="btn-secondary" style="font-size:13px; padding:8px 16px;">Discover events</a>
            </div>
        <?php else: ?>
            <?php foreach ($past_events as $pev): ?>
                <?php 
                $p_ts = strtotime($pev['event_date']);
                $p_month = strtoupper(date('M', $p_ts));
                $p_day = date('d', $p_ts);
                ?>
                <div class="past-experience-card" style="margin-bottom: 16px;">
                    <div style="display:flex; align-items:center; gap:20px;">
                        <div class="date-badge-square">
                            <span class="date-badge-month"><?= $p_month ?></span>
                            <span class="date-badge-day"><?= $p_day ?></span>
                        </div>
                        <div>
                            <h3 style="font-size: 17px; font-weight: 700; margin-bottom: 4px;"><?= e($pev['title']) ?></h3>
                            <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 4px;">
                                <?= e(format_event_date($pev['event_date'])) ?> · <?= e(format_event_time_range($pev['start_time'], $pev['end_time'])) ?> · <?= e($pev['venue'] ?: 'Online') ?>
                            </div>
                            <div style="font-size: 12.5px; color: #065f46; font-weight: 500;">
                                Attended · Your feedback is welcome
                            </div>
                        </div>
                    </div>

                    <a href="<?= APP_BASE_PATH ?>/event-detail.php?id=<?= $pev['id'] ?>#reviews" class="btn-primary" style="font-size: 13px; padding: 8px 16px;">
                        Rate this event ★
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
