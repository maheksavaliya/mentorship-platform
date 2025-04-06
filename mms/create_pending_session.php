<?php
require_once 'config/db.php';

try {
    // Get mentee details
    $stmt = $conn->prepare("SELECT * FROM mentees WHERE name LIKE ?");
    $stmt->execute(['%Mahek%']);
    $mentee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mentee) {
        die("Mentee not found");
    }
    
    // Get mentor details (Manender Dutt)
    $stmt = $conn->prepare("SELECT * FROM mentors WHERE name LIKE ?");
    $stmt->execute(['%Manender%']);
    $mentor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mentor) {
        die("Mentor not found");
    }
    
    // Create pending session
    $stmt = $conn->prepare("INSERT INTO sessions (mentor_id, mentee_id, title, description, date_time, duration, status) 
                          VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    
    $future_date = date('Y-m-d H:i:s', strtotime('+3 days')); // Set for 3 days ahead
    
    $stmt->execute([
        $mentor['id'],
        $mentee['id'],
        'Career Guidance Session',
        'Discussion about career growth in mobile development and future opportunities',
        $future_date,
        60 // 60 minutes duration
    ]);
    
    $session_id = $conn->lastInsertId();
    echo "Created pending session with ID: $session_id\n";

    // Create notification for mentor
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, title, message, type) 
                          VALUES (?, 'mentor', 'New Session Request', ?, 'session_request')");
    $message = "You have a new session request from Mahek Savaliya. Please review and respond.";
    $stmt->execute([$mentor['id'], $message]);
    
    echo "Created notification for mentor\n";
    echo "\nPlease check your My Sessions page - you should see the pending session in the Pending tab.\n";

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
} 