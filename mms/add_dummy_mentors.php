<?php
require_once 'config/db.php';

// Define dummy mentors
$dummy_mentors = [
    [
        'name' => 'Manender Dutt',
        'email' => 'manender@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'expertise' => 'Web Development, React, Node.js',
        'experience_years' => 8,
        'bio' => 'Senior Web Developer with 8+ years of experience',
        'linkedin_profile' => 'https://linkedin.com/in/manender',
        'hourly_rate' => 2000,
        'rating' => 4.9
    ],
    [
        'name' => 'Priya Sharma',
        'email' => 'priya@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'expertise' => 'Data Science, Machine Learning',
        'experience_years' => 5,
        'bio' => 'Data Scientist at Google with PhD in ML',
        'linkedin_profile' => 'https://linkedin.com/in/priya',
        'hourly_rate' => 2500,
        'rating' => 4.8
    ],
    [
        'name' => 'Rahul Verma',
        'email' => 'rahul@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'expertise' => 'Mobile Development, Flutter, React Native',
        'experience_years' => 5,
        'bio' => 'Mobile App Developer with 5+ years of experience',
        'linkedin_profile' => 'https://linkedin.com/in/rahul',
        'hourly_rate' => 1800,
        'rating' => 4.7
    ],
    [
        'name' => 'Anjali Gupta',
        'email' => 'anjali@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'expertise' => 'UI/UX Design, Product Design',
        'experience_years' => 6,
        'bio' => 'Senior UX Designer at Microsoft',
        'linkedin_profile' => 'https://linkedin.com/in/anjali',
        'hourly_rate' => 2200,
        'rating' => 4.9
    ],
    [
        'name' => 'Rajesh Kumar',
        'email' => 'rajesh@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'expertise' => 'DevOps, Cloud Computing, AWS',
        'experience_years' => 7,
        'bio' => 'DevOps Engineer with AWS certification',
        'linkedin_profile' => 'https://linkedin.com/in/rajesh',
        'hourly_rate' => 2800,
        'rating' => 4.8
    ],
    [
        'name' => 'Neha Singh',
        'email' => 'neha@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'expertise' => 'Python, Django, Data Analysis',
        'experience_years' => 4,
        'bio' => 'Full Stack Developer and Data Analyst',
        'linkedin_profile' => 'https://linkedin.com/in/neha',
        'hourly_rate' => 1900,
        'rating' => 4.6
    ],
    [
        'name' => 'Amit Patel',
        'email' => 'amit@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'expertise' => 'Blockchain, Smart Contracts',
        'experience_years' => 6,
        'bio' => 'Blockchain Developer and Consultant',
        'linkedin_profile' => 'https://linkedin.com/in/amit',
        'hourly_rate' => 3000,
        'rating' => 4.7
    ],
    [
        'name' => 'Sneha Reddy',
        'email' => 'sneha@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'expertise' => 'AI, Deep Learning, Computer Vision',
        'experience_years' => 5,
        'bio' => 'AI Researcher and Deep Learning Expert',
        'linkedin_profile' => 'https://linkedin.com/in/sneha',
        'hourly_rate' => 2600,
        'rating' => 4.8
    ]
];

try {
    // Insert dummy mentors
    $stmt = $conn->prepare("INSERT INTO mentors (name, email, password, expertise, experience_years, bio, linkedin_profile, hourly_rate, rating) 
                           VALUES (:name, :email, :password, :expertise, :experience_years, :bio, :linkedin_profile, :hourly_rate, :rating)");
    
    $success_count = 0;
    foreach ($dummy_mentors as $mentor) {
        try {
            $stmt->execute($mentor);
            $success_count++;
            echo "Added mentor: " . $mentor['name'] . "<br>";
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                echo "Mentor already exists: " . $mentor['name'] . "<br>";
            } else {
                echo "Error adding mentor " . $mentor['name'] . ": " . $e->getMessage() . "<br>";
            }
        }
    }
    
    echo "<br>Successfully added " . $success_count . " mentors!";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 