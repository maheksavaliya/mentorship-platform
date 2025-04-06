<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['mentee_id'])) {
    header("Location: ../login.php");
    exit();
}

try {
    // Mark all notifications as read for this mentee
    $stmt = $conn->prepare("UPDATE notifications 
                           SET is_read = 1 
                           WHERE user_id = ? 
                           AND user_type = 'mentee' 
                           AND is_read = 0");
    $stmt->execute([$_SESSION['mentee_id']]);
    
    $_SESSION['success'] = "All notifications marked as read.";
} catch(PDOException $e) {
    error_log("Error marking notifications as read: " . $e->getMessage());
    $_SESSION['error'] = "Failed to mark notifications as read.";
}

// Redirect back to dashboard
header("Location: dashboard.php");
exit(); 