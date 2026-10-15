<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = current_user();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Please log in first.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$event_id = (int)($input['event_id'] ?? 0);
$action = trim($input['action'] ?? '');
$token = $input['csrf_token'] ?? '';

if (!verify_csrf_token($token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired CSRF token.']);
    exit;
}

$db = get_db_connection();
$stmt = $db->prepare("SELECT * FROM events WHERE id = :id AND status != 'cancelled'");
$stmt->execute([':id' => $event_id]);
$event = $stmt->fetch();

if (!$event) {
    echo json_encode(['success' => false, 'message' => 'Event not found or cancelled.']);
    exit;
}

$user_id = (int)$user['id'];
$seats = get_event_seat_info($event_id);
$cur_reg = get_user_event_status($event_id, $user_id);

switch ($action) {
    case 'join':
        if ($cur_reg && in_array($cur_reg['status'], ['confirmed', 'waitlisted'])) {
            echo json_encode(['success' => false, 'message' => 'You are already registered for this event.']);
            exit;
        }

        if ($seats['is_full']) {
            echo json_encode(['success' => false, 'message' => 'This event is full. You can join the waitlist instead.']);
            exit;
        }

        // Insert or update registration
        $stmt = $db->prepare("
            INSERT INTO registrations (event_id, user_id, status, waitlist_position) 
            VALUES (:eid, :uid, 'confirmed', NULL)
            ON DUPLICATE KEY UPDATE status = 'confirmed', waitlist_position = NULL, updated_at = NOW()
        ");
        $stmt->execute([':eid' => $event_id, ':uid' => $user_id]);

        echo json_encode(['success' => true, 'message' => 'Seat reserved successfully!']);
        exit;

    case 'leave':
        if (!$cur_reg || $cur_reg['status'] !== 'confirmed') {
            echo json_encode(['success' => false, 'message' => 'You are not confirmed for this event.']);
            exit;
        }

        // Cancel registration
        $stmt = $db->prepare("DELETE FROM registrations WHERE event_id = :eid AND user_id = :uid");
        $stmt->execute([':eid' => $event_id, ':uid' => $user_id]);

        // Auto-promote first waitlisted user FIFO!
        $promoted_user_id = promote_first_waitlisted($event_id);

        echo json_encode([
            'success' => true, 
            'message' => 'Registration cancelled.', 
            'promoted_user' => $promoted_user_id
        ]);
        exit;

    case 'join-waitlist':
        if (!$event['allow_waitlist']) {
            echo json_encode(['success' => false, 'message' => 'Waitlist is not enabled for this event.']);
            exit;
        }

        if ($cur_reg && in_array($cur_reg['status'], ['confirmed', 'waitlisted'])) {
            echo json_encode(['success' => false, 'message' => 'You are already registered or on the waitlist.']);
            exit;
        }

        // Calculate next waitlist position
        $pos = $seats['waitlisted'] + 1;
        $stmt = $db->prepare("
            INSERT INTO registrations (event_id, user_id, status, waitlist_position) 
            VALUES (:eid, :uid, 'waitlisted', :pos)
            ON DUPLICATE KEY UPDATE status = 'waitlisted', waitlist_position = :pos, updated_at = NOW()
        ");
        $stmt->execute([':eid' => $event_id, ':uid' => $user_id, ':pos' => $pos]);

        echo json_encode(['success' => true, 'message' => "You have joined the waitlist at position #{$pos}."]);
        exit;

    case 'leave-waitlist':
        if (!$cur_reg || $cur_reg['status'] !== 'waitlisted') {
            echo json_encode(['success' => false, 'message' => 'You are not on the waitlist.']);
            exit;
        }

        $stmt = $db->prepare("DELETE FROM registrations WHERE event_id = :eid AND user_id = :uid");
        $stmt->execute([':eid' => $event_id, ':uid' => $user_id]);

        // Reindex positions
        reindex_waitlist_positions($event_id);

        echo json_encode(['success' => true, 'message' => 'You have left the waitlist.']);
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        exit;
}
