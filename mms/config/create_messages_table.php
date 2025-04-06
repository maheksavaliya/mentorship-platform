<?php
require_once 'db.php';

try {
    // Create messages table
    $sql = "CREATE TABLE IF NOT EXISTS messages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        sender_id INT NOT NULL,
        recipient_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id),
        FOREIGN KEY (recipient_id) REFERENCES users(id)
    )";
    
    $conn->exec($sql);
    echo "Messages table created successfully";
    
} catch(PDOException $e) {
    echo "Error creating messages table: " . $e->getMessage();
}
?> 