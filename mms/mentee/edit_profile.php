<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is a mentee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentee') {
    header("Location: ../login.php");
    exit();
}

// Get mentee details
try {
    $stmt = $conn->prepare("SELECT u.*, m.bio, m.expertise 
                           FROM users u 
                           LEFT JOIN mentees m ON u.id = m.user_id 
                           WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found");
    }
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while fetching your profile.";
    header("Location: dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $bio = filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_STRING);
    $expertise = implode(',', $_POST['expertise'] ?? []);

    try {
        $conn->beginTransaction();

        // Update users table
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $_SESSION['user_id']]);

        // Check if mentee record exists
        $stmt = $conn->prepare("SELECT id FROM mentees WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $mentee = $stmt->fetch();

        if ($mentee) {
            // Update existing mentee record
            $stmt = $conn->prepare("UPDATE mentees SET bio = ?, expertise = ? WHERE user_id = ?");
            $stmt->execute([$bio, $expertise, $_SESSION['user_id']]);
        } else {
            // Insert new mentee record
            $stmt = $conn->prepare("INSERT INTO mentees (user_id, bio, expertise) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $bio, $expertise]);
        }

        // Handle profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['profile_image']['tmp_name'];
            $file_name = $_SESSION['user_id'] . '_' . time() . '.jpg';
            $upload_dir = '../assets/images/mentees/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
                $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $stmt->execute([$file_name, $_SESSION['user_id']]);
            }
        }

        $conn->commit();
        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: dashboard.php");
        exit();

    } catch(PDOException $e) {
        $conn->rollBack();
        error_log("Error: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred while updating your profile.";
    }
}

// Get available expertise options
$expertise_options = [
    'Web Development',
    'Mobile Development',
    'Data Science',
    'UI/UX Design',
    'Machine Learning',
    'Cloud Computing',
    'DevOps',
    'Cybersecurity',
    'Blockchain',
    'Digital Marketing'
];

// Get user's current expertise as array
$user_expertise = explode(',', $user['expertise'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - MMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            --secondary-gradient: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            --background-color: #f3f4f6;
            --text-color: #1f2937;
            --card-shadow: 0 10px 25px rgba(99, 102, 241, 0.1);
            --hover-shadow: 0 15px 30px rgba(99, 102, 241, 0.2);
        }

        body {
            background: var(--background-color);
            font-family: 'Inter', sans-serif;
            color: var(--text-color);
            min-height: 100vh;
            padding: 40px 0;
        }

        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .profile-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            animation: slideUp 0.5s ease forwards;
        }

        .profile-header {
            background: var(--primary-gradient);
            padding: 40px;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 40%, transparent 50%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .profile-header:hover::before {
            transform: translateX(100%);
        }

        .profile-image-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
        }

        .profile-image {
            width: 100%;
            height: 100%;
            border-radius: 20px;
            object-fit: cover;
            border: 4px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .profile-image:hover {
            transform: scale(1.05) rotate(3deg);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .image-upload-label {
            position: absolute;
            bottom: -5px;
            right: -5px;
            background: white;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            color: #6366f1;
        }

        .image-upload-label:hover {
            transform: scale(1.1);
            background: #6366f1;
            color: white;
        }

        .profile-body {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        .form-control {
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            padding: 12px 15px;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
            background: white;
        }

        .expertise-options {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
            margin-top: 15px;
        }

        .expertise-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .expertise-option:hover {
            background: #f3f4f6;
            transform: translateX(5px);
        }

        .expertise-option input[type="checkbox"] {
            width: 18px;
            height: 18px;
            border-radius: 6px;
            border: 2px solid #6366f1;
            cursor: pointer;
            position: relative;
            transition: all 0.2s ease;
        }

        .expertise-option input[type="checkbox"]:checked {
            background: #6366f1;
            border-color: #6366f1;
        }

        .expertise-option input[type="checkbox"]:checked::after {
            content: '✓';
            position: absolute;
            color: white;
            font-size: 12px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .btn-save {
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 15px 30px;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-save::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }

        .btn-save:hover::before {
            left: 100%;
        }

        .btn-save:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background: white;
            color: var(--text-color);
            border: none;
            border-radius: 15px;
            margin-bottom: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: var(--card-shadow);
        }

        .btn-back:hover {
            background: var(--text-color);
            color: white;
            transform: translateX(-5px);
        }

        .alert {
            border-radius: 12px;
            margin-bottom: 25px;
            padding: 15px 20px;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .profile-container {
                padding: 0 15px;
            }

            .profile-header {
                padding: 30px;
            }

            .profile-body {
                padding: 30px;
            }

            .expertise-options {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <a href="dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-image-container">
                    <img src="<?php echo isset($user['profile_image']) ? '../assets/images/mentees/' . $user['profile_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=random&color=fff&size=200'; ?>" 
                         alt="Profile" class="profile-image" id="profileImagePreview">
                    <label for="profile_image" class="image-upload-label">
                        <i class="fas fa-camera"></i>
                    </label>
                </div>
                <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                <p class="mb-0">Mentee</p>
            </div>

            <div class="profile-body">
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="file" id="profile_image" name="profile_image" accept="image/*" style="display: none;">

                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Bio</label>
                        <textarea class="form-control" name="bio" rows="4" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Areas of Interest</label>
                        <div class="expertise-options">
                            <?php foreach ($expertise_options as $option): ?>
                                <label class="expertise-option">
                                    <input type="checkbox" name="expertise[]" value="<?php echo $option; ?>"
                                           <?php echo in_array($option, $user_expertise) ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($option); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-save">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Preview uploaded image
        document.getElementById('profile_image').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileImagePreview').src = e.target.result;
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // Add smooth transitions for form elements
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.style.transform = 'translateY(-2px)';
            });

            input.addEventListener('blur', () => {
                input.parentElement.style.transform = 'translateY(0)';
            });
        });

        // Add animation for expertise options
        document.querySelectorAll('.expertise-option').forEach(option => {
            option.addEventListener('change', function() {
                this.style.transform = 'scale(1.05)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 200);
            });
        });
    </script>
</body>
</html> 