<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get all messages for this mentor
try {
    $stmt = $conn->prepare("
        SELECT m.*, u.name as sender_name, u.profile_image 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE (m.sender_id = ? OR m.receiver_id = ?)
        ORDER BY m.sent_at DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching messages: " . $e->getMessage());
    $error = "Failed to load messages.";
}

// Add dummy messages if none exist
if (empty($messages)) {
    $messages = [
        [
            'sender_id' => $_SESSION['user_id'],
            'sender_name' => 'You',
            'subject' => 'Welcome Message',
            'message' => 'Hello! Welcome to our mentorship program. I\'m excited to work with you and help you achieve your goals.',
            'sent_at' => '2024-04-02 10:00:00',
            'sender_role' => 'mentor'
        ],
        [
            'sender_id' => 2, // mentee
            'sender_name' => 'Rahul Kumar',
            'subject' => 'Re: Welcome Message',
            'message' => 'Thank you for the warm welcome! I\'m looking forward to learning from your experience.',
            'sent_at' => '2024-04-02 10:05:00',
            'sender_role' => 'mentee'
        ],
        [
            'sender_id' => $_SESSION['user_id'],
            'sender_name' => 'You',
            'subject' => 'Next Session',
            'message' => 'Would you like to schedule our next session? I\'m available next week on Tuesday or Thursday.',
            'sent_at' => '2024-04-02 10:10:00',
            'sender_role' => 'mentor'
        ],
        [
            'sender_id' => 2,
            'sender_name' => 'Rahul Kumar',
            'subject' => 'Re: Next Session',
            'message' => 'Tuesday would work great for me! What time works best for you?',
            'sent_at' => '2024-04-02 10:15:00',
            'sender_role' => 'mentee'
        ]
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - MMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --mentor-message-bg: #4461ae;
            --mentee-message-bg: #6c757d;
            --message-text: #fff;
            --chat-bg: #f8f9fa;
        }

        body {
            background-color: var(--chat-bg);
            font-family: 'Inter', sans-serif;
        }

        .chat-container {
            max-width: 1000px;
            margin: 2rem auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .chat-header {
            background: #4461ae;
            color: white;
            padding: 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .chat-messages {
            padding: 2rem;
            max-height: 600px;
            overflow-y: auto;
        }

        .message {
            margin-bottom: 1.5rem;
            max-width: 80%;
        }

        .message.mentor {
            margin-right: auto;
        }

        .message.mentee {
            margin-left: auto;
        }

        .message-content {
            padding: 1rem;
            border-radius: 15px;
            color: var(--message-text);
            position: relative;
        }

        .mentor .message-content {
            background: var(--mentor-message-bg);
            border-bottom-left-radius: 5px;
        }

        .mentee .message-content {
            background: var(--mentee-message-bg);
            border-bottom-right-radius: 5px;
        }

        .message-header {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .message-subject {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .message-time {
            font-size: 0.8rem;
            opacity: 0.8;
            margin-top: 0.5rem;
        }

        .back-button {
            text-decoration: none;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-button:hover {
            color: rgba(255,255,255,0.8);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #4461ae;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <div class="d-flex justify-content-between align-items-center">
                <a href="dashboard.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
                <h4 class="mb-0">Messages</h4>
                <div style="width: 24px;"></div>
            </div>
        </div>

        <div class="chat-messages">
            <?php if (empty($messages)): ?>
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h4>No Messages Yet</h4>
                    <p>Start a conversation with your mentees!</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo $message['sender_role']; ?>">
                        <div class="message-content">
                            <div class="message-header">
                                <strong><?php echo htmlspecialchars($message['sender_name']); ?></strong>
                            </div>
                            <?php if ($message['subject']): ?>
                                <div class="message-subject">
                                    <?php echo htmlspecialchars($message['subject']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="message-body">
                                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                            </div>
                            <div class="message-time">
                                <?php echo date('M j, Y g:i A', strtotime($message['sent_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 