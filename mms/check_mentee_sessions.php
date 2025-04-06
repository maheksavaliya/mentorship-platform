<?php
require_once 'config/db.php';

try {
    // Get mentee ID for Mahek
    $stmt = $conn->prepare("SELECT id FROM mentees WHERE name LIKE ?");
    $stmt->execute(['%Mahek%']);
    $mentee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mentee) {
        die("Mentee not found\n");
    }

    echo "Checking all sessions for mentee ID: " . $mentee['id'] . "\n\n";

    // Get ALL sessions for this mentee
    $stmt = $conn->prepare("SELECT s.*, m.name as mentor_name 
                           FROM sessions s 
                           JOIN mentors m ON s.mentor_id = m.id 
                           WHERE s.mentee_id = ? 
                           ORDER BY s.date_time DESC");
    $stmt->execute([$mentee['id']]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($sessions)) {
        echo "No sessions found for this mentee.\n";
    } else {
        echo "Found " . count($sessions) . " total sessions:\n\n";
        foreach ($sessions as $session) {
            echo "Session ID: " . $session['id'] . "\n";
            echo "Title: " . $session['title'] . "\n";
            echo "Mentor: " . $session['mentor_name'] . "\n";
            echo "Status: " . $session['status'] . "\n";
            echo "Date/Time: " . $session['date_time'] . "\n";
            echo "Meet Link: " . ($session['meet_link'] ?? 'Not set') . "\n";
            echo str_repeat('-', 50) . "\n";
        }

        // Now check specifically for accepted upcoming sessions
        $stmt = $conn->prepare("SELECT COUNT(*) as count 
                              FROM sessions 
                              WHERE mentee_id = ? 
                              AND status = 'accepted' 
                              AND date_time >= CURRENT_TIMESTAMP");
        $stmt->execute([$mentee['id']]);
        $upcoming = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "\nNumber of accepted upcoming sessions: " . $upcoming['count'] . "\n";
    }

} catch(PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
} 