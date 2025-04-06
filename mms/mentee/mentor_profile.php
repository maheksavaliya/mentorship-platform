<?php
session_start();
require_once '../config/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug logging
error_log("Mentor profile page accessed");
error_log("Session data: " . print_r($_SESSION, true));
error_log("GET data: " . print_r($_GET, true));

// Get mentor ID from URL
if (!isset($_GET['id'])) {
    error_log("No mentor ID provided in URL");
    header("Location: find_mentor.php");
    exit();
}

$mentor_id = $_GET['id'];
error_log("Attempting to fetch mentor with ID: " . $mentor_id);

// Get mentor's information
try {
    $stmt = $conn->prepare("SELECT * FROM mentors WHERE id = ?");
    $stmt->execute([$mentor_id]);
    $mentor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mentor) {
        error_log("No mentor found with ID: " . $mentor_id);
        header("Location: find_mentor.php");
        exit();
    }
    error_log("Successfully fetched mentor: " . $mentor['name']);
} catch(PDOException $e) {
    error_log("Error fetching mentor: " . $e->getMessage());
    $error = "An error occurred. Please try again.";
}

// Add notification check
$notifications_count = 0;
try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND user_type = 'mentor' AND is_read = 0");
    $stmt->execute([$mentor_id]);
    $notifications_count = $stmt->fetchColumn();
} catch(PDOException $e) {
    error_log("Error checking notifications: " . $e->getMessage());
}

// Handle session booking
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_session'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $datetime = $_POST['datetime'];
    $duration = $_POST['duration'];

    try {
        // Start transaction
        $conn->beginTransaction();

        // Get mentee's ID from session
        $mentee_id = $_SESSION['mentee_id'];
        
        error_log("Booking session - Mentee ID: " . $mentee_id . ", Mentor ID: " . $mentor_id);

        // Insert session with status 'pending'
        $stmt = $conn->prepare("INSERT INTO sessions (mentor_id, mentee_id, title, description, date_time, duration, status) 
                              VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        
        error_log("Session data to insert: " . print_r([
            'mentor_id' => $mentor_id,
            'mentee_id' => $mentee_id,
            'title' => $title,
            'description' => $description,
            'datetime' => $datetime,
            'duration' => $duration
        ], true));
        
        $stmt->execute([$mentor_id, $mentee_id, $title, $description, $datetime, $duration]);
        
        $session_id = $conn->lastInsertId();
        error_log("New session created with ID: " . $session_id);
        
        // Create notification for mentor
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, title, message, type, related_id, is_read) VALUES (?, 'mentor', ?, ?, 'session_request', ?, 0)");
        $notification_title = "New Session Request";
        $notification_message = "You have a new session request for: " . $title;
        $stmt->execute([$mentor_id, $notification_title, $notification_message, $session_id]);

        // Commit transaction
        $conn->commit();
        
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                Session request sent successfully! You will be notified when the mentor responds.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
        
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $mentor_id . "&success=1");
        exit();
    } catch(PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        error_log("Error booking session: " . $e->getMessage());
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                Failed to book session. Error: ' . $e->getMessage() . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
    }
}

// Handle session status updates
if (isset($_POST['action']) && isset($_POST['session_id'])) {
    $action = $_POST['action'];
    $session_id = $_POST['session_id'];
    
    try {
        if ($action === 'accept' || $action === 'reject') {
            $stmt = $conn->prepare("UPDATE sessions SET status = ? WHERE id = ? AND mentor_id = ?");
            $stmt->execute([$action . 'ed', $session_id, $mentor_id]);
            
            // Create notification for mentee
            $stmt = $conn->prepare("SELECT mentee_id, title FROM sessions WHERE id = ?");
            $stmt->execute([$session_id]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($session) {
                $notification_title = "Session " . ucfirst($action) . "ed";
                $notification_message = "Your session '" . $session['title'] . "' has been " . $action . "ed by the mentor.";
                
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, title, message, type, related_id) VALUES (?, 'mentee', ?, ?, 'session_update', ?)");
                $stmt->execute([$session['mentee_id'], $notification_title, $notification_message, $session_id]);
            }
            
            $success = "Session successfully " . $action . "ed!";
        }
    } catch(PDOException $e) {
        error_log("Error updating session: " . $e->getMessage());
        $error = "Failed to update session status. Please try again.";
    }
}

// Get mentor's skills
try {
    $stmt = $conn->prepare("SELECT skill_name FROM mentor_skills WHERE mentor_id = ?");
    $stmt->execute([$mentor_id]);
    $skills = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    error_log("Error fetching skills: " . $e->getMessage());
    $skills = [];
}

// Get mentor's stats
try {
    // Get total sessions
    $stmt = $conn->prepare("SELECT COUNT(*) FROM sessions WHERE mentor_id = ? AND status = 'completed'");
    $stmt->execute([$mentor_id]);
    $total_sessions = $stmt->fetchColumn();

    // Get average rating
    $stmt = $conn->prepare("SELECT AVG(rating) FROM session_feedback WHERE mentor_id = ?");
    $stmt->execute([$mentor_id]);
    $avg_rating = number_format($stmt->fetchColumn() ?: 0, 2);

    // Get years of experience
    $years_exp = $mentor['years_experience'] ?? 0;
} catch(PDOException $e) {
    error_log("Error fetching stats: " . $e->getMessage());
}

// Show success message if redirected after booking
if (isset($_GET['success'])) {
    $success = "Session request sent successfully! You will be notified when the mentor responds.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Profile - <?php echo htmlspecialchars($mentor['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --background-color: #f8f9fc;
            --card-bg: #ffffff;
            --text-color: #5a5c69;
            --border-radius: 15px;
        }

        body {
            background: var(--background-color);
            font-family: 'Nunito', sans-serif;
            color: var(--text-color);
        }

        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), #224abe);
            padding: 3rem 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="0" cy="0" r="20" fill="rgba(255,255,255,0.05)"/></svg>') 0 0/50px 50px;
            opacity: 0.1;
            animation: patternMove 20s linear infinite;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .profile-avatar:hover {
            transform: scale(1.1);
        }

        .profile-info {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 2rem;
        }

        .skill-tag {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: var(--primary-color);
            color: white;
            border-radius: 50px;
            margin: 0.25rem;
            font-size: 0.9rem;
            transition: transform 0.2s ease;
        }

        .skill-tag:hover {
            transform: translateY(-2px);
        }

        .rating-stars {
            color: var(--warning-color);
            font-size: 1.2rem;
        }

        .session-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transition: transform 0.3s ease;
        }

        .session-card:hover {
            transform: translateY(-5px);
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #224abe;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 115, 223, 0.3);
        }

        @keyframes patternMove {
            from { background-position: 0 0; }
            to { background-position: 100px 100px; }
        }

        .stats-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            text-align: center;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .stats-label {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .back-button:hover {
            transform: translateX(-5px) scale(1.05);
            background: #2e59d9;
            color: white;
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.3);
        }
    </style>
</head>
<body>
    <a href="find_mentor.php" class="back-button">
        <i class="fas fa-arrow-left"></i> Back to Mentors
    </a>

    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="profile-container">
        <div class="profile-header text-white text-center">
            <img src="<?php echo htmlspecialchars($mentor['profile_picture'] ?? 'default-avatar.jpg'); ?>" 
                 alt="Profile Picture" 
                 class="profile-avatar mb-3">
            <h1 class="mb-2"><?php echo htmlspecialchars($mentor['name']); ?></h1>
            <p class="mb-0"><?php echo htmlspecialchars($mentor['title'] ?? 'Professional Mentor'); ?></p>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="profile-info">
                    <h4 class="mb-4">About</h4>
                    <p class="mb-4"><?php echo htmlspecialchars($mentor['bio'] ?? 'No bio available'); ?></p>
                    
                    <h5 class="mb-3">Skills</h5>
                    <div class="skills-container">
                        <?php foreach($skills as $skill): ?>
                            <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="stats-card mb-4">
                    <div class="row">
                        <div class="col-4">
                            <div class="stats-number"><?php echo $total_sessions; ?></div>
                            <div class="stats-label">Sessions</div>
                        </div>
                        <div class="col-4">
                            <div class="stats-number"><?php echo $avg_rating; ?></div>
                            <div class="stats-label">Rating</div>
                        </div>
                        <div class="col-4">
                            <div class="stats-number"><?php echo $years_exp; ?></div>
                            <div class="stats-label">Years Exp</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="profile-info">
                    <h4 class="mb-4">Book a Session</h4>
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="title" class="form-label">Session Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="datetime" class="form-label">Date & Time</label>
                                <input type="datetime-local" class="form-control" id="datetime" name="datetime" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="duration" class="form-label">Duration (minutes)</label>
                                <select class="form-select" id="duration" name="duration" required>
                                    <option value="30">30 minutes</option>
                                    <option value="45">45 minutes</option>
                                    <option value="60">1 hour</option>
                                    <option value="90">1.5 hours</option>
                                </select>
                            </div>
                        </div>
                        <input type="hidden" name="book_session" value="1">
                        <button type="submit" class="btn btn-primary w-100">Book Session</button>
                    </form>
                </div>

                <div class="profile-info">
                    <h4 class="mb-4">Upcoming Sessions</h4>
                    <?php
                    // Fetch upcoming sessions
                    $stmt = $conn->prepare("SELECT * FROM sessions WHERE mentor_id = ? AND status = 'accepted' AND date_time > NOW() ORDER BY date_time ASC LIMIT 5");
                    $stmt->execute([$mentor_id]);
                    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($sessions) > 0) {
                        foreach($sessions as $session) {
                            echo '<div class="session-card">';
                            echo '<h5>' . htmlspecialchars($session['title']) . '</h5>';
                            echo '<p class="mb-2">' . htmlspecialchars($session['description']) . '</p>';
                            echo '<div class="d-flex justify-content-between align-items-center">';
                            echo '<span><i class="far fa-calendar-alt me-2"></i>' . date('M d, Y H:i', strtotime($session['date_time'])) . '</span>';
                            echo '<span><i class="far fa-clock me-2"></i>' . $session['duration'] . ' minutes</span>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p class="text-center">No upcoming sessions</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true
        });

        // Initialize Flatpickr
        flatpickr("#datetime", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
            time_24hr: true
        });

        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html> 