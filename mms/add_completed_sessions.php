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
    
    // Past session dates
    $past_dates = [
        date('Y-m-d H:i:s', strtotime('-7 days')),
        date('Y-m-d H:i:s', strtotime('-14 days')),
        date('Y-m-d H:i:s', strtotime('-21 days'))
    ];
    
    // Session titles and descriptions
    $sessions = [
        [
            'title' => 'Introduction to Mobile Development',
            'description' => 'Initial session covering mobile development fundamentals and career path discussion'
        ],
        [
            'title' => 'Android Development Best Practices',
            'description' => 'Deep dive into Android development patterns and best practices'
        ],
        [
            'title' => 'UI/UX Design for Mobile Apps',
            'description' => 'Session focused on creating user-friendly mobile interfaces and experiences'
        ]
    ];
    
    // Add completed sessions
    $stmt = $conn->prepare("INSERT INTO sessions (mentor_id, mentee_id, title, description, date_time, duration, status, meet_link) 
                          VALUES (?, ?, ?, ?, ?, ?, 'completed', ?)");
    
    for ($i = 0; $i < 3; $i++) {
        $meet_link = "https://meet.google.com/" . substr(md5(uniqid(rand(), true)), 0, 10);
        
        $stmt->execute([
            $mentor['id'],
            $mentee['id'],
            $sessions[$i]['title'],
            $sessions[$i]['description'],
            $past_dates[$i],
            60, // 60 minutes duration
            $meet_link
        ]);
        
        $session_id = $conn->lastInsertId();
        echo "Created completed session with ID: $session_id\n";
    }

    echo "\nSuccessfully added 3 completed sessions.\n";
    echo "Please check your dashboard - you should now see updated session statistics.\n";

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
} 