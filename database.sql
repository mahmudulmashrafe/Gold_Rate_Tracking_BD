-- Gold Rate Tracking Database Schema
-- Create database for storing gold rates history

CREATE DATABASE IF NOT EXISTS gold_rate_tracker;
USE gold_rate_tracker;

-- Table to store daily gold rates
CREATE TABLE IF NOT EXISTS gold_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE UNIQUE NOT NULL,
    gold_22k DECIMAL(10,2) NOT NULL COMMENT '22 KARAT Gold price per gram in BDT',
    gold_21k DECIMAL(10,2) NOT NULL COMMENT '21 KARAT Gold price per gram in BDT',
    gold_18k DECIMAL(10,2) NOT NULL COMMENT '18 KARAT Gold price per gram in BDT',
    gold_traditional DECIMAL(10,2) NOT NULL COMMENT 'Traditional Gold price per gram in BDT',
    silver_22k DECIMAL(10,2) NOT NULL COMMENT '22 KARAT Silver price per gram in BDT',
    silver_21k DECIMAL(10,2) NOT NULL COMMENT '21 KARAT Silver price per gram in BDT',
    silver_18k DECIMAL(10,2) NOT NULL COMMENT '18 KARAT Silver price per gram in BDT',
    silver_traditional DECIMAL(10,2) NOT NULL COMMENT 'Traditional Silver price per gram in BDT',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Index for faster date queries
CREATE INDEX idx_date ON gold_rates(date);
CREATE INDEX idx_created_at ON gold_rates(created_at);

-- Table to store scraping logs
CREATE TABLE IF NOT EXISTS scraping_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scrape_date DATE NOT NULL,
    status ENUM('success', 'failed', 'partial') NOT NULL,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data for testing (optional)
INSERT IGNORE INTO gold_rates (date, gold_22k, gold_21k, gold_18k, gold_traditional, silver_22k, silver_21k, silver_18k, silver_traditional) 
VALUES 
(CURDATE(), 14712.00, 14043.00, 12037.00, 9956.00, 241.00, 230.00, 197.00, 148.00);