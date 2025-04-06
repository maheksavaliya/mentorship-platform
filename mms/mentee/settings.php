<?php
session_start();
require_once '../config/db.php';

// Initialize variables
$user = [];
$error = null;
$success = null;
$preferences = [
    'email_notifications' => 1,
    'session_reminders' => 1,
    'marketing_emails' => 0
];

// Get mentee's information if logged in
try {
    if(isset($_SESSION['mentee_id'])) {
        $stmt = $conn->prepare("SELECT * FROM mentees WHERE id = ?");
        $stmt->execute([$_SESSION['mentee_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $error = "An error occurred while fetching your information.";
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['mentee_id'])) {
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $bio = trim($_POST['bio']);
        $interests = trim($_POST['interests']);

        // Handle profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_image']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);

            if (in_array(strtolower($filetype), $allowed)) {
                $temp_name = $_FILES['profile_image']['tmp_name'];
                $new_filename = 'mentee_' . $_SESSION['mentee_id'] . '.' . $filetype;
                $upload_path = '../assets/images/mentees/' . $new_filename;

                if (move_uploaded_file($temp_name, $upload_path)) {
                    // Update profile image in database
                    $stmt = $conn->prepare("UPDATE mentees SET profile_image = ? WHERE id = ?");
                    $stmt->execute([$new_filename, $_SESSION['mentee_id']]);
                }
            }
        }

        try {
            $stmt = $conn->prepare("UPDATE mentees SET name = ?, email = ?, phone = ?, bio = ?, interests = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $bio, $interests, $_SESSION['mentee_id']]);
            $success = "Profile updated successfully!";
            
            // Update session name
            $_SESSION['name'] = $name;
        } catch(PDOException $e) {
            error_log("Error updating profile: " . $e->getMessage());
            $error = "Failed to update profile. Please try again.";
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } else {
            try {
                // Verify current password
                $stmt = $conn->prepare("SELECT password FROM mentees WHERE id = ?");
                $stmt->execute([$_SESSION['mentee_id']]);
                $stored_password = $stmt->fetchColumn();

                if (password_verify($current_password, $stored_password)) {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE mentees SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $_SESSION['mentee_id']]);
                    $success = "Password changed successfully!";
                } else {
                    $error = "Current password is incorrect.";
                }
            } catch(PDOException $e) {
                error_log("Error changing password: " . $e->getMessage());
                $error = "Failed to change password. Please try again.";
            }
        }
    } elseif (isset($_POST['update_preferences'])) {
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $session_reminders = isset($_POST['session_reminders']) ? 1 : 0;
        $marketing_emails = isset($_POST['marketing_emails']) ? 1 : 0;

        try {
            $stmt = $conn->prepare("UPDATE mentee_preferences SET 
                                  email_notifications = ?,
                                  session_reminders = ?,
                                  marketing_emails = ?
                                  WHERE mentee_id = ?");
            $stmt->execute([$email_notifications, $session_reminders, $marketing_emails, $_SESSION['mentee_id']]);
            $success = "Preferences updated successfully!";
        } catch(PDOException $e) {
            error_log("Error updating preferences: " . $e->getMessage());
            $error = "Failed to update preferences. Please try again.";
        }
    }
}

// Get current preferences
try {
    if(isset($_SESSION['mentee_id'])) {
        $stmt = $conn->prepare("SELECT * FROM mentee_preferences WHERE mentee_id = ?");
        $stmt->execute([$_SESSION['mentee_id']]);
        $preferences = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'email_notifications' => 1,
            'session_reminders' => 1,
            'marketing_emails' => 0
        ];
    }
} catch(PDOException $e) {
    error_log("Error fetching preferences: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Mentor Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #4f46e5;
            --accent-color: #818cf8;
            --success-color: #22c55e;
            --warning-color: #eab308;
            --danger-color: #ef4444;
        }

        body {
            background: #f3f4f6;
            font-family: 'Inter', sans-serif;
        }

        .settings-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: url("data:image/svg+xml,%3Csvg width='300' height='300' viewBox='0 0 300 300' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='150' cy='150' r='150' fill='rgba(255,255,255,0.1)'/%3E%3C/svg%3E") no-repeat;
            opacity: 0.3;
        }

        .settings-nav {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .nav-pills .nav-link {
            color: #6b7280;
            border-radius: 10px;
            padding: 12px 20px;
            margin: 0 5px;
            transition: all 0.3s ease;
        }

        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .nav-pills .nav-link:hover:not(.active) {
            background: #f3f4f6;
        }

        .settings-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .form-label {
            color: #374151;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .btn-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 25px;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3);
            color: white;
        }

        .form-check-input {
            width: 1.2em;
            height: 1.2em;
            margin-top: 0.15em;
            margin-right: 0.5em;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-label {
            color: #4b5563;
            font-size: 0.95rem;
        }

        .alert {
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 30px;
            border: none;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success-color);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .profile-image-container {
            width: 120px;
            height: 120px;
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 20px;
            position: relative;
        }

        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-image-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.5);
            padding: 8px;
            text-align: center;
            color: white;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .profile-image-overlay:hover {
            background: rgba(0, 0, 0, 0.7);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease forwards;
        }
    </style>
</head>
<body>
    <div class="settings-container">
        <!-- Back Button -->
        <a href="dashboard.php" class="btn btn-outline-primary mb-4">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>

        <div class="page-header">
            <h2 class="mb-3">Settings</h2>
            <p class="mb-0">Manage your account settings and preferences</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success animate-fade-in">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger animate-fade-in">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="settings-nav">
            <ul class="nav nav-pills nav-justified" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#profile" type="button">
                        <i class="fas fa-user me-2"></i>Profile
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#security" type="button">
                        <i class="fas fa-lock me-2"></i>Security
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#preferences" type="button">
                        <i class="fas fa-cog me-2"></i>Preferences
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content">
            <!-- Profile Settings -->
            <div class="tab-pane fade show active" id="profile">
                <div class="settings-card animate-fade-in">
                    <h4 class="mb-4">Profile Information</h4>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="profile-image-container mx-auto">
                            <img src="<?php echo isset($user['profile_image']) ? '../assets/images/mentees/' . $user['profile_image'] : '../assets/images/default-avatar.png'; ?>" 
                                 alt="Profile" class="profile-image">
                            <div class="profile-image-overlay">
                                <i class="fas fa-camera me-2"></i>Change Photo
                            </div>
                            <input type="file" name="profile_image" id="profile_image" class="d-none" accept="image/*">
                        </div>

                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Bio</label>
                                    <textarea class="form-control" name="bio" rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Interests</label>
                                    <input type="text" class="form-control" name="interests" value="<?php echo htmlspecialchars($user['interests'] ?? ''); ?>" placeholder="e.g., Web Development, Data Science, AI">
                                </div>
                            </div>
                        </div>
                        <div class="text-end mt-4">
                            <button type="submit" name="update_profile" class="btn btn-custom">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="tab-pane fade" id="security">
                <div class="settings-card animate-fade-in">
                    <h4 class="mb-4">Change Password</h4>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <div class="text-end mt-4">
                            <button type="submit" name="change_password" class="btn btn-custom">
                                <i class="fas fa-key me-2"></i>Change Password
                            </button>
                        </div>
                    </form>
                </div>

                <div class="settings-card animate-fade-in">
                    <h4 class="mb-4">Two-Factor Authentication</h4>
                    <p class="text-muted mb-4">Add an extra layer of security to your account</p>
                    <button class="btn btn-custom" onclick="alert('Feature coming soon!')">
                        <i class="fas fa-shield-alt me-2"></i>Enable 2FA
                    </button>
                </div>
            </div>

            <!-- Preferences -->
            <div class="tab-pane fade" id="preferences">
                <div class="settings-card animate-fade-in">
                    <h4 class="mb-4">Notification Settings</h4>
                    <form method="POST">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="email_notifications" id="emailNotifications" 
                                       <?php echo ($preferences['email_notifications'] ?? true) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="emailNotifications">
                                    Email Notifications
                                </label>
                            </div>
                            <small class="text-muted d-block mt-1">Receive notifications about your sessions and messages via email</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="session_reminders" id="sessionReminders"
                                       <?php echo ($preferences['session_reminders'] ?? true) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="sessionReminders">
                                    Session Reminders
                                </label>
                            </div>
                            <small class="text-muted d-block mt-1">Get reminders before your upcoming sessions</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="marketing_emails" id="marketingEmails"
                                       <?php echo ($preferences['marketing_emails'] ?? false) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="marketingEmails">
                                    Marketing Emails
                                </label>
                            </div>
                            <small class="text-muted d-block mt-1">Receive updates about new features and promotions</small>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" name="update_preferences" class="btn btn-custom">
                                <i class="fas fa-save me-2"></i>Save Preferences
                            </button>
                        </div>
                    </form>
                </div>

                <div class="settings-card animate-fade-in">
                    <h4 class="mb-4">Account Management</h4>
                    <p class="text-muted mb-4">Manage your account data and preferences</p>
                    <div class="d-flex gap-3">
                        <button class="btn btn-outline-primary" onclick="alert('Your data will be sent to your email')">
                            <i class="fas fa-download me-2"></i>Download My Data
                        </button>
                        <button class="btn btn-outline-danger" onclick="if(confirm('Are you sure? This cannot be undone.')) alert('Please contact support to delete your account')">
                            <i class="fas fa-trash-alt me-2"></i>Delete Account
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle profile image upload
        document.querySelector('.profile-image-overlay').addEventListener('click', function() {
            document.getElementById('profile_image').click();
        });

        document.getElementById('profile_image').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.profile-image').src = e.target.result;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // Add animation when switching tabs
        document.querySelectorAll('[data-bs-toggle="pill"]').forEach(trigger => {
            trigger.addEventListener('click', function() {
                document.querySelectorAll('.settings-card').forEach(card => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 300);
                });
            });
        });
    </script>
</body>
</html> 