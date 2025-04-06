<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is a mentee
if (!isset($_SESSION['mentee_id'])) {
    header("Location: login");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $mentor_id = $_POST['mentor_id'];
        $session_date = $_POST['session_date'];
        $session_time = $_POST['session_time'];
        $duration = $_POST['duration'];
        $purpose = $_POST['purpose'];

        // Combine date and time
        $date_time = date('Y-m-d H:i:s', strtotime("$session_date $session_time"));

        // Insert session into database
        $stmt = $conn->prepare("INSERT INTO sessions (mentor_id, mentee_id, title, description, date_time, duration) 
                              VALUES (?, ?, 'Mentoring Session', ?, ?, ?)");
        
        $stmt->execute([
            $mentor_id,
            $_SESSION['mentee_id'],
            $purpose,
            $date_time,
            $duration
        ]);

        // Get mentor's email for notification
        $stmt = $conn->prepare("SELECT email, name FROM mentors WHERE id = ?");
        $stmt->execute([$mentor_id]);
        $mentor = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get mentee's name
        $stmt = $conn->prepare("SELECT name FROM mentees WHERE id = ?");
        $stmt->execute([$_SESSION['mentee_id']]);
        $mentee = $stmt->fetch(PDO::FETCH_ASSOC);

        // Send email notification to mentor (you'll need to implement your email sending logic)
        $to = $mentor['email'];
        $subject = "New Session Request";
        $message = "Hello " . $mentor['name'] . ",\n\n";
        $message .= $mentee['name'] . " has requested a mentoring session with you.\n";
        $message .= "Date: " . date('F j, Y', strtotime($session_date)) . "\n";
        $message .= "Time: " . date('g:i A', strtotime($session_time)) . "\n";
        $message .= "Duration: " . $duration . " minutes\n";
        $message .= "Purpose: " . $purpose . "\n\n";
        $message .= "Please log in to your dashboard to accept or decline this request.";

        // mail($to, $subject, $message); // Uncomment this line when you have email configured

        $_SESSION['success_message'] = "Session request sent successfully! You will be notified when the mentor responds.";
        header("Location: mentor_profile.php?id=" . $mentor_id);
        exit();

    } catch(PDOException $e) {
        error_log("Error booking session: " . $e->getMessage());
        $_SESSION['error_message'] = "An error occurred while booking the session. Please try again.";
        header("Location: mentor_profile.php?id=" . $mentor_id);
        exit();
    }
} else {
    header("Location: find_mentor.php");
    exit();
}
?> 