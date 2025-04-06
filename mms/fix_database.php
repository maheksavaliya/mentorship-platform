<?php
require_once 'config/db.php';

try {
    // First, check if sessions table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'sessions'");
    $sessions_table_exists = $stmt->rowCount() > 0;

    if (!$sessions_table_exists) {
        echo "Creating sessions table...\n";
        
        $sessions_table = "CREATE TABLE sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            mentor_id INT NOT NULL,
            mentee_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            date_time DATETIME NOT NULL,
            duration INT NOT NULL,
            status ENUM('pending', 'accepted', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
            meet_link VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (mentor_id) REFERENCES mentors(id),
            FOREIGN KEY (mentee_id) REFERENCES mentees(id)
        )";
        
        $conn->exec($sessions_table);
        echo "Sessions table created successfully!\n";
    } else {
        echo "Sessions table exists, checking structure...\n";
        
        // Check if status column has correct ENUM values
        $stmt = $conn->query("SHOW COLUMNS FROM sessions LIKE 'status'");
        $status_column = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($status_column) {
            echo "Current status column type: " . $status_column['Type'] . "\n";
            
            // If status is not ENUM or doesn't have all required values, modify it
            if (strpos($status_column['Type'], "enum('pending','accepted','rejected','completed','cancelled')") === false) {
                echo "Modifying status column...\n";
                $conn->exec("ALTER TABLE sessions MODIFY COLUMN status 
                           ENUM('pending', 'accepted', 'rejected', 'completed', 'cancelled') 
                           DEFAULT 'pending'");
                echo "Status column modified successfully!\n";
            }
        }
        
        // Check if meet_link column exists
        $stmt = $conn->query("SHOW COLUMNS FROM sessions LIKE 'meet_link'");
        if ($stmt->rowCount() === 0) {
            echo "Adding meet_link column...\n";
            $conn->exec("ALTER TABLE sessions ADD COLUMN meet_link VARCHAR(255)");
            echo "Meet link column added successfully!\n";
        }
        
        // Check if updated_at column exists
        $stmt = $conn->query("SHOW COLUMNS FROM sessions LIKE 'updated_at'");
        if ($stmt->rowCount() === 0) {
            echo "Adding updated_at column...\n";
            $conn->exec("ALTER TABLE sessions ADD COLUMN 
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            echo "Updated_at column added successfully!\n";
        }
    }
    
    // Now check if there are any sessions with invalid status
    $stmt = $conn->query("SELECT * FROM sessions WHERE status NOT IN 
                         ('pending', 'accepted', 'rejected', 'completed', 'cancelled')");
    $invalid_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($invalid_sessions)) {
        echo "\nFound " . count($invalid_sessions) . " sessions with invalid status. Fixing...\n";
        
        $stmt = $conn->prepare("UPDATE sessions SET status = 'pending' WHERE id = ?");
        foreach ($invalid_sessions as $session) {
            $stmt->execute([$session['id']]);
            echo "Fixed session ID " . $session['id'] . "\n";
        }
    }
    
    // Check for any sessions that should be marked as completed (past date)
    $stmt = $conn->prepare("UPDATE sessions 
                           SET status = 'completed' 
                           WHERE status = 'accepted' 
                           AND date_time < CURRENT_TIMESTAMP");
    $stmt->execute();
    $completed_count = $stmt->rowCount();
    
    if ($completed_count > 0) {
        echo "\nMarked $completed_count past sessions as completed\n";
    }
    
    echo "\nDatabase structure verification and fixes completed successfully!\n";
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
} 