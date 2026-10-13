CREATE DATABASE IF NOT EXISTS skill_exchange CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE skill_exchange;

DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS exchange_requests;
DROP TABLE IF EXISTS user_learning_goals;
DROP TABLE IF EXISTS skills;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    bio TEXT DEFAULT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_role (role)
);

CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE skills (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    level ENUM('Beginner', 'Intermediate', 'Advanced') NOT NULL,
    description TEXT NOT NULL,
    learning_method ENUM('Online', 'In-person', 'Hybrid') NOT NULL DEFAULT 'Online',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_skills_user (user_id),
    INDEX idx_skills_category (category_id),
    INDEX idx_skills_name (name)
);

CREATE TABLE user_learning_goals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    skill_name VARCHAR(120) NOT NULL,
    goal_description TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_learning_user (user_id)
);

CREATE TABLE exchange_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    offered_skill_id INT DEFAULT NULL,
    requested_skill_id INT DEFAULT NULL,
    offered_skill_name VARCHAR(120) DEFAULT NULL,
    requested_skill_name VARCHAR(120) DEFAULT NULL,
    message TEXT NOT NULL,
    status ENUM('Pending', 'Accepted', 'Rejected', 'Cancelled') NOT NULL DEFAULT 'Pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (offered_skill_id) REFERENCES skills(id) ON DELETE SET NULL,
    FOREIGN KEY (requested_skill_id) REFERENCES skills(id) ON DELETE SET NULL,
    INDEX idx_requests_sender (sender_id),
    INDEX idx_requests_receiver (receiver_id),
    INDEX idx_requests_status (status)
);

CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    request_id INT DEFAULT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES exchange_requests(id) ON DELETE SET NULL,
    INDEX idx_messages_sender (sender_id),
    INDEX idx_messages_receiver (receiver_id),
    INDEX idx_messages_request (request_id)
);

CREATE TABLE sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT DEFAULT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    session_date DATETIME NOT NULL,
    mode ENUM('Online', 'In-person') NOT NULL,
    status ENUM('Proposed', 'Confirmed', 'Rescheduled', 'Cancelled', 'Completed') NOT NULL DEFAULT 'Proposed',
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES exchange_requests(id) ON DELETE SET NULL,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_sessions_request (request_id),
    INDEX idx_sessions_date (session_date)
);

CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review_per_request (request_id, reviewer_id),
    FOREIGN KEY (request_id) REFERENCES exchange_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_reviews_reviewee (reviewee_id)
);

CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    related_id INT DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notifications_user (user_id),
    INDEX idx_notifications_read (is_read)
);

CREATE TABLE contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact_email (email)
);

CREATE TABLE reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reporter_id INT NOT NULL,
    target_user_id INT DEFAULT NULL,
    target_skill_id INT DEFAULT NULL,
    reason TEXT NOT NULL,
    status ENUM('Open', 'Resolved') NOT NULL DEFAULT 'Open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (target_skill_id) REFERENCES skills(id) ON DELETE SET NULL,
    INDEX idx_reports_target (target_user_id)
);

INSERT INTO categories (name, slug, description) VALUES
('Programming', 'programming', 'Software development and coding skills.'),
('Web Development', 'web-development', 'Frontend and backend website building skills.'),
('Design', 'design', 'UI, UX, and visual design capabilities.'),
('Languages', 'languages', 'Language learning and translation skills.'),
('Photography', 'photography', 'Editing, lighting, and creative shooting skills.'),
('Communication', 'communication', 'Presentation, writing, and interpersonal communication.' );

INSERT INTO users (full_name, email, phone, password_hash, bio, role, is_active) VALUES
('Platform Admin', 'admin@skillx.com', '9876543210', '$2y$10$B9lXc660JV5Nyq/BkitV8uq9prdkuJGhT7Z.q2.628.Oa6cy4QI8y', 'Administrator for the Skill Exchange Platform.', 'admin', 1);

INSERT INTO skills (user_id, category_id, name, level, description, learning_method) VALUES
((SELECT id FROM users WHERE email = 'admin@skillx.com'), 1, 'Python Programming', 'Advanced', 'I teach Python for beginners and intermediate learners with practical projects.', 'Online'),
((SELECT id FROM users WHERE email = 'admin@skillx.com'), 2, 'Responsive Web Design', 'Advanced', 'I help students create modern responsive layouts and polished interfaces.', 'Hybrid'),
((SELECT id FROM users WHERE email = 'admin@skillx.com'), 3, 'Graphic Design Basics', 'Intermediate', 'I guide people through branding, color theory, and creative layouts.', 'In-person');

INSERT INTO user_learning_goals (user_id, category_id, skill_name, goal_description) VALUES
((SELECT id FROM users WHERE email = 'admin@skillx.com'), 2, 'JavaScript', 'I want to strengthen my DOM and frontend logic understanding.'),
((SELECT id FROM users WHERE email = 'admin@skillx.com'), 5, 'Portrait Photography', 'I would like to improve composition and editing techniques.');
