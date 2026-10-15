<?php
require_once __DIR__ . '/../config/db.php';

function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function format_event_date(string $date_str): string {
    $time = strtotime($date_str);
    return date('j M Y', $time);
}

function format_event_full_date(string $date_str): string {
    $time = strtotime($date_str);
    return date('l, j M Y', $time);
}

function format_event_time(string $time_str): string {
    $time = strtotime($time_str);
    return date('g:i A', $time);
}

function format_event_time_range(string $start_time, string $end_time): string {
    return format_event_time($start_time) . ' – ' . format_event_time($end_time) . ' · MYT';
}

function get_initials(string $name): string {
    $parts = explode(' ', trim($name));
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

// Get event registration summary (capacity, confirmed, waitlisted, seats left)
function get_event_seat_info(int $event_id): array {
    $db = get_db_connection();
    
    // Get capacity
    $stmt = $db->prepare("SELECT capacity, allow_waitlist, status FROM events WHERE id = :id");
    $stmt->execute([':id' => $event_id]);
    $event = $stmt->fetch();
    if (!$event) {
        return ['capacity' => 0, 'confirmed' => 0, 'waitlisted' => 0, 'seats_left' => 0, 'is_full' => true, 'allow_waitlist' => false];
    }
    
    $capacity = (int) $event['capacity'];
    
    // Confirmed count
    $stmt = $db->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = :id AND status = 'confirmed'");
    $stmt->execute([':id' => $event_id]);
    $confirmed = (int) $stmt->fetchColumn();
    
    // Waitlisted count
    $stmt = $db->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = :id AND status = 'waitlisted'");
    $stmt->execute([':id' => $event_id]);
    $waitlisted = (int) $stmt->fetchColumn();
    
    $seats_left = max(0, $capacity - $confirmed);
    $is_full = ($confirmed >= $capacity);
    
    return [
        'capacity' => $capacity,
        'confirmed' => $confirmed,
        'waitlisted' => $waitlisted,
        'seats_left' => $seats_left,
        'is_full' => $is_full,
        'allow_waitlist' => (bool) $event['allow_waitlist'],
        'status' => $event['status'],
    ];
}

// Get user status for an event
function get_user_event_status(int $event_id, ?int $user_id): ?array {
    if (!$user_id) {
        return null;
    }
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT status, waitlist_position, created_at FROM registrations WHERE event_id = :event_id AND user_id = :user_id LIMIT 1");
    $stmt->execute([':event_id' => $event_id, ':user_id' => $user_id]);
    $reg = $stmt->fetch();
    return $reg ?: null;
}

// Auto-promote first waitlisted attendee (FIFO) when seat frees up
function promote_first_waitlisted(int $event_id): ?int {
    $db = get_db_connection();
    
    // Check if seats are currently available
    $seat_info = get_event_seat_info($event_id);
    if ($seat_info['seats_left'] <= 0) {
        return null;
    }
    
    // Find next in line (lowest waitlist_position or earliest created_at)
    $stmt = $db->prepare("
        SELECT id, user_id FROM registrations 
        WHERE event_id = :event_id AND status = 'waitlisted' 
        ORDER BY waitlist_position ASC, created_at ASC 
        LIMIT 1
    ");
    $stmt->execute([':event_id' => $event_id]);
    $next = $stmt->fetch();
    
    if (!$next) {
        return null;
    }
    
    // Promote user to confirmed
    $upd = $db->prepare("UPDATE registrations SET status = 'confirmed', waitlist_position = NULL, updated_at = NOW() WHERE id = :id");
    $upd->execute([':id' => $next['id']]);
    
    // Recalculate remaining waitlist positions
    reindex_waitlist_positions($event_id);
    
    return (int) $next['user_id'];
}

// Reindex waitlist positions 1, 2, 3...
function reindex_waitlist_positions(int $event_id): void {
    $db = get_db_connection();
    $stmt = $db->prepare("
        SELECT id FROM registrations 
        WHERE event_id = :event_id AND status = 'waitlisted' 
        ORDER BY waitlist_position ASC, created_at ASC
    ");
    $stmt->execute([':event_id' => $event_id]);
    $rows = $stmt->fetchAll();
    
    $upd = $db->prepare("UPDATE registrations SET waitlist_position = :pos WHERE id = :id");
    $pos = 1;
    foreach ($rows as $r) {
        $upd->execute([':pos' => $pos, ':id' => $r['id']]);
        $pos++;
    }
}

// Calculate rating info for an event
function get_event_rating_info(int $event_id): array {
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM comments WHERE event_id = :event_id");
    $stmt->execute([':event_id' => $event_id]);
    $res = $stmt->fetch();
    
    $avg = $res && $res['avg_rating'] !== null ? round((float)$res['avg_rating'], 1) : null;
    $count = $res ? (int)$res['review_count'] : 0;
    
    return [
        'avg' => $avg,
        'count' => $count,
        'formatted' => $avg !== null ? number_format($avg, 1) : null,
    ];
}

// Render poster card art or uploaded image
function render_event_poster(array $event, string $size = 'card'): string {
    if (!empty($event['poster_path']) && file_exists(__DIR__ . '/../' . $event['poster_path'])) {
        return '<img src="' . e($event['poster_path']) . '" alt="' . e($event['title']) . '" class="poster-uploaded-img">';
    }

    $theme = $event['poster_theme'] ?? 'purple';
    $year = date('Y', strtotime($event['event_date'] ?? '2026-10-24'));
    $date_formatted = strtoupper(date('j M Y', strtotime($event['event_date'] ?? '2026-10-24')));
    $title = strtoupper($event['title']);

    $html = '<div class="poster-art-wrapper poster-theme-' . e($theme) . '">';
    $html .= '  <div class="poster-eyebrow">';
    $html .= '    <span>TAYLOR\'S / CAMPUS CULTURE</span>';
    $html .= '    <span>' . e($year) . '</span>';
    $html .= '  </div>';
    $html .= '  <div class="poster-big-title">' . e($title) . '</div>';
    $html .= '  <div class="poster-art-graphic"></div>';
    $html .= '  <div class="poster-footer-meta">';
    $html .= '    <div class="poster-tagline">' . e(substr($event['description'], 0, 36)) . '...</div>';
    $html .= '    <div class="poster-date-flag">' . e($date_formatted) . ' ↗</div>';
    $html .= '  </div>';
    $html .= '</div>';

    return $html;
}

