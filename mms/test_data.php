<?php
require_once 'config/db.php';

try {
    // First, insert a test mentor
    $stmt = $conn->prepare("INSERT INTO mentors (name, expertise, rating) VALUES (?, ?, ?)");
    $stmt->execute(['Test Mentor', 'Web Development', 4.5]);
    $mentor_id = $conn->lastInsertId();
    
    // Then, insert a test mentee
    $stmt = $conn->prepare("INSERT INTO mentees (name) VALUES (?)");
    $stmt->execute(['Test Mentee']);
    $mentee_id = $conn->lastInsertId();
    
    // Finally, insert a test session
    $stmt = $conn->prepare("
        INSERT INTO sessions (
            mentor_id, 
            mentee_id, 
            title, 
            description, 
            date_time, 
            duration, 
            status,
            meet_link
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $mentor_id,
        $mentee_id,
        'Test Session',
        'This is a test mentoring session',
        date('Y-m-d H:i:s', strtotime('+1 day')), // tomorrow
        60, // 60 minutes
        'accepted',
        'https://meet.google.com/test-link'
    ]);
    
    echo "Test data inserted successfully!";
    
    // Debug: Check if session was inserted correctly
    $stmt = $conn->prepare("SELECT * FROM sessions WHERE mentor_id = ? AND mentee_id = ?");
    $stmt->execute([$mentor_id, $mentee_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\n\nInserted session details:\n";
    print_r($session);
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 