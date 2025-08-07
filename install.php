<?php
/**
 * Installation Script for Gold Rate Tracker
 * 
 * This script sets up the database and initial configuration
 * Run this once to set up the system
 */

require_once 'config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gold Rate Tracker - Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-cog me-2"></i>
                            Gold Rate Tracker Installation
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $step = isset($_GET['step']) ? $_GET['step'] : 1;
                        
                        if ($step == 1):
                        ?>
                        <!-- Step 1: Prerequisites Check -->
                        <h4>Step 1: System Requirements Check</h4>
                        <div class="mt-3">
                            <?php
                            $requirements = [
                                'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
                                'MySQL Extension' => extension_loaded('pdo_mysql'),
                                'cURL Extension' => extension_loaded('curl'),
                                'DOM Extension' => extension_loaded('dom'),
                                'Write Permission (logs/)' => is_writable(__DIR__) || @mkdir(__DIR__ . '/logs', 0755, true)
                            ];
                            
                            $allGood = true;
                            foreach ($requirements as $req => $status) {
                                echo '<div class="d-flex justify-content-between align-items-center mb-2">';
                                echo '<span>' . $req . '</span>';
                                if ($status) {
                                    echo '<span class="badge bg-success"><i class="fas fa-check"></i> OK</span>';
                                } else {
                                    echo '<span class="badge bg-danger"><i class="fas fa-times"></i> FAIL</span>';
                                    $allGood = false;
                                }
                                echo '</div>';
                            }
                            ?>
                        </div>
                        
                        <?php if ($allGood): ?>
                        <div class="mt-4">
                            <a href="?step=2" class="btn btn-primary">
                                Continue to Database Setup
                                <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-danger mt-4">
                            <strong>Error:</strong> Please fix the failed requirements before continuing.
                        </div>
                        <?php endif; ?>
                        
                        <?php elseif ($step == 2): ?>
                        <!-- Step 2: Database Setup -->
                        <h4>Step 2: Database Configuration</h4>
                        
                        <?php
                        if ($_POST) {
                            try {
                                // Test database connection
                                $testPdo = new PDO(
                                    "mysql:host=" . DB_HOST,
                                    DB_USERNAME,
                                    DB_PASSWORD
                                );
                                
                                // Read and execute SQL file
                                $sql = file_get_contents(__DIR__ . '/database.sql');
                                $statements = explode(';', $sql);
                                
                                foreach ($statements as $statement) {
                                    $statement = trim($statement);
                                    if (!empty($statement)) {
                                        $testPdo->exec($statement);
                                    }
                                }
                                
                                echo '<div class="alert alert-success">';
                                echo '<i class="fas fa-check-circle me-2"></i>';
                                echo 'Database setup completed successfully!';
                                echo '</div>';
                                
                                echo '<div class="mt-4">';
                                echo '<a href="?step=3" class="btn btn-primary">Continue to Initial Data <i class="fas fa-arrow-right ms-2"></i></a>';
                                echo '</div>';
                                
                            } catch (Exception $e) {
                                echo '<div class="alert alert-danger">';
                                echo '<strong>Database Error:</strong> ' . $e->getMessage();
                                echo '</div>';
                            }
                        } else {
                        ?>
                        
                        <div class="alert alert-info">
                            <strong>Database Configuration:</strong><br>
                            Host: <?= DB_HOST ?><br>
                            Username: <?= DB_USERNAME ?><br>
                            Database: <?= DB_NAME ?>
                        </div>
                        
                        <p>This step will create the necessary database and tables for the Gold Rate Tracker.</p>
                        
                        <form method="POST">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-database me-2"></i>
                                Create Database & Tables
                            </button>
                        </form>
                        
                        <?php } ?>
                        
                        <?php elseif ($step == 3): ?>
                        <!-- Step 3: Initial Data -->
                        <h4>Step 3: Initial Data Setup</h4>
                        
                        <?php
                        if ($_POST && isset($_POST['scrape_initial'])) {
                            require_once 'scraper.php';
                            
                            try {
                                $scraper = new GoldRateScraper();
                                $rates = $scraper->scrapeRates();
                                
                                if ($rates && $scraper->saveRates($rates)) {
                                    echo '<div class="alert alert-success">';
                                    echo '<i class="fas fa-check-circle me-2"></i>';
                                    echo 'Initial data scraped and saved successfully!';
                                    echo '</div>';
                                    
                                    echo '<h5>Scraped Rates:</h5>';
                                    echo '<ul class="list-group">';
                                    foreach ($rates as $key => $value) {
                                        echo '<li class="list-group-item d-flex justify-content-between">';
                                        echo '<span>' . ucwords(str_replace('_', ' ', $key)) . '</span>';
                                        echo '<strong>৳' . number_format($value, 2) . '</strong>';
                                        echo '</li>';
                                    }
                                    echo '</ul>';
                                    
                                    echo '<div class="mt-4">';
                                    echo '<a href="?step=4" class="btn btn-primary">Continue to Completion <i class="fas fa-arrow-right ms-2"></i></a>';
                                    echo '</div>';
                                    
                                } else {
                                    echo '<div class="alert alert-warning">';
                                    echo '<strong>Warning:</strong> Could not scrape initial data. You can do this later from the main interface.';
                                    echo '</div>';
                                    
                                    echo '<div class="mt-4">';
                                    echo '<a href="?step=4" class="btn btn-secondary">Skip and Continue</a>';
                                    echo '</div>';
                                }
                                
                            } catch (Exception $e) {
                                echo '<div class="alert alert-danger">';
                                echo '<strong>Scraping Error:</strong> ' . $e->getMessage();
                                echo '</div>';
                            }
                        } else {
                        ?>
                        
                        <p>Now let's fetch the initial gold rates from BAJUS website to populate your database.</p>
                        
                        <div class="alert alert-warning">
                            <strong>Note:</strong> This will attempt to scrape data from the BAJUS website. 
                            Make sure you have an internet connection.
                        </div>
                        
                        <form method="POST">
                            <button type="submit" name="scrape_initial" class="btn btn-success">
                                <i class="fas fa-download me-2"></i>
                                Fetch Initial Data
                            </button>
                            <a href="?step=4" class="btn btn-secondary ms-2">
                                Skip This Step
                            </a>
                        </form>
                        
                        <?php } ?>
                        
                        <?php elseif ($step == 4): ?>
                        <!-- Step 4: Completion -->
                        <h4>Step 4: Installation Complete!</h4>
                        
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Congratulations!</strong> Gold Rate Tracker has been installed successfully.
                        </div>
                        
                        <h5>Next Steps:</h5>
                        <ul class="list-group mb-4">
                            <li class="list-group-item">
                                <i class="fas fa-globe me-2"></i>
                                <strong>Access your site:</strong> 
                                <a href="index.php" target="_blank">Open Gold Rate Tracker</a>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-clock me-2"></i>
                                <strong>Set up automation:</strong> Add this to your crontab for daily updates:
                                <code class="d-block mt-2">0 10 * * * /usr/bin/php <?= __DIR__ ?>/daily_scraper.php</code>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-shield-alt me-2"></i>
                                <strong>Security:</strong> Consider deleting this install.php file for security.
                            </li>
                        </ul>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <a href="index.php" class="btn btn-success btn-lg w-100">
                                    <i class="fas fa-rocket me-2"></i>
                                    Launch Application
                                </a>
                            </div>
                            <div class="col-md-6">
                                <button onclick="deleteInstaller()" class="btn btn-danger btn-lg w-100">
                                    <i class="fas fa-trash me-2"></i>
                                    Delete Installer
                                </button>
                            </div>
                        </div>
                        
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div class="mt-4">
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" 
                             style="width: <?= ($step * 25) ?>%" 
                             aria-valuenow="<?= $step ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="4">
                            Step <?= $step ?> of 4
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteInstaller() {
            if (confirm('Are you sure you want to delete the installer? This action cannot be undone.')) {
                fetch('<?= $_SERVER['PHP_SELF'] ?>?action=delete_installer', {
                    method: 'POST'
                })
                .then(response => response.text())
                .then(data => {
                    alert('Installer deleted successfully!');
                    window.location.href = 'index.php';
                })
                .catch(error => {
                    alert('Error deleting installer: ' + error);
                });
            }
        }
    </script>
</body>
</html>

<?php
// Handle installer deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_installer') {
    if (unlink(__FILE__)) {
        echo 'success';
    } else {
        echo 'error';
    }
    exit;
}
?>