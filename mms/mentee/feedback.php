<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a mentee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentee') {
    header('Location: ../login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: notifications.php');
    exit();
}

$session_id = $_GET['id'];

// Get session details
$stmt = $pdo->prepare("SELECT br.*, u.name as mentor_name, u.email as mentor_email,
                       ts.date, ts.start_time, ts.end_time,
                       p.expertise, p.experience
                       FROM booking_requests br
                       JOIN users u ON br.mentor_id = u.id
                       JOIN profiles p ON u.id = p.user_id
                       JOIN time_slots ts ON br.slot_id = ts.id
                       WHERE br.id = ? AND br.mentee_id = ? AND br.status = 'accepted'");
$stmt->execute([$session_id, $_SESSION['user_id']]);
$session = $stmt->fetch();

if (!$session) {
    header('Location: notifications.php');
    exit();
}

// Check if session is completed
$session_time = strtotime($session['date'] . ' ' . $session['start_time']);
$is_completed = $session_time < time();

// Check if feedback already submitted
$stmt = $pdo->prepare("SELECT * FROM session_feedback WHERE session_id = ?");
$stmt->execute([$session_id]);
$existing_feedback = $stmt->fetch();

if ($existing_feedback) {
    header('Location: session_details.php?id=' . $session_id);
    exit();
}

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $rating = $_POST['rating'] ?? 0;
        $communication = $_POST['communication'] ?? 0;
        $knowledge = $_POST['knowledge'] ?? 0;
        $helpfulness = $_POST['helpfulness'] ?? 0;
        $feedback_text = $_POST['feedback_text'] ?? '';
        $recommend = $_POST['recommend'] ?? 'no';

        $stmt = $pdo->prepare("INSERT INTO session_feedback 
                              (session_id, mentee_id, mentor_id, rating, communication, 
                               knowledge, helpfulness, feedback_text, recommend, created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $session_id,
            $_SESSION['user_id'],
            $session['mentor_id'],
            $rating,
            $communication,
            $knowledge,
            $helpfulness,
            $feedback_text,
            $recommend
        ]);

        // Update mentor's average rating
        $stmt = $pdo->prepare("UPDATE profiles p 
                              SET rating = (
                                  SELECT AVG(rating) 
                                  FROM session_feedback sf 
                                  JOIN booking_requests br ON sf.session_id = br.id 
                                  WHERE br.mentor_id = ?
                              )
                              WHERE p.user_id = ?");
        $stmt->execute([$session['mentor_id'], $session['mentor_id']]);

        header('Location: session_details.php?id=' . $session_id);
        exit();
    } catch (Exception $e) {
        $error = "Failed to submit feedback. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Feedback - Mentor Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: linear-gradient(45deg, #4158D0, #C850C0) !important;
        }

        .feedback-header {
            background: linear-gradient(45deg, #4158D0, #C850C0);
            color: white;
            padding: 50px 0;
            margin-bottom: 40px;
        }

        .feedback-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .rating-stars {
            color: #ffc107;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .rating-stars i {
            transition: transform 0.2s;
        }

        .rating-stars i:hover {
            transform: scale(1.2);
        }

        .rating-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 5px;
        }

        .btn-primary {
            background: linear-gradient(45deg, #4158D0, #C850C0);
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
        }

        .mentor-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .mentor-image {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }

        .feedback-textarea {
            min-height: 150px;
            resize: vertical;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="session_details.php?id=<?php echo $session_id; ?>">
                <i class="fas fa-arrow-left me-2"></i>Back to Session
            </a>
            <span class="navbar-text text-white">
                Session Feedback
            </span>
        </div>
    </nav>

    <div class="feedback-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <h1>Share Your Experience</h1>
                    <p class="lead">Your feedback helps mentors improve and helps other mentees make informed decisions.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="feedback-card">
                    <div class="mentor-info">
                        <img src="../images/default-avatar.png" class="mentor-image" alt="Mentor">
                        <div>
                            <h4 class="mb-1"><?php echo htmlspecialchars($session['mentor_name']); ?></h4>
                            <p class="mb-0 text-muted"><?php echo htmlspecialchars($session['expertise']); ?></p>
                        </div>
                    </div>

                    <form method="POST" id="feedbackForm">
                        <div class="mb-4">
                            <label class="form-label">Overall Rating</label>
                            <div class="rating-stars" data-rating="0">
                                <i class="far fa-star" data-value="1"></i>
                                <i class="far fa-star" data-value="2"></i>
                                <i class="far fa-star" data-value="3"></i>
                                <i class="far fa-star" data-value="4"></i>
                                <i class="far fa-star" data-value="5"></i>
                            </div>
                            <input type="hidden" name="rating" id="rating" value="0">
                            <div class="rating-label">Click to rate</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Communication Skills</label>
                            <div class="rating-stars" data-rating="0">
                                <i class="far fa-star" data-value="1"></i>
                                <i class="far fa-star" data-value="2"></i>
                                <i class="far fa-star" data-value="3"></i>
                                <i class="far fa-star" data-value="4"></i>
                                <i class="far fa-star" data-value="5"></i>
                            </div>
                            <input type="hidden" name="communication" id="communication" value="0">
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Knowledge & Expertise</label>
                            <div class="rating-stars" data-rating="0">
                                <i class="far fa-star" data-value="1"></i>
                                <i class="far fa-star" data-value="2"></i>
                                <i class="far fa-star" data-value="3"></i>
                                <i class="far fa-star" data-value="4"></i>
                                <i class="far fa-star" data-value="5"></i>
                            </div>
                            <input type="hidden" name="knowledge" id="knowledge" value="0">
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Helpfulness</label>
                            <div class="rating-stars" data-rating="0">
                                <i class="far fa-star" data-value="1"></i>
                                <i class="far fa-star" data-value="2"></i>
                                <i class="far fa-star" data-value="3"></i>
                                <i class="far fa-star" data-value="4"></i>
                                <i class="far fa-star" data-value="5"></i>
                            </div>
                            <input type="hidden" name="helpfulness" id="helpfulness" value="0">
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Detailed Feedback</label>
                            <textarea class="form-control feedback-textarea" name="feedback_text" 
                                      placeholder="Share your experience with this session..."></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Would you recommend this mentor?</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="recommend" value="yes" id="recommendYes">
                                <label class="form-check-label" for="recommendYes">Yes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="recommend" value="no" id="recommendNo">
                                <label class="form-check-label" for="recommendNo">No</label>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Submit Feedback
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Star rating functionality
        document.querySelectorAll('.rating-stars').forEach(container => {
            const stars = container.querySelectorAll('i');
            const input = document.getElementById(container.previousElementSibling.getAttribute('for'));

            stars.forEach(star => {
                star.addEventListener('mouseover', () => {
                    const value = star.getAttribute('data-value');
                    stars.forEach(s => {
                        if (s.getAttribute('data-value') <= value) {
                            s.classList.remove('far');
                            s.classList.add('fas');
                        } else {
                            s.classList.remove('fas');
                            s.classList.add('far');
                        }
                    });
                });

                star.addEventListener('click', () => {
                    const value = star.getAttribute('data-value');
                    container.setAttribute('data-rating', value);
                    input.value = value;
                    stars.forEach(s => {
                        if (s.getAttribute('data-value') <= value) {
                            s.classList.remove('far');
                            s.classList.add('fas');
                        } else {
                            s.classList.remove('fas');
                            s.classList.add('far');
                        }
                    });
                });
            });

            container.addEventListener('mouseout', () => {
                const rating = container.getAttribute('data-rating');
                stars.forEach(star => {
                    if (star.getAttribute('data-value') <= rating) {
                        star.classList.remove('far');
                        star.classList.add('fas');
                    } else {
                        star.classList.remove('fas');
                        star.classList.add('far');
                    }
                });
            });
        });

        // Form validation
        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            const rating = document.getElementById('rating').value;
            if (rating === '0') {
                e.preventDefault();
                alert('Please provide an overall rating');
            }
        });
    </script>
</body>
</html> 