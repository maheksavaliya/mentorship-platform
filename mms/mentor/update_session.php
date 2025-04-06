<?php
session_start();
require_once '../config/db.php';

// Ensure tables exist with correct structure
try {
    // Check if sessions table exists and create if it doesn't
    $conn->query("CREATE TABLE IF NOT EXISTS sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mentee_id INT NOT NULL,
        mentor_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        date_time DATETIME NOT NULL,
        duration INT NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        meet_link VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Add title column if it doesn't exist
    try {
        $conn->query("ALTER TABLE sessions ADD COLUMN title VARCHAR(255) NOT NULL AFTER mentor_id");
    } catch(PDOException $e) {
        // Column might already exist, ignore the error
        error_log("Note: title column might already exist: " . $e->getMessage());
    }

    // Check if notifications table exists and create if it doesn't
    $conn->query("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_type VARCHAR(10) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(50) NOT NULL,
        related_id INT,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

} catch (PDOException $e) {
    error_log("Table creation error: " . $e->getMessage());
}

// Debug logging at start
error_log("Starting update_session.php");
error_log("POST data: " . print_r($_POST, true));
error_log("SESSION data: " . print_r($_SESSION, true));

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['session_id']) || !isset($_POST['action'])) {
    $_SESSION['error'] = "Invalid request";
    header("Location: my_sessions.php");
    exit();
}

$session_id = $_POST['session_id'];
$action = $_POST['action'];

try {
    // First verify that this session belongs to the logged-in mentor
    $stmt = $conn->prepare("SELECT s.*, COALESCE(me.name, 'Deleted User') as mentee_name 
                           FROM sessions s 
                           LEFT JOIN mentees me ON s.mentee_id = me.id 
                           WHERE s.id = ? AND s.mentor_id = ? AND s.status = 'pending'");
    $stmt->execute([$session_id, $_SESSION['user_id']]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        $_SESSION['error'] = "Session not found or already processed";
        header("Location: my_sessions.php");
        exit();
    }

    error_log("Processing session update - Session data: " . print_r($session, true));

    if ($action === 'accept') {
        // Start transaction
        $conn->beginTransaction();
        
        try {
            // Generate a meet link
            $meet_link = 'https://meet.google.com/' . substr(md5(uniqid()), 0, 10);
            
            // Update session status and meet link
            $stmt = $conn->prepare("UPDATE sessions SET 
                status = 'accepted',
                meet_link = ?,
                updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?");
            $stmt->execute([$meet_link, $session_id]);

            // Create notification for mentee
            $stmt = $conn->prepare("INSERT INTO notifications (
                user_id, 
                user_type, 
                title, 
                message, 
                type, 
                related_id,
                created_at
            ) VALUES (?, 'mentee', 'Session Accepted', ?, 'session_accepted', ?, CURRENT_TIMESTAMP)");
            
            $message = sprintf(
                "Your session '%s' has been accepted by %s. The session is scheduled for %s. Click to view details.", 
                $session['title'],
                $_SESSION['name'],
                date('F j, Y \a\t g:i A', strtotime($session['date_time']))
            );
            
            $stmt->execute([$session['mentee_id'], $message, $session_id]);

            // Commit transaction
            $conn->commit();
            
            $_SESSION['success'] = "Session accepted successfully";
            unset($_SESSION['mentee_id']); // Clean up any mentee_id in session
            unset($_SESSION['error']); // Clean up any error messages
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollBack();
            error_log("Error in accept session: " . $e->getMessage());
            $_SESSION['error'] = "Error accepting session. Please try again.";
        }
    } else if ($action === 'reject') {
        // Update session status to rejected
        $stmt = $conn->prepare("UPDATE sessions SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$session_id]);

        // Create notification for mentee
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, title, message, type) 
                              VALUES (?, 'mentee', 'Session Rejected', ?, 'session_rejected')");
        $message = "Your session request could not be accommodated at this time.";
        $stmt->execute([$session['mentee_id'], $message]);

        $_SESSION['success'] = "Session rejected successfully";
    }

    header("Location: my_sessions.php");
    exit();

} catch(PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header("Location: my_sessions.php");
    exit();
}
?> 