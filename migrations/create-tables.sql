-- Luna Chatbot Database Schema
-- Creates all necessary tables for the system

-- Create admin table
CREATE TABLE IF NOT EXISTS `admin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `last_login` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create settings table
CREATE TABLE IF NOT EXISTS `settings` (
  `key` VARCHAR(100) PRIMARY KEY,
  `value` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create prompt_data table (knowledge base)
CREATE TABLE IF NOT EXISTS `prompt_data` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `question` TEXT NOT NULL,
  `answer` TEXT NOT NULL,
  `confidence_level` FLOAT DEFAULT 1.0,
  `status` ENUM('active','inactive','draft') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FULLTEXT KEY `question_fulltext` (`question`),
  FULLTEXT KEY `answer_fulltext` (`answer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create prompt_log table (interaction history)
CREATE TABLE IF NOT EXISTS `prompt_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `question` TEXT NOT NULL,
  `answer` TEXT NOT NULL,
  `source` ENUM('db','gpt-4.1','gpt-4o') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `ip_address` VARCHAR(45),
  `user_agent` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin (password: admin123)
-- Using more compatible hash approach
INSERT INTO `admin` (`username`, `password_hash`) VALUES
('admin', '$2y$10$KyjziJPqbSYpMuVgJ1oYH.TLKv3v/ZcV.J0ZJnzgrg0UYzxiCc.3W');

-- Insert default settings
INSERT INTO `settings` (`key`, `value`) VALUES
('api_token', MD5(RAND())),
('openai_key', 'sk-your-openai-key'),
('gpt_model', 'gpt-4.1'),
('fallback_model', 'gpt-4o'),
('fallback_response', 'Sorry, I could not process your request at this time. Please try again later.'),
('max_retries', '3'),
('rate_limit_per_minute', '10'),
('log_retention_days', '90');