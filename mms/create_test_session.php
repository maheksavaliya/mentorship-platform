<?php
require_once 'config/db.php';

try {
    // Get the first mentor
    $stmt = $conn->query("SELECT * FROM mentors LIMIT 1");
    $mentor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mentor) {
        echo "No mentors found in database.\n";
        exit();
    }
    
    // Get the first mentee
    $stmt = $conn->query("SELECT * FROM mentees LIMIT 1");
    $mentee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mentee) {
        echo "No mentees found in database.\n";
        exit();
    }
    
    echo "Creating test session...\n";
    echo "Mentor: " . $mentor['name'] . " (ID: " . $mentor['id'] . ")\n";
    echo "Mentee: " . $mentee['name'] . " (ID: " . $mentee['id'] . ")\n";
    
    // Create a test session
    $stmt = $conn->prepare("INSERT INTO sessions (
        mentor_id,
        mentee_id,
        title,
        description,
        date_time,
        duration,
        status,
        meet_link
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $mentor['id'],
        $mentee['id'],
        'Test Mentoring Session',
        'This is a test session for debugging purposes',
        date('Y-m-d H:i:s', strtotime('+1 day')),
        60,
        'pending',
        'https://meet.google.com/test-link'
    ]);
    
    $session_id = $conn->lastInsertId();
    echo "Test session created with ID: " . $session_id . "\n";
    
    // Create a notification for the mentee
    $stmt = $conn->prepare("INSERT INTO notifications (
        user_id,
        user_type,
        title,
        message,
        type,
        related_id
    ) VALUES (?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $mentee['id'],
        'mentee',
        'New Session Request',
        'You have a new session request from ' . $mentor['name'],
        'session_request',
        $session_id
    ]);
    
    echo "Notification created for mentee.\n";
    echo "Done! Please check your My Sessions page.\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 