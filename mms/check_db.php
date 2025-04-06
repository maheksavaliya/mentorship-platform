<?php
require_once 'config/db.php';

try {
    // Check sessions table structure
    $stmt = $conn->query("SHOW COLUMNS FROM sessions");
    echo "Sessions table structure:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    
    // Check session data
    $stmt = $conn->query("SELECT s.*, m.name as mentor_name 
                         FROM sessions s 
                         INNER JOIN mentors m ON s.mentor_id = m.id 
                         ORDER BY s.date_time DESC");
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nSession data:\n";
    foreach ($sessions as $session) {
        echo "ID: " . $session['id'] . "\n";
        echo "Title: " . $session['title'] . "\n";
        echo "Mentor: " . $session['mentor_name'] . "\n";
        echo "Status: " . $session['status'] . "\n";
        echo "Date: " . $session['date_time'] . "\n";
        echo "------------------------\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 