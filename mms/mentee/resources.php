<?php
session_start();
require_once '../config/db.php';

// Initialize variables
$interests = [];
$error = null;

// Get mentee's expertise interests if logged in
try {
    if(isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("SELECT expertise FROM mentees WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $mentee = $stmt->fetch(PDO::FETCH_ASSOC);
        $interests = explode(',', $mentee['expertise'] ?? '');
    }
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $interests = [];
}

// Sample resources data (in a real application, this would come from a database)
$resources = [
    'Web Development' => [
        [
            'title' => 'Modern JavaScript Course',
            'description' => 'Learn modern JavaScript from basics to advanced concepts',
            'type' => 'course',
            'difficulty' => 'Intermediate',
            'duration' => '20 hours',
            'link' => 'https://example.com/js-course',
            'image' => 'https://img.icons8.com/color/96/000000/javascript.png'
        ],
        [
            'title' => 'React.js Fundamentals',
            'description' => 'Master React.js and build modern web applications',
            'type' => 'tutorial',
            'difficulty' => 'Beginner',
            'duration' => '15 hours',
            'link' => 'https://example.com/react-course',
            'image' => 'https://img.icons8.com/color/96/000000/react-native.png'
        ]
    ],
    'Data Science' => [
        [
            'title' => 'Python for Data Science',
            'description' => 'Learn Python programming for data analysis',
            'type' => 'course',
            'difficulty' => 'Beginner',
            'duration' => '25 hours',
            'link' => 'https://example.com/python-ds',
            'image' => 'https://img.icons8.com/color/96/000000/python.png'
        ],
        [
            'title' => 'Machine Learning Basics',
            'description' => 'Introduction to machine learning algorithms',
            'type' => 'workshop',
            'difficulty' => 'Advanced',
            'duration' => '30 hours',
            'link' => 'https://example.com/ml-course',
            'image' => 'https://img.icons8.com/color/96/000000/machine-learning.png'
        ]
    ],
    'UI/UX Design' => [
        [
            'title' => 'UI Design Principles',
            'description' => 'Learn fundamental principles of UI design',
            'type' => 'course',
            'difficulty' => 'Beginner',
            'duration' => '12 hours',
            'link' => 'https://example.com/ui-design',
            'image' => 'https://img.icons8.com/color/96/000000/design.png'
        ],
        [
            'title' => 'UX Research Methods',
            'description' => 'Master user research techniques and methodologies',
            'type' => 'workshop',
            'difficulty' => 'Intermediate',
            'duration' => '18 hours',
            'link' => 'https://example.com/ux-research',
            'image' => 'https://img.icons8.com/color/96/000000/ux-design.png'
        ]
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Resources - MMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #4f46e5;
            --background-color: #f3f4f6;
            --text-color: #1f2937;
        }

        body {
            background: var(--background-color);
            font-family: 'Inter', sans-serif;
            color: var(--text-color);
        }

        .resources-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .section-title {
            color: var(--text-color);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 30px;
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60%;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border-radius: 2px;
        }

        .resource-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .resource-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .resource-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 20px;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .resource-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="20" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: 100px 100px;
            opacity: 0.1;
        }

        .resource-icon {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-bottom: 15px;
        }

        .resource-body {
            padding: 20px;
        }

        .resource-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .resource-description {
            color: #6b7280;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .resource-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            color: #6b7280;
            font-size: 0.875rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .resource-tags {
            margin-bottom: 20px;
        }

        .tag {
            display: inline-block;
            padding: 5px 12px;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary-color);
            border-radius: 20px;
            font-size: 0.875rem;
            margin-right: 8px;
            margin-bottom: 8px;
        }

        .btn-access {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-access:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3);
            color: white;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .filter-group {
            margin-bottom: 15px;
        }

        .filter-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-color);
        }

        .filter-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-option {
            padding: 5px 15px;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary-color);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-option:hover,
        .filter-option.active {
            background: var(--primary-color);
            color: white;
        }

        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 20px;
            padding-left: 40px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            outline: none;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .resource-card {
            animation: fadeIn 0.5s ease-out forwards;
        }

        .btn-back {
            background: transparent;
            color: var(--text-color);
            border: 2px solid var(--text-color);
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }

        .btn-back:hover {
            background: var(--text-color);
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="resources-container">
        <!-- Back Button -->
        <a href="dashboard.php" class="btn btn-back">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>

        <h1 class="section-title">Learning Resources</h1>

        <!-- Filters -->
        <div class="filters">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchResources" placeholder="Search resources...">
            </div>
            <div class="filter-group">
                <div class="filter-label">Categories</div>
                <div class="filter-options" id="categoryFilters">
                    <div class="filter-option active" data-category="all">All</div>
                    <?php foreach (array_keys($resources) as $category): ?>
                        <div class="filter-option" data-category="<?php echo htmlspecialchars($category); ?>">
                            <?php echo htmlspecialchars($category); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="filter-group">
                <div class="filter-label">Difficulty Level</div>
                <div class="filter-options" id="difficultyFilters">
                    <div class="filter-option active" data-difficulty="all">All</div>
                    <div class="filter-option" data-difficulty="Beginner">Beginner</div>
                    <div class="filter-option" data-difficulty="Intermediate">Intermediate</div>
                    <div class="filter-option" data-difficulty="Advanced">Advanced</div>
                </div>
            </div>
        </div>

        <!-- Resources Grid -->
        <div class="row" id="resourcesGrid">
            <?php foreach ($resources as $category => $categoryResources): ?>
                <?php foreach ($categoryResources as $resource): ?>
                    <div class="col-md-6 col-lg-4" 
                         data-category="<?php echo htmlspecialchars($category); ?>"
                         data-difficulty="<?php echo htmlspecialchars($resource['difficulty']); ?>">
                        <div class="resource-card">
                            <div class="resource-header">
                                <img src="<?php echo htmlspecialchars($resource['image']); ?>" alt="<?php echo htmlspecialchars($resource['title']); ?>" class="resource-icon">
                                <h3 class="resource-title"><?php echo htmlspecialchars($resource['title']); ?></h3>
                            </div>
                            <div class="resource-body">
                                <p class="resource-description"><?php echo htmlspecialchars($resource['description']); ?></p>
                                <div class="resource-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-clock"></i>
                                        <?php echo htmlspecialchars($resource['duration']); ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-signal"></i>
                                        <?php echo htmlspecialchars($resource['difficulty']); ?>
                                    </div>
                                </div>
                                <div class="resource-tags">
                                    <span class="tag"><?php echo htmlspecialchars($resource['type']); ?></span>
                                    <span class="tag"><?php echo htmlspecialchars($category); ?></span>
                                </div>
                                <a href="<?php echo htmlspecialchars($resource['link']); ?>" class="btn btn-access" target="_blank">
                                    <i class="fas fa-external-link-alt me-2"></i>Access Resource
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search and filter functionality
        const searchInput = document.getElementById('searchResources');
        const resourceCards = document.querySelectorAll('#resourcesGrid > div');
        const categoryFilters = document.querySelectorAll('#categoryFilters .filter-option');
        const difficultyFilters = document.querySelectorAll('#difficultyFilters .filter-option');

        function filterResources() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedCategory = document.querySelector('#categoryFilters .filter-option.active').dataset.category;
            const selectedDifficulty = document.querySelector('#difficultyFilters .filter-option.active').dataset.difficulty;

            resourceCards.forEach(card => {
                const title = card.querySelector('.resource-title').textContent.toLowerCase();
                const description = card.querySelector('.resource-description').textContent.toLowerCase();
                const category = card.dataset.category;
                const difficulty = card.dataset.difficulty;

                const matchesSearch = title.includes(searchTerm) || description.includes(searchTerm);
                const matchesCategory = selectedCategory === 'all' || category === selectedCategory;
                const matchesDifficulty = selectedDifficulty === 'all' || difficulty === selectedDifficulty;

                if (matchesSearch && matchesCategory && matchesDifficulty) {
                    card.style.display = 'block';
                    card.style.animation = 'fadeIn 0.5s ease-out';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Event listeners
        searchInput.addEventListener('input', filterResources);

        categoryFilters.forEach(filter => {
            filter.addEventListener('click', () => {
                document.querySelector('#categoryFilters .filter-option.active').classList.remove('active');
                filter.classList.add('active');
                filterResources();
            });
        });

        difficultyFilters.forEach(filter => {
            filter.addEventListener('click', () => {
                document.querySelector('#difficultyFilters .filter-option.active').classList.remove('active');
                filter.classList.add('active');
                filterResources();
            });
        });
    </script>
</body>
</html> 