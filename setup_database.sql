-- Database setup for HVAC Tool with company management
-- Run this in your MySQL/phpMyAdmin to set up the required tables

USE hvac_keywords;

-- Companies table to store company information
CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    hours TEXT NOT NULL,
    company_type ENUM('HVAC', 'Plumbing', 'Electric', 'Commercial HVAC') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company_type (company_type),
    INDEX idx_company_name (company_name)
);

-- Blog posts table to store generated content linked to companies
CREATE TABLE IF NOT EXISTS blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    zip_code VARCHAR(10) NOT NULL,
    keyword TEXT NOT NULL,
    title VARCHAR(500),
    content LONGTEXT NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    word_count INT DEFAULT 0,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_company_zip (company_id, zip_code),
    INDEX idx_generated_at (generated_at),
    INDEX idx_keyword (keyword(100))
);

-- Keywords tracking table (optional - for analytics)
CREATE TABLE IF NOT EXISTS keyword_searches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    zip_code VARCHAR(10) NOT NULL,
    keyword VARCHAR(500) NOT NULL,
    search_count INT DEFAULT 1,
    last_searched TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_company_zip_keyword (company_id, zip_code, keyword),
    INDEX idx_search_stats (company_id, zip_code, search_count)
);