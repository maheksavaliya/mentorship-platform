<?php
require_once 'config/db.php';

try {
    $name = "Test Mentee";
    $email = "test@test.com";
    $password = password_hash("test123", PASSWORD_DEFAULT);

    $sql = "INSERT INTO mentees (name, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$name, $email, $password]);

    echo "Test user created successfully!";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 