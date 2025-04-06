<?php
require_once 'config/db.php';

try {
    // Check sessions table
    $stmt = $conn->query("DESCRIBE sessions");
    echo "Sessions Table Structure:\n";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    
    echo "\n\nNotifications Table Structure:\n";
    $stmt = $conn->query("DESCRIBE notifications");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 