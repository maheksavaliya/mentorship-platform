<?php
require_once 'config/db.php';

try {
    // Check if sessions table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'sessions'");
    $tableExists = $stmt->rowCount() > 0;
    
    echo "Sessions table exists: " . ($tableExists ? "Yes" : "No") . "\n\n";
    
    if ($tableExists) {
        // Check table structure
        $stmt = $conn->query("DESCRIBE sessions");
        echo "Table structure:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
        
        // Check session data
        echo "\nSession data:\n";
        $stmt = $conn->query("SELECT s.*, m.name as mentor_name, m.expertise 
                             FROM sessions s 
                             LEFT JOIN mentors m ON s.mentor_id = m.id");
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sessions as $session) {
            echo "\nSession ID: " . $session['id'] . "\n";
            echo "Mentor: " . $session['mentor_name'] . "\n";
            echo "Mentee ID: " . $session['mentee_id'] . "\n";
            echo "Status: " . $session['status'] . "\n";
            echo "Date: " . $session['date_time'] . "\n";
            echo "------------------------\n";
        }
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 