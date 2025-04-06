<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is a mentee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentee') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$mentor_id = $data['mentor_id'] ?? null;
$message = $data['message'] ?? null;

if (!$mentor_id || !$message) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

try {
    // Insert message
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, is_read, created_at) 
                           VALUES (?, ?, ?, 0, NOW())");
    $stmt->execute([$_SESSION['user_id'], $mentor_id, $message]);

    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    error_log("Error sending message: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
} 