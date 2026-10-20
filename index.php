<?php
$page_title = "Go beyond the classroom";
require_once __DIR__ . '/includes/header.php';

$db = get_db_connection();

// Spotlight event (Design Futures Festival - ID 1)
$stmt = $db->prepare("SELECT e.*, c.name as category_name FROM events e JOIN categories c ON e.category_id = c.id WHERE e.id = 1 LIMIT 1");
$stmt->execute();
$spotlight = $stmt->fetch();
$spotlight_seats = $spotlight ? get_event_seat_info((int)$spotlight['id']) : null;

// Categories
$cats = $db->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();

// Top 3 upcoming events (excluding spotlight)
$stmt = $db->prepare("
    SELECT e.*, c.name as category_name 
    FROM events e 
    JOIN categories c ON e.category_id = c.id 
    WHERE e.status = 'published' AND e.event_date >= '2026-10-01' AND e.id != 1
    ORDER BY e.event_date ASC 
    LIMIT 3
");
$stmt->execute();
$upcoming = $stmt->fetchAll();
?>

<div class="app-container">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-grid">
            <div>
                <div class="section-label">THE LAKESIDE LIFE / OCTOBER — NOVEMBER 2026</div>
                <h1 class="hero-heading">Go beyond<br>the classroom.</h1>
                <p class="hero-lead">
                    Big ideas, late-night sets, new friendships. Find the experiences that make Taylor's feel like yours.
                </p>

                <form action="<?= APP_BASE_PATH ?>/events.php" method="GET" class="hero-search-bar" role="search">
                    <svg class="hero-search-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" name="q" placeholder="Search events, interests or clubs..." class="hero-search-input" aria-label="Search events">
                    <button type="submit" class="hero-search-btn" aria-label="Find events">
                        <span>Find events</span>
                        <svg class="hero-btn-arrow" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </button>
                </form>

                <div class="hero-quick-tags">
                    <span class="quick-tags-label">Popular:</span>
                    <a href="<?= APP_BASE_PATH ?>/events.php?category=arts-culture" class="quick-tag-chip">🎨 Arts</a>
                    <a href="<?= APP_BASE_PATH ?>/events.php?category=music" class="quick-tag-chip">🎵 Music</a>
                    <a href="<?= APP_BASE_PATH ?>/events.php?category=tech-innovation" class="quick-tag-chip">⚡ Tech & AI</a>
                    <a href="<?= APP_BASE_PATH ?>/events.php?category=careers" class="quick-tag-chip">💼 Career</a>
                </div>

                <div class="hero-search-sub">Free to discover. Open to the Taylor's community.</div>
            </div>

            <?php if ($spotlight): ?>
                <div class="spotlight-card">
                    <?= render_event_poster($spotlight) ?>
                    <div class="spotlight-info-bar">
                        <div>
                            <div class="spotlight-label">This month's spotlight</div>
                            <div class="spotlight-meta">
                                <?= e(format_event_date($spotlight['event_date'])) ?> · <?= e($spotlight['venue']) ?> · <?= e($spotlight_seats['seats_left']) ?> seats left
                            </div>
                        </div>
                        <a href="<?= APP_BASE_PATH ?>/event-detail.php?id=<?= $spotlight['id'] ?>" class="spotlight-cta-btn">
                            <span>Explore festival</span>
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M7 17L17 7M7 7h10v10"/></svg>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Categories Strip -->
    <section class="category-strip">
        <?php foreach ($cats as $cat): ?>
            <a href="<?= APP_BASE_PATH ?>/events.php?category=<?= urlencode($cat['slug']) ?>" class="category-tile">
                <div class="cat-icon">
                    <?php if ($cat['slug'] === 'arts-culture'): ?>
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.563-2.512 5.563-5.563C22 6.5 17.5 2 12 2Z"/></svg>
                    <?php elseif ($cat['slug'] === 'music'): ?>
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                    <?php elseif ($cat['slug'] === 'tech-innovation'): ?>
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                    <?php elseif ($cat['slug'] === 'careers'): ?>
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                    <?php elseif ($cat['slug'] === 'community'): ?>
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                    <?php else: ?>
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    <?php endif; ?>
                </div>
                <span class="cat-name"><?= e($cat['name']) ?></span>
            </a>
        <?php endforeach; ?>
    </section>

    <!-- Coming Up On Campus Section -->
    <section style="margin-bottom: 48px;">
        <div class="section-header-row">
            <div>
                <div class="section-label">FIND YOUR NEXT THING</div>
                <h2 class="section-title">Coming up on campus</h2>
                <p class="section-desc">Make space in your calendar for something good.</p>
            </div>
            <a href="<?= APP_BASE_PATH ?>/events.php" class="btn-secondary">
                View all events 
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </a>
        </div>

        <div class="events-grid">
            <?php foreach ($upcoming as $event): 
                $seats = get_event_seat_info((int)$event['id']);
                $user_status = $current_user ? get_user_event_status((int)$event['id'], (int)$current_user['id']) : null;
                
                if ($user_status && $user_status['status'] === 'confirmed') {
                    $badge_class = 'badge-joined';
                    $badge_label = 'Joined';
                } elseif ($user_status && $user_status['status'] === 'waitlisted') {
                    $badge_class = 'badge-waitlisted';
                    $badge_label = 'Waitlisted';
                } elseif ($seats['is_full']) {
                    $badge_class = 'badge-full';
                    $badge_label = 'Full';
                } else {
                    $badge_class = 'badge-open';
                    $badge_label = 'Open';
                }
            ?>
                <a href="<?= APP_BASE_PATH ?>/event-detail.php?id=<?= $event['id'] ?>" class="event-card">
                    <?= render_event_poster($event) ?>
                    <div class="event-card-body">
                        <div class="event-card-meta-top">
                            <span class="badge-category"><?= e($event['category_name']) ?></span>
                            <span class="badge <?= $badge_class ?>"><?= $badge_label ?></span>
                        </div>
                        <h3 class="event-card-title"><?= e($event['title']) ?></h3>
                        <div class="event-card-schedule">
                            <?= e(format_event_date($event['event_date'])) ?> · <?= e(format_event_time($event['start_time'])) ?> · <?= e(ucfirst($event['format'])) ?>
                        </div>
                        <div class="event-card-location"><?= e($event['venue'] ?: 'Online') ?></div>
                    </div>
                    <div class="event-card-footer">
                        <span class="event-seats-status">
                            <?php if ($user_status && $user_status['status'] === 'waitlisted'): ?>
                                Waitlist #<?= e($user_status['waitlist_position']) ?>
                            <?php elseif ($seats['is_full']): ?>
                                0 seats left · Waitlist open
                            <?php else: ?>
                                <?= e($seats['seats_left']) ?> seats left
                            <?php endif; ?>
                        </span>
                        <svg class="card-arrow-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Values Banner -->
    <section class="values-banner">
        <div>
            <div class="section-label">MORE THAN A CALENDAR</div>
            <h2 style="font-size: 36px; font-weight: 800; line-height: 1.15; margin-bottom: 16px;">
                Find your people. Make your mark.
            </h2>
            <p style="font-size: 15px; color: var(--text-secondary); line-height: 1.6; margin-bottom: 24px;">
                From your first workshop to your next standing ovation, a little curiosity can go a long way. Your Taylor's email is all you need to get started.
            </p>
            <a href="<?= APP_BASE_PATH ?>/register.php" class="btn-primary">
                Join the community
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </a>
        </div>

        <div class="banner-stats-box">
            <div class="stats-number">80+ clubs.</div>
            <div class="stats-subtitle">One vibrant campus.</div>
            <p style="font-size: 13.5px; color: var(--text-secondary); margin-top: 12px; line-height: 1.5;">
                Student-led experiences. Shared discoveries. A place for every interest.
            </p>
        </div>
    </section>

    <!-- Trio Steps -->
    <section class="features-trio">
        <div>
            <div class="feature-step-num">01 /</div>
            <h3 class="feature-title">Discover something new</h3>
            <p class="feature-desc">Browse by interest, date or format. There's always something happening.</p>
        </div>
        <div>
            <div class="feature-step-num">02 /</div>
            <h3 class="feature-title">Save your seat</h3>
            <p class="feature-desc">Join with your Taylor's account. If it's full, we'll help you find the waitlist.</p>
        </div>
        <div>
            <div class="feature-step-num">03 /</div>
            <h3 class="feature-title">Show up. Connect.</h3>
            <p class="feature-desc">Keep track in My events and share your thoughts after the experience.</p>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
