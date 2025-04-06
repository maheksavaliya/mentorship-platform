<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is a mentor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../login.php");
    exit();
}

$success = $error = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        try {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM mentors WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $mentor = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($mentor && password_verify($current_password, $mentor['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE mentors SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                $success = "Password updated successfully!";
            } else {
                $error = "Current password is incorrect.";
            }
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            $error = "Failed to update password. Please try again.";
        }
    }
}

// Handle notification settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_notifications') {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $session_reminders = isset($_POST['session_reminders']) ? 1 : 0;
    $message_alerts = isset($_POST['message_alerts']) ? 1 : 0;

    try {
        $stmt = $conn->prepare("UPDATE mentors SET 
            email_notifications = ?,
            session_reminders = ?,
            message_alerts = ?
            WHERE id = ?");
        
        $stmt->execute([
            $email_notifications,
            $session_reminders,
            $message_alerts,
            $_SESSION['user_id']
        ]);
        
        $success = "Notification settings updated successfully!";
    } catch(PDOException $e) {
        error_log("Error: " . $e->getMessage());
        $error = "Failed to update notification settings.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Mentor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            --background-color: #f3f4f6;
        }

        body {
            background-color: var(--background-color);
            font-family: 'Inter', sans-serif;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.1);
            margin-bottom: 30px;
        }

        .card-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: 20px 20px 0 0 !important;
            padding: 20px;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #e5e7eb;
        }

        .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 500;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.2);
        }

        .back-link {
            color: #4b5563;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .back-link:hover {
            color: #6366f1;
        }

        .form-check-input:checked {
            background-color: #6366f1;
            border-color: #6366f1;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Change Password -->
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-lock me-2"></i>Change Password</h4>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Password
                    </button>
                </form>
            </div>
        </div>

        <!-- Notification Settings -->
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-bell me-2"></i>Notification Settings</h4>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="update_notifications">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="email_notifications" id="emailNotifications" checked>
                            <label class="form-check-label" for="emailNotifications">
                                Email Notifications
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1">Receive email notifications for new session requests and messages</small>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="session_reminders" id="sessionReminders" checked>
                            <label class="form-check-label" for="sessionReminders">
                                Session Reminders
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1">Get reminders before your scheduled sessions</small>
                    </div>
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="message_alerts" id="messageAlerts" checked>
                            <label class="form-check-label" for="messageAlerts">
                                Message Alerts
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1">Receive alerts for new messages from mentees</small>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Preferences
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 