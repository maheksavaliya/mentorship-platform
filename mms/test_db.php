<?php
require_once 'config/db.php';

try {
    // Test database connection
    echo "Testing database connection...\n";
    $conn->query("SELECT 1");
    echo "Database connection successful!\n\n";
    
    // Check if sessions table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'sessions'");
    if ($stmt->rowCount() > 0) {
        echo "Sessions table exists.\n\n";
        
        // Check table structure
        $stmt = $conn->query("DESCRIBE sessions");
        echo "Sessions table structure:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
        
        // Check if mentors table exists
        $stmt = $conn->query("SHOW TABLES LIKE 'mentors'");
        if ($stmt->rowCount() > 0) {
            echo "\nMentors table exists.\n";
        } else {
            echo "\nMentors table does not exist!\n";
        }
        
        // Check if there are any sessions in the database
        $stmt = $conn->query("SELECT COUNT(*) as count FROM sessions");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "\nTotal number of sessions: " . $result['count'] . "\n";
        
        // Check for any sessions without valid mentor IDs
        $stmt = $conn->query("SELECT s.* FROM sessions s LEFT JOIN mentors m ON s.mentor_id = m.id WHERE m.id IS NULL");
        $invalid_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($invalid_sessions)) {
            echo "\nWarning: Found sessions with invalid mentor IDs:\n";
            foreach ($invalid_sessions as $session) {
                echo "Session ID: " . $session['id'] . ", Mentor ID: " . $session['mentor_id'] . "\n";
            }
        }
    } else {
        echo "Sessions table does not exist!\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 