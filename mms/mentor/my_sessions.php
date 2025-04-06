<?php
session_start();
require_once '../config/db.php';

error_log("Session data: " . print_r($_SESSION, true));

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    error_log("Access denied: user_id=" . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set') . 
              ", role=" . (isset($_SESSION['role']) ? $_SESSION['role'] : 'not set'));
    header("Location: ../login.php");
    exit();
}

try {
    // First, get the mentor's ID
    $stmt = $conn->prepare("SELECT id FROM mentors WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $mentor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mentor) {
        error_log("Mentor not found for user_id: " . $_SESSION['user_id']);
        header("Location: ../login.php");
        exit();
    }
    
    error_log("Fetching sessions for mentor_id: " . $mentor['id']);
    
    // Get all sessions for the mentor
    $stmt = $conn->prepare("SELECT s.*, me.name as mentee_name, me.email as mentee_email 
                           FROM sessions s 
                           INNER JOIN mentees me ON s.mentee_id = me.id 
                           WHERE s.mentor_id = ?
                           ORDER BY s.date_time DESC");
    $stmt->execute([$mentor['id']]);
    $all_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found " . count($all_sessions) . " total sessions");
    error_log("Sessions data: " . print_r($all_sessions, true));
    
    // Separate sessions by status
    $upcoming_sessions = array_filter($all_sessions, function($session) {
        return ($session['status'] === 'accepted' && strtotime($session['date_time']) > time());
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

    error_log("After filtering - Upcoming: " . count($upcoming_sessions) . ", Pending: " . count($pending_sessions) . ", Completed: " . count($completed_sessions));
    
} catch(PDOException $e) {
    error_log("My Sessions Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
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
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 15px;
        }

        .header-section {
            background-color: white;
            padding: 20px 0;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .back-link {
            color: #6366f1;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            font-weight: 500;
        }

        .back-link:hover {
            color: #4f46e5;
        }

        .page-title {
            font-size: 28px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 8px;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 16px;
        }

        .nav-pills {
            margin-bottom: 30px;
            gap: 10px;
            background: white;
            padding: 10px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .nav-pills .nav-link {
            color: #6b7280;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
        }

        .nav-pills .nav-link:not(.active):hover {
            background-color: #f3f4f6;
        }

        .session-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .session-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .mentor-avatar {
            width: 48px;
            height: 48px;
            border-radius: 24px;
            object-fit: cover;
        }

        .session-title {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 8px;
        }

        .session-info {
            color: #6b7280;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .session-meta {
            color: #6b7280;
            font-size: 14px;
            margin-top: 12px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }

        .status-accepted {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-completed {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .btn-view-details {
            color: #6366f1;
            font-weight: 500;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 6px;
            transition: background-color 0.2s;
        }

        .btn-view-details:hover {
            background-color: #f3f4f6;
            color: #4f46e5;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3);
        }

        .btn-primary i {
            margin-right: 6px;
        }

        .btn-success {
            background-color: #22c55e;
            border-color: #22c55e;
        }

        .btn-danger {
            background-color: #ef4444;
            border-color: #ef4444;
        }
    </style>
</head>
<body>
    <div class="header-section">
        <div class="container">
            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
            <h1 class="page-title mt-4">My Sessions</h1>
            <p class="page-subtitle">Track and manage all your mentoring sessions</p>
        </div>
    </div>

    <div class="container">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab === 'upcoming' ? 'active' : ''; ?>" href="?tab=upcoming">
                    <i class="fas fa-calendar-alt"></i>
                    Upcoming (<?php echo count($upcoming_sessions); ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab === 'pending' ? 'active' : ''; ?>" href="?tab=pending">
                    <i class="fas fa-clock"></i>
                    Pending (<?php echo count($pending_sessions); ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab === 'completed' ? 'active' : ''; ?>" href="?tab=completed">
                    <i class="fas fa-check-circle"></i>
                    Completed (<?php echo count($completed_sessions); ?>)
                </a>
            </li>
        </ul>

        <?php
        $current_sessions = [];
        switch($active_tab) {
            case 'pending':
                $current_sessions = $pending_sessions;
                break;
            case 'completed':
                $current_sessions = $completed_sessions;
                break;
            default:
                $current_sessions = $upcoming_sessions;
        }

        if (empty($current_sessions)): ?>
            <div class="text-center py-5">
                <img src="../assets/images/no-sessions.svg" alt="No Sessions" class="mb-4" style="width: 200px; opacity: 0.6;">
                <h4 class="text-muted">No <?php echo $active_tab; ?> sessions found</h4>
            </div>
        <?php else:
            // Debug output
            error_log("Current tab: " . $active_tab);
            error_log("Number of sessions: " . count($current_sessions));
            foreach ($current_sessions as $session): 
                error_log("Session ID: " . $session['id'] . ", Title: " . $session['title']);
        ?>
                <div class="session-card">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <img src="<?php echo isset($session['mentee_image']) ? '../uploads/mentee/' . $session['mentee_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($session['mentee_name']) . '&background=6366f1&color=fff'; ?>" 
                                 alt="<?php echo htmlspecialchars($session['mentee_name']); ?>" 
                                 class="mentor-avatar">
                        </div>
                        <div class="col">
                            <h3 class="session-title"><?php echo htmlspecialchars($session['title']); ?></h3>
                            <div class="session-info">
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($session['mentee_name']); ?>
                                <span class="mx-2">•</span>
                                <i class="fas fa-graduation-cap"></i>
                                mobile development etc
                            </div>
                            <div class="session-meta">
                                <i class="far fa-calendar"></i>
                                <?php echo date('F j, Y', strtotime($session['date_time'])); ?>
                                <span class="mx-2">•</span>
                                <i class="far fa-clock"></i>
                                <?php echo date('g:i A', strtotime($session['date_time'])); ?> 
                                (<?php echo $session['duration']; ?> minutes)
                            </div>
                        </div>
                        <div class="col-auto">
                            <span class="status-badge status-<?php echo strtolower($session['status']); ?> mb-2 d-block">
                                <?php echo ucfirst($session['status']); ?>
                            </span>
                            <?php if ($session['status'] === 'pending'): ?>
                                <form method="POST" action="update_session.php" class="mb-2">
                                    <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                                    <button type="submit" name="action" value="accept" class="btn btn-sm btn-success me-1" onclick="return confirm('Are you sure you want to accept this session?');">
                                        <i class="fas fa-check me-1"></i>Accept
                                    </button>
                                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to reject this session?');">
                                        <i class="fas fa-times me-1"></i>Reject
                                    </button>
                                </form>
                            <?php endif; ?>
                            <a href="session_detail.php?id=<?php echo $session['id']; ?>" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach;
        endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 