<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is a mentor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../login.php");
    exit();
}

// Set mentor_id from user_id for consistency
$_SESSION['mentor_id'] = $_SESSION['user_id'];

// Get mentor's information and mentees
try {
    $stmt = $conn->prepare("SELECT u.*, m.* 
                           FROM users u 
                           JOIN mentors m ON u.id = m.user_id 
                           WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $mentor = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get all mentees with their session stats
    $stmt = $conn->prepare("SELECT u.*, m.bio, m.expertise,
                           COUNT(s.id) as total_sessions,
                           SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) as completed_sessions,
                           AVG(sf.rating) as avg_rating
                           FROM users u 
                           JOIN mentees m ON u.id = m.user_id
                           JOIN sessions s ON m.id = s.mentee_id
                           LEFT JOIN session_feedback sf ON s.id = sf.session_id
                           WHERE s.mentor_id = ?
                           GROUP BY u.id");
    $stmt->execute([$mentor['id']]);
    $mentees = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $error = "An error occurred. Please try again.";
}

// Add some dummy data if needed
if (empty($mentees)) {
    $mentees = [
        [
            'id' => 1,
            'name' => 'Rahul Kumar',
            'email' => 'rahul@example.com',
            'phone' => '9876543210',
            'profile_image' => 'mentee1.jpg',
            'bio' => 'Aspiring web developer with a passion for frontend technologies.',
            'expertise' => 'Web Development,JavaScript',
            'total_sessions' => 15,
            'completed_sessions' => 12,
            'avg_rating' => 4.8
        ],
        [
            'id' => 2,
            'name' => 'Priya Sharma',
            'email' => 'priya@example.com',
            'phone' => '9876543211',
            'profile_image' => 'mentee2.jpg',
            'bio' => 'Data science enthusiast learning machine learning and AI.',
            'expertise' => 'Data Science,Python',
            'total_sessions' => 8,
            'completed_sessions' => 6,
            'avg_rating' => 4.9
        ],
        [
            'id' => 3,
            'name' => 'Mahek Savaliya',
            'email' => 'maheksavaliya2@gmail.com',
            'phone' => '9876543212',
            'profile_image' => 'mentee3.jpg',
            'bio' => 'Full stack developer focusing on PHP and MySQL development.',
            'expertise' => 'Web Development,PHP,MySQL',
            'total_sessions' => 10,
            'completed_sessions' => 8,
            'avg_rating' => 4.7
        ]
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Mentees - MMS</title>
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

        .mentees-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }

        .page-header {
            background: var(--primary-gradient);
            border-radius: 20px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .page-header::before {
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

        .page-header:hover::before {
            transform: translateX(100%);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background: white;
            color: var(--text-color);
            border: none;
            border-radius: 15px;
            margin-bottom: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: var(--card-shadow);
        }

        .btn-back:hover {
            background: var(--text-color);
            color: white;
            transform: translateX(-5px);
        }

        .mentee-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 30px;
            transition: all 0.3s ease;
            box-shadow: var(--card-shadow);
        }

        .mentee-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .mentee-header {
            background: var(--primary-gradient);
            padding: 25px;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .mentee-header::before {
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

        .mentee-card:hover .mentee-header::before {
            transform: translateX(100%);
        }

        .mentee-avatar {
            width: 100px;
            height: 100px;
            border-radius: 20px;
            object-fit: cover;
            border: 4px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .mentee-card:hover .mentee-avatar {
            transform: scale(1.1) rotate(5deg);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .mentee-info h4 {
            margin: 15px 0 5px;
            font-size: 1.5rem;
        }

        .mentee-body {
            padding: 25px;
        }

        .mentee-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-item {
            background: #f9fafb;
            padding: 15px;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            background: var(--primary-gradient);
            transform: translateY(-5px);
        }

        .stat-item:hover .stat-value,
        .stat-item:hover .stat-label {
            color: white;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #6366f1;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6b7280;
            transition: all 0.3s ease;
        }

        .expertise-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
        }

        .expertise-tag {
            padding: 8px 16px;
            background: #f3f4f6;
            color: #6366f1;
            border-radius: 30px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .expertise-tag:hover {
            background: var(--primary-gradient);
            color: white;
            transform: translateY(-2px);
        }

        .mentee-contact {
            display: flex;
            align-items: center;
            color: #6b7280;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        .mentee-contact i {
            width: 20px;
            margin-right: 10px;
            color: #6366f1;
        }

        .btn-custom {
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 25px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
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
            color: white;
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3);
        }

        .rating {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #f59e0b;
            font-size: 1.1rem;
            margin-top: 10px;
        }

        .rating i {
            color: #f59e0b;
        }

        .search-box {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
        }

        .search-input {
            width: 100%;
            padding: 15px 20px 15px 45px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .search-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
            background: white;
        }

        .search-icon {
            position: absolute;
            left: 35px;
            top: 50%;
            transform: translateY(-50%);
            color: #6366f1;
            font-size: 1.2rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease forwards;
        }

        @media (max-width: 768px) {
            .mentee-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="mentees-container">
        <a href="dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>

        <div class="page-header">
            <h2 class="mb-3">My Mentees</h2>
            <p class="mb-0">View and manage your mentee relationships</p>
        </div>

        <div class="search-box position-relative">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchMentee" class="search-input" placeholder="Search mentees by name or expertise...">
        </div>

        <div class="row" id="menteesGrid">
            <?php foreach ($mentees as $mentee): ?>
                <div class="col-lg-6 animate-fade-in mentee-item" 
                     data-name="<?php echo strtolower($mentee['name']); ?>"
                     data-expertise="<?php echo strtolower($mentee['expertise']); ?>">
                    <div class="mentee-card">
                        <div class="mentee-header">
                            <div class="d-flex align-items-center gap-4">
                                <img src="<?php echo isset($mentee['profile_image']) ? '../assets/images/mentees/' . $mentee['profile_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($mentee['name']) . '&background=random&color=fff&size=200'; ?>" 
                                     alt="<?php echo htmlspecialchars($mentee['name']); ?>" 
                                     class="mentee-avatar">
                                <div class="mentee-info">
                                    <h4><?php echo htmlspecialchars($mentee['name']); ?></h4>
                                    <div class="rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i <= round($mentee['avg_rating']) ? '' : '-o'; ?>"></i>
                                        <?php endfor; ?>
                                        <span class="text-white ms-2"><?php echo number_format($mentee['avg_rating'], 1); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mentee-body">
                            <div class="mentee-stats">
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $mentee['total_sessions']; ?></div>
                                    <div class="stat-label">Total Sessions</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $mentee['completed_sessions']; ?></div>
                                    <div class="stat-label">Completed</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo round(($mentee['completed_sessions'] / $mentee['total_sessions']) * 100); ?>%</div>
                                    <div class="stat-label">Completion</div>
                                </div>
                            </div>

                            <div class="mentee-contact">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo htmlspecialchars($mentee['email']); ?></span>
                            </div>
                            <div class="mentee-contact">
                                <i class="fas fa-phone"></i>
                                <span><?php echo htmlspecialchars($mentee['phone']); ?></span>
                            </div>

                            <p class="mt-3 mb-3"><?php echo htmlspecialchars($mentee['bio']); ?></p>

                            <div class="expertise-tags">
                                <?php foreach (explode(',', $mentee['expertise']) as $expertise): ?>
                                    <span class="expertise-tag"><?php echo trim(htmlspecialchars($expertise)); ?></span>
                                <?php endforeach; ?>
                            </div>

                            <div class="d-flex gap-3">
                                <a href="schedule_session.php?mentee_id=<?php echo $mentee['id']; ?>" class="btn btn-custom">
                                    <i class="fas fa-calendar-plus"></i>
                                    Schedule Session
                                </a>
                                <?php
                                $message_url = "send_message.php?mentee_id=" . $mentee['id'];
                                ?>
                                <a href="<?php echo $message_url; ?>" class="btn btn-custom" onclick="location.href='<?php echo $message_url; ?>'; return false;">
                                    <i class="fas fa-comment"></i>
                                    Send Message
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        const searchInput = document.getElementById('searchMentee');
        const menteeItems = document.querySelectorAll('.mentee-item');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();

            menteeItems.forEach(item => {
                const name = item.dataset.name;
                const expertise = item.dataset.expertise;
                const isVisible = name.includes(searchTerm) || expertise.includes(searchTerm);

                if (isVisible) {
                    item.style.display = 'block';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                } else {
                    item.style.opacity = '0';
                    item.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        item.style.display = 'none';
                    }, 300);
                }
            });
        });

        // Add hover effects for cards
        document.querySelectorAll('.mentee-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-5px)';
                card.style.boxShadow = '0 15px 30px rgba(99, 102, 241, 0.2)';
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
                card.style.boxShadow = '0 10px 25px rgba(99, 102, 241, 0.1)';
            });
        });

        // Add animation delay for cards
        document.querySelectorAll('.animate-fade-in').forEach((item, index) => {
            item.style.animationDelay = `${index * 0.1}s`;
        });
    </script>
</body>
</html> 