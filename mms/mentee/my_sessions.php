<?php
session_start();
require_once '../config/db.php';

// Debug logging
error_log("Starting my_sessions.php");
error_log("SESSION data: " . print_r($_SESSION, true));

// Check if user is logged in as mentee
if (!isset($_SESSION['mentee_id'])) {
    header("Location: ../login.php");
    exit();
}

try {
    // Get all sessions for the mentee
    $stmt = $conn->prepare("SELECT s.*, m.name as mentor_name, m.expertise, m.rating, m.profile_image 
                           FROM sessions s 
                           INNER JOIN mentors m ON s.mentor_id = m.id 
                           WHERE s.mentee_id = ?
                           ORDER BY s.date_time ASC");
    $stmt->execute([$_SESSION['mentee_id']]);
    $all_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Total sessions found: " . count($all_sessions));
    
    // Separate sessions by status
    $upcoming_sessions = array_filter($all_sessions, function($session) {
        return ($session['status'] === 'accepted' || $session['status'] === 'pending') && 
               strtotime($session['date_time']) > time();
    });
    
    $pending_sessions = array_filter($all_sessions, function($session) {
        return $session['status'] === 'pending';
    });
    
    $completed_sessions = array_filter($all_sessions, function($session) {
        return $session['status'] === 'completed' || 
              ($session['status'] === 'accepted' && strtotime($session['date_time']) < time());
    });
    
    // Convert to array values to reindex arrays
    $upcoming_sessions = array_values($upcoming_sessions);
    $pending_sessions = array_values($pending_sessions);
    $completed_sessions = array_values($completed_sessions);
    
    error_log("Upcoming sessions: " . count($upcoming_sessions));
    error_log("Pending sessions: " . count($pending_sessions));
    error_log("Completed sessions: " . count($completed_sessions));
    
} catch(PDOException $e) {
    error_log("Error in my_sessions.php: " . $e->getMessage());
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header("Location: dashboard.php");
    exit();
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'upcoming';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Sessions - MMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f3f4f6; }
        .session-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }
        .session-card:hover {
            transform: translateY(-2px);
        }
        .mentor-avatar {
            width: 60px;
            height: 60px;
            border-radius: 30px;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-accepted { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-completed { background: #dbeafe; color: #1e40af; }
        .nav-pills .nav-link.active {
            background-color: #6366f1;
        }
        .nav-pills .nav-link {
            color: #6366f1;
            border-radius: 10px;
            padding: 10px 20px;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        .empty-state img {
            width: 200px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <a href="dashboard.php" class="btn btn-link text-decoration-none mb-4">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>

        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-body p-4">
                <h2 class="mb-1">My Sessions</h2>
                <p class="text-muted">Track and manage all your mentoring sessions</p>
            </div>
        </div>

        <ul class="nav nav-pills mb-4">
            <li class="nav-item me-2">
                <a class="nav-link <?php echo $active_tab === 'upcoming' ? 'active' : ''; ?>" 
                   href="?tab=upcoming">
                   <i class="fas fa-calendar me-2"></i>Upcoming (<?php echo count($upcoming_sessions); ?>)
                </a>
            </li>
            <li class="nav-item me-2">
                <a class="nav-link <?php echo $active_tab === 'pending' ? 'active' : ''; ?>" 
                   href="?tab=pending">
                   <i class="fas fa-clock me-2"></i>Pending (<?php echo count($pending_sessions); ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab === 'completed' ? 'active' : ''; ?>" 
                   href="?tab=completed">
                   <i class="fas fa-check-circle me-2"></i>Completed (<?php echo count($completed_sessions); ?>)
                </a>
            </li>
        </ul>

        <?php
        $current_sessions = [];
        switch($active_tab) {
            case 'pending':
                $current_sessions = $pending_sessions;
                $empty_message = "You don't have any pending session requests.";
                break;
            case 'completed':
                $current_sessions = $completed_sessions;
                $empty_message = "You haven't completed any sessions yet.";
                break;
            default:
                $current_sessions = $upcoming_sessions;
                $empty_message = "You don't have any upcoming sessions.";
        }
        ?>

        <?php if (empty($current_sessions)): ?>
            <div class="empty-state">
                <img src="../assets/images/no-sessions.svg" alt="No Sessions" 
                     onerror="this.src='https://ui-avatars.com/api/?background=6366f1&color=fff&text=No+Sessions'">
                <h4><?php echo $empty_message; ?></h4>
                <a href="find_mentor.php" class="btn btn-primary mt-3">Find a Mentor</a>
            </div>
        <?php else: ?>
            <?php foreach ($current_sessions as $session): ?>
                <div class="session-card">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <img src="<?php echo isset($session['profile_image']) ? '../assets/images/mentors/' . $session['profile_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($session['mentor_name']) . '&background=6366f1&color=fff'; ?>" 
                                 alt="Mentor" class="mentor-avatar">
                        </div>
                        <div class="col">
                            <h5 class="mb-1"><?php echo htmlspecialchars($session['title']); ?></h5>
                            <p class="mb-2 text-muted">
                                <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($session['mentor_name']); ?> 
                                <span class="mx-2">•</span> 
                                <i class="fas fa-graduation-cap me-2"></i><?php echo htmlspecialchars($session['expertise']); ?>
                            </p>
                            <p class="mb-0">
                                <i class="far fa-calendar me-2"></i><?php echo date('F j, Y', strtotime($session['date_time'])); ?> 
                                <span class="mx-2">•</span> 
                                <i class="far fa-clock me-2"></i><?php echo date('g:i A', strtotime($session['date_time'])); ?> 
                                (<?php echo $session['duration']; ?> minutes)
                            </p>
                        </div>
                        <div class="col-auto">
                            <span class="status-badge status-<?php echo strtolower($session['status']); ?> mb-2 d-block">
                                <?php echo ucfirst($session['status']); ?>
                            </span>
                            <?php 
                            error_log("Session data in my_sessions.php: " . print_r($session, true));
                            if (isset($session['id'])): 
                            ?>
                                <a href="session_detail.php?id=<?php echo $session['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary"
                                   onclick="console.log('Clicked session ID: <?php echo $session['id']; ?>')">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                            <?php else: ?>
                                <?php error_log("Session ID missing for session: " . print_r($session, true)); ?>
                                <button class="btn btn-sm btn-outline-secondary" disabled>
                                    <i class="fas fa-exclamation-circle me-1"></i>Error: No Session ID
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 