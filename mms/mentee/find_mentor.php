<?php
session_start();
require_once '../config/db.php';

// Get mentors from database
try {
    // Simplified query to avoid table not found error
    $stmt = $conn->prepare("SELECT * FROM mentors");
    $stmt->execute();
    $mentors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If no mentors found, add dummy data
    if (empty($mentors)) {
        $mentors = [
            [
                'id' => 1,
                'name' => 'Manender Dutt',
                'expertise' => 'Web Development,React,Node.js',
                'rating' => 4.9,
                'hourly_rate' => 2000,
                'bio' => 'Senior Web Developer with 8+ years of experience',
                'availability' => 'Available Now'
            ],
            [
                'id' => 2,
                'name' => 'Priya Sharma',
                'expertise' => 'Data Science,Machine Learning',
                'rating' => 4.8,
                'hourly_rate' => 2500,
                'bio' => 'Data Scientist at Google with PhD in ML',
                'availability' => 'This Week'
            ],
            [
                'id' => 3,
                'name' => 'Rahul Verma',
                'expertise' => 'Mobile Development,Flutter,React Native',
                'rating' => 4.7,
                'hourly_rate' => 1800,
                'bio' => 'Mobile App Developer with 5+ years of experience',
                'availability' => 'Available Now'
            ]
        ];
    }
} catch(PDOException $e) {
    $mentors = []; // Set empty array in case of error
    error_log("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find a Mentor - MMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color:rgb(70, 97, 174);
            --secondary-color: #283593;
            --accent-color: #3949ab;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
            --background-color: #f5f6fa;
            --card-bg: #ffffff;
            --text-color: #2c3e50;
            --text-light: #7f8c8d;
            --border-radius: 20px;
            --transition: all 0.3s ease;
        }

        body {
            background: var(--background-color);
            font-family: 'Inter', sans-serif;
            color: var(--text-color);
            min-height: 100vh;
            margin: 0;
            padding: 2rem;
        }

        .container-fluid {
            padding: 2rem;
        }

        .sidebar {
            background: var(--primary-color);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            height: calc(100vh - 4rem);
            position: sticky;
            top: 2rem;
            overflow-y: auto;
            color: white;
        }

        .main-content {
            padding-left: 2rem;
        }

        .search-container {
            margin-bottom: 2rem;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1.5rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: calc(var(--border-radius) - 4px);
            font-size: 1.1rem;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .filter-section {
            margin-bottom: 2rem;
        }

        .filter-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .filter-options {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-chip {
            width: 100%;
            text-align: left;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            margin: 0;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid transparent;
            color: white;
            transition: var(--transition);
        }

        .filter-chip:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }

        .filter-chip.active {
            background: var(--accent-color);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .mentors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .mentor-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
            position: relative;
            border: none;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
            height: 400px;
        }

        .mentor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .mentor-header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            padding: 1.5rem;
            color: white;
            position: relative;
            height: 200px;
        }

        .mentor-avatar {
            width: 70px;
            height: 70px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            border: 4px solid rgba(255, 255, 255, 0.3);
            transition: var(--transition);
            margin: 0 auto 1rem;
        }

        .mentor-name {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
            text-align: center;
        }

        .mentor-rating {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin: 0.5rem 0;
            font-size: 1.1rem;
        }

        .mentor-expertise {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 0.5rem 0;
            justify-content: center;
        }

        .expertise-tag {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            backdrop-filter: blur(5px);
        }

        .mentor-body {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .mentor-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            padding: 1rem;
            background: var(--background-color);
            border-radius: var(--border-radius);
            margin: 0;
        }

        .stat-item {
            text-align: center;
            padding: 0.5rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }

        .stat-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--accent-color);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-light);
            font-weight: 500;
        }

        .mentor-bio {
            color: var(--text-light);
            margin: 1rem 0;
            line-height: 1.5;
            font-size: 0.9rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .availability-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--success-color);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 25px;
            font-size: 0.8rem;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(46, 204, 113, 0.2);
            z-index: 1;
        }

        @media (max-width: 768px) {
            .mentors-grid {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: static;
                height: auto;
                margin-bottom: 2rem;
            }
            
            .main-content {
                padding-left: 0;
            }
        }

        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .back-button:hover {
            transform: translateX(-5px) scale(1.05);
            background: #2e59d9;
            color: white;
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.3);
        }
    </style>
</head>
<body>
    <a href="dashboard.php" class="back-button">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar with filters -->
            <div class="col-lg-3">
                <div class="sidebar">
                    <div class="search-container">
                        <input type="text" class="search-input" id="searchInput" placeholder="Search mentors...">
                    </div>

                    <div class="filter-section">
                        <h3 class="filter-title">Expertise</h3>
                        <div class="filter-options">
                            <button class="filter-chip" data-filter="expertise" data-value="Web Development">
                                <i class="fas fa-code me-2"></i>Web Development
                            </button>
                            <button class="filter-chip" data-filter="expertise" data-value="Mobile Development">
                                <i class="fas fa-mobile-alt me-2"></i>Mobile Development
                            </button>
                            <button class="filter-chip" data-filter="expertise" data-value="Data Science">
                                <i class="fas fa-chart-bar me-2"></i>Data Science
                            </button>
                            <button class="filter-chip" data-filter="expertise" data-value="UI/UX Design">
                                <i class="fas fa-paint-brush me-2"></i>UI/UX Design
                            </button>
                            <button class="filter-chip" data-filter="expertise" data-value="Machine Learning">
                                <i class="fas fa-brain me-2"></i>Machine Learning
                            </button>
                        </div>
                    </div>

                    <div class="filter-section">
                        <h3 class="filter-title">Availability</h3>
                        <div class="filter-options">
                            <button class="filter-chip" data-filter="availability" data-value="Available Now">
                                <i class="fas fa-clock me-2"></i>Available Now
                            </button>
                            <button class="filter-chip" data-filter="availability" data-value="This Week">
                                <i class="fas fa-calendar-alt me-2"></i>This Week
                            </button>
                        </div>
                    </div>

                    <div class="filter-section">
                        <h3 class="filter-title">Price Range</h3>
                        <div class="filter-options">
                            <button class="filter-chip" data-filter="price" data-value="0-1000">
                                <i class="fas fa-rupee-sign me-2"></i>Under ₹1,000
                            </button>
                            <button class="filter-chip" data-filter="price" data-value="1000-2000">
                                <i class="fas fa-rupee-sign me-2"></i>₹1,000 - ₹2,000
                            </button>
                            <button class="filter-chip" data-filter="price" data-value="2000+">
                                <i class="fas fa-rupee-sign me-2"></i>Above ₹2,000
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <div class="col-lg-9 main-content">
                <div class="mentors-grid">
                    <?php foreach ($mentors as $mentor): 
                        $initials = strtoupper(substr($mentor['name'], 0, 2));
                        $expertise = explode(',', $mentor['expertise']);
                    ?>
                    <a href="mentor_profile.php?id=<?php echo htmlspecialchars($mentor['id']); ?>" 
                       class="mentor-card" 
                       data-expertise="<?php echo htmlspecialchars($mentor['expertise']); ?>"
                       data-availability="<?php echo htmlspecialchars($mentor['availability'] ?? 'Available Now'); ?>"
                       data-price="<?php echo htmlspecialchars($mentor['hourly_rate']); ?>">
                        <div class="mentor-header">
                            <span class="availability-badge">
                                <i class="fas fa-circle" style="font-size: 8px; margin-right: 5px;"></i>
                                <?php echo htmlspecialchars($mentor['availability'] ?? 'Available Now'); ?>
                            </span>
                            <div class="mentor-avatar"><?php echo $initials; ?></div>
                            <h3 class="mentor-name"><?php echo htmlspecialchars($mentor['name']); ?></h3>
                            <div class="mentor-rating">
                                <i class="fas fa-star"></i>
                                <span><?php echo number_format($mentor['rating'], 1); ?></span>
                            </div>
                            <div class="mentor-expertise">
                                <?php 
                                $count = 0;
                                foreach ($expertise as $skill): 
                                    if ($count < 3): // Show only first 3 skills
                                ?>
                                    <span class="expertise-tag"><?php echo trim(htmlspecialchars($skill)); ?></span>
                                <?php 
                                    endif;
                                    $count++;
                                endforeach; 
                                if ($count > 3):
                                ?>
                                    <span class="expertise-tag">+<?php echo $count - 3; ?> more</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mentor-body">
                            <p class="mentor-bio"><?php echo htmlspecialchars($mentor['bio']); ?></p>
                            <div class="mentor-stats">
                                <div class="stat-item">
                                    <div class="stat-value">₹<?php echo number_format($mentor['hourly_rate']); ?></div>
                                    <div class="stat-label">Per Hour</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo rand(50, 200); ?></div>
                                    <div class="stat-label">Sessions</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo rand(10, 50); ?></div>
                                    <div class="stat-label">Students</div>
                                </div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Search and filter functionality
        const searchInput = document.getElementById('searchInput');
        const mentorCards = document.querySelectorAll('.mentor-card');
        const filterChips = document.querySelectorAll('.filter-chip');

        // Active filters
        let activeFilters = {
            expertise: [],
            availability: []
        };

        // Search functionality
        searchInput.addEventListener('input', filterMentors);

        // Filter chips click handler
        filterChips.forEach(chip => {
            chip.addEventListener('click', () => {
                chip.classList.toggle('active');
                const filterType = chip.dataset.filter;
                const filterValue = chip.dataset.value;

                if (chip.classList.contains('active')) {
                    activeFilters[filterType].push(filterValue);
                } else {
                    activeFilters[filterType] = activeFilters[filterType]
                        .filter(value => value !== filterValue);
                }

                filterMentors();
            });
        });

        function filterMentors() {
            const searchTerm = searchInput.value.toLowerCase();

            mentorCards.forEach(card => {
                const mentorName = card.querySelector('.mentor-name').textContent.toLowerCase();
                const mentorExpertise = card.dataset.expertise.toLowerCase();
                const mentorBio = card.querySelector('.mentor-bio').textContent.toLowerCase();
                const mentorAvailability = card.dataset.availability.toLowerCase();

                // Search match
                const matchesSearch = mentorName.includes(searchTerm) || 
                                    mentorExpertise.includes(searchTerm) || 
                                    mentorBio.includes(searchTerm);

                // Expertise filter match
                const matchesExpertise = activeFilters.expertise.length === 0 || 
                    activeFilters.expertise.some(exp => mentorExpertise.includes(exp.toLowerCase()));

                // Availability filter match
                const matchesAvailability = activeFilters.availability.length === 0 ||
                    activeFilters.availability.some(avail => mentorAvailability.includes(avail.toLowerCase()));

                // Show/hide card with animation
                if (matchesSearch && matchesExpertise && matchesAvailability) {
                    card.style.display = 'block';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                } else {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        card.style.display = 'none';
                    }, 300);
                }
            });
        }

        // Add smooth transitions
        mentorCards.forEach(card => {
            card.style.transition = 'all 0.3s ease';
        });

        // Add debug logging for mentor card clicks
        document.querySelectorAll('.mentor-card').forEach(card => {
            card.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                console.log('Mentor card clicked');
                console.log('Link:', href);
                if (href) {
                    window.location.href = href;
                }
            });
        });
    </script>
</body>
</html> 