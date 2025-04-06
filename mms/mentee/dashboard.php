<?php
session_start();
require_once '../config/db.php';

// First, let's ensure our tables have the correct structure
try {
    // Add updated_at column to sessions table if it doesn't exist
    $conn->query("ALTER TABLE sessions ADD COLUMN IF NOT EXISTS updated_at 
                 TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
                 
    // Add updated_at column to notifications table if it doesn't exist
    $conn->query("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS updated_at 
                 TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
} catch(PDOException $e) {
    error_log("Column addition error (can be ignored if columns exist): " . $e->getMessage());
}

// Initialize variables with default values
$user = [];
$upcoming_sessions = [];
$stats = [
    'upcoming_count' => 0,
    'completed_count' => 0,
    'total_hours' => 0
];
$notifications = [];

// Debug logging at start
error_log("Starting mentee dashboard - Session data: " . print_r($_SESSION, true));

// Check if user is logged in and is a mentee
if (!isset($_SESSION['mentee_id'])) {
    error_log("No mentee_id found in session");
    $_SESSION['error'] = "Please login as a mentee to continue.";
    header("Location: ../login.php");
    exit();
}

try {
    // Debug logging
    error_log("Loading dashboard for mentee_id: " . $_SESSION['mentee_id']);
    
    // Get mentee details
    $stmt = $conn->prepare("SELECT * FROM mentees WHERE id = ?");
    $stmt->execute([$_SESSION['mentee_id']]);
    $mentee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mentee) {
        throw new Exception("Mentee account not found");
    }

    // Get upcoming sessions with mentor details
    $upcoming_query = "SELECT s.*, m.name as mentor_name, m.expertise, m.rating, m.profile_image 
                      FROM sessions s 
                      INNER JOIN mentors m ON s.mentor_id = m.id 
                      WHERE s.mentee_id = ? 
                      AND s.status = 'accepted'
                      AND s.date_time >= CURRENT_TIMESTAMP 
                      ORDER BY s.date_time ASC";

    $stmt = $conn->prepare($upcoming_query);
    $stmt->execute([$_SESSION['mentee_id']]);
    $upcoming_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Found " . count($upcoming_sessions) . " upcoming sessions");
    error_log("Upcoming sessions data: " . print_r($upcoming_sessions, true));

    // Get session statistics
    $stats_query = "SELECT 
        COUNT(CASE WHEN status = 'accepted' AND date_time >= CURRENT_TIMESTAMP THEN 1 END) as upcoming_count,
        COUNT(CASE WHEN status = 'completed' OR (status = 'accepted' AND date_time < CURRENT_TIMESTAMP) THEN 1 END) as completed_count,
        COALESCE(SUM(CASE WHEN status = 'completed' OR (status = 'accepted' AND date_time < CURRENT_TIMESTAMP) THEN duration ELSE 0 END), 0) as total_hours
        FROM sessions 
        WHERE mentee_id = ?";
    
    $stmt = $conn->prepare($stats_query);
    $stmt->execute([$_SESSION['mentee_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get unread notifications
    $notify_stmt = $conn->prepare("SELECT * FROM notifications 
                                 WHERE user_id = ? 
                                 AND user_type = 'mentee' 
                                 AND is_read = 0 
                                 ORDER BY created_at DESC 
                                 LIMIT 5");
    $notify_stmt->execute([$_SESSION['mentee_id']]);
    $notifications = $notify_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(Exception $e) {
    error_log("Error in mentee dashboard: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $_SESSION['error'] = "An error occurred while fetching session details.";
}

// Set default name if not available
$user_name = isset($mentee['name']) ? $mentee['name'] : 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentee Dashboard - MMS</title>
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
            background-color: #f3f4f6;
            font-family: 'Inter', sans-serif;
        }

        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .back-button:hover {
            background: var(--secondary-color);
            transform: scale(1.1);
        }

        .dashboard-container {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .sidebar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 20px;
            padding: 25px;
            height: calc(100vh - 60px);
            position: sticky;
            top: 30px;
            color: white;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.2);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .nav.flex-column {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .nav-link.logout-link {
            margin-top: auto;
            color: #ef4444;
        }

        .nav-link:hover {
            transform: translateX(5px);
            background: rgba(255, 255, 255, 0.1);
        }

        /* Hide scrollbar for Chrome, Safari and Opera */
        .sidebar::-webkit-scrollbar {
            display: none;
        }

        /* Hide scrollbar for IE, Edge and Firefox */
        .sidebar {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }

        .profile-section {
            text-align: center;
            padding-bottom: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 25px;
        }

        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin-bottom: 15px;
            border: 4px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease;
        }

        .profile-image:hover {
            transform: scale(1.05);
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .nav-link i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .welcome-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 20px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.2);
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: url("data:image/svg+xml,%3Csvg width='200' height='200' viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill='rgba(255,255,255,0.1)' d='M100 0C44.8 0 0 44.8 0 100s44.8 100 100 100 100-44.8 100-100S155.2 0 100 0zm0 180c-44.1 0-80-35.9-80-80s35.9-80 80-80 80 35.9 80 80-35.9 80-80 80z'/%3E%3C/svg%3E") no-repeat;
            opacity: 0.5;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            height: 100%;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            background: var(--accent-color);
            color: white;
            font-size: 1.5rem;
        }

        .session-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .session-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .mentor-avatar {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            margin-right: 15px;
        }

        .session-info h5 {
            color: #1f2937;
            margin-bottom: 10px;
        }

        .session-meta {
            display: flex;
            align-items: center;
            color: #6b7280;
            margin-bottom: 5px;
        }

        .session-meta i {
            width: 20px;
            margin-right: 8px;
            color: var(--primary-color);
        }

        .rating {
            color: var(--warning-color);
        }

        .quick-action {
            background: white;
            border-radius: 20px;
            padding: 25px;
            height: 100%;
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            text-align: center;
        }

        .quick-action:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .quick-action-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            background: var(--accent-color);
            color: white;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }

        .btn-custom {
            background: var(--accent-color);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 25px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-custom:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .btn-edit-profile {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 8px 20px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }

        .btn-edit-profile:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
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
    <div class="dashboard-container">
        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="sidebar">
                    <div class="profile-section">
                        <img src="<?php echo isset($mentee['profile_image']) ? '../assets/images/mentees/' . $mentee['profile_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=6366f1&color=fff'; ?>" 
                             alt="Profile" class="profile-image">
                        <h3><?php echo htmlspecialchars($user_name); ?></h3>
                        <p>Mentee</p>
                        <a href="edit_profile.php" class="btn-edit-profile">
                            <i class="fas fa-user-edit"></i> Edit Profile
                        </a>
                    </div>

                    <div class="nav flex-column">
                        <a href="dashboard.php" class="nav-link active">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                        <a href="find_mentor.php" class="nav-link">
                            <i class="fas fa-search"></i> Find Mentor
                        </a>
                        <a href="my_sessions.php" class="nav-link">
                            <i class="fas fa-calendar-alt"></i> My Sessions
                        </a>
                        <a href="messages.php" class="nav-link">
                            <i class="fas fa-envelope"></i> Messages
                        </a>
                        <a href="resources.php" class="nav-link">
                            <i class="fas fa-book"></i> Resources
                        </a>
                        <a href="settings.php" class="nav-link">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <a href="logout.php" class="nav-link logout-link">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="welcome-section animate-fade-in">
                    <h2 class="mb-3">Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h2>
                    <p class="mb-0">Track your mentorship journey and connect with experienced mentors.</p>
                </div>

                <!-- Notifications -->
                <?php if (!empty($notifications)): ?>
                    <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                        <h5 class="alert-heading mb-3">
                            <i class="fas fa-bell me-2"></i>New Notifications
                        </h5>
                        <div class="notification-list">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item mb-3 p-3 bg-white rounded shadow-sm">
                                    <h6 class="mb-2"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                    <p class="mb-2"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('F j, Y \a\t g:i A', strtotime($notification['created_at'])); ?>
                                    </small>
                                    <?php if ($notification['type'] === 'session_accepted'): ?>
                                        <div class="mt-2">
                                            <a href="session_detail.php?id=<?php echo $notification['related_id']; ?>" 
                                               class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye me-1"></i>View Session Details
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <form action="mark_notifications_read.php" method="POST" class="mt-3">
                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-check-double me-1"></i>Mark All as Read
                            </button>
                        </form>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Session Status Updates -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats Row -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4 animate-fade-in" style="animation-delay: 0.1s">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <h3><?php echo $stats['upcoming_count']; ?></h3>
                            <p class="text-muted mb-0">Upcoming Sessions</p>
                        </div>
                    </div>
                    <div class="col-md-4 animate-fade-in" style="animation-delay: 0.2s">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h3><?php echo round($stats['total_hours'] / 60, 1); ?></h3>
                            <p class="text-muted mb-0">Hours Mentored</p>
                        </div>
                    </div>
                    <div class="col-md-4 animate-fade-in" style="animation-delay: 0.3s">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <h3><?php echo $stats['completed_count']; ?></h3>
                            <p class="text-muted mb-0">Completed Sessions</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6 animate-fade-in" style="animation-delay: 0.4s">
                        <div class="quick-action">
                            <div class="quick-action-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <h5>Find a Mentor</h5>
                            <p class="text-muted mb-3">Connect with experienced professionals</p>
                            <a href="find_mentor.php" class="btn btn-custom">Search Now</a>
                        </div>
                    </div>
                    <div class="col-md-6 animate-fade-in" style="animation-delay: 0.5s">
                        <div class="quick-action">
                            <div class="quick-action-icon">
                                <i class="fas fa-calendar-plus"></i>
                            </div>
                            <h5>Schedule Session</h5>
                            <p class="text-muted mb-3">Book a mentoring session</p>
                            <a href="schedule_session.php" class="btn btn-custom">Schedule Now</a>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Sessions -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 pb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Upcoming Sessions</h5>
                            <a href="my_sessions.php" class="btn btn-custom btn-sm">View All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($upcoming_sessions)): ?>
                            <?php foreach ($upcoming_sessions as $session): ?>
                                <div class="session-card">
                                    <div class="d-flex">
                                        <img src="<?php echo isset($session['profile_image']) ? '../assets/images/mentors/' . $session['profile_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($session['mentor_name']) . '&background=6366f1&color=fff'; ?>" 
                                             alt="Mentor" class="mentor-avatar">
                                        <div class="session-info flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h5 class="mb-2"><?php echo htmlspecialchars($session['title']); ?></h5>
                                                    <p class="mb-2">
                                                        <i class="fas fa-user-tie me-2"></i>
                                                        <?php echo htmlspecialchars($session['mentor_name']); ?>
                                                        <?php if (!empty($session['rating'])): ?>
                                                            <span class="ms-2">
                                                                <i class="fas fa-star text-warning"></i>
                                                                <?php echo number_format($session['rating'], 1); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                                <span class="badge bg-success">Confirmed</span>
                                            </div>
                                            <div class="session-meta">
                                                <i class="fas fa-graduation-cap me-2"></i>
                                                <span><?php echo htmlspecialchars($session['expertise']); ?></span>
                                            </div>
                                            <div class="session-meta">
                                                <i class="fas fa-calendar me-2"></i>
                                                <span><?php echo date('F j, Y', strtotime($session['date_time'])); ?></span>
                                            </div>
                                            <div class="session-meta">
                                                <i class="fas fa-clock me-2"></i>
                                                <span><?php echo date('g:i A', strtotime($session['date_time'])); ?> (<?php echo $session['duration']; ?> minutes)</span>
                                            </div>
                                            <?php if (!empty($session['description'])): ?>
                                                <div class="session-meta">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    <span><?php echo htmlspecialchars($session['description']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="mt-3">
                                                <?php if (!empty($session['meet_link'])): ?>
                                                    <a href="<?php echo htmlspecialchars($session['meet_link']); ?>" target="_blank" class="btn btn-success btn-sm me-2">
                                                        <i class="fas fa-video me-1"></i>Join Meeting
                                                    </a>
                                                <?php endif; ?>
                                                <a href="session_detail.php?id=<?php echo $session['id']; ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-eye me-1"></i>View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <img src="../assets/images/no-sessions.svg" alt="No Sessions" style="width: 150px; margin-bottom: 20px;">
                                <p class="text-muted mb-3">No upcoming sessions found</p>
                                <a href="find_mentor.php" class="btn btn-primary">Find a Mentor</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 