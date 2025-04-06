<?php
require_once 'config/db.php';

try {
    // Check mentee account
    $stmt = $conn->prepare("SELECT * FROM mentees WHERE email = ?");
    $stmt->execute(['maheksavaliya2@gmail.com']);
    $mentee = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($mentee) {
        echo "Found mentee account:\n";
        echo "ID: " . $mentee['id'] . "\n";
        echo "Name: " . $mentee['name'] . "\n";
        echo "Email: " . $mentee['email'] . "\n";
        
        // Check if password needs to be reset
        $stmt = $conn->prepare("UPDATE mentees SET password = ? WHERE id = ?");
        $new_password = password_hash('password123', PASSWORD_DEFAULT);
        $stmt->execute([$new_password, $mentee['id']]);
        
        echo "\nPassword has been reset to: password123\n";
        echo "Please try logging in with:\n";
        echo "Email: maheksavaliya2@gmail.com\n";
        echo "Password: password123\n";
        echo "Role: Mentee\n";
    } else {
        // Create new mentee account
        $stmt = $conn->prepare("INSERT INTO mentees (name, email, password) VALUES (?, ?, ?)");
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $stmt->execute(['Mahek Savaliya', 'maheksavaliya2@gmail.com', $password]);
        
        echo "Created new mentee account:\n";
        echo "Email: maheksavaliya2@gmail.com\n";
        echo "Password: password123\n";
        echo "Role: Mentee\n";
    }

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
} 