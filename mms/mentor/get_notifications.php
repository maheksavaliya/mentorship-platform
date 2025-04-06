<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['mentor_id'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

try {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE mentor_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['mentor_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($notifications);
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?> 