-- ASTHA - MELAMCHI WATER ALERT SYSTEM
-- Database Setup Script
-- Run this in phpMyAdmin or MySQL command line

-- Create database
CREATE DATABASE IF NOT EXISTS Astha CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE Astha;

-- Table 1: locations (42 locations)
CREATE TABLE IF NOT EXISTS locations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    location_name VARCHAR(100) NOT NULL,
    district VARCHAR(100),
    zone VARCHAR(100),
    water_status ENUM('flowing', 'not_flowing') DEFAULT 'not_flowing',
    status_updated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_location_name (location_name),
    INDEX idx_water_status (water_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 2: users
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    location_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_location (location_id),
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 3: admins (Not used - login hardcoded, but table created for future use)
CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 4: water_events (History tracking)
CREATE TABLE IF NOT EXISTS water_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    location_id INT NOT NULL,
    arrival_date DATE NOT NULL,
    arrival_time TIME NOT NULL,
    admin_id INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_location_date (location_id, arrival_date),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert 42 Locations

-- Kathmandu District (19 locations)
INSERT INTO locations (location_name, district, zone) VALUES
('Swayambhu', 'Kathmandu', 'Bagmati'),
('Baneshwor', 'Kathmandu', 'Bagmati'),
('Koteshwor', 'Kathmandu', 'Bagmati'),
('Chabahil', 'Kathmandu', 'Bagmati'),
('Thamel', 'Kathmandu', 'Bagmati'),
('Balaju', 'Kathmandu', 'Bagmati'),
('Kalimati', 'Kathmandu', 'Bagmati'),
('Maharajgunj', 'Kathmandu', 'Bagmati'),
('Boudha', 'Kathmandu', 'Bagmati'),
('Naxal', 'Kathmandu', 'Bagmati'),
('Tripureshwor', 'Kathmandu', 'Bagmati'),
('Gongabu', 'Kathmandu', 'Bagmati'),
('Kalanki', 'Kathmandu', 'Bagmati'),
('Sitapaila', 'Kathmandu', 'Bagmati'),
('Bouddha', 'Kathmandu', 'Bagmati'),
('Jorpati', 'Kathmandu', 'Bagmati'),
('Pepsicola', 'Kathmandu', 'Bagmati'),
('Budhanilkantha', 'Kathmandu', 'Bagmati'),
('Thankot', 'Kathmandu', 'Bagmati');

-- Lalitpur District (9 locations)
INSERT INTO locations (location_name, district, zone) VALUES
('Patan', 'Lalitpur', 'Bagmati'),
('Lagankhel', 'Lalitpur', 'Bagmati'),
('Kupondole', 'Lalitpur', 'Bagmati'),
('Sanepa', 'Lalitpur', 'Bagmati'),
('Jawalakhel', 'Lalitpur', 'Bagmati'),
('Ekantakuna', 'Lalitpur', 'Bagmati'),
('Satdobato', 'Lalitpur', 'Bagmati'),
('Imadol', 'Lalitpur', 'Bagmati'),
('Gwarko', 'Lalitpur', 'Bagmati');

-- Bhaktapur District (4 locations)
INSERT INTO locations (location_name, district, zone) VALUES
('Bhaktapur', 'Bhaktapur', 'Bagmati'),
('Thimi', 'Bhaktapur', 'Bagmati'),
('Suryabinayak', 'Bhaktapur', 'Bagmati'),
('Madhyapur', 'Bhaktapur', 'Bagmati');

-- Other Cities (10 locations)
INSERT INTO locations (location_name, district, zone) VALUES
('Pokhara', 'Kaski', 'Gandaki'),
('Biratnagar', 'Morang', 'Koshi'),
('Birgunj', 'Parsa', 'Madhesh'),
('Bharatpur', 'Chitwan', 'Bagmati'),
('Janakpur', 'Dhanusha', 'Madhesh'),
('Hetauda', 'Makwanpur', 'Bagmati'),
('Dharan', 'Sunsari', 'Koshi'),
('Butwal', 'Rupandehi', 'Lumbini'),
('Nepalgunj', 'Banke', 'Lumbini'),
('Itahari', 'Sunsari', 'Koshi');

-- Insert sample admin (password: admin123)
-- Password hash for 'admin123' using bcrypt
INSERT INTO admins (username, password) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert sample user (password: test123)
-- Password hash for 'test123' using bcrypt
-- Location: Chabahil (id = 4)
INSERT INTO users (name, email, password, location_id) VALUES
('Test User', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4);

-- Success message
SELECT 'Database setup complete! 42 locations loaded.' as message;
