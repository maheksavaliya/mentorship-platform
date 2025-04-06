<?php
session_start();
require_once 'config/db.php';

header('Content-Type: text/plain');

try {
    // Get Mahek's mentee ID using email
    $stmt = $conn->prepare("SELECT * FROM mentees WHERE email = ?");
    $stmt->execute(['maheksavaliya2@gmail.com']);
    $mentee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mentee) {
        echo "Mentee not found with email maheksavaliya2@gmail.com\n";
        // Try finding by name
        $stmt = $conn->prepare("SELECT * FROM mentees WHERE name LIKE ?");
        $stmt->execute(['%Mahek%']);
        $mentee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$mentee) {
            die("Could not find mentee account. Please make sure you're registered.\n");
        }
    }

    $mentee_id = $mentee['id'];
    echo "Found existing mentee with ID: $mentee_id\n";
    echo "Mentee email: {$mentee['email']}\n";

    // Get Manender's mentor ID
    $stmt = $conn->prepare("SELECT * FROM mentors WHERE name LIKE ?");
    $stmt->execute(['%Manender%']);
    $mentor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mentor) {
        // Create mentor if not exists
        $stmt = $conn->prepare("INSERT INTO mentors (name, email, password, expertise, experience_years, bio) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'Manender Dutt',
            'manender@example.com',
            password_hash('password123', PASSWORD_DEFAULT),
            'Full Stack Development',
            5,
            'Experienced full stack developer with expertise in web technologies.'
        ]);
        $mentor_id = $conn->lastInsertId();
        echo "Created new mentor with ID: $mentor_id\n";
    } else {
        $mentor_id = $mentor['id'];
        echo "Found existing mentor with ID: $mentor_id\n";
    }

    // Check if session exists
    $stmt = $conn->prepare("SELECT * FROM sessions WHERE mentee_id = ? AND mentor_id = ?");
    $stmt->execute([$mentee_id, $mentor_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        // Create new session
        $stmt = $conn->prepare("INSERT INTO sessions (mentor_id, mentee_id, title, description, date_time, duration, status, meet_link) 
                              VALUES (?, ?, ?, ?, ?, ?, 'accepted', ?)");
        
        $meet_link = "https://meet.google.com/" . substr(md5(uniqid(rand(), true)), 0, 10);
        $future_date = date('Y-m-d H:i:s', strtotime('+1 day'));
        
        $stmt->execute([
            $mentor_id,
            $mentee_id,
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
        $stmt->execute([$mentee_id, $message]);
        
        echo "Created notification for mentee\n";
    } else {
        echo "Found existing session with ID: {$session['id']}\n";
        
        // Update session to accepted status with new date
        $stmt = $conn->prepare("UPDATE sessions SET 
                              status = 'accepted',
                              date_time = ?, 
                              meet_link = ? 
                              WHERE id = ?");
        
        $future_date = date('Y-m-d H:i:s', strtotime('+1 day'));
        $meet_link = "https://meet.google.com/" . substr(md5(uniqid(rand(), true)), 0, 10);
        
        $stmt->execute([$future_date, $meet_link, $session['id']]);
        echo "Updated session with new date and meet link\n";
    }

    echo "\nAll done! Please try logging in with your email: {$mentee['email']}\n";
    echo "If you're having trouble logging in, please check these details:\n";
    echo "1. Email: {$mentee['email']}\n";
    echo "2. Role: Select 'Mentee' from the dropdown\n";
    echo "3. Use your registered password\n";

} catch(PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
} 