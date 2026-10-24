<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in first.']);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = $_POST;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST required.']);
    exit;
}
if (!verify_csrf_token($input['csrf_token'] ?? '')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired CSRF token.']);
    exit;
}
$event_id = (int)($input['event_id'] ?? 0);
$user_id = (int)$user['id'];
$action = trim($input['action'] ?? '');

try {
    // Lock before the first capacity read; commit after every related queue update.
    $result = with_event_lock($event_id, function (PDO $db, ?array $event) use ($event_id, $user_id, $action): array {
        if (!$event) return ['success' => false, 'message' => 'Event not found.'];
        if (!in_array($action, ['join', 'join-waitlist', 'leave', 'leave-waitlist'], true)) {
            return ['success' => false, 'message' => 'Unknown action.'];
        }
        if (in_array($action, ['join', 'join-waitlist'], true) &&
            ($event['status'] !== 'published' || $event['event_date'] < date('Y-m-d'))) {
            return ['success' => false, 'message' => 'Registration is closed for this event.'];
        }
        $seats = get_event_seat_info($event_id);
        $current = get_user_event_status($event_id, $user_id);
        if (in_array($action, ['join', 'join-waitlist'], true)) {
            if ($current && in_array($current['status'], ['confirmed', 'waitlisted'], true)) {
                return ['success' => false, 'message' => 'You are already registered or on the waitlist.'];
            }
            if ($action === 'join' && $seats['is_full']) {
                return ['success' => false, 'message' => 'This event is full. You can join the waitlist instead.'];
            }
            if ($action === 'join-waitlist' && (!$event['allow_waitlist'] || !$seats['is_full'])) {
                return ['success' => false, 'message' => 'Waitlisting requires a full event with its waitlist enabled.'];
            }
            $status = $action === 'join' ? 'confirmed' : 'waitlisted';
            $position = $action === 'join' ? null : $seats['waitlisted'] + 1;
            // Reuse inserted values without repeating native PDO parameter markers.
            $stmt = $db->prepare("INSERT INTO registrations (event_id, user_id, status, waitlist_position)
                VALUES (:eid, :uid, :status, :position)
                ON DUPLICATE KEY UPDATE status = VALUES(status), waitlist_position = VALUES(waitlist_position), updated_at = NOW()");
            $stmt->execute([':eid' => $event_id, ':uid' => $user_id, ':status' => $status, ':position' => $position]);
            return ['success' => true, 'message' => $action === 'join' ? 'Seat reserved successfully!' : "You have joined the waitlist at position #{$position}."];
        }
        $expected_status = $action === 'leave' ? 'confirmed' : 'waitlisted';
        if (!$current || $current['status'] !== $expected_status) {
            return ['success' => false, 'message' => 'No matching registration to cancel.'];
        }
        $stmt = $db->prepare('DELETE FROM registrations WHERE event_id = :eid AND user_id = :uid');
        $stmt->execute([':eid' => $event_id, ':uid' => $user_id]);
        $promoted = null;
        if ($action === 'leave') $promoted = promote_first_waitlisted($event_id);
        else reindex_waitlist_positions($event_id);
        return ['success' => true, 'message' => 'Registration cancelled.', 'promoted_user' => $promoted];
    });
    echo json_encode($result);
} catch (Throwable $error) {
    error_log('Registration update failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Registration could not be updated. Please try again.']);
}
