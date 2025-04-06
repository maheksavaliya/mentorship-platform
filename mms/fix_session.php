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
    
    // Check if session exists
    $stmt = $conn->prepare("SELECT * FROM sessions WHERE mentee_id = ? AND mentor_id = ?");
    $stmt->execute([$mentee['id'], $mentor['id']]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        // Create new session
        $stmt = $conn->prepare("INSERT INTO sessions (mentor_id, mentee_id, title, description, date_time, duration, status, meet_link) 
                              VALUES (?, ?, ?, ?, ?, ?, 'accepted', ?)");
        
        $meet_link = "https://meet.google.com/" . substr(md5(uniqid(rand(), true)), 0, 10);
        $future_date = date('Y-m-d H:i:s', strtotime('+1 day'));
        
        $stmt->execute([
            $mentor['id'],
            $mentee['id'],
            'Mentoring Session with Manender Dutt',
            'One-on-one mentoring session to discuss career growth and technical skills',
            $future_date,
            60,
            $meet_link
        ]);
        
        $session_id = $conn->lastInsertId();
        echo "Created new session with ID: $session_id\n";

        // Create notification
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, title, message, type) 
                              VALUES (?, 'mentee', 'Session Confirmed', ?, 'session_accepted')");
        $message = "Your session with Manender Dutt has been confirmed. Check your dashboard for details.";
        $stmt->execute([$mentee['id'], $message]);
        
        echo "Created notification for mentee\n";
        
    } else {
        // Update existing session to accepted if not already
        if ($session['status'] !== 'accepted') {
            $stmt = $conn->prepare("UPDATE sessions SET status = 'accepted', 
                                  meet_link = ? WHERE id = ?");
            
            $meet_link = "https://meet.google.com/" . substr(md5(uniqid(rand(), true)), 0, 10);
            $stmt->execute([$meet_link, $session['id']]);
            
            echo "Existing session updated to accepted!\n";
        } else {
            echo "Session is already accepted!\n";
        }
    }

    // Create notification
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, title, message, type) 
                           VALUES (?, 'mentee', 'Session Confirmed', ?, 'session_accepted')");
    $message = "Your session with Manender Dutt has been confirmed. Check your dashboard for details.";
    $stmt->execute([$mentee['id'], $message]);

    echo "\nPlease check your dashboard now - the session should be visible.\n";

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
} 