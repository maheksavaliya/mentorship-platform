<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['mentee_id'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

try {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE mentee_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['mentee_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($notifications);
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?> 