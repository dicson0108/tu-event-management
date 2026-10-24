<?php
$page_title = "Find your kind of campus";
require_once __DIR__ . '/includes/header.php';

$db = get_db_connection();
$user = current_user();

// Filter parameters
$search = trim($_GET['q'] ?? '');
$category_slug = trim($_GET['category'] ?? '');
$date_filter = trim($_GET['date'] ?? '');
$format_filter = trim($_GET['format'] ?? '');
$sort = trim($_GET['sort'] ?? 'date_asc');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 6;
$offset = ($page - 1) * $per_page;

// Build query
$where = ["e.status = 'published'"];
$params = [];

if ($search !== '') {
    $where[] = "(e.title LIKE :search_title OR e.description LIKE :search_description OR e.organiser_name LIKE :search_organiser OR e.venue LIKE :search_venue)";
    foreach (['title', 'description', 'organiser', 'venue'] as $field) {
        $params[':search_' . $field] = "%{$search}%";
    }
}

if ($category_slug !== '') {
    $where[] = "c.slug = :category";
    $params[':category'] = $category_slug;
}

if ($format_filter !== '') {
    $where[] = "e.format = :format";
    $params[':format'] = $format_filter;
}

if ($date_filter === 'today') {
    $where[] = "e.event_date = CURDATE()";
} elseif ($date_filter === 'week') {
    $where[] = "e.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
} elseif ($date_filter === 'october') {
    $where[] = "e.event_date BETWEEN '2026-10-01' AND '2026-10-31'";
} elseif ($date_filter === 'november') {
    $where[] = "e.event_date BETWEEN '2026-11-01' AND '2026-11-30'";
} else {
    // Default: show upcoming events from October 2026 onwards
    $where[] = "e.event_date >= '2026-10-01'";
}

$where_clause = implode(" AND ", $where);

// Count total
$count_sql = "SELECT COUNT(*) FROM events e JOIN categories c ON e.category_id = c.id WHERE {$where_clause}";
$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$total_events = (int) $count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_events / $per_page));

// Sort order
$order_clause = "e.event_date ASC, e.start_time ASC";
if ($sort === 'date_desc') {
    $order_clause = "e.event_date DESC, e.start_time DESC";
} elseif ($sort === 'popular') {
    $order_clause = "(SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) DESC";
}

// Fetch events
$sql = "
    SELECT e.*, c.name as category_name, c.slug as category_slug
    FROM events e 
    JOIN categories c ON e.category_id = c.id 
    WHERE {$where_clause}
    ORDER BY {$order_clause}
    LIMIT {$per_page} OFFSET {$offset}
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

// Categories for dropdown
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<div class="app-container" style="padding-top: 32px;">
    <div style="margin-bottom: 24px;">
        <div class="section-label">DISCOVER / OCTOBER — NOVEMBER</div>
        <h1 style="font-size: 40px; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 8px;">
            Find your kind of campus.
        </h1>
        <p style="font-size: 15px; color: var(--text-secondary);">
            Explore approved upcoming events, from fresh ideas to familiar faces.
        </p>
    </div>

    <!-- Filter Form -->
    <div class="filter-container">
        <form method="GET" action="<?= APP_BASE_PATH ?>/events.php">
            <div class="search-field-main">
                <div class="search-input-wrap">
                    <svg class="search-icon-inside" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search by event title, topic or organiser" class="input-text-standard">
                </div>
                <button type="submit" class="btn-primary" style="padding: 10px 24px;">Search events</button>
            </div>

            <div class="filter-row">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">All categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat['slug']) ?>" <?= $category_slug === $cat['slug'] ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Date</label>
                    <select name="date" class="form-select" onchange="this.form.submit()">
                        <option value="">Any upcoming date</option>
                        <option value="today" <?= $date_filter === 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="week" <?= $date_filter === 'week' ? 'selected' : '' ?>>This week</option>
                        <option value="october" <?= $date_filter === 'october' ? 'selected' : '' ?>>October 2026</option>
                        <option value="november" <?= $date_filter === 'november' ? 'selected' : '' ?>>November 2026</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Format</label>
                    <select name="format" class="form-select" onchange="this.form.submit()">
                        <option value="">All formats</option>
                        <option value="physical" <?= $format_filter === 'physical' ? 'selected' : '' ?>>Physical</option>
                        <option value="online" <?= $format_filter === 'online' ? 'selected' : '' ?>>Online</option>
                        <option value="hybrid" <?= $format_filter === 'hybrid' ? 'selected' : '' ?>>Hybrid</option>
                    </select>
                </div>

                <div>
                    <a href="<?= APP_BASE_PATH ?>/events.php" class="btn-link" style="color:var(--color-primary); font-size:13px; font-weight:600; padding-bottom:10px; display:inline-block;">Reset filters</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Results meta bar -->
    <div class="filter-results-bar">
        <div>
            <?= $total_events ?> upcoming events · Showing <?= $total_events > 0 ? ($offset + 1) . '–' . min($total_events, $offset + count($events)) : '0' ?>
        </div>

        <div class="sort-group">
            <span style="font-size:13px; color:var(--text-muted)">Sort by</span>
            <form method="GET" action="<?= APP_BASE_PATH ?>/events.php" style="display:inline-flex; align-items:center; gap:8px;">
                <input type="hidden" name="q" value="<?= e($search) ?>">
                <input type="hidden" name="category" value="<?= e($category_slug) ?>">
                <input type="hidden" name="date" value="<?= e($date_filter) ?>">
                <input type="hidden" name="format" value="<?= e($format_filter) ?>">
                <select name="sort" class="form-select" style="padding: 6px 10px; font-size: 13px;" onchange="this.form.submit()">
                    <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Date: soonest first</option>
                    <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Date: latest first</option>
                    <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Most popular</option>
                </select>
            </form>
            <a href="<?= APP_BASE_PATH ?>/events.php?sort=popular" class="btn-link" style="font-size:13px; color:var(--color-primary); font-weight:600;">Most popular</a>
        </div>
    </div>

    <!-- Events Grid -->
    <?php if (empty($events)): ?>
        <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-lg); padding:64px; text-align:center; margin:32px 0;">
            <h3 style="font-size:20px; font-weight:700; margin-bottom:8px;">No events found</h3>
            <p style="color:var(--text-muted); font-size:14px; margin-bottom:20px;">Try adjusting your filters or search keywords.</p>
            <a href="<?= APP_BASE_PATH ?>/events.php" class="btn-primary">Clear all filters</a>
        </div>
    <?php else: ?>
        <div class="events-grid">
            <?php foreach ($events as $event): 
                $seats = get_event_seat_info((int)$event['id']);
                $user_status = $user ? get_user_event_status((int)$event['id'], (int)$user['id']) : null;
                
                // Determine badge
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
                                Your waitlist position: #<?= e($user_status['waitlist_position']) ?>
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
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination-wrap">
            <a href="?page=<?= max(1, $page - 1) ?>&q=<?= urlencode($search) ?>&category=<?= urlencode($category_slug) ?>&date=<?= urlencode($date_filter) ?>&format=<?= urlencode($format_filter) ?>&sort=<?= urlencode($sort) ?>" 
               class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>">
                ← Previous
            </a>

            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <a href="?page=<?= $p ?>&q=<?= urlencode($search) ?>&category=<?= urlencode($category_slug) ?>&date=<?= urlencode($date_filter) ?>&format=<?= urlencode($format_filter) ?>&sort=<?= urlencode($sort) ?>" 
                   class="page-btn <?= $p === $page ? 'active' : '' ?>">
                    <?= $p ?>
                </a>
            <?php endfor; ?>

            <a href="?page=<?= min($total_pages, $page + 1) ?>&q=<?= urlencode($search) ?>&category=<?= urlencode($category_slug) ?>&date=<?= urlencode($date_filter) ?>&format=<?= urlencode($format_filter) ?>&sort=<?= urlencode($sort) ?>" 
               class="page-btn <?= $page >= $total_pages ? 'disabled' : '' ?>">
                Next →
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
