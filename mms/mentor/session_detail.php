<?php
session_start();
require_once '../config/db.php';

// Debug logging
error_log("=== Mentor Session Detail Debug Start ===");
error_log("SESSION data: " . print_r($_SESSION, true));
error_log("GET data: " . print_r($_GET, true));

// Check if user is logged in as mentor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    error_log("No mentor access");
    $_SESSION['error'] = "Please login as a mentor to continue.";
    header("Location: ../login.php");
    exit();
}

// Check if session ID is provided
if (!isset($_GET['id'])) {
    error_log("No session ID provided");
    $_SESSION['error'] = "Session ID not provided";
    header("Location: my_sessions.php");
    exit();
}

try {
    // Get session details with mentee information
    $stmt = $conn->prepare("SELECT s.*, me.name as mentee_name, me.email as mentee_email, me.bio as mentee_bio 
                           FROM sessions s 
                           LEFT JOIN mentees me ON s.mentee_id = me.id 
                           WHERE s.id = ? AND s.mentor_id = ?");
    
    error_log("Executing query with session_id=" . $_GET['id'] . " and mentor_id=" . $_SESSION['user_id']);
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Query result: " . ($session ? "Session found" : "No session found"));
    if ($session) {
        error_log("Session details: " . print_r($session, true));
    }

    if (!$session) {
        error_log("Session not found or unauthorized access");
        $_SESSION['error'] = "Session not found or you don't have permission to view it.";
        header("Location: my_sessions.php");
        exit();
    }

} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $_SESSION['error'] = "An error occurred while fetching session details.";
    header("Location: my_sessions.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Details - MMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f3f4f6;
            font-family: 'Inter', sans-serif;
        }
        .session-detail-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-top: 20px;
        }
        .mentee-profile {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        .mentee-avatar {
            width: 120px;
            height: 120px;
            border-radius: 60px;
            margin-bottom: 15px;
        }
        .session-info {
            margin-bottom: 30px;
        }
        .session-meta {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            color: #6b7280;
        }
        .session-meta i {
            width: 25px;
            margin-right: 10px;
            color: #6366f1;
        }
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .status-accepted { background: #dcfce7; color: #166534; }
        .status-completed { background: #dbeafe; color: #1e40af; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .btn-accept {
            background-color: #22c55e;
            border-color: #22c55e;
            color: white;
        }
        .btn-accept:hover {
            background-color: #16a34a;
            border-color: #16a34a;
            color: white;
        }
        .btn-reject {
            background-color: #ef4444;
            border-color: #ef4444;
            color: white;
        }
        .btn-reject:hover {
            background-color: #dc2626;
            border-color: #dc2626;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="my_sessions.php" class="btn btn-link text-decoration-none">
                <i class="fas fa-arrow-left me-2"></i>Back to My Sessions
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="session-detail-card">
            <div class="row">
                <div class="col-md-4">
                    <div class="mentee-profile">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($session['mentee_name']); ?>&background=6366f1&color=fff" 
                             alt="<?php echo htmlspecialchars($session['mentee_name']); ?>" 
                             class="mentee-avatar">
                        <h4 class="mt-3"><?php echo htmlspecialchars($session['mentee_name']); ?></h4>
                        <p class="text-muted mb-2"><?php echo htmlspecialchars($session['mentee_email']); ?></p>
                        <?php if (!empty($session['mentee_bio'])): ?>
                            <p class="mt-3"><?php echo htmlspecialchars($session['mentee_bio']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="session-info">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <h3><?php echo htmlspecialchars($session['title'] ?? 'Mentoring Session'); ?></h3>
                            <span class="status-badge status-<?php echo strtolower($session['status']); ?>">
                                <?php echo ucfirst($session['status']); ?>
                            </span>
                        </div>
                        
                        <div class="session-meta">
                            <i class="fas fa-calendar"></i>
                            <span><?php echo date('F j, Y', strtotime($session['date_time'])); ?></span>
                        </div>
                        
                        <div class="session-meta">
                            <i class="fas fa-clock"></i>
                            <span><?php echo date('g:i A', strtotime($session['date_time'])); ?> 
                                  (<?php echo $session['duration']; ?> minutes)</span>
                        </div>
                        
                        <?php if (!empty($session['description'])): ?>
                            <div class="session-meta">
                                <i class="fas fa-info-circle"></i>
                                <span><?php echo htmlspecialchars($session['description']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($session['status'] === 'pending'): ?>
                            <div class="mt-4">
                                <form action="update_session.php" method="POST" class="d-inline-block me-2">
                                    <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="btn btn-accept">
                                        <i class="fas fa-check me-2"></i>Accept Session
                                    </button>
                                </form>
                                <form action="update_session.php" method="POST" class="d-inline-block">
                                    <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-reject">
                                        <i class="fas fa-times me-2"></i>Reject Session
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($session['status'] === 'accepted' && !empty($session['meet_link'])): ?>
                            <div class="session-meta">
                                <i class="fas fa-video"></i>
                                <a href="<?php echo htmlspecialchars($session['meet_link']); ?>" 
                                   target="_blank" 
                                   class="btn btn-primary mt-3">
                                    <i class="fas fa-video me-2"></i>Join Meeting
                                </a>
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