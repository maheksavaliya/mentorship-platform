<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is a mentee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentee') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$session_id = $data['session_id'] ?? null;

if (!$session_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

try {
    // Check if session belongs to this mentee
    $stmt = $conn->prepare("SELECT mentor_id FROM sessions WHERE id = ? AND mentee_id = ?");
    $stmt->execute([$session_id, $_SESSION['user_id']]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Session not found']);
        exit();
    }

    // Update session status
    $stmt = $conn->prepare("UPDATE sessions SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$session_id]);

    // Create notification for mentor
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, title, message, created_at) 
                           VALUES (?, 'mentor', 'Session Cancelled', 'A mentee has cancelled their session.', NOW())");
    $stmt->execute([$session['mentor_id']]);

    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    error_log("Error cancelling session: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to cancel session']);
} 