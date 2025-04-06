<?php
session_start();
require_once '../config/db.php';

// Debug logging
error_log("=== Session Detail Debug Start ===");
error_log("SESSION data: " . print_r($_SESSION, true));
error_log("GET data: " . print_r($_GET, true));

// Check if user is logged in as mentee
if (!isset($_SESSION['mentee_id']) || $_SESSION['role'] !== 'mentee') {
    error_log("No mentee_id found in session or role is not mentee");
    $_SESSION['error'] = "Please login as a mentee to continue.";
    header("Location: ../login.php");
    exit();
}

// Check if session ID is provided
if (!isset($_GET['id'])) {
    error_log("No session ID provided in GET parameters");
    $_SESSION['error'] = "Session ID not provided";
    header("Location: dashboard.php");
    exit();
}

try {
    // Get session details with mentor information
    $stmt = $conn->prepare("SELECT s.*, m.name as mentor_name, m.email as mentor_email, m.bio as mentor_bio, 
                                  m.expertise as mentor_expertise, m.profile_image as mentor_image
                           FROM sessions s 
                           LEFT JOIN mentors m ON s.mentor_id = m.id 
                           WHERE s.id = ? AND s.mentee_id = ?");
    
    error_log("Executing query with session_id=" . $_GET['id'] . " and mentee_id=" . $_SESSION['mentee_id']);
    $stmt->execute([$_GET['id'], $_SESSION['mentee_id']]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Query result: " . ($session ? "Session found" : "No session found"));
    if ($session) {
        error_log("Session details: " . print_r($session, true));
    }

    if (!$session) {
        error_log("Session not found or you don't have permission to view it.");
        $_SESSION['error'] = "Session not found or you don't have permission to view it.";
        header("Location: dashboard.php");
        exit();
    }

} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while fetching session details.";
    header("Location: dashboard.php");
    exit();
}

error_log("=== Session Detail Debug End ===");
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
        .mentor-profile {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        .mentor-avatar {
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
        .expertise-tag {
            display: inline-block;
            padding: 4px 12px;
            background: #f3f4f6;
            color: #6b7280;
            border-radius: 15px;
            font-size: 0.9rem;
            margin: 2px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="dashboard.php" class="btn btn-link text-decoration-none">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="session-detail-card">
            <div class="row">
                <div class="col-md-4">
                    <div class="mentor-profile">
                        <img src="<?php echo isset($session['mentor_image']) ? '../uploads/mentor/' . $session['mentor_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($session['mentor_name']) . '&background=6366f1&color=fff'; ?>" 
                             alt="<?php echo htmlspecialchars($session['mentor_name']); ?>" 
                             class="mentor-avatar">
                        <h4 class="mt-3"><?php echo htmlspecialchars($session['mentor_name']); ?></h4>
                        <p class="text-muted mb-2"><?php echo htmlspecialchars($session['mentor_email']); ?></p>
                        <?php if (!empty($session['mentor_expertise'])): ?>
                            <div class="mt-3">
                                <?php 
                                $expertise_areas = explode(',', $session['mentor_expertise']);
                                foreach($expertise_areas as $area): ?>
                                    <span class="expertise-tag"><?php echo htmlspecialchars(trim($area)); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($session['mentor_bio'])): ?>
                            <p class="mt-3"><?php echo htmlspecialchars($session['mentor_bio']); ?></p>
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