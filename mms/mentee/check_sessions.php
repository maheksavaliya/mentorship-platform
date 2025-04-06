<?php
require_once '../config/db.php';

try {
    // Check sessions data
    $stmt = $conn->query("SELECT * FROM sessions");
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Sessions in database:\n";
    print_r($sessions);

    // Check mentors data
    $stmt = $conn->query("SELECT * FROM mentors");
    $mentors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nMentors in database:\n";
    print_r($mentors);

    // Check mentees data
    $stmt = $conn->query("SELECT * FROM mentees");
    $mentees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nMentees in database:\n";
    print_r($mentees);

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 