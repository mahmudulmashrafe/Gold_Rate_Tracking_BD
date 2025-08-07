<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'gold_rate_tracker');

// Scraping configuration
define('BAJUS_URL', 'https://www.bajus.org/gold-price');
define('USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

// Timezone
date_default_timezone_set('Asia/Dhaka');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection function
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USERNAME,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Utility function to clean price strings
function cleanPrice($priceString) {
    // Convert Bengali numerals to English numerals
    $bengaliToEnglish = [
        '০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4',
        '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9'
    ];
    
    $converted = str_replace(array_keys($bengaliToEnglish), array_values($bengaliToEnglish), $priceString);
    
    // Remove "BDT/GRAM" and other text, keep only numbers and decimal
    $cleaned = preg_replace('/[^0-9.]/', '', $converted);
    return floatval($cleaned);
}
?>