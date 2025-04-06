<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/db.php';

// Debug information
error_log("Accessed send_message.php");
error_log("Session data: " . print_r($_SESSION, true));
error_log("GET data: " . print_r($_GET, true));

// Check if user is logged in and is a mentor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    error_log("User not logged in or not a mentor");
    header("Location: ../login.php");
    exit();
}

// Get mentee ID from URL
if (!isset($_GET['mentee_id'])) {
    error_log("No mentee_id provided");
    header("Location: my_mentees.php");
    exit();
}

$mentee_id = $_GET['mentee_id'];
error_log("Mentee ID: " . $mentee_id);

// Get mentee details
try {
    $stmt = $conn->prepare("SELECT m.*, u.name, u.email 
                           FROM mentees m 
                           JOIN users u ON m.user_id = u.id 
                           WHERE m.id = ?");
    $stmt->execute([$mentee_id]);
    $mentee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mentee) {
        error_log("Mentee not found for ID: " . $mentee_id);
        header("Location: my_mentees.php");
        exit();
    }
    
    error_log("Mentee data: " . print_r($mentee, true));
} catch(Exception $e) {
    error_log("Database error: " . $e->getMessage());
    header("Location: my_mentees.php");
    exit();
}

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Form submitted");
    $message = $_POST['message'];
    $subject = $_POST['subject'];
    
    try {
        // Insert message into database
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message, sender_role, sent_at) VALUES (?, ?, ?, ?, 'mentor', NOW())");
        $stmt->execute([$_SESSION['user_id'], $mentee_id, $subject, $message]);
        
        // Create notification
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, title, message, type) VALUES (?, 'mentee', ?, ?, 'message')");
        $notification_title = "New Message from Mentor";
        $notification_message = "You have received a new message: " . substr($message, 0, 100) . "...";
        $stmt->execute([$mentee_id, $notification_title, $notification_message]);
        
        $success = "Message sent successfully!";
        error_log("Message sent successfully");
    } catch(Exception $e) {
        error_log("Error sending message: " . $e->getMessage());
        $error = "Failed to send message. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Message - MMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Inter', sans-serif;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .message-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-top: 20px;
        }
        .btn-primary {
            background-color: #4461ae;
            border-color: #4461ae;
        }
        .btn-primary:hover {
            background-color: #34498d;
            border-color: #34498d;
        }
    </style>
</head>
<body>
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="my_mentees.php">My Mentees</a></li>
                <li class="breadcrumb-item active">Send Message</li>
            </ol>
        </nav>

        <div class="message-card">
            <h2 class="mb-4">Send Message to <?php echo htmlspecialchars($mentee['name']); ?></h2>

            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="subject" class="form-label">Subject</label>
                    <input type="text" class="form-control" id="subject" name="subject" required>
                </div>

                <div class="mb-3">
                    <label for="message" class="form-label">Message</label>
                    <textarea class="form-control" id="message" name="message" rows="6" required></textarea>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Send Message
                    </button>
                    <a href="my_mentees.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Mentees
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 