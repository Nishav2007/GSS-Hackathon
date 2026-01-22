-- ASTHA - Database Migration for Wallet, Billing, Khalti, and User Service Control
-- Run this if you already have the previous database installed.

USE Astha;

-- Users table updates
ALTER TABLE users
    ADD COLUMN wallet_balance_paisa INT DEFAULT 0,
    ADD COLUMN service_status ENUM('active', 'suspended') DEFAULT 'active',
    ADD COLUMN total_liters_used INT DEFAULT 0,
    ADD COLUMN billed_blocks INT DEFAULT 0,
    ADD COLUMN unbilled_liters INT DEFAULT 0,
    ADD COLUMN last_topup_at DATETIME NULL,
    ADD COLUMN last_wallet_warning_level ENUM('none', 'below_zero', 'below_900', 'suspended') DEFAULT 'none',
    ADD COLUMN last_wallet_email_sent_at DATETIME NULL;

CREATE INDEX idx_service_status ON users (service_status);

-- User water usage table
CREATE TABLE IF NOT EXISTS user_water_usage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    liters INT NOT NULL,
    note VARCHAR(255) NULL,
    admin_id INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wallet ledger table
CREATE TABLE IF NOT EXISTS wallet_ledger (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('topup', 'deduction', 'adjustment') NOT NULL,
    amount_paisa INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    ref_type VARCHAR(50) NULL,
    ref_id INT NULL,
    balance_after_paisa INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

