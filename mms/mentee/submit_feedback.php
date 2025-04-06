<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is a mentee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentee') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $session_id = $_POST['session_id'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $comments = $_POST['comments'] ?? null;

    if (!$session_id || !$rating || !$comments) {
        $_SESSION['error'] = "Please fill in all fields.";
        header("Location: my_sessions.php");
        exit();
    }

    try {
        // Check if session belongs to this mentee
        $stmt = $conn->prepare("SELECT mentor_id FROM sessions WHERE id = ? AND mentee_id = ?");
        $stmt->execute([$session_id, $_SESSION['user_id']]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            $_SESSION['error'] = "Session not found.";
            header("Location: my_sessions.php");
            exit();
        }

        // Insert feedback
        $stmt = $conn->prepare("INSERT INTO session_feedback (session_id, rating, comments, created_at) 
                               VALUES (?, ?, ?, NOW())");
        $stmt->execute([$session_id, $rating, $comments]);

        // Update session status to include feedback
        $stmt = $conn->prepare("UPDATE sessions SET feedback_given = 1 WHERE id = ?");
        $stmt->execute([$session_id]);

        // Create notification for mentor
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, title, message, created_at) 
                               VALUES (?, 'mentor', 'New Feedback', 'A mentee has provided feedback for your session.', NOW())");
        $stmt->execute([$session['mentor_id']]);

        // Update mentor's average rating
        $stmt = $conn->prepare("UPDATE mentors SET 
                               rating = (SELECT AVG(rating) FROM session_feedback sf 
                                       JOIN sessions s ON sf.session_id = s.id 
                                       WHERE s.mentor_id = ?)
                               WHERE id = ?");
        $stmt->execute([$session['mentor_id'], $session['mentor_id']]);

        $_SESSION['success'] = "Thank you for your feedback!";
    } catch(PDOException $e) {
        error_log("Error submitting feedback: " . $e->getMessage());
        $_SESSION['error'] = "Failed to submit feedback. Please try again.";
    }

    header("Location: my_sessions.php");
    exit();
} 