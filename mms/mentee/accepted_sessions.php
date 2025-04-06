<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in as mentee
if (!isset($_SESSION['mentee_id'])) {
    header("Location: ../login.php");
    exit();
}

try {
    // Get accepted sessions with mentor details
    $stmt = $conn->prepare("
        SELECT s.*, m.name as mentor_name, m.expertise, m.rating, m.profile_image,
               CASE 
                   WHEN s.date_time >= CURRENT_TIMESTAMP THEN 'upcoming'
                   ELSE 'completed'
               END as session_status
        FROM sessions s 
        INNER JOIN mentors m ON s.mentor_id = m.id 
        WHERE s.mentee_id = ? AND s.status = 'accepted'
        ORDER BY s.date_time DESC
    ");
    
    $stmt->execute([$_SESSION['mentee_id']]);
    $accepted_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Error fetching accepted sessions: " . $e->getMessage());
    $error = "An error occurred while fetching sessions.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accepted Sessions - Mentor Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .session-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }
        .session-card:hover {
            transform: translateY(-5px);
        }
        .mentor-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        .session-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-upcoming {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        .status-completed {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .meet-link {
            color: #1976d2;
            text-decoration: none;
        }
        .meet-link:hover {
            text-decoration: underline;
        }
        .back-btn {
            background: linear-gradient(45deg, #4158D0, #C850C0);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .back-btn:hover {
            transform: translateX(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <a href="dashboard.php" class="back-btn mb-4">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>

        <div class="card border-0 rounded-4 bg-primary text-white mb-4">
            <div class="card-body p-4">
                <h2 class="mb-2">Accepted Sessions</h2>
                <p class="mb-0">View all your confirmed mentoring sessions</p>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (empty($accepted_sessions)): ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                <h4>No Accepted Sessions</h4>
                <p class="text-muted">You don't have any accepted sessions yet.</p>
                <a href="find_mentor.php" class="btn btn-primary">Find a Mentor</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($accepted_sessions as $session): ?>
                    <div class="col-md-6 mb-4">
                        <div class="session-card p-4">
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo isset($session['profile_image']) ? '../assets/images/mentors/' . $session['profile_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($session['mentor_name']) . '&background=6366f1&color=fff'; ?>" 
                                     alt="Mentor" class="mentor-avatar">
                                <div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($session['mentor_name']); ?></h5>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($session['expertise']); ?></p>
                                </div>
                            </div>

                            <div class="mb-3">
                                <h6 class="mb-2"><?php echo htmlspecialchars($session['title']); ?></h6>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($session['description']); ?></p>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <p class="mb-1">
                                        <i class="far fa-calendar me-2"></i>
                                        <?php echo date('M d, Y', strtotime($session['date_time'])); ?>
                                    </p>
                                    <p class="mb-0">
                                        <i class="far fa-clock me-2"></i>
                                        <?php echo date('h:i A', strtotime($session['date_time'])); ?>
                                    </p>
                                </div>
                                <span class="session-status <?php echo $session['session_status'] === 'upcoming' ? 'status-upcoming' : 'status-completed'; ?>">
                                    <?php echo ucfirst($session['session_status']); ?>
                                </span>
                            </div>

                            <?php if ($session['session_status'] === 'upcoming' && !empty($session['meet_link'])): ?>
                                <div class="text-center">
                                    <a href="<?php echo htmlspecialchars($session['meet_link']); ?>" 
                                       target="_blank" 
                                       class="btn btn-primary w-100 meet-link">
                                        <i class="fas fa-video me-2"></i>Join Meeting
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh the page every 30 seconds to check for new sessions
        setTimeout(function() {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html> 