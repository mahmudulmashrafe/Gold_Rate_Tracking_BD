<?php
require_once 'config.php';
require_once 'database_functions.php';
require_once 'scraper.php';

$db = new GoldRateDatabase();

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_chart_data':
            $days = isset($_GET['days']) ? intval($_GET['days']) : 30;
            $metal = isset($_GET['metal']) ? $_GET['metal'] : 'gold';
            $karat = isset($_GET['karat']) ? $_GET['karat'] : '22k';
            echo json_encode($db->getChartData($days, $metal, $karat));
            exit;
            
        case 'get_current_rates':
            echo json_encode($db->getCurrentRates());
            exit;
            
        case 'manual_scrape':
            $scraper = new GoldRateScraper();
            $result = $scraper->runSilent();
            echo json_encode(['success' => $result]);
            exit;
    }
}

// Get data for the page
$currentRates = $db->getCurrentRates();
$comparison = $db->getPriceComparison();
$statistics = $db->getStatistics(30);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bangladesh Gold Rate Tracker</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3e%3ccircle cx='50' cy='50' r='45' fill='%23FFD700'/%3e%3ctext x='50' y='65' text-anchor='middle' font-family='Arial' font-size='45' font-weight='bold' fill='%23B8860B'%3e৳%3c/text%3e%3c/svg%3e">
    <link rel="apple-touch-icon" sizes="180x180" href="data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3e%3ccircle cx='50' cy='50' r='45' fill='%23FFD700'/%3e%3ctext x='50' y='65' text-anchor='middle' font-family='Arial' font-size='45' font-weight='bold' fill='%23B8860B'%3e৳%3c/text%3e%3c/svg%3e">
    <link rel="icon" type="image/png" sizes="32x32" href="data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3e%3ccircle cx='50' cy='50' r='45' fill='%23FFD700'/%3e%3ctext x='50' y='65' text-anchor='middle' font-family='Arial' font-size='45' font-weight='bold' fill='%23B8860B'%3e৳%3c/text%3e%3c/svg%3e">
    <link rel="icon" type="image/png" sizes="16x16" href="data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3e%3ccircle cx='50' cy='50' r='45' fill='%23FFD700'/%3e%3ctext x='50' y='65' text-anchor='middle' font-family='Arial' font-size='45' font-weight='bold' fill='%23B8860B'%3e৳%3c/text%3e%3c/svg%3e">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --gold-primary: #FFD700;
            --gold-secondary: #FFA500;
            --gold-dark: #B8860B;
            --silver-primary: #C0C0C0;
            --silver-secondary: #A9A9A9;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --primary-blue: #0066cc;
            --light-bg: #f8f9fa;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .gold-card {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-secondary));
            color: #333;
            border: none;
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
        }

        .silver-card {
            background: linear-gradient(135deg, var(--silver-primary), var(--silver-secondary));
            color: #333;
            border: none;
            box-shadow: 0 8px 25px rgba(192, 192, 192, 0.3);
        }

        .rate-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }

        .rate-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.25rem;
        }

        .navbar {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .chart-container {
            position: relative;
            height: 400px;
            margin: 20px 0;
            background: white;
            border-radius: 10px;
            padding: 20px;
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Calculator Specific Styles */
        .calculator-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .calculator-card .card-body {
            background: white;
            color: #333;
            margin: 0;
            border-radius: 0 0 15px 15px;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.2rem rgba(0, 102, 204, 0.25);
        }

        .calculator-result {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            border-radius: 12px;
            padding: 20px;
        }

        .calculator-stats {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            margin-top: 15px;
        }

        .stat-item {
            text-align: center;
            padding: 10px;
        }

        .stat-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-blue);
        }

        .btn-update {
            background: linear-gradient(135deg, var(--success-color), #20c997);
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        }

        /* Icon improvements */
        .card-icon {
            font-size: 1.3rem;
            margin-right: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .rate-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        /* Statistics card */
        .stats-card {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%);
        }

        .stats-card .card-body {
            background: white;
            margin: 0;
            border-radius: 0 0 15px 15px;
        }

        /* Badge improvements */
        .change-badge {
            font-size: 0.9rem;
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 600;
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .rate-card {
                margin-bottom: 1rem;
            }
            
            .chart-container {
                height: 300px;
                padding: 10px;
            }
            
            /* Mobile navbar improvements */
            .navbar .container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .navbar-brand {
                font-size: 1.1rem !important;
                line-height: 1.3;
            }
            
            .navbar-brand span {
                display: block;
                margin-top: 5px;
            }
            
            .navbar .d-flex {
                flex-direction: column;
                gap: 10px;
                width: 100%;
            }
            
            .btn-update {
                padding: 8px 20px;
                font-size: 0.9rem;
            }
            
            .navbar-text {
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 576px) {
            .container {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            .navbar-brand {
                font-size: 1rem !important;
            }
            
            .card {
                margin-bottom: 1rem;
            }
            
            /* Mobile rate cards improvements */
            .rate-card .card-body {
                padding: 15px;
            }
            
            .rate-card h4 {
                font-size: 1.3rem;
            }
            
            .rate-card h6 {
                font-size: 0.9rem;
            }
            
            .change-badge {
                font-size: 0.75rem;
                padding: 6px 10px;
            }
            
            /* Section titles mobile */
            h4, h5 {
                font-size: 1.1rem !important;
            }
            
            /* Statistics mobile layout */
            .col-lg-6 .row.g-1 .col-6 {
                margin-bottom: 8px;
            }
            
            .col-lg-6 .row.g-1 .col-6 .p-2 {
                padding: 10px !important;
            }
            
            .col-lg-6 .row.g-1 .col-6 .text-white {
                font-size: 0.7rem !important;
            }
            
            /* Mobile calculator improvements */
            .calculator-card .card-body {
                padding: 15px;
            }
            
            .form-control, .form-select {
                padding: 10px 12px;
                font-size: 0.9rem;
            }
            
            .calculator-result {
                padding: 15px;
                margin-bottom: 15px;
            }
            
            .calculator-result h4 {
                font-size: 1.2rem;
            }
            
            .calculator-result h5 {
                font-size: 1rem;
            }
            
            .calculator-result h6 {
                font-size: 0.9rem;
            }
            
            .calculator-stats {
                padding: 12px;
            }
            
            .stat-value {
                font-size: 1rem;
            }
            
            .stat-item {
                padding: 8px;
            }
            
            /* Mobile chart improvements */
            .chart-card .card-header h4 {
                font-size: 1.2rem;
            }
            
            .chart-container {
                height: 250px !important;
                padding: 15px !important;
            }
            
            /* Chart controls mobile layout */
            .chart-card .row .col-md-4 {
                margin-bottom: 15px;
            }
            
            .form-select-lg {
                font-size: 0.9rem;
                padding: 8px 12px;
            }
            
            .chart-card .form-label {
                font-size: 0.9rem;
                margin-bottom: 5px;
            }
            
            /* Chart stats mobile */
            .chart-stat {
                margin-bottom: 15px;
            }
            
            .chart-stat i {
                font-size: 1rem !important;
            }
            
            .chart-stat .text-white {
                font-size: 0.9rem;
            }
            
            .chart-stat .fw-bold {
                font-size: 0.9rem;
            }
            
            /* Touch-friendly improvements */
            .btn {
                min-height: 44px;
                padding: 12px 20px;
            }
            
            .form-control, .form-select {
                min-height: 44px;
            }
            
            .rate-card {
                cursor: pointer;
                user-select: none;
                -webkit-tap-highlight-color: transparent;
            }
            
            .rate-card:active {
                transform: scale(0.98);
            }
            
            /* Better spacing for mobile */
            .row {
                margin-left: -8px;
                margin-right: -8px;
            }
            
            .row > * {
                padding-left: 8px;
                padding-right: 8px;
            }
            
            /* Larger tap targets */
            .change-badge {
                min-height: 32px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
        }
        
        /* Extra small devices (phones in portrait) */
        @media (max-width: 480px) {
            body {
                font-size: 14px;
            }
            
            .navbar {
                padding: 10px 0;
            }
            
            .navbar-brand {
                font-size: 0.9rem !important;
            }
            
            .btn-update {
                font-size: 0.8rem;
                padding: 6px 15px;
            }
            
            .rate-card h4 {
                font-size: 1.1rem;
            }
            
            .rate-card h6 {
                font-size: 0.8rem;
            }
            
            .calculator-result h4 {
                font-size: 1.1rem;
            }
            
            .chart-container {
                height: 200px !important;
                padding: 10px !important;
            }
            
            .form-label {
                font-size: 0.8rem;
            }
            
            .form-control, .form-select {
                font-size: 0.8rem;
                padding: 8px 10px;
            }
            
            /* Statistics grid for very small screens */
            .col-lg-6 .row.g-1 .col-6 {
                flex: 0 0 50%;
                max-width: 50%;
            }
            
            /* Mobile statistics improvements */
            .stats-card .card-body {
                padding: 10px;
            }
            
            .stats-card .card-header {
                padding: 8px 15px;
            }
            
            /* Stack statistics on very small screens */
            .statistics-row .col-lg-6 {
                margin-bottom: 10px;
            }
            
            .statistics-item {
                background: rgba(255, 255, 255, 0.1);
                border-radius: 8px;
                padding: 8px;
                margin-bottom: 5px;
            }
        }

        /* Animation for loading */
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .loading {
            animation: pulse 1.5s infinite;
        }

        /* Footer improvements */
        footer {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%) !important;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <!-- Header -->
        <nav class="navbar navbar-dark mb-5">
            <div class="container">
                <span class="navbar-brand mb-0 h1">
                    <i class="fas fa-gem me-2" style="color: #FFD700;"></i>
                    <span style="background: linear-gradient(45deg, #FFD700, #FFA500); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 700;">Bangladesh Gold Rate Tracker</span>
                </span>
                <div class="d-flex align-items-center mobile-nav-controls">
                    <button class="btn btn-update btn-sm me-3" onclick="manualScrape()">
                        <i class="fas fa-sync-alt me-1"></i>
                        <span class="d-none d-sm-inline">Update Rates</span>
                        <span class="d-inline d-sm-none">Update</span>
                    </button>
                    <span class="navbar-text">
                        <i class="fas fa-clock me-1" style="color: #20c997;"></i>
                        <span class="d-none d-md-inline">Last Updated: </span>
                        <?= $currentRates ? date('d M Y, H:i', strtotime($currentRates['updated_at'])) : 'Never' ?>
                    </span>
                </div>
            </div>
        </nav>

        <!-- Current Rates Section -->
        <div class="container mb-5">
            <div class="row">
                <div class="col-12">
                    <div class="text-center mb-4">
                        <h4 class="fw-bold mb-2" style="color: #2c3e50;">
                            Current Gold & Silver Rates
                        </h4>
                        <p class="text-muted mb-0">
                            <i class="fas fa-calendar-day me-2"></i>
                            Today: <?= date('l, F j, Y') ?>
                        </p>
                    </div>
                </div>
            </div>

            <?php if ($currentRates): ?>
            <div class="row mb-4">
                <!-- Gold & Silver Rates -->
                <div class="col-lg-8">
                    <!-- Gold Rates -->
                    <div class="row mb-3">
                        <div class="col-12 mb-2">
                            <h5 class="text-center mb-3" style="color: #B8860B;">
                                <i class="fas fa-crown me-2"></i>Gold Rates
                            </h5>
                        </div>
                        <div class="col-6 col-sm-6 col-lg-3 mb-3">
                    <div class="card rate-card gold-card h-100">
                        <div class="card-body text-center py-3">
                            <h6 class="card-title fw-bold mb-2">22K</h6>
                            <h4 class="fw-bold mb-1">৳<?= number_format($currentRates['gold_22k'], 0) ?></h4>
                            <small class="text-muted">per gram</small>
                            <?php if (isset($comparison['changes']) && $comparison['changes']['gold_22k_change'] != 0): ?>
                            <div class="mt-2">
                                <span class="change-badge <?= $comparison['changes']['gold_22k_change'] >= 0 ? 'bg-success' : 'bg-danger' ?>">
                                    <i class="fas fa-<?= $comparison['changes']['gold_22k_change'] >= 0 ? 'trending-up' : 'trending-down' ?>"></i>
                                    ৳<?= number_format(abs($comparison['changes']['gold_22k_change']), 2) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                        <div class="col-6 col-sm-6 col-lg-3 mb-3">
                    <div class="card rate-card gold-card h-100">
                        <div class="card-body text-center py-3">
                            <h6 class="card-title fw-bold mb-2">21K</h6>
                            <h4 class="fw-bold mb-1">৳<?= number_format($currentRates['gold_21k'], 0) ?></h4>
                            <small class="text-muted">per gram</small>
                            <?php if (isset($comparison['changes']) && $comparison['changes']['gold_21k_change'] != 0): ?>
                            <div class="mt-2">
                                <span class="change-badge <?= $comparison['changes']['gold_21k_change'] >= 0 ? 'bg-success' : 'bg-danger' ?>">
                                    <i class="fas fa-<?= $comparison['changes']['gold_21k_change'] >= 0 ? 'trending-up' : 'trending-down' ?>"></i>
                                    ৳<?= number_format(abs($comparison['changes']['gold_21k_change']), 2) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                        <div class="col-6 col-sm-6 col-lg-3 mb-3">
                    <div class="card rate-card gold-card h-100">
                        <div class="card-body text-center py-3">
                            <h6 class="card-title fw-bold mb-2">18K</h6>
                            <h4 class="fw-bold mb-1">৳<?= number_format($currentRates['gold_18k'], 0) ?></h4>
                            <small class="text-muted">per gram</small>
                            <?php if (isset($comparison['changes']) && $comparison['changes']['gold_18k_change'] != 0): ?>
                            <div class="mt-2">
                                <span class="change-badge <?= $comparison['changes']['gold_18k_change'] >= 0 ? 'bg-success' : 'bg-danger' ?>">
                                    <i class="fas fa-<?= $comparison['changes']['gold_18k_change'] >= 0 ? 'trending-up' : 'trending-down' ?>"></i>
                                    ৳<?= number_format(abs($comparison['changes']['gold_18k_change']), 2) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                        <div class="col-6 col-sm-6 col-lg-3 mb-3">
                    <div class="card rate-card gold-card h-100">
                        <div class="card-body text-center py-3">
                            <h6 class="card-title fw-bold mb-2">Traditional</h6>
                            <h4 class="fw-bold mb-1">৳<?= number_format($currentRates['gold_traditional'], 0) ?></h4>
                            <small class="text-muted">per gram</small>
                            <?php if (isset($comparison['changes']) && $comparison['changes']['gold_traditional_change'] != 0): ?>
                            <div class="mt-2">
                                <span class="change-badge <?= $comparison['changes']['gold_traditional_change'] >= 0 ? 'bg-success' : 'bg-danger' ?>">
                                    <i class="fas fa-<?= $comparison['changes']['gold_traditional_change'] >= 0 ? 'trending-up' : 'trending-down' ?>"></i>
                                    ৳<?= number_format(abs($comparison['changes']['gold_traditional_change']), 2) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                                            </div>
                    </div>
                    </div>

                    <!-- Silver Rates -->
                    <div class="row mb-3">
                        <div class="col-12 mb-2">
                            <h5 class="text-center mb-3" style="color: #A9A9A9;">
                                <i class="fas fa-star me-2"></i>Silver Rates
                            </h5>
                        </div>
                        <div class="col-6 col-sm-6 col-lg-3 mb-3">
                    <div class="card rate-card silver-card h-100">
                        <div class="card-body text-center py-3">
                            <h6 class="card-title fw-bold mb-2">22K</h6>
                            <h4 class="fw-bold mb-1">৳<?= number_format($currentRates['silver_22k'], 0) ?></h4>
                            <small class="text-muted">per gram</small>
                        </div>
                    </div>
                </div>
                        <div class="col-6 col-sm-6 col-lg-3 mb-3">
                    <div class="card rate-card silver-card h-100">
                        <div class="card-body text-center py-3">
                            <h6 class="card-title fw-bold mb-2">21K</h6>
                            <h4 class="fw-bold mb-1">৳<?= number_format($currentRates['silver_21k'], 0) ?></h4>
                            <small class="text-muted">per gram</small>
                        </div>
                    </div>
                </div>
                        <div class="col-6 col-sm-6 col-lg-3 mb-3">
                    <div class="card rate-card silver-card h-100">
                        <div class="card-body text-center py-3">
                            <h6 class="card-title fw-bold mb-2">18K</h6>
                            <h4 class="fw-bold mb-1">৳<?= number_format($currentRates['silver_18k'], 0) ?></h4>
                            <small class="text-muted">per gram</small>
                        </div>
                    </div>
                </div>
                        <div class="col-6 col-sm-6 col-lg-3 mb-3">
                    <div class="card rate-card silver-card h-100">
                        <div class="card-body text-center py-3">
                            <h6 class="card-title fw-bold mb-2">Traditional</h6>
                            <h4 class="fw-bold mb-1">৳<?= number_format($currentRates['silver_traditional'], 0) ?></h4>
                            <small class="text-muted">per gram</small>
                        </div>
                    </div>
                    </div>
                    </div>

                    <!-- 30-Day Statistics -->
                    <div class="row mb-2">
                        <div class="col-12">
                            <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);">
                                <div class="card-header border-0 py-2" style="background: transparent;">
                                    <h6 class="mb-0 text-white text-center">
                                        <i class="fas fa-chart-line me-1"></i>
                                        30-Day Statistics
                                    </h6>
                                </div>
                                <div class="card-body py-2">
                                    <?php if ($statistics): ?>
                                    <!-- Gold & Silver in Same Row -->
                                    <div class="row g-2">
                                        <!-- Gold Section -->
                                        <div class="col-lg-6">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-crown me-1" style="color: #FFD700; font-size: 0.9rem;"></i>
                                                <small class="text-white fw-bold">GOLD</small>
                                            </div>
                                            <div class="row g-1">
                                                <div class="col-6">
                                                    <div class="p-2 rounded" style="background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(10px);">
                                                        <div class="text-center">
                                                            <div class="text-warning fw-bold mb-1" style="font-size: 0.7rem;">22K</div>
                                                            <div class="text-white" style="font-size: 0.75rem;">
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Min:</span>
                                                                    <span>৳<?= number_format($statistics['min_gold_22k'], 0) ?></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Max:</span>
                                                                    <span>৳<?= number_format($statistics['max_gold_22k'], 0) ?></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Avg:</span>
                                                                    <span class="text-warning">৳<?= number_format($statistics['avg_gold_22k'], 0) ?></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="p-2 rounded" style="background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(10px);">
                                                        <div class="text-center">
                                                            <div class="text-warning fw-bold mb-1" style="font-size: 0.7rem;">21K</div>
                                                            <div class="text-white" style="font-size: 0.75rem;">
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Min:</span>
                                                                    <span>৳<?= number_format($statistics['min_gold_21k'], 0) ?></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Max:</span>
                                                                    <span>৳<?= number_format($statistics['max_gold_21k'], 0) ?></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Avg:</span>
                                                                    <span class="text-warning">৳<?= number_format($statistics['avg_gold_21k'], 0) ?></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="p-2 rounded" style="background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(10px);">
                                                        <div class="text-center">
                                                            <div class="text-warning fw-bold mb-1" style="font-size: 0.7rem;">18K</div>
                                                            <div class="text-white" style="font-size: 0.75rem;">
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Min:</span>
                                                                    <span>৳<?= number_format($statistics['min_gold_18k'], 0) ?></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Max:</span>
                                                                    <span>৳<?= number_format($statistics['max_gold_18k'], 0) ?></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Avg:</span>
                                                                    <span class="text-warning">৳<?= number_format($statistics['avg_gold_18k'], 0) ?></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="p-2 rounded" style="background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(10px);">
                                                        <div class="text-center">
                                                            <div class="text-warning fw-bold mb-1" style="font-size: 0.7rem;">TRAD</div>
                                                            <div class="text-white" style="font-size: 0.75rem;">
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Min:</span>
                                                                    <span>৳<?= number_format($statistics['min_gold_traditional'], 0) ?></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Max:</span>
                                                                    <span>৳<?= number_format($statistics['max_gold_traditional'], 0) ?></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Avg:</span>
                                                                    <span class="text-warning">৳<?= number_format($statistics['avg_gold_traditional'], 0) ?></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Silver Section -->
                                        <div class="col-lg-6">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-star me-1" style="color: #C0C0C0; font-size: 0.9rem;"></i>
                                                <small class="text-white fw-bold">SILVER</small>
                                            </div>
                                            <div class="row g-1">
                                                <div class="col-6">
                                                    <div class="p-2 rounded" style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px);">
                                                        <div class="text-center">
                                                            <div class="fw-bold mb-1" style="color: #C0C0C0; font-size: 0.7rem;">22K</div>
                                                            <div class="text-white" style="font-size: 0.75rem;">
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Min:</span>
                                                                    <span>৳<?= number_format($statistics['min_silver_22k'], 0) ?></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Max:</span>
                                                                    <span>৳<?= number_format($statistics['max_silver_22k'], 0) ?></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Avg:</span>
                                                                    <span style="color: #C0C0C0;">৳<?= number_format($statistics['avg_silver_22k'], 0) ?></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="p-2 rounded" style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px);">
                                                        <div class="text-center">
                                                            <div class="fw-bold mb-1" style="color: #C0C0C0; font-size: 0.7rem;">21K</div>
                                                            <div class="text-white" style="font-size: 0.75rem;">
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Min:</span>
                                                                    <span>৳<?= number_format($statistics['min_silver_21k'], 0) ?></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Max:</span>
                                                                    <span>৳<?= number_format($statistics['max_silver_21k'], 0) ?></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Avg:</span>
                                                                    <span style="color: #C0C0C0;">৳<?= number_format($statistics['avg_silver_21k'], 0) ?></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="p-2 rounded" style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px);">
                                                        <div class="text-center">
                                                            <div class="fw-bold mb-1" style="color: #C0C0C0; font-size: 0.7rem;">18K</div>
                                                            <div class="text-white" style="font-size: 0.75rem;">
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Min:</span>
                                                                    <span>৳<?= number_format($statistics['min_silver_18k'], 0) ?></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Max:</span>
                                                                    <span>৳<?= number_format($statistics['max_silver_18k'], 0) ?></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Avg:</span>
                                                                    <span style="color: #C0C0C0;">৳<?= number_format($statistics['avg_silver_18k'], 0) ?></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="p-2 rounded" style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px);">
                                                        <div class="text-center">
                                                            <div class="fw-bold mb-1" style="color: #C0C0C0; font-size: 0.7rem;">TRAD</div>
                                                            <div class="text-white" style="font-size: 0.75rem;">
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Min:</span>
                                                                    <span>৳<?= number_format($statistics['min_silver_traditional'], 0) ?></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Max:</span>
                                                                    <span>৳<?= number_format($statistics['max_silver_traditional'], 0) ?></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Avg:</span>
                                                                    <span style="color: #C0C0C0;">৳<?= number_format($statistics['avg_silver_traditional'], 0) ?></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center mt-2">
                                        <small class="text-white-50" style="font-size: 0.7rem;">
                                            <i class="fas fa-info-circle me-1"></i>
                                            <?= $statistics['total_records'] ?> records (30 days)
                                        </small>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-chart-line mb-2" style="font-size: 2rem; color: rgba(255,255,255,0.5);"></i>
                                        <p class="text-white mb-0" style="font-size: 0.9rem;">No data available</p>
                                        <small class="text-white-50" style="font-size: 0.75rem;">Update rates to generate statistics</small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Price Calculator -->
                <div class="col-lg-4">
                    <div class="card calculator-card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-calculator card-icon"></i>
                                Price Calculator
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-sm-6">
                                    <label for="metalType" class="form-label">Metal Type:</label>
                                    <select class="form-select" id="metalType" onchange="calculatePrice()">
                                        <option value="gold">Gold</option>
                                        <option value="silver">Silver</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label for="karatType" class="form-label">Karat/Type:</label>
                                    <select class="form-select" id="karatType" onchange="calculatePrice()">
                                        <option value="22k">22 Karat</option>
                                        <option value="21k">21 Karat</option>
                                        <option value="18k">18 Karat</option>
                                        <option value="traditional">Traditional</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-sm-6">
                                    <label for="weightInput" class="form-label">Weight:</label>
                                    <input type="number" class="form-control" id="weightInput" placeholder="Enter weight" min="0" step="0.01" oninput="calculatePrice()">
                                </div>
                                <div class="col-sm-6">
                                    <label for="weightUnit" class="form-label">Unit:</label>
                                    <select class="form-select" id="weightUnit" onchange="calculatePrice()">
                                        <option value="gram">Gram</option>
                                        <option value="tola">Tola</option>
                                        <option value="vhori">Vhori</option>
                                        <option value="ounce">Ounce</option>
                                        <option value="kg">KG</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="includeMaking" checked onchange="toggleMakingCharges()">
                                        <label class="form-check-label" for="includeMaking">
                                            Include Making Charges
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div id="makingChargeSettings" class="row mb-3">
                                <div class="col-sm-6">
                                    <label for="chargeType" class="form-label">Charge Type:</label>
                                    <select class="form-select form-select-sm" id="chargeType" disabled onchange="calculatePrice()">
                                        <option value="percentage">Percentage (%)</option>
                                        <option value="fixed">Fixed Amount (৳)</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label for="chargeValue" class="form-label">Charge Value:</label>
                                    <input type="number" class="form-control form-control-sm" id="chargeValue" value="8" min="0" step="0.1" disabled oninput="calculatePrice()">
                                </div>
                            </div>
                            
                            <div class="calculator-result mb-3" id="calculatorResult">
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold"><i class="fas fa-coins me-2"></i>Material Cost:</span>
                                        <span class="h5 mb-0 text-white" id="materialCost">৳0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-1" id="makingCostRow">
                                        <span class="fw-bold"><i class="fas fa-tools me-2"></i>Making Charges:</span>
                                        <span class="h6 mb-0 text-white" id="makingCost">+৳0.00</span>
                                    </div>
                                    <hr class="my-2" style="opacity: 0.5;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold"><i class="fas fa-tag me-2"></i>Total Cost:</span>
                                        <span class="h4 mb-0 text-white" id="totalCost">৳0.00</span>
                                    </div>
                                </div>
                                <small class="d-block text-center" id="calcDetails" style="opacity: 0.9;">Enter weight to calculate price</small>
                            </div>
                            
                            <div class="calculator-stats">
                                <div class="row text-center">
                                    <div class="col-6 stat-item">
                                        <div class="text-muted mb-1"><i class="fas fa-weight me-1"></i>Per Gram Rate</div>
                                        <div class="stat-value" id="perGramRate">৳0.00</div>
                                    </div>
                                    <div class="col-6 stat-item">
                                        <div class="text-muted mb-1"><i class="fas fa-balance-scale me-1"></i>Total Weight</div>
                                        <div class="stat-value" id="totalGrams">0.00g</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                No current rates available. Please run the scraper to fetch latest data.
            </div>
            <?php endif; ?>
        </div>

        <!-- Chart Section -->
        <div class="container mb-5">
            <div class="row">
                <div class="col-12">
                    <div class="card chart-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);">
                        <div class="card-header border-0" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);">
                            <div class="d-flex align-items-center justify-content-center">
                                <i class="fas fa-chart-line me-2" style="color: #FFD700; font-size: 1.5rem;"></i>
                                <h4 class="mb-0 text-white fw-bold">Price History Chart</h4>
                                <i class="fas fa-chart-line ms-2" style="color: #FFD700; font-size: 1.5rem;"></i>
                            </div>
                        </div>
                        <div class="card-body" style="background: rgba(255,255,255,0.05);">
                            <!-- Chart Controls -->
                            <div class="row mb-4">
                                <div class="col-md-4 mb-3">
                                    <label for="metalSelect" class="form-label text-white fw-bold">
                                        <i class="fas fa-gem me-2" style="color: #FFD700;"></i>Metal Type:
                                    </label>
                                    <select class="form-select form-select-lg" id="metalSelect" onchange="updateChart()" style="background: rgba(255,255,255,0.9); border: 2px solid #FFD700; border-radius: 10px; font-weight: bold;">
                                        <option value="gold">🥇 Gold</option>
                                        <option value="silver">🥈 Silver</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="karatSelect" class="form-label text-white fw-bold">
                                        <i class="fas fa-star me-2" style="color: #FFD700;"></i>Karat Type:
                                    </label>
                                    <select class="form-select form-select-lg" id="karatSelect" onchange="updateChart()" style="background: rgba(255,255,255,0.9); border: 2px solid #FFD700; border-radius: 10px; font-weight: bold;">
                                        <option value="22k">⭐ 22K Premium</option>
                                        <option value="21k">🌟 21K Standard</option>
                                        <option value="18k">✨ 18K Classic</option>
                                        <option value="traditional">🏺 Traditional</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="daysSelect" class="form-label text-white fw-bold">
                                        <i class="fas fa-calendar-alt me-2" style="color: #FFD700;"></i>Time Period:
                                    </label>
                                    <select class="form-select form-select-lg" id="daysSelect" onchange="updateChart()" style="background: rgba(255,255,255,0.9); border: 2px solid #FFD700; border-radius: 10px; font-weight: bold;">
                                        <option value="7">📅 Last 7 Days</option>
                                        <option value="30" selected>📆 Last 30 Days</option>
                                        <option value="90">🗓️ Last 3 Months</option>
                                        <option value="365">📊 Last Year</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Chart Info Bar -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="p-3 rounded" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2);">
                                        <div class="row text-center">
                                            <div class="col-md-3">
                                                <div class="chart-stat">
                                                    <i class="fas fa-arrow-trend-up mb-2" style="color: #28a745; font-size: 1.2rem;"></i>
                                                    <div class="text-white fw-bold">Highest Price</div>
                                                    <div class="text-success fw-bold" id="chartMax">৳0</div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="chart-stat">
                                                    <i class="fas fa-arrow-trend-down mb-2" style="color: #dc3545; font-size: 1.2rem;"></i>
                                                    <div class="text-white fw-bold">Lowest Price</div>
                                                    <div class="text-danger fw-bold" id="chartMin">৳0</div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="chart-stat">
                                                    <i class="fas fa-chart-bar mb-2" style="color: #ffc107; font-size: 1.2rem;"></i>
                                                    <div class="text-white fw-bold">Average Price</div>
                                                    <div class="text-warning fw-bold" id="chartAvg">৳0</div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="chart-stat">
                                                    <i class="fas fa-percentage mb-2" style="color: #17a2b8; font-size: 1.2rem;"></i>
                                                    <div class="text-white fw-bold">Price Change</div>
                                                    <div class="text-info fw-bold" id="chartChange">0%</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Chart Canvas -->
                            <div class="chart-container" style="background: rgba(255,255,255,0.95); border-radius: 15px; padding: 20px; box-shadow: 0 8px 25px rgba(0,0,0,0.1);">
                                <canvas id="priceChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-dark text-light py-4 mt-5">
            <div class="container text-center">
                <p class="mb-0">
                    <i class="fas fa-database me-2"></i>
                    Data source: <a href="https://www.bajus.org/gold-price" target="_blank" class="text-light">BAJUS (Bangladesh Jewellers Association)</a>
                </p>
                <small class="text-muted">
                    Rates are updated daily. This is for informational purposes only.
                </small>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let priceChart;

        // Initialize chart on page load
        document.addEventListener('DOMContentLoaded', function() {
            initChart();
            updateChart();
        });

        function initChart() {
            const ctx = document.getElementById('priceChart').getContext('2d');
            
            // Create gradient
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(255, 215, 0, 0.8)');
            gradient.addColorStop(0.5, 'rgba(255, 215, 0, 0.4)');
            gradient.addColorStop(1, 'rgba(255, 215, 0, 0.1)');
            
            priceChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: []
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    layout: {
                        padding: {
                            top: 20,
                            right: 20,
                            bottom: 20,
                            left: 20
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)',
                                lineWidth: 1
                            },
                            ticks: {
                                color: '#333',
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                },
                                callback: function(value) {
                                    return '৳' + value.toLocaleString();
                                }
                            },
                            title: {
                                display: true,
                                text: '💰 Price (BDT)',
                                color: '#333',
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                }
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)',
                                lineWidth: 1
                            },
                            ticks: {
                                color: '#333',
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            },
                            title: {
                                display: true,
                                text: '📅 Date',
                                color: '#333',
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                color: '#333',
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#FFD700',
                            bodyColor: '#fff',
                            borderColor: '#FFD700',
                            borderWidth: 2,
                            cornerRadius: 10,
                            displayColors: true,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            callbacks: {
                                label: function(context) {
                                    return '💎 ' + context.dataset.label + ': ৳' + context.parsed.y.toLocaleString();
                                },
                                title: function(context) {
                                    return '📅 ' + context[0].label;
                                }
                            }
                        }
                    },
                    elements: {
                        line: {
                            tension: 0.4,
                            borderWidth: 3,
                            fill: true
                        },
                        point: {
                            radius: 5,
                            hoverRadius: 8,
                            borderWidth: 2,
                            hoverBorderWidth: 3
                        }
                    },
                    animation: {
                        duration: 2000,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        }

        function updateChart() {
            const metal = document.getElementById('metalSelect').value;
            const karat = document.getElementById('karatSelect').value;
            const days = document.getElementById('daysSelect').value;

            // Show loading state
            document.querySelector('.chart-container').classList.add('loading');

            fetch(`?action=get_chart_data&metal=${metal}&karat=${karat}&days=${days}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.labels && data.datasets) {
                        // Enhanced chart styling
                        if (data.datasets.length > 0) {
                            const dataset = data.datasets[0];
                            const isGold = metal === 'gold';
                            
                            // Create gradient based on metal type
                            const ctx = priceChart.ctx;
                            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
                            
                            if (isGold) {
                                gradient.addColorStop(0, 'rgba(255, 215, 0, 0.8)');
                                gradient.addColorStop(0.5, 'rgba(255, 215, 0, 0.4)');
                                gradient.addColorStop(1, 'rgba(255, 215, 0, 0.1)');
                                dataset.borderColor = '#FFD700';
                                dataset.backgroundColor = gradient;
                                dataset.pointBackgroundColor = '#FFD700';
                                dataset.pointBorderColor = '#FFA500';
                            } else {
                                gradient.addColorStop(0, 'rgba(192, 192, 192, 0.8)');
                                gradient.addColorStop(0.5, 'rgba(192, 192, 192, 0.4)');
                                gradient.addColorStop(1, 'rgba(192, 192, 192, 0.1)');
                                dataset.borderColor = '#C0C0C0';
                                dataset.backgroundColor = gradient;
                                dataset.pointBackgroundColor = '#C0C0C0';
                                dataset.pointBorderColor = '#A0A0A0';
                            }
                            
                            dataset.borderWidth = 3;
                            dataset.tension = 0.4;
                            dataset.fill = true;
                            dataset.pointRadius = 5;
                            dataset.pointHoverRadius = 8;
                            dataset.pointBorderWidth = 2;
                            dataset.pointHoverBorderWidth = 3;
                        }
                        
                        priceChart.data = data;
                        priceChart.update('active');
                        
                        // Update statistics
                        updateChartStatistics(data);
                    }
                })
                .catch(error => {
                    console.error('Error updating chart:', error);
                    showNotification('Failed to load chart data', 'error');
                })
                .finally(() => {
                    document.querySelector('.chart-container').classList.remove('loading');
                });
        }

        function updateChartStatistics(data) {
            if (data && data.datasets && data.datasets.length > 0) {
                const values = data.datasets[0].data;
                if (values && values.length > 0) {
                    const numericValues = values.filter(v => v !== null && v !== undefined);
                    
                    if (numericValues.length > 0) {
                        const min = Math.min(...numericValues);
                        const max = Math.max(...numericValues);
                        const avg = numericValues.reduce((a, b) => a + b, 0) / numericValues.length;
                        const change = numericValues.length > 1 ? 
                            ((numericValues[numericValues.length - 1] - numericValues[0]) / numericValues[0] * 100) : 0;
                        
                        document.getElementById('chartMax').textContent = '৳' + max.toLocaleString();
                        document.getElementById('chartMin').textContent = '৳' + min.toLocaleString();
                        document.getElementById('chartAvg').textContent = '৳' + Math.round(avg).toLocaleString();
                        
                        const changeElement = document.getElementById('chartChange');
                        const changeText = (change >= 0 ? '+' : '') + change.toFixed(2) + '%';
                        changeElement.textContent = changeText;
                        
                        // Update change color based on positive/negative
                        if (change >= 0) {
                            changeElement.className = 'text-success fw-bold';
                        } else {
                            changeElement.className = 'text-danger fw-bold';
                        }
                    }
                }
            }
        }

        function manualScrape() {
            const button = event.target;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Updating...';

            fetch('?action=manual_scrape')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Rates updated successfully!', 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showNotification('Failed to update rates. Please try again.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error occurred while updating rates.', 'error');
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Update Rates';
                });
        }

        function showNotification(message, type) {
            // Remove any existing notifications
            const existingNotification = document.getElementById('notification');
            if (existingNotification) {
                existingNotification.remove();
            }

            // Create notification element
            const notification = document.createElement('div');
            notification.id = 'notification';
            notification.className = `notification notification-${type}`;
            
            const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
            const bgColor = type === 'success' ? '#28a745' : '#dc3545';
            
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas fa-${icon} me-2"></i>
                    ${message}
                </div>
            `;
            
            // Apply styles
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${bgColor};
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 9999;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                font-weight: 500;
                transform: translateX(400px);
                transition: transform 0.3s ease;
                max-width: 350px;
            `;
            
            // Mobile responsive notification positioning
            if (window.innerWidth <= 768) {
                notification.style.cssText += `
                    left: 10px;
                    right: 10px;
                    top: 10px;
                    max-width: none;
                    transform: translateY(-100px);
                `;
            }
            
            // Add to page
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                if (window.innerWidth <= 768) {
                    notification.style.transform = 'translateY(0)';
                } else {
                    notification.style.transform = 'translateX(0)';
                }
            }, 100);
            
            // Auto remove after 4 seconds
            setTimeout(() => {
                if (window.innerWidth <= 768) {
                    notification.style.transform = 'translateY(-100px)';
                } else {
                    notification.style.transform = 'translateX(400px)';
                }
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }, 4000);
        }

        // Toggle making charge settings visibility and editability
        function toggleMakingCharges() {
            const includeMaking = document.getElementById('includeMaking').checked;
            const makingCostRow = document.getElementById('makingCostRow');
            const chargeTypeSelect = document.getElementById('chargeType');
            const chargeValueInput = document.getElementById('chargeValue');
            
            if (includeMaking) {
                makingCostRow.style.display = 'flex';
                chargeTypeSelect.disabled = false;
                chargeValueInput.disabled = false;
            } else {
                makingCostRow.style.display = 'none';
                chargeTypeSelect.disabled = true;
                chargeValueInput.disabled = true;
            }
            calculatePrice();
        }

        // Price Calculator Function
        function calculatePrice() {
            const metalType = document.getElementById('metalType').value;
            const karatType = document.getElementById('karatType').value;
            const weight = parseFloat(document.getElementById('weightInput').value) || 0;
            const unit = document.getElementById('weightUnit').value;
            const includeMaking = document.getElementById('includeMaking').checked;
            const chargeType = document.getElementById('chargeType').value;
            const chargeValue = parseFloat(document.getElementById('chargeValue').value) || 0;
            
            // Get current rates from PHP
            const rates = <?= json_encode($currentRates) ?>;
            
            if (!rates || weight === 0) {
                document.getElementById('materialCost').textContent = '৳0.00';
                document.getElementById('totalCost').textContent = '৳0.00';
                document.getElementById('calcDetails').textContent = weight === 0 ? 'Enter weight to calculate price' : 'No current rates available';
                document.getElementById('perGramRate').textContent = '৳0.00';
                document.getElementById('totalGrams').textContent = '0.00g';
                document.getElementById('makingCost').textContent = '+৳0.00';
                return;
            }
            
            // Get rate per gram
            const rateKey = metalType + '_' + karatType;
            const ratePerGram = rates[rateKey] || 0;
            
            if (ratePerGram === 0) {
                document.getElementById('materialCost').textContent = '৳0.00';
                document.getElementById('totalCost').textContent = '৳0.00';
                document.getElementById('calcDetails').textContent = 'Rate not available for selected type';
                return;
            }
            
            // Convert weight to grams
            let weightInGrams = weight;
            switch (unit) {
                case 'tola':
                    weightInGrams = weight * 11.664;
                    break;
                case 'vhori':
                    weightInGrams = weight * 11.664;
                    break;
                case 'ounce':
                    weightInGrams = weight * 28.35;
                    break;
                case 'kg':
                    weightInGrams = weight * 1000;
                    break;
            }
            
            // Calculate costs
            const materialCost = weightInGrams * ratePerGram;
            let makingCost = 0;
            
            if (includeMaking) {
                if (chargeType === 'percentage') {
                    makingCost = materialCost * (chargeValue / 100);
                } else { // fixed amount
                    makingCost = chargeValue * weightInGrams; // fixed amount per gram
                }
            }
            
            const totalCost = materialCost + makingCost;
            
            // Update display
            document.getElementById('materialCost').textContent = '৳' + materialCost.toLocaleString('en-BD', {minimumFractionDigits: 0, maximumFractionDigits: 0});
            document.getElementById('totalCost').textContent = '৳' + totalCost.toLocaleString('en-BD', {minimumFractionDigits: 0, maximumFractionDigits: 0});
            document.getElementById('perGramRate').textContent = '৳' + ratePerGram.toLocaleString('en-BD', {minimumFractionDigits: 0});
            document.getElementById('totalGrams').textContent = weightInGrams.toFixed(2) + 'g';
            
            if (includeMaking) {
                if (chargeType === 'percentage') {
                    document.getElementById('makingCost').textContent = '+৳' + makingCost.toLocaleString('en-BD', {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ` (${chargeValue}%)`;
                } else {
                    document.getElementById('makingCost').textContent = '+৳' + makingCost.toLocaleString('en-BD', {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ` (৳${chargeValue}/g)`;
                }
            } else {
                document.getElementById('makingCost').textContent = 'Not included';
            }
            
            // Update details
            const unitText = unit === 'gram' ? 'grams' : unit + (weight > 1 ? 's' : '');
            document.getElementById('calcDetails').textContent = `${weight} ${unitText} of ${karatType.toUpperCase()} ${metalType} (${weightInGrams.toFixed(2)}g total)`;
        }
        
        // Initialize calculator on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculatePrice();
        });
    </script>
</body>
</html>