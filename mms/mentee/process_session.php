<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is a mentee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentee') {
    header("Location: ../login.php");
    exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $mentor_id = filter_input(INPUT_POST, 'mentor_id', FILTER_SANITIZE_NUMBER_INT);
    $datetime = filter_input(INPUT_POST, 'datetime', FILTER_SANITIZE_STRING);
    $duration = filter_input(INPUT_POST, 'duration', FILTER_SANITIZE_NUMBER_INT);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $mentee_id = $_SESSION['user_id'];

    // Validate required fields
    if (!$mentor_id || !$datetime || !$duration || !$description) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: schedule_session.php");
        exit();
    }

    try {
        // Begin transaction
        $conn->beginTransaction();

        // Insert session
        $stmt = $conn->prepare("INSERT INTO sessions (mentor_id, mentee_id, session_date, duration, description, status, created_at) 
                               VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([$mentor_id, $mentee_id, $datetime, $duration, $description]);
        $session_id = $conn->lastInsertId();

        // Create notification for mentor
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message, related_id, created_at) 
                               VALUES (?, 'new_session_request', ?, ?, NOW())");
        
        // Get mentee name
        $mentee_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
        $mentee_stmt->execute([$mentee_id]);
        $mentee_name = $mentee_stmt->fetchColumn();

        // Format datetime for notification
        $formatted_date = date('F j, Y \a\t g:i A', strtotime($datetime));
        $notification_message = "$mentee_name has requested a session on $formatted_date";
        
        $stmt->execute([$mentor_id, $notification_message, $session_id]);

        // Commit transaction
        $conn->commit();

        // Set success message
        $_SESSION['success'] = "Session request sent successfully! You will be notified once the mentor accepts.";
        header("Location: my_sessions.php");
        exit();

    } catch(PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        error_log("Error: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred while scheduling the session. Please try again.";
        header("Location: schedule_session.php");
        exit();
    }
} else {
    // If not POST request, redirect to schedule page
    header("Location: schedule_session.php");
    exit();
} 