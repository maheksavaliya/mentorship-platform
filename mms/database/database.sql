-- Create database if not exists
CREATE DATABASE IF NOT EXISTS mms;
USE mms;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('mentee', 'mentor', 'admin') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Profiles table
CREATE TABLE IF NOT EXISTS profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    bio TEXT,
    expertise VARCHAR(255),
    experience INT DEFAULT 0,
    rating DECIMAL(2,1) DEFAULT 0.0,
    total_sessions INT DEFAULT 0,
    image_url VARCHAR(255) DEFAULT '../images/default-avatar.png',
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Booking requests table
CREATE TABLE IF NOT EXISTS booking_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    mentee_id INT NOT NULL,
    mentor_id INT NOT NULL,
    slot_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'completed') DEFAULT 'pending',
    meeting_link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mentee_id) REFERENCES users(id),
    FOREIGN KEY (mentor_id) REFERENCES users(id)
);

-- Time slots table
CREATE TABLE IF NOT EXISTS time_slots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    mentor_id INT NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_booked BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mentor_id) REFERENCES users(id)
);

-- Session feedback table
CREATE TABLE IF NOT EXISTS session_feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    mentee_id INT NOT NULL,
    mentor_id INT NOT NULL,
    rating DECIMAL(2,1) NOT NULL,
    communication DECIMAL(2,1) NOT NULL,
    knowledge DECIMAL(2,1) NOT NULL,
    helpfulness DECIMAL(2,1) NOT NULL,
    feedback_text TEXT,
    recommend ENUM('yes', 'no') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES booking_requests(id),
    FOREIGN KEY (mentee_id) REFERENCES users(id),
    FOREIGN KEY (mentor_id) REFERENCES users(id)
);

-- Insert sample mentor data
INSERT INTO users (name, email, password, role) VALUES
('Dr. Rajesh Kumar', 'rajesh@example.com', '$2y$10$sample_hash', 'mentor'),
('Priya Sharma', 'priya@example.com', '$2y$10$sample_hash', 'mentor'),
('Amit Patel', 'amit@example.com', '$2y$10$sample_hash', 'mentor'),
('Neha Verma', 'neha@example.com', '$2y$10$sample_hash', 'mentor');

-- Insert sample profiles
INSERT INTO profiles (user_id, bio, expertise, experience, rating, total_sessions) VALUES
(1, 'Expert in AI and Machine Learning with extensive research experience', 'Machine Learning,AI,Data Science', 15, 4.9, 120),
(2, 'Full-stack developer specializing in modern web technologies', 'Web Development,React,Node.js', 8, 4.8, 85),
(3, 'Mobile app developer with expertise in multiple platforms', 'Mobile App Development,Flutter,iOS', 10, 4.7, 95),
(4, 'Creative UI/UX designer with a focus on user-centered design', 'UI/UX Design,Product Design', 7, 4.9, 75); 