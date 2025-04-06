<?php
define('INITIALIZE_TABLES', true);
require_once 'db.php';

// Array of all mentors
$mentors = [
    [
        'name' => 'Mahek Savaliya',
        'email' => 'mahek@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'expertise' => 'AI, Machine Learning, Data Science',
        'experience_years' => 2,
        'bio' => 'Experienced AI researcher and practitioner with a passion for helping others learn and grow in the field of artificial intelligence.',
        'linkedin_profile' => 'https://linkedin.com/in/mahek',
        'avatar' => 'default.jpg',
        'hourly_rate' => 2000.00,
        'rating' => 4.8,
        'total_sessions' => 25,
        'response_rate' => 95,
        'availability_hours' => json_encode([
            'weekdays' => '9:00 AM - 5:00 PM',
            'weekends' => '10:00 AM - 2:00 PM'
        ])
    ],
    [
        'name' => 'Raj Patel',
        'email' => 'raj@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'expertise' => 'Web Development, React, Node.js',
        'experience_years' => 5,
        'bio' => 'Full-stack developer with expertise in modern web technologies. Passionate about teaching and mentoring aspiring developers.',
        'linkedin_profile' => 'https://linkedin.com/in/raj',
        'avatar' => 'default.jpg',
        'hourly_rate' => 2500.00,
        'rating' => 4.9,
        'total_sessions' => 50,
        'response_rate' => 98,
        'availability_hours' => json_encode([
            'weekdays' => '10:00 AM - 6:00 PM',
            'weekends' => '11:00 AM - 3:00 PM'
        ])
    ],
    [
        'name' => 'Priya Sharma',
        'email' => 'priya@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'expertise' => 'UI/UX Design, Product Design, Figma',
        'experience_years' => 4,
        'bio' => 'Creative UI/UX designer with a strong portfolio of successful projects. Love helping others develop their design skills.',
        'linkedin_profile' => 'https://linkedin.com/in/priya',
        'avatar' => 'default.jpg',
        'hourly_rate' => 1800.00,
        'rating' => 4.7,
        'total_sessions' => 35,
        'response_rate' => 90,
        'availability_hours' => json_encode([
            'weekdays' => '9:00 AM - 5:00 PM',
            'weekends' => '10:00 AM - 1:00 PM'
        ])
    ],
    [
        'name' => 'Amit Kumar',
        'email' => 'amit@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'expertise' => 'Mobile Development, Flutter, React Native',
        'experience_years' => 6,
        'bio' => 'Mobile app developer specializing in cross-platform development. Experienced in building and scaling mobile applications.',
        'linkedin_profile' => 'https://linkedin.com/in/amit',
        'avatar' => 'default.jpg',
        'hourly_rate' => 2800.00,
        'rating' => 4.9,
        'total_sessions' => 45,
        'response_rate' => 96,
        'availability_hours' => json_encode([
            'weekdays' => '11:00 AM - 7:00 PM',
            'weekends' => '1:00 PM - 5:00 PM'
        ])
    ],
    [
        'name' => 'Neha Gupta',
        'email' => 'neha@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'expertise' => 'Digital Marketing, SEO, Content Strategy',
        'experience_years' => 7,
        'bio' => 'Digital marketing expert with proven track record in SEO and content strategy. Passionate about sharing knowledge and experience.',
        'linkedin_profile' => 'https://linkedin.com/in/neha',
        'avatar' => 'default.jpg',
        'hourly_rate' => 1500.00,
        'rating' => 4.6,
        'total_sessions' => 60,
        'response_rate' => 92,
        'availability_hours' => json_encode([
            'weekdays' => '10:00 AM - 6:00 PM',
            'weekends' => '11:00 AM - 2:00 PM'
        ])
    ],
    [
        'name' => 'Vikram Singh',
        'email' => 'vikram@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'expertise' => 'Cloud Computing, AWS, DevOps',
        'experience_years' => 8,
        'bio' => 'Cloud architect with extensive experience in AWS and DevOps practices. Helping others navigate the world of cloud computing.',
        'linkedin_profile' => 'https://linkedin.com/in/vikram',
        'avatar' => 'default.jpg',
        'hourly_rate' => 3000.00,
        'rating' => 4.8,
        'total_sessions' => 40,
        'response_rate' => 94,
        'availability_hours' => json_encode([
            'weekdays' => '9:00 AM - 5:00 PM',
            'weekends' => '10:00 AM - 3:00 PM'
        ])
    ]
];

try {
    // Clear existing data
    $conn->exec("DROP TABLE IF EXISTS sessions");
    $conn->exec("DROP TABLE IF EXISTS mentors");
    $conn->exec("DROP TABLE IF EXISTS mentees");
    echo "Cleared existing tables.<br>";

    // Create mentees table
    $mentees_table = "CREATE TABLE mentees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        field_of_interest VARCHAR(50) NOT NULL,
        education_level VARCHAR(50) NOT NULL,
        career_goals TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($mentees_table);
    echo "Created mentees table.<br>";

    // Create mentors table
    $mentors_table = "CREATE TABLE mentors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        expertise VARCHAR(255) NOT NULL,
        experience_years INT NOT NULL,
        bio TEXT NOT NULL,
        linkedin_profile VARCHAR(255) NOT NULL,
        avatar VARCHAR(255) DEFAULT 'default.jpg',
        hourly_rate DECIMAL(10,2) DEFAULT 0.00,
        rating DECIMAL(3,2) DEFAULT 0.00,
        total_sessions INT DEFAULT 0,
        response_rate INT DEFAULT 0,
        availability_hours TEXT,
        email_notifications BOOLEAN DEFAULT 1,
        session_reminders BOOLEAN DEFAULT 1,
        message_alerts BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($mentors_table);
    echo "Created mentors table.<br>";

    // Create resources table
    $resources_table = "CREATE TABLE resources (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mentor_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        type VARCHAR(50) NOT NULL,
        category VARCHAR(100) NOT NULL,
        difficulty VARCHAR(50) NOT NULL,
        duration INT NOT NULL,
        link VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (mentor_id) REFERENCES mentors(id)
    )";
    $conn->exec($resources_table);
    echo "Created resources table.<br>";

    // Create messages table
    $messages_table = "CREATE TABLE messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        sender_type ENUM('mentor', 'mentee') NOT NULL,
        recipient_id INT NOT NULL,
        recipient_type ENUM('mentor', 'mentee') NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($messages_table);
    echo "Created messages table.<br>";

    // Create sessions table
    $sessions_table = "CREATE TABLE sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mentor_id INT NOT NULL,
        mentee_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        date_time DATETIME NOT NULL,
        duration INT NOT NULL,
        status ENUM('pending', 'accepted', 'rejected', 'completed') DEFAULT 'pending',
        meet_link VARCHAR(255),
        rating DECIMAL(3,2) DEFAULT NULL,
        feedback TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE,
        FOREIGN KEY (mentee_id) REFERENCES mentees(id) ON DELETE CASCADE
    )";
    $conn->exec($sessions_table);
    echo "Created sessions table.<br>";

    // Create notifications table
    $notifications_table = "CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_type ENUM('mentor', 'mentee') NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(50) NOT NULL,
        related_id INT,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->exec($notifications_table);
    echo "Created notifications table.<br>";

    // Add all mentors
    $sql = "INSERT INTO mentors (name, email, password, expertise, experience_years, bio, linkedin_profile, 
            avatar, hourly_rate, rating, total_sessions, response_rate, availability_hours) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    foreach ($mentors as $mentor) {
        $stmt->execute([
            $mentor['name'],
            $mentor['email'],
            $mentor['password'],
            $mentor['expertise'],
            $mentor['experience_years'],
            $mentor['bio'],
            $mentor['linkedin_profile'],
            $mentor['avatar'],
            $mentor['hourly_rate'],
            $mentor['rating'],
            $mentor['total_sessions'],
            $mentor['response_rate'],
            $mentor['availability_hours']
        ]);
        echo "Added mentor: " . $mentor['name'] . "<br>";
    }

    echo "Database initialization complete!";
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?> 