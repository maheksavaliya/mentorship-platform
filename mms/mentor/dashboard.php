<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is a mentor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../login.php");
    exit();
}

// Initialize variables with default values
$active_mentees = 0;
$monthly_sessions = 0;
$total_hours = 0;
$avg_rating = 0;
$upcoming_sessions = array();
$pending_sessions = array();
$mentor = array();
$upcoming_badge = 0;
$pending_badge = 0;
$message_badge = 0; // Initialize message badge counter

// Get mentor's information
try {
    // Get mentor details
    $stmt = $conn->prepare("SELECT * FROM mentors WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $mentor = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($mentor) {
        // Get unread messages count
        $stmt = $conn->prepare("SELECT COUNT(*) as message_count FROM messages 
                               WHERE recipient_id = ? 
                               AND recipient_type = 'mentor' 
                               AND is_read = 0");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $message_badge = $result['message_count'] ?? 0;

        // Get active mentees count
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT mentee_id) as active_mentees 
                               FROM sessions 
                               WHERE mentor_id = ? AND status IN ('accepted', 'ongoing')");
        $stmt->execute([$mentor['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $active_mentees = $result ? $result['active_mentees'] : 0;

        // Get sessions this month
        $stmt = $conn->prepare("SELECT COUNT(*) as monthly_sessions 
                               FROM sessions 
                               WHERE mentor_id = ? AND MONTH(date_time) = MONTH(CURRENT_DATE())");
        $stmt->execute([$mentor['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $monthly_sessions = $result ? $result['monthly_sessions'] : 0;

        // Get total hours mentored
        $stmt = $conn->prepare("SELECT SUM(duration) as total_hours 
                               FROM sessions 
                               WHERE mentor_id = ? AND status = 'completed'");
        $stmt->execute([$mentor['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_hours = $result ? ($result['total_hours'] / 60) : 0; // Convert minutes to hours

        // Get average rating
        $stmt = $conn->prepare("SELECT AVG(rating) as avg_rating 
                               FROM sessions 
                               WHERE mentor_id = ? AND rating IS NOT NULL");
        $stmt->execute([$mentor['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $avg_rating = $result ? number_format($result['avg_rating'] ?? 0, 1) : 0;

        // Get upcoming sessions (last 5)
        $stmt = $conn->prepare("SELECT s.*, me.name as mentee_name 
                               FROM sessions s 
                               JOIN mentees me ON s.mentee_id = me.id 
                               WHERE s.mentor_id = ? 
                               AND s.date_time >= NOW() 
                               AND s.status IN ('accepted', 'pending')
                               ORDER BY s.date_time ASC 
                               LIMIT 5");
        $stmt->execute([$mentor['id']]);
        $upcoming_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get pending session requests with LEFT JOIN to handle deleted mentees
        $stmt = $conn->prepare("SELECT s.*, 
                               COALESCE(me.name, 'Deleted User') as mentee_name,
                               COALESCE(me.email, 'N/A') as mentee_email
                               FROM sessions s 
                               LEFT JOIN mentees me ON s.mentee_id = me.id 
                               WHERE s.mentor_id = ? 
                               AND s.status = 'pending' 
                               ORDER BY s.created_at DESC");
        
        error_log("Executing pending sessions query for mentor ID: " . $mentor['id']);
        $stmt->execute([$mentor['id']]);
        $pending_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Found " . count($pending_sessions) . " pending sessions");
        error_log("Pending sessions data: " . print_r($pending_sessions, true));

        // Get total sessions count including sessions from deleted mentees
        $stmt = $conn->prepare("SELECT 
            COUNT(CASE WHEN status = 'accepted' AND date_time >= NOW() THEN 1 END) as upcoming_count,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN status = 'completed' OR (status = 'accepted' AND date_time < NOW()) THEN 1 END) as completed_count
        FROM sessions 
        WHERE mentor_id = ?");
        $stmt->execute([$mentor['id']]);
        $session_counts = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Session counts: " . print_r($session_counts, true));

        // Set the badge counts
        $upcoming_badge = $session_counts['upcoming_count'] ?? 0;
        $pending_badge = $session_counts['pending_count'] ?? 0;
    }

} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $error = "An error occurred. Please try again.";
}

// Set default values if no data
if ($active_mentees == 0) $active_mentees = 5;
if ($monthly_sessions == 0) $monthly_sessions = 12;
if ($total_hours == 0) $total_hours = 45;
if ($avg_rating == 0) $avg_rating = 4.8;

// Ensure mentor array has required fields
if (empty($mentor)) {
    $mentor = [
        'name' => 'Manender Dutt',
        'profile_image' => null
    ];
}

// Fetch notifications
$stmt = $conn->prepare("SELECT * FROM notifications 
                        WHERE user_id = ? 
                        AND user_type = 'mentor' 
                        AND is_read = 0 
                        ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Dashboard - MMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            --secondary-gradient: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
            --success-gradient: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --info-gradient: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            --background-color: #f3f4f6;
            --card-shadow: 0 10px 25px rgba(99, 102, 241, 0.1);
            --hover-shadow: 0 15px 30px rgba(99, 102, 241, 0.2);
        }

        body {
            background: var(--background-color);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        .dashboard-container {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .sidebar {
            background: var(--primary-gradient);
            border-radius: 20px;
            padding: 25px;
            height: calc(100vh - 60px);
            position: sticky;
            top: 30px;
            color: white;
            box-shadow: var(--card-shadow);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
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
            border-radius: 20px;
            margin-bottom: 15px;
            border: 4px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .profile-image:hover {
            transform: scale(1.05) rotate(3deg);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .nav.flex-column {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            position: relative;
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

        .notification-badge {
            position: absolute;
            top: 8px;
            right: 15px;
            background: var(--secondary-gradient);
            color: white;
            border-radius: 20px;
            padding: 2px 8px;
            font-size: 0.75rem;
        }

        .welcome-section {
            background: var(--primary-gradient);
            border-radius: 20px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .welcome-section::before {
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

        .welcome-section:hover::before {
            transform: translateX(100%);
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            height: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 5px;
            background: var(--primary-gradient);
            border-radius: 0 20px 20px 0;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            color: white;
            font-size: 1.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .session-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            box-shadow: var(--card-shadow);
        }

        .session-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .mentee-avatar {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            object-fit: cover;
            margin-right: 15px;
            border: 3px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .session-card:hover .mentee-avatar {
            transform: scale(1.1) rotate(5deg);
            border-color: #6366f1;
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
            font-size: 0.9rem;
        }

        .session-meta i {
            width: 20px;
            margin-right: 8px;
            color: #6366f1;
        }

        .btn-custom {
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }

        .btn-custom:hover::before {
            left: 100%;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3);
            color: white;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease forwards;
        }

        .earnings-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
        }

        .earnings-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .earnings-chart {
            height: 200px;
            margin-bottom: 20px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .quick-action-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: var(--card-shadow);
        }

        .quick-action-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .quick-action-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 1.5rem;
        }

        .mentees-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .mentee-item {
            text-align: center;
        }

        .mentee-item img {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            margin-bottom: 8px;
            border: 3px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .mentee-item:hover img {
            transform: scale(1.1) rotate(5deg);
            border-color: #6366f1;
        }

        .mentee-item p {
            font-size: 0.85rem;
            color: #6b7280;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-link.logout-link {
            margin-top: auto;
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
                        <?php 
                        $profile_image = '';
                        if (!empty($mentor['avatar'])) {
                            $profile_image = file_exists('../assets/images/mentors/' . $mentor['avatar']) 
                                ? '../assets/images/mentors/' . $mentor['avatar'] 
                                : 'https://ui-avatars.com/api/?name=' . urlencode($mentor['name']) . '&background=6366f1&color=fff&size=200';
                        } else {
                            $profile_image = 'https://ui-avatars.com/api/?name=' . urlencode($mentor['name']) . '&background=6366f1&color=fff&size=200';
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="profile-image">
                        <h3><?php echo htmlspecialchars($mentor['name']); ?></h3>
                        <p class="mb-3">Professional Mentor</p>
                        <a href="edit_profile.php" class="btn btn-custom btn-sm">
                            <i class="fas fa-user-edit"></i> Edit Profile
                        </a>
                    </div>

                    <div class="nav flex-column">
                        <a href="dashboard.php" class="nav-link active">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                        <a href="my_sessions.php" class="nav-link">
                            <i class="fas fa-calendar-alt"></i> My Sessions
                            <?php if (($upcoming_badge + $pending_badge) > 0): ?>
                                <span class="notification-badge"><?php echo $upcoming_badge + $pending_badge; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="my_mentees.php" class="nav-link">
                            <i class="fas fa-users"></i> My Mentees
                            <span class="notification-badge">8</span>
                        </a>
                        <a href="availability.php" class="nav-link">
                            <i class="fas fa-clock"></i> Availability
                        </a>
                        <a href="messages.php" class="nav-link">
                            <i class="fas fa-envelope"></i> Messages
                            <?php if ($message_badge > 0): ?>
                                <span class="badge"><?php echo $message_badge; ?></span>
                            <?php endif; ?>
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
                <!-- Welcome Section -->
                <div class="welcome-section animate-fade-in">
                    <h2 class="mb-3">Welcome back, <?php echo htmlspecialchars($mentor['name']); ?>!</h2>
                    <p class="mb-0">You're making a difference in your mentees' lives. Keep up the great work!</p>
                </div>

                <!-- Stats Row -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3 animate-fade-in" style="animation-delay: 0.1s">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: var(--primary-gradient)">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-value"><?php echo $active_mentees; ?></div>
                            <div class="stat-label">Active Mentees</div>
                        </div>
                    </div>
                    <div class="col-md-3 animate-fade-in" style="animation-delay: 0.2s">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: var(--success-gradient)">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-value"><?php echo $monthly_sessions; ?></div>
                            <div class="stat-label">Sessions This Month</div>
                        </div>
                    </div>
                    <div class="col-md-3 animate-fade-in" style="animation-delay: 0.3s">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: var(--warning-gradient)">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-value"><?php echo $total_hours; ?></div>
                            <div class="stat-label">Hours Mentored</div>
                        </div>
                    </div>
                    <div class="col-md-3 animate-fade-in" style="animation-delay: 0.4s">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: var(--info-gradient)">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-value"><?php echo $avg_rating; ?></div>
                            <div class="stat-label">Average Rating</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions mb-4">
                    <div class="quick-action-card animate-fade-in" style="animation-delay: 0.5s">
                        <div class="quick-action-icon" style="background: var(--primary-gradient)">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <h5>Schedule Session</h5>
                        <p class="text-muted mb-3">Set up a new mentoring session</p>
                        <a href="schedule_session.php" class="btn btn-custom">Schedule Now</a>
                    </div>
                    <div class="quick-action-card animate-fade-in" style="animation-delay: 0.6s">
                        <div class="quick-action-icon" style="background: var(--success-gradient)">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h5>Update Availability</h5>
                        <p class="text-muted mb-3">Manage your available time slots</p>
                        <a href="availability.php" class="btn btn-custom">Update Now</a>
                    </div>
                    <div class="quick-action-card animate-fade-in" style="animation-delay: 0.7s">
                        <div class="quick-action-icon" style="background: var(--info-gradient)">
                            <i class="fas fa-book"></i>
                        </div>
                        <h5>Create Resource</h5>
                        <p class="text-muted mb-3">Share knowledge with mentees</p>
                        <a href="create_resource.php" class="btn btn-custom">Create Now</a>
                    </div>
                </div>

                <!-- Pending Session Requests -->
                <div class="card mb-4 animate-fade-in" style="animation-delay: 0.5s">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">
                            <i class="fas fa-clock text-warning me-2"></i>
                            Pending Session Requests (<?php echo count($pending_sessions); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($pending_sessions) > 0): ?>
                            <?php foreach ($pending_sessions as $session): ?>
                                <div class="session-card mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="mentee-avatar-container me-3">
                                            <div class="mentee-avatar d-flex align-items-center justify-content-center" 
                                                 style="background-color: #6366f1; color: white; width: 50px; height: 50px; border-radius: 10px; font-size: 1.2rem;">
                                                <?php echo strtoupper(substr($session['mentee_name'], 0, 2)); ?>
                                            </div>
                                        </div>
                                        <div class="session-info flex-grow-1">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($session['title']); ?></h5>
                                            <div class="session-meta mb-1">
                                                <i class="fas fa-user me-2"></i>
                                                <span><?php echo htmlspecialchars($session['mentee_name']); ?></span>
                                                <i class="fas fa-graduation-cap ms-3 me-2"></i>
                                                <span><?php echo htmlspecialchars($session['mentee_email']); ?></span>
                                            </div>
                                            <div class="session-meta">
                                                <i class="fas fa-calendar me-2"></i>
                                                <span><?php echo date('F j, Y', strtotime($session['date_time'])); ?></span>
                                                <i class="fas fa-clock ms-3 me-2"></i>
                                                <span><?php echo date('g:i A', strtotime($session['date_time'])); ?> (<?php echo $session['duration']; ?> minutes)</span>
                                            </div>
                                        </div>
                                        <div class="ms-3">
                                            <form method="POST" action="update_session.php" class="d-flex gap-2">
                                                <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                                                <button type="submit" name="action" value="accept" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to accept this session?');">
                                                    <i class="fas fa-check me-1"></i> Accept
                                                </button>
                                                <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to reject this session?');">
                                                    <i class="fas fa-times me-1"></i> Reject
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted mb-0">No pending session requests</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-bell text-primary me-2"></i>
                            Notifications
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($notifications) > 0): ?>
                            <div class="list-group">
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="list-group-item">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                        <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">No new notifications</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upcoming Sessions -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 animate-fade-in" style="animation-delay: 0.8s">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="card-title mb-0">Upcoming Sessions</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($upcoming_sessions)): ?>
                            <?php foreach($upcoming_sessions as $session): ?>
                                <div class="session-item p-3 mb-3 bg-light rounded-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($session['title']); ?></h6>
                                            <p class="mb-2 text-muted">
                                                <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($session['mentee_name']); ?>
                                            </p>
                                            <p class="mb-2">
                                                <i class="far fa-calendar me-1"></i> 
                                                <?php echo date('M d, Y', strtotime($session['date_time'])); ?>
                                                <i class="far fa-clock ms-2 me-1"></i>
                                                <?php echo date('h:i A', strtotime($session['date_time'])); ?>
                                                <span class="ms-2">
                                                    <i class="fas fa-hourglass-half me-1"></i>
                                                    <?php echo $session['duration']; ?> minutes
                                                </span>
                                            </p>
                                        </div>
                                        <div>
                                            <?php if ($session['status'] == 'pending'): ?>
                                                <span class="badge bg-warning">Pending</span>
                                                <div class="mt-2">
                                                    <form method="POST" action="update_session.php" style="display: inline;">
                                                        <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                                                        <button type="submit" name="action" value="accept" class="btn btn-success btn-sm">Accept</button>
                                                        <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                                                    </form>
                                                </div>
                                            <?php elseif ($session['status'] == 'accepted'): ?>
                                                <span class="badge bg-success">Accepted</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted mb-0">No upcoming sessions</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Add hover effects for cards
        document.querySelectorAll('.stat-card, .session-card, .quick-action-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-5px)';
                card.style.boxShadow = '0 15px 30px rgba(99, 102, 241, 0.2)';
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
                card.style.boxShadow = '0 10px 25px rgba(99, 102, 241, 0.1)';
            });
        });
    </script>
</body>
</html> 