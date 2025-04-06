<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Initialize variables
$mentor_id = null;
$messages = [];
$error = null;

try {
    // First create messages table if it doesn't exist
    $create_table_sql = "CREATE TABLE IF NOT EXISTS messages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        sender_id INT NOT NULL,
        recipient_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($create_table_sql);
    
    // For mentee, get mentor's ID
    $mentor_stmt = $conn->prepare("
        SELECT u.id, u.name
        FROM users u 
        JOIN mentors m ON u.id = m.user_id 
        WHERE u.name = 'Manendar Dutt'
    ");
    $mentor_stmt->execute();
    $mentor = $mentor_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mentor) {
        // Insert mentor if not exists
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute(['Manendar Dutt', 'manendar@example.com', password_hash('password123', PASSWORD_DEFAULT)]);
        $mentor_id = $conn->lastInsertId();
        $mentor_name = 'Manendar Dutt';
        
        // Insert into mentors table
        $stmt = $conn->prepare("INSERT INTO mentors (user_id, specialization, experience) VALUES (?, ?, ?)");
        $stmt->execute([$mentor_id, 'Software Development', '10 years']);
    } else {
        $mentor_id = $mentor['id'];
        $mentor_name = $mentor['name'];
    }

    // Clear existing messages for testing
    $stmt = $conn->prepare("DELETE FROM messages WHERE sender_id = ? OR recipient_id = ?");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);

    // Add new dummy conversation
    $dummy_conversation = [
        [
            'sender_id' => $mentor_id,
            'recipient_id' => $_SESSION['user_id'],
            'subject' => 'Project Discussion',
            'message' => 'Hi! I reviewed your latest project submission. The code structure looks good, but we should discuss some potential optimizations.',
            'sent_at' => '2024-04-02 10:00:00'
        ],
        [
            'sender_id' => $_SESSION['user_id'],
            'recipient_id' => $mentor_id,
            'subject' => 'Re: Project Discussion',
            'message' => 'Thank you for the feedback! I would love to learn about the optimizations. When would be a good time to discuss?',
            'sent_at' => '2024-04-02 10:05:00'
        ],
        [
            'sender_id' => $mentor_id,
            'recipient_id' => $_SESSION['user_id'],
            'subject' => 'Code Review Session',
            'message' => 'Let\'s schedule a code review session. I can walk you through some best practices and performance improvements.',
            'sent_at' => '2024-04-02 10:10:00'
        ],
        [
            'sender_id' => $_SESSION['user_id'],
            'recipient_id' => $mentor_id,
            'subject' => 'Re: Code Review Session',
            'message' => 'That would be great! I\'m available tomorrow afternoon or Friday morning. Which time works better for you?',
            'sent_at' => '2024-04-02 10:15:00'
        ],
        [
            'sender_id' => $mentor_id,
            'recipient_id' => $_SESSION['user_id'],
            'subject' => 'Session Confirmed',
            'message' => 'Let\'s meet tomorrow at 2 PM. I\'ll send you a meeting link shortly. Please prepare any specific questions you have about the code.',
            'sent_at' => '2024-04-02 10:20:00'
        ]
    ];

    foreach ($dummy_conversation as $msg) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, message, sent_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$msg['sender_id'], $msg['recipient_id'], $msg['subject'], $msg['message'], $msg['sent_at']]);
    }

    // Get conversation messages
    $stmt = $conn->prepare("
        SELECT m.*, 
               u.name as sender_name,
               CASE WHEN m.sender_id = ? THEN 'sent' ELSE 'received' END as message_type
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE (m.sender_id = ? AND m.recipient_id = ?) 
           OR (m.sender_id = ? AND m.recipient_id = ?)
        ORDER BY m.sent_at ASC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $mentor_id, $mentor_id, $_SESSION['user_id']]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Error in messages.php: " . $e->getMessage());
    $error = "An error occurred while loading messages.";
}

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify database connection
        if (!isset($conn) || $conn === null) {
            throw new Exception("Database connection error");
        }

        // Verify mentor_id is set
        if (!isset($mentor_id) || $mentor_id === null) {
            throw new Exception("Mentor not found");
        }

        // Validate input
        if (empty($_POST['subject']) || empty($_POST['new_message'])) {
            throw new Exception("Subject and message are required");
        }

        $subject = trim($_POST['subject']);
        $message = trim($_POST['new_message']);
        
        if (strlen($subject) < 1 || strlen($message) < 1) {
            throw new Exception("Subject and message cannot be empty");
        }

        // Insert message with error checking
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, message) VALUES (?, ?, ?, ?)");
        $success = $stmt->execute([$_SESSION['user_id'], $mentor_id, $subject, $message]);
        
        if (!$success) {
            throw new Exception("Failed to insert message into database");
        }

        // Redirect only if everything was successful
        header("Location: messages.php?success=1");
        exit();
    } catch(Exception $e) {
        error_log("Error sending message: " . $e->getMessage());
        $error = "Failed to send message: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - MMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .chat-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: calc(100vh - 100px);
            display: flex;
            flex-direction: column;
        }
        .chat-header {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            background: #4461ae;
            color: white;
            border-radius: 15px 15px 0 0;
        }
        .chat-messages {
            flex-grow: 1;
            overflow-y: auto;
            padding: 1rem;
        }
        .message {
            margin-bottom: 1rem;
            max-width: 70%;
        }
        .message.sent {
            margin-left: auto;
        }
        .message.received {
            margin-right: auto;
        }
        .message-content {
            padding: 0.75rem 1rem;
            border-radius: 15px;
            position: relative;
        }
        .sent .message-content {
            background: #4461ae;
            color: white;
            border-bottom-right-radius: 5px;
        }
        .received .message-content {
            background: #e9ecef;
            color: #212529;
            border-bottom-left-radius: 5px;
        }
        .message-meta {
            font-size: 0.75rem;
            margin-top: 0.25rem;
            color: #6c757d;
        }
        .chat-input {
            padding: 1rem;
            border-top: 1px solid #dee2e6;
            background: white;
            border-radius: 0 0 15px 15px;
        }
        .message-time {
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        .sent .message-time {
            color: rgba(255,255,255,0.8);
        }
        .received .message-time {
            color: #6c757d;
        }
        .message-subject {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .success-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success success-message" role="alert">
                Message sent successfully!
            </div>
        <?php endif; ?>

        <div class="chat-container">
            <div class="chat-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">Chat with Mentor</h4>
                    </div>
                    <a href="dashboard.php" class="btn btn-outline-light btn-sm">Back to Dashboard</a>
                </div>
            </div>

            <div class="chat-messages" id="chat-messages">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message <?php echo $message['message_type']; ?>">
                            <div class="message-content">
                                <?php if ($message['subject']): ?>
                                    <div class="message-subject"><?php echo htmlspecialchars($message['subject']); ?></div>
                                <?php endif; ?>
                                <div><?php echo nl2br(htmlspecialchars($message['message'])); ?></div>
                                <div class="message-time">
                                    <?php echo date('M d, g:i A', strtotime($message['sent_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="chat-input">
                <form method="POST" action="" class="mb-0">
                    <div class="row g-2">
                        <div class="col-md-2">
                            <input type="text" class="form-control" name="subject" placeholder="Subject" required>
                        </div>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="new_message" placeholder="Type your message..." required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Send</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Scroll to bottom of chat
        const chatMessages = document.getElementById('chat-messages');
        chatMessages.scrollTop = chatMessages.scrollHeight;

        // Auto-hide success message
        const successMessage = document.querySelector('.success-message');
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html> 