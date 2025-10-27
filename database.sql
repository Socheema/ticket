-- TicketFlow Database Schema

-- Create database
CREATE DATABASE IF NOT EXISTS ticketflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ticketflow;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tickets table
CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data
INSERT INTO users (name, email, password) VALUES
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'), -- password: password
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO tickets (user_id, title, description, status, created_at, updated_at) VALUES
(1, 'Website login not working', 'Users are reporting they cannot log into the website', 'open', '2024-10-20 10:00:00', '2024-10-20 10:00:00'),
(1, 'Add dark mode feature', 'Implement dark mode toggle for better user experience', 'in_progress', '2024-10-21 14:30:00', '2024-10-22 09:15:00'),
(1, 'Fix mobile responsive issues', 'Dashboard not displaying correctly on mobile devices', 'closed', '2024-10-18 08:45:00', '2024-10-19 16:20:00'),
(1, 'Update user profile page', 'Add ability to upload profile pictures', 'open', '2024-10-23 11:00:00', '2024-10-23 11:00:00');
