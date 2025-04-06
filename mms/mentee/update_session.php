<?php
session_start();
require_once '../config/db.php';

// Initialize response array
$response = array(
    'success' => false,
    'message' => ''
);

try {
    // Check if user is logged in as mentee
    if (!isset($_SESSION['mentee_id'])) {
        throw new Exception('Please login to continue.');
    }

    // Check if session_id is provided
    if (!isset($_POST['session_id'])) {
        throw new Exception('Session ID is required.');
    }

    $session_id = $_POST['session_id'];
    $action = $_POST['action'] ?? '';

    // Verify the session belongs to the logged-in mentee
    $stmt = $conn->prepare("SELECT * FROM sessions WHERE id = ? AND mentee_id = ?");
    $stmt->execute([$session_id, $_SESSION['mentee_id']]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        throw new Exception('Invalid session or unauthorized access.');
    }

    // Handle different actions
    switch ($action) {
        case 'cancel':
            // Only allow cancellation of pending or accepted sessions
            if ($session['status'] != 'pending' && $session['status'] != 'accepted') {
                throw new Exception('Cannot cancel this session.');
            }

            $stmt = $conn->prepare("UPDATE sessions SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$session_id]);

            // Add notification for mentor
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, title, message, created_at) 
                                  VALUES (?, 'mentor', 'Session Cancelled', 'A session has been cancelled by the mentee.', NOW())");
            $stmt->execute([$session['mentor_id']]);

            $response['success'] = true;
            $response['message'] = 'Session cancelled successfully.';
            break;

        case 'reschedule':
            // Validate new date and time
            if (!isset($_POST['new_date_time'])) {
                throw new Exception('New date and time are required.');
            }

            $new_date_time = $_POST['new_date_time'];
            
            // Update session
            $stmt = $conn->prepare("UPDATE sessions SET date_time = ?, status = 'pending' WHERE id = ?");
            $stmt->execute([$new_date_time, $session_id]);

            // Add notification for mentor
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, title, message, created_at) 
                                  VALUES (?, 'mentor', 'Session Rescheduled', 'A session has been rescheduled by the mentee.', NOW())");
            $stmt->execute([$session['mentor_id']]);

            $response['success'] = true;
            $response['message'] = 'Session rescheduled successfully.';
            break;

        default:
            throw new Exception('Invalid action.');
    }

    // Set session message for success
    $_SESSION['success'] = $response['message'];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    // Set session message for error
    $_SESSION['error'] = $response['message'];
}

// Redirect back to dashboard
header('Location: dashboard.php');
exit;
?> 