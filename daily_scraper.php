<?php
/**
 * Daily Gold Rate Scraper - Automated Script
 * 
 * This script is designed to be run daily via cron job to automatically
 * fetch and store the latest gold rates from BAJUS website.
 * 
 * Cron job example (runs daily at 10:00 AM):
 * 0 10 * * * /usr/bin/php /Applications/XAMPP/xamppfiles/htdocs/Gold_Rate_with_History/daily_scraper.php
 */

// Set execution time limit for web scraping
set_time_limit(300); // 5 minutes

// Include required files
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/scraper.php';
require_once __DIR__ . '/database_functions.php';

// Log file for cron job output
$logFile = __DIR__ . '/logs/daily_scraper.log';

// Ensure logs directory exists
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

/**
 * Log message with timestamp
 */
function logMessage($message, $level = 'INFO') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message\n";
    
    // Write to log file
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Also output to console if running from CLI
    if (php_sapi_name() === 'cli') {
        echo $logEntry;
    }
}

/**
 * Send notification email (optional)
 */
function sendNotification($subject, $message) {
    // Configure this function if you want email notifications
    // You can use PHP's mail() function or a service like SendGrid
    
    // Example:
    // mail('admin@yoursite.com', $subject, $message);
    
    logMessage("Notification: $subject - $message", 'NOTICE');
}

/**
 * Main execution function
 */
function runDailyScraper() {
    logMessage("=== Daily Gold Rate Scraper Started ===");
    
    try {
        // Initialize scraper
        $scraper = new GoldRateScraper();
        $db = new GoldRateDatabase();
        
        // Check if today's rates already exist
        $currentRates = $db->getCurrentRates();
        $today = date('Y-m-d');
        
        if ($currentRates && $currentRates['date'] === $today) {
            logMessage("Today's rates already exist. Checking if update is needed...");
            
            // Optional: Still scrape to check for updates
            $newRates = $scraper->scrapeRates();
            if ($newRates) {
                // Compare rates and update if significant change
                $hasSignificantChange = false;
                $threshold = 10; // BDT threshold for significant change
                
                foreach (['gold_22k', 'gold_21k', 'gold_18k'] as $key) {
                    if (abs($newRates[$key] - $currentRates[$key]) > $threshold) {
                        $hasSignificantChange = true;
                        break;
                    }
                }
                
                if ($hasSignificantChange) {
                    logMessage("Significant price change detected. Updating rates...");
                    if ($scraper->saveRates($newRates)) {
                        logMessage("Rates updated successfully due to significant change");
                        sendNotification("Gold Rates Updated", "Significant price change detected and rates updated");
                    }
                } else {
                    logMessage("No significant change in rates. Skipping update.");
                }
            }
        } else {
            // Scrape new rates for today
            logMessage("Scraping new rates for today...");
            
            $rates = $scraper->scrapeRates();
            if ($rates === false) {
                throw new Exception("Failed to scrape rates from website");
            }
            
            logMessage("Successfully scraped rates:");
            foreach ($rates as $key => $value) {
                logMessage("  $key: $value BDT");
            }
            
            // Save rates to database
            if ($scraper->saveRates($rates)) {
                logMessage("Rates saved successfully to database");
                sendNotification("Gold Rates Updated", "Daily rates scraped and saved successfully");
            } else {
                throw new Exception("Failed to save rates to database");
            }
        }
        
        // Cleanup old logs (keep last 30 days)
        cleanupOldLogs();
        
        // Optional: Cleanup old database records (keep last 2 years)
        $deletedCount = $db->cleanOldRecords(730);
        if ($deletedCount > 0) {
            logMessage("Cleaned up $deletedCount old database records");
        }
        
        logMessage("=== Daily Gold Rate Scraper Completed Successfully ===");
        return true;
        
    } catch (Exception $e) {
        $errorMessage = "Daily scraper failed: " . $e->getMessage();
        logMessage($errorMessage, 'ERROR');
        sendNotification("Gold Rate Scraper Failed", $errorMessage);
        return false;
    }
}

/**
 * Cleanup old log files
 */
function cleanupOldLogs() {
    global $logFile;
    $logDir = dirname($logFile);
    $files = glob($logDir . '/*.log');
    
    foreach ($files as $file) {
        if (filemtime($file) < strtotime('-30 days')) {
            unlink($file);
            logMessage("Deleted old log file: " . basename($file));
        }
    }
}

/**
 * Health check function
 */
function healthCheck() {
    try {
        // Test database connection
        $db = new GoldRateDatabase();
        $currentRates = $db->getCurrentRates();
        
        // Test scraper
        $scraper = new GoldRateScraper();
        
        // Check if rates are recent (within last 7 days)
        if ($currentRates) {
            $lastUpdate = strtotime($currentRates['date']);
            $daysSinceUpdate = (time() - $lastUpdate) / (24 * 60 * 60);
            
            if ($daysSinceUpdate > 7) {
                logMessage("WARNING: Last rate update was $daysSinceUpdate days ago", 'WARNING');
                sendNotification("Gold Rate Data Stale", "Last update was $daysSinceUpdate days ago");
            }
        }
        
        logMessage("Health check completed successfully");
        return true;
        
    } catch (Exception $e) {
        logMessage("Health check failed: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

// Handle command line arguments
if (php_sapi_name() === 'cli') {
    $command = isset($argv[1]) ? $argv[1] : 'run';
    
    switch ($command) {
        case 'run':
            $success = runDailyScraper();
            exit($success ? 0 : 1);
            
        case 'health':
            $success = healthCheck();
            exit($success ? 0 : 1);
            
        case 'test':
            logMessage("=== Test Mode ===");
            $scraper = new GoldRateScraper();
            $rates = $scraper->scrapeRates();
            
            if ($rates) {
                logMessage("Test scraping successful:");
                foreach ($rates as $key => $value) {
                    logMessage("  $key: $value BDT");
                }
                exit(0);
            } else {
                logMessage("Test scraping failed", 'ERROR');
                exit(1);
            }
            
        default:
            echo "Usage: php daily_scraper.php [run|health|test]\n";
            echo "  run    - Run the daily scraper (default)\n";
            echo "  health - Perform health check\n";
            echo "  test   - Test scraping without saving\n";
            exit(1);
    }
} else {
    // If accessed via web (for testing purposes)
    if (isset($_GET['action']) && $_GET['action'] === 'test') {
        header('Content-Type: text/plain');
        $success = runDailyScraper();
        echo $success ? "Scraper completed successfully" : "Scraper failed";
    } else {
        echo "This script is designed to be run from command line or cron job.";
    }
}
?>