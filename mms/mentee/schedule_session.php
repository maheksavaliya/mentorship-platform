<?php
session_start();
require_once '../config/db.php';

// Initialize variables
$mentors = [];
$error = null;
$success = null;

// Get all mentors
try {
    $stmt = $conn->prepare("SELECT m.*, 
                           GROUP_CONCAT(DISTINCT e.name) as expertise
                           FROM mentors m
                           LEFT JOIN mentor_expertise me ON m.id = me.mentor_id
                           LEFT JOIN expertise e ON me.expertise_id = e.id
                           GROUP BY m.id");
    $stmt->execute();
    $mentors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add default values for missing data
    foreach ($mentors as &$mentor) {
        $mentor['rating'] = $mentor['rating'] ?? 4.5;
        $mentor['hourly_rate'] = $mentor['hourly_rate'] ?? 1000;
        $mentor['expertise'] = $mentor['expertise'] ?? 'General Mentoring';
    }
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $error = "An error occurred while fetching mentors. Please try again.";
}

// Handle session scheduling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['mentee_id'])) {
    $mentor_id = $_POST['mentor_id'] ?? null;
    $date_time = $_POST['date_time'] ?? null;
    $duration = $_POST['duration'] ?? 60;
    $topic = $_POST['topic'] ?? '';
    $description = $_POST['description'] ?? '';

    if ($mentor_id && $date_time) {
        try {
            $stmt = $conn->prepare("INSERT INTO sessions (mentor_id, mentee_id, date_time, duration, topic, description, status) 
                                  VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$mentor_id, $_SESSION['mentee_id'], $date_time, $duration, $topic, $description]);
            $success = "Session scheduled successfully! Waiting for mentor's confirmation.";
        } catch(PDOException $e) {
            error_log("Error scheduling session: " . $e->getMessage());
            $error = "Failed to schedule session. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Session - MMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #6366f1;
            --accent-color: #818cf8;
            --background-color: #f8fafc;
            --text-color: #1f2937;
            --card-shadow: 0 10px 20px rgba(99, 102, 241, 0.1);
            --hover-shadow: 0 20px 40px rgba(99, 102, 241, 0.2);
            --border-radius: 20px;
        }

        body {
            background: var(--background-color);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding: 30px;
            color: var(--text-color);
        }

        .schedule-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 15px 25px;
            background: white;
            color: var(--text-color);
            border: none;
            border-radius: 15px;
            margin-bottom: 30px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(99, 102, 241, 0.1);
        }

        .btn-back:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(-5px);
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 40px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .schedule-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(99, 102, 241, 0.1);
        }

        .schedule-card:hover {
            box-shadow: var(--hover-shadow);
            transform: translateY(-5px);
        }

        .schedule-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 30px;
            color: white;
        }

        .schedule-header h4 {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
        }

        .schedule-body {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 30px;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 15px;
            display: block;
            color: var(--text-color);
            font-size: 1.1rem;
        }

        .form-control {
            padding: 15px 20px;
            border-radius: 12px;
            border: 2px solid rgba(99, 102, 241, 0.2);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .duration-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .duration-option {
            background: white;
            border: 2px solid rgba(99, 102, 241, 0.2);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .duration-option:hover {
            border-color: var(--primary-color);
            transform: translateY(-3px);
        }

        .duration-option.selected {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-color: transparent;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 15px 30px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }

        .btn-submit:hover::before {
            left: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }

        .mentor-select {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .mentor-option {
            background: white;
            border: 2px solid rgba(99, 102, 241, 0.2);
            border-radius: 15px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .mentor-option:hover {
            border-color: var(--primary-color);
            transform: translateY(-3px);
        }

        .mentor-option.selected {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-color: transparent;
        }

        .mentor-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .mentor-avatar {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            object-fit: cover;
        }

        .mentor-details h5 {
            margin: 0 0 5px;
            font-size: 1.1rem;
        }

        .rating {
            color: #fbbf24;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .expertise-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .expertise-tag {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary-color);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .selected .expertise-tag {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .schedule-card {
            animation: fadeInUp 0.6s ease forwards;
        }
    </style>
</head>
<body>
    <div class="schedule-container">
        <a href="dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>

        <h1 class="section-title">Schedule a Session</h1>

        <div class="schedule-card">
            <div class="schedule-header">
                <h4>Book Your Mentoring Session</h4>
            </div>
            <div class="schedule-body">
                <form id="scheduleForm" action="process_session.php" method="POST">
                    <div class="form-group">
                        <label class="form-label">Select a Mentor</label>
                        <div class="mentor-select">
                            <?php foreach ($mentors as $mentor): ?>
                                <div class="mentor-option" data-mentor-id="<?php echo $mentor['id']; ?>">
                                    <div class="mentor-info">
                                        <img src="<?php echo isset($mentor['profile_image']) ? '../assets/images/mentors/' . $mentor['profile_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($mentor['name']) . '&background=random'; ?>" 
                                             alt="<?php echo htmlspecialchars($mentor['name']); ?>" 
                                             class="mentor-avatar">
                                        <div class="mentor-details">
                                            <h5><?php echo htmlspecialchars($mentor['name']); ?></h5>
                                            <div class="rating">
                                                <i class="fas fa-star"></i>
                                                <?php echo number_format($mentor['rating'], 1); ?>
                                            </div>
                                            <p>₹<?php echo $mentor['hourly_rate']; ?>/hour</p>
                                        </div>
                                    </div>
                                    <div class="expertise-tags">
                                        <?php 
                                        $expertise_array = explode(',', $mentor['expertise']);
                                        foreach ($expertise_array as $expertise): ?>
                                            <span class="expertise-tag"><?php echo trim(htmlspecialchars($expertise)); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="mentor_id" id="selectedMentor">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Select Date and Time</label>
                        <input type="text" class="form-control" id="dateTimePicker" name="date_time" placeholder="Pick date and time" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Select Duration</label>
                        <div class="duration-options">
                            <div class="duration-option" data-duration="30">30 mins</div>
                            <div class="duration-option" data-duration="60">1 hour</div>
                            <div class="duration-option" data-duration="90">1.5 hours</div>
                            <div class="duration-option" data-duration="120">2 hours</div>
                        </div>
                        <input type="hidden" name="duration" id="selectedDuration">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Session Description</label>
                        <textarea class="form-control" name="description" rows="5" placeholder="Describe what you'd like to discuss in this session" required></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Schedule Session</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize date picker
        flatpickr("#dateTimePicker", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
            time_24hr: true
        });

        // Handle mentor selection
        document.querySelectorAll('.mentor-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.mentor-option').forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('selectedMentor').value = this.dataset.mentorId;
            });
        });

        // Handle duration selection
        document.querySelectorAll('.duration-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.duration-option').forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('selectedDuration').value = this.dataset.duration;
            });
        });

        // Form validation
        document.getElementById('scheduleForm').addEventListener('submit', function(e) {
            if (!document.getElementById('selectedMentor').value) {
                e.preventDefault();
                alert('Please select a mentor');
            }
            if (!document.getElementById('selectedDuration').value) {
                e.preventDefault();
                alert('Please select a duration');
            }
        });
    </script>
</body>
</html> 