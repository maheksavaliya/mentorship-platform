<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is a mentor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../login.php");
    exit();
}

// Set mentor_id from user_id for consistency
$_SESSION['mentor_id'] = $_SESSION['user_id'];

// Get mentee ID from URL
if (!isset($_GET['mentee_id'])) {
    header("Location: my_mentees.php");
    exit();
}

$mentee_id = $_GET['mentee_id'];

// Handle session scheduling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $datetime = $_POST['datetime'];
    $duration = $_POST['duration'];
    
    try {
        // Insert session into database
        $stmt = $conn->prepare("INSERT INTO sessions (mentor_id, mentee_id, title, description, date_time, duration, status) VALUES (?, ?, ?, ?, ?, ?, 'scheduled')");
        $stmt->execute([$_SESSION['mentor_id'], $mentee_id, $title, $description, $datetime, $duration]);
        
        // Create notification for mentee
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, title, message, type) VALUES (?, 'mentee', ?, ?, 'session_scheduled')");
        $notification_title = "New Session Scheduled";
        $notification_message = "A new session has been scheduled: " . $title;
        $stmt->execute([$mentee_id, $notification_title, $notification_message]);
        
        $success = "Session scheduled successfully!";
    } catch(PDOException $e) {
        error_log("Error scheduling session: " . $e->getMessage());
        $error = "Failed to schedule session. Please try again.";
    }
}

// Get mentee details
try {
    $stmt = $conn->prepare("SELECT name, email FROM mentees WHERE id = ?");
    $stmt->execute([$mentee_id]);
    $mentee = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching mentee: " . $e->getMessage());
    header("Location: my_mentees.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Session - MMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        :root {
            --primary-color: rgb(70, 97, 174);
            --secondary-color: #283593;
            --background-color: #f5f6fa;
            --card-bg: #ffffff;
            --text-color: #2c3e50;
            --border-radius: 20px;
            --transition: all 0.3s ease;
        }

        body {
            background: var(--background-color);
            font-family: 'Inter', sans-serif;
            color: var(--text-color);
            min-height: 100vh;
            margin: 0;
            padding: 2rem;
        }

        .schedule-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            max-width: 800px;
            margin: 2rem auto;
        }

        .card-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .form-control {
            border-radius: 15px;
            padding: 1rem;
            border: 2px solid rgba(0, 0, 0, 0.1);
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(70, 97, 174, 0.1);
        }

        .duration-options {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin: 1rem 0;
        }

        .duration-option {
            position: relative;
        }

        .duration-option input[type="radio"] {
            display: none;
        }

        .duration-option span {
            display: block;
            padding: 1rem;
            text-align: center;
            background: rgba(70, 97, 174, 0.1);
            border-radius: 15px;
            cursor: pointer;
            transition: var(--transition);
        }

        .duration-option input[type="radio"]:checked + span {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(70, 97, 174, 0.2);
        }

        .btn-schedule {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 1rem 2rem;
            font-weight: 600;
            transition: var(--transition);
            width: 100%;
            margin-top: 1rem;
        }

        .btn-schedule:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 1rem;
            transition: var(--transition);
        }

        .back-button:hover {
            transform: translateX(-5px);
            color: var(--secondary-color);
        }

        .alert {
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .mentee-info {
            background: rgba(70, 97, 174, 0.1);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
        }

        .mentee-info p {
            margin: 0;
            color: var(--primary-color);
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .duration-options {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="my_mentees.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Mentees
        </a>

        <div class="schedule-card">
            <div class="card-header">
                <h2>Schedule Session</h2>
            </div>

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

            <div class="mentee-info">
                <p><i class="fas fa-user me-2"></i>With: <?php echo htmlspecialchars($mentee['name'] ?? 'Mentee'); ?></p>
                <p><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($mentee['email'] ?? ''); ?></p>
            </div>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="title" class="form-label">Session Title</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Session Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                </div>

                <div class="mb-3">
                    <label for="datetime" class="form-label">Date & Time</label>
                    <input type="text" class="form-control" id="datetime" name="datetime" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Duration</label>
                    <div class="duration-options">
                        <label class="duration-option">
                            <input type="radio" name="duration" value="30" required>
                            <span>30 min</span>
                        </label>
                        <label class="duration-option">
                            <input type="radio" name="duration" value="60">
                            <span>1 hour</span>
                        </label>
                        <label class="duration-option">
                            <input type="radio" name="duration" value="90">
                            <span>1.5 hours</span>
                        </label>
                        <label class="duration-option">
                            <input type="radio" name="duration" value="120">
                            <span>2 hours</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-schedule">
                    <i class="fas fa-calendar-check me-2"></i>Schedule Session
                </button>
            </form>
        </div>
    </div>

    <script>
        // Initialize datetime picker
        flatpickr("#datetime", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
            time_24hr: true,
            minuteIncrement: 30
        });
    </script>
</body>
</html> 