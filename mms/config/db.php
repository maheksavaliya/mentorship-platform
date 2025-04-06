<?php
// Error reporting settings
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Database configuration
$db_host = 'localhost';
$db_name = 'mms';
$db_user = 'root';
$db_pass = '';

try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die("Connection failed. Please try again later.");
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['mentee_id']) || isset($_SESSION['mentor_id']);
}

// Function to check user role
function checkRole($required_role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $required_role;
}

// Function to sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Function to validate file upload
function validateImage($file) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $file['name'];
    $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($filetype, $allowed)) {
        return false;
    }
    
    if ($file['size'] > 5000000) { // 5MB max
        return false;
    }
    
    return true;
}

// Function to handle file upload
function uploadFile($file, $target_dir) {
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $filename = time() . '_' . basename($file['name']);
    $target_path = $target_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $filename;
    }
    
    return false;
}

// Function to regenerate session
function regenerateSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        @session_regenerate_id(true);
    }
}

// Function to destroy session safely
function destroySession() {
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    @session_destroy();
}

// Regenerate session ID periodically for security
if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration']) > 3600) {
    regenerateSession();
    $_SESSION['last_regeneration'] = time();
}

// Create tables if they don't exist
$users_table = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('mentor', 'mentee') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$mentees_table = "CREATE TABLE IF NOT EXISTS mentees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

$mentors_table = "CREATE TABLE IF NOT EXISTS mentors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    expertise TEXT NOT NULL,
    experience_years INT NOT NULL,
    bio TEXT NOT NULL,
    linkedin_profile VARCHAR(255),
    profile_image VARCHAR(255) DEFAULT 'default-avatar.png',
    hourly_rate DECIMAL(10,2) DEFAULT 0.00,
    rating DECIMAL(3,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

$sessions_table = "CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT NOT NULL,
    mentee_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    date_time DATETIME NOT NULL,
    duration INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
    meet_link VARCHAR(255),
    rating DECIMAL(3,2) DEFAULT NULL,
    feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mentor_id) REFERENCES mentors(id),
    FOREIGN KEY (mentee_id) REFERENCES mentees(id)
)";

$notifications_table = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('mentor', 'mentee') NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    related_id INT,
    type ENUM('session_request', 'session_accepted', 'session_rejected', 'session_reminder', 'other') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$availability_table = "CREATE TABLE IF NOT EXISTS availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT NOT NULL,
    day_of_week INT NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE
)";

try {
    $conn->exec($users_table);
    $conn->exec($mentees_table);
    $conn->exec($mentors_table);
    $conn->exec($sessions_table);
    $conn->exec($notifications_table);
    $conn->exec($availability_table);
} catch(PDOException $e) {
    error_log("Error creating tables: " . $e->getMessage());
}

// Function to connect mentee with mentor
function connectMenteeWithMentor($conn, $mentee_id, $mentor_id, $title = null, $description = null) {
    try {
        // Start transaction
        $conn->beginTransaction();

        // Get mentor details
        $stmt = $conn->prepare("SELECT name, expertise FROM mentors WHERE id = ?");
        $stmt->execute([$mentor_id]);
        $mentor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$mentor) {
            throw new Exception("Mentor not found");
        }

        // Create default title and description if not provided
        if (!$title) {
            $title = "Introduction to " . $mentor['expertise'];
        }
        if (!$description) {
            $description = "Initial mentoring session to discuss goals and expectations";
        }

        // Create session
        $stmt = $conn->prepare("INSERT INTO sessions (mentor_id, mentee_id, title, description, date_time, duration, status) 
                               VALUES (?, ?, ?, ?, ?, ?, 'accepted')");
        $stmt->execute([
            $mentor_id,
            $mentee_id,
            $title,
            $description,
            date('Y-m-d H:i:s', strtotime('+2 days')),
            60
        ]);

        $session_id = $conn->lastInsertId();

        // Create Google Meet link
        $meet_link = "https://meet.google.com/" . substr(md5(uniqid()), 0, 10);
        
        // Update session with meet link
        $stmt = $conn->prepare("UPDATE sessions SET meet_link = ? WHERE id = ?");
        $stmt->execute([$meet_link, $session_id]);

        // Create notification for both parties
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, title, message, type, related_id, is_read) 
                               VALUES (?, ?, ?, ?, ?, ?, 0)");
        
        // Notify mentee
        $stmt->execute([
            $mentee_id,
            'mentee',
            'Session Scheduled',
            "You have been connected with mentor " . $mentor['name'],
            'session_accepted',
            $session_id
        ]);

        // Notify mentor
        $stmt->execute([
            $mentor_id,
            'mentor',
            'New Mentee Connected',
            "A new mentee has been assigned to you",
            'session_accepted',
            $session_id
        ]);

        // Commit transaction
        $conn->commit();
        return true;

    } catch(Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        error_log("Error connecting mentee with mentor: " . $e->getMessage());
        return false;
    }
}
?> 