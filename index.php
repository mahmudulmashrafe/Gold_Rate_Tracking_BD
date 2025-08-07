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
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .rate-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .rate-card .card-body {
            padding: 1rem;
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
            font-size: 1.5rem;
            margin-bottom: 8px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        /* Compact rate card styling */
        .rate-card .card-title {
            font-size: 0.9rem;
            margin-bottom: 4px;
        }

        .rate-card h5 {
            font-size: 1.1rem;
            margin-bottom: 4px;
        }

        .rate-card h6 {
            font-size: 0.85rem;
            margin-bottom: 4px;
        }

        .rate-card p, .rate-card small {
            margin-bottom: 4px;
            font-size: 0.75rem;
        }

        .change-badge {
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 600;
        }

        /* Compact card body */
        .rate-card .card-body.p-2 {
            padding: 0.75rem !important;
        }

        /* Section headers */
        .rate-section-header {
            border-bottom: 2px solid;
            padding-bottom: 4px;
            margin-bottom: 8px;
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

        /* Enhanced Header Buttons */
        .header-tool-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 25px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .header-tool-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            background: linear-gradient(135deg, #5a67d8 0%, #667eea 100%);
            color: white;
        }

        .header-tool-btn:active {
            transform: translateY(0);
        }

        /* Special styling for each button */
        .btn-charts {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-charts:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #667eea 100%);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-stats {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            box-shadow: 0 4px 15px rgba(255, 154, 158, 0.3);
        }

        .btn-stats:hover {
            background: linear-gradient(135deg, #ff8a95 0%, #fdb5d5 100%);
            box-shadow: 0 8px 25px rgba(255, 154, 158, 0.4);
        }

        .btn-calculator {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            box-shadow: 0 4px 15px rgba(168, 237, 234, 0.3);
            color: #2c3e50;
        }

        .btn-calculator:hover {
            background: linear-gradient(135deg, #91e5e0 0%, #fcbdd3 100%);
            box-shadow: 0 8px 25px rgba(168, 237, 234, 0.4);
            color: #2c3e50;
        }

        /* Icon styling within buttons */
        .header-tool-btn i {
            margin-right: 6px;
            font-size: 0.9rem;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .header-tool-btn {
                padding: 6px 12px;
                font-size: 0.75rem;
                margin: 0 2px;
            }
            
            .header-tool-btn span {
                display: none;
            }
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
                <div class="d-flex align-items-center flex-wrap">
                    <!-- Enhanced Quick Tools -->
                    <button class="header-tool-btn btn-charts me-2 mb-1" onclick="openChartModal()" title="View Price Charts">
                        <i class="fas fa-chart-line"></i>
                        <span>Charts</span>
                    </button>
                    <button class="header-tool-btn btn-stats me-2 mb-1" onclick="openStatsModal()" title="View Statistics">
                        <i class="fas fa-chart-bar"></i>
                        <span>History</span>
                    </button>

                    
                    <div class="d-flex align-items-center">
                        <button class="btn btn-update btn-sm me-3" onclick="manualScrape()">
                            <i class="fas fa-sync-alt me-1"></i>
                            Update Rates
                        </button>
                        <span class="navbar-text">
                            <i class="fas fa-clock me-1" style="color: #20c997;"></i>
                            Last Updated: <?= $currentRates ? date('d M Y, H:i', strtotime($currentRates['updated_at'])) : 'Never' ?>
                        </span>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Current Rates Section -->
        <div class="container mb-4">
            <div class="row">
                <div class="col-12">
                    <div class="text-center mb-4">
                        <h2 class="h3 fw-bold mb-2" style="color: #2c3e50;">
                            Current Gold & Silver Rates
                        </h2>
                        <p class="text-muted mb-0">
                            <i class="fas fa-calendar-day me-2"></i>
                            Today: <?= date('l, F j, Y') ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Rates Column -->
                <div class="col-lg-8">
                    <?php if ($currentRates): ?>
            <!-- Gold Rates -->
            <div class="mb-2">
                <h6 class="text-warning fw-bold mb-2"><i class="fas fa-crown me-1"></i>Gold Rates</h6>
                <div class="row g-2 mb-3">
                    <div class="col-3">
                        <div class="card rate-card gold-card h-100">
                            <div class="card-body text-center p-2">
                                <div class="rate-icon mb-1">
                                    <i class="fas fa-crown"></i>
                                </div>
                                <h6 class="card-title fw-bold mb-1">22K</h6>
                                <h5 class="fw-bold mb-1">৳<?= number_format($currentRates['gold_22k'], 0) ?></h5>
                                <small class="text-muted">per gram</small>
                                <?php if (isset($comparison['changes'])): ?>
                                <div class="mt-1">
                                    <small class="change-badge <?= $comparison['changes']['gold_22k_change'] >= 0 ? 'bg-success' : 'bg-danger' ?>">
                                        <i class="fas fa-<?= $comparison['changes']['gold_22k_change'] >= 0 ? 'trending-up' : 'trending-down' ?>"></i>
                                        ৳<?= number_format(abs($comparison['changes']['gold_22k_change']), 0) ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card rate-card gold-card h-100">
                            <div class="card-body text-center p-2">
                                <div class="rate-icon mb-1">
                                    <i class="fas fa-ring"></i>
                                </div>
                                <h6 class="card-title fw-bold mb-1">21K</h6>
                                <h5 class="fw-bold mb-1">৳<?= number_format($currentRates['gold_21k'], 0) ?></h5>
                                <small class="text-muted">per gram</small>
                                <?php if (isset($comparison['changes'])): ?>
                                <div class="mt-1">
                                    <small class="change-badge <?= $comparison['changes']['gold_21k_change'] >= 0 ? 'bg-success' : 'bg-danger' ?>">
                                        <i class="fas fa-<?= $comparison['changes']['gold_21k_change'] >= 0 ? 'trending-up' : 'trending-down' ?>"></i>
                                        ৳<?= number_format(abs($comparison['changes']['gold_21k_change']), 0) ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card rate-card gold-card h-100">
                            <div class="card-body text-center p-2">
                                <div class="rate-icon mb-1">
                                    <i class="fas fa-gem"></i>
                                </div>
                                <h6 class="card-title fw-bold mb-1">18K</h6>
                                <h5 class="fw-bold mb-1">৳<?= number_format($currentRates['gold_18k'], 0) ?></h5>
                                <small class="text-muted">per gram</small>
                                <?php if (isset($comparison['changes'])): ?>
                                <div class="mt-1">
                                    <small class="change-badge <?= $comparison['changes']['gold_18k_change'] >= 0 ? 'bg-success' : 'bg-danger' ?>">
                                        <i class="fas fa-<?= $comparison['changes']['gold_18k_change'] >= 0 ? 'trending-up' : 'trending-down' ?>"></i>
                                        ৳<?= number_format(abs($comparison['changes']['gold_18k_change']), 0) ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card rate-card gold-card h-100">
                            <div class="card-body text-center p-2">
                                <div class="rate-icon mb-1">
                                    <i class="fas fa-coins"></i>
                                </div>
                                <h6 class="card-title fw-bold mb-1">Traditional</h6>
                                <h5 class="fw-bold mb-1">৳<?= number_format($currentRates['gold_traditional'], 0) ?></h5>
                                <small class="text-muted">per gram</small>
                                <?php if (isset($comparison['changes'])): ?>
                                <div class="mt-1">
                                    <small class="change-badge <?= $comparison['changes']['gold_traditional_change'] >= 0 ? 'bg-success' : 'bg-danger' ?>">
                                        <i class="fas fa-<?= $comparison['changes']['gold_traditional_change'] >= 0 ? 'trending-up' : 'trending-down' ?>"></i>
                                        ৳<?= number_format(abs($comparison['changes']['gold_traditional_change']), 0) ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Silver Rates -->
            <div class="mb-2">
                <h6 class="text-info fw-bold mb-2"><i class="fas fa-star me-1"></i>Silver Rates</h6>
                <div class="row g-2 mb-3">
                    <div class="col-3">
                        <div class="card rate-card silver-card h-100">
                            <div class="card-body text-center p-2">
                                <div class="rate-icon mb-1">
                                    <i class="fas fa-star"></i>
                                </div>
                                <h6 class="card-title fw-bold mb-1">22K</h6>
                                <h5 class="fw-bold mb-1">৳<?= number_format($currentRates['silver_22k'], 0) ?></h5>
                                <small class="text-muted">per gram</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card rate-card silver-card h-100">
                            <div class="card-body text-center p-2">
                                <div class="rate-icon mb-1">
                                    <i class="fas fa-award"></i>
                                </div>
                                <h6 class="card-title fw-bold mb-1">21K</h6>
                                <h5 class="fw-bold mb-1">৳<?= number_format($currentRates['silver_21k'], 0) ?></h5>
                                <small class="text-muted">per gram</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card rate-card silver-card h-100">
                            <div class="card-body text-center p-2">
                                <div class="rate-icon mb-1">
                                    <i class="fas fa-certificate"></i>
                                </div>
                                <h6 class="card-title fw-bold mb-1">18K</h6>
                                <h5 class="fw-bold mb-1">৳<?= number_format($currentRates['silver_18k'], 0) ?></h5>
                                <small class="text-muted">per gram</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card rate-card silver-card h-100">
                            <div class="card-body text-center p-2">
                                <div class="rate-icon mb-1">
                                    <i class="fas fa-moon"></i>
                                </div>
                                <h6 class="card-title fw-bold mb-1">Traditional</h6>
                                <h5 class="fw-bold mb-1">৳<?= number_format($currentRates['silver_traditional'], 0) ?></h5>
                                <small class="text-muted">per gram</small>
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

                <!-- Calculator Column -->
                <div class="col-lg-4">
                    <div class="card calculator-card sticky-top" style="top: 20px;">
                        <div class="card-header text-white py-2">
                            <h6 class="mb-0">
                                <i class="fas fa-calculator me-1"></i>
                                Calculator
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <!-- Compact Inputs in 2 columns -->
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small mb-1">Metal:</label>
                                    <select class="form-select form-select-sm" id="sideMetalType" onchange="calculateSidePrice()">
                                        <option value="gold">Gold</option>
                                        <option value="silver">Silver</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small mb-1">Karat:</label>
                                    <select class="form-select form-select-sm" id="sideKaratType" onchange="calculateSidePrice()">
                                        <option value="22k">22K</option>
                                        <option value="21k">21K</option>
                                        <option value="18k">18K</option>
                                        <option value="traditional">Traditional</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row g-2 mb-2">
                                <div class="col-7">
                                    <label class="form-label small mb-1">Weight:</label>
                                    <input type="number" class="form-control form-control-sm" id="sideWeightInput" placeholder="0.00" min="0" step="0.01" oninput="calculateSidePrice()">
                                </div>
                                <div class="col-5">
                                    <label class="form-label small mb-1">Unit:</label>
                                    <select class="form-select form-select-sm" id="sideWeightUnit" onchange="calculateSidePrice()">
                                        <option value="gram">Gram</option>
                                        <option value="tola">Tola</option>
                                        <option value="vhori">Vhori</option>
                                        <option value="ounce">Ounce</option>
                                        <option value="kg">Kg</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Making Cost Input -->
                            <div class="row g-2 mb-2">
                                <div class="col-7">
                                    <label class="form-label small mb-1">Making:</label>
                                    <input type="number" class="form-control form-control-sm" id="sideMakingChargeInput" placeholder="Auto" min="0" step="0.1" oninput="calculateSidePrice()">
                                </div>
                                <div class="col-5">
                                    <label class="form-label small mb-1">Type:</label>
                                    <select class="form-select form-select-sm" id="sideMakingType" onchange="calculateSidePrice()">
                                        <option value="percentage">%</option>
                                        <option value="amount">৳</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Compact Results -->
                            <div class="row g-1 mb-2">
                                <div class="col-6">
                                    <div class="calculator-result text-center py-2">
                                        <small class="text-white-50 d-block mb-1"><i class="fas fa-gem text-warning"></i> Base</small>
                                        <div class="fw-bold text-white" id="sideBaseCost">৳0.00</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="calculator-result text-center py-2">
                                        <small class="text-white-50 d-block mb-1"><i class="fas fa-tag text-success"></i> Total</small>
                                        <div class="fw-bold text-white" id="sideTotalCost">৳0.00</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Compact Stats -->
                            <div class="row text-center g-1 mb-2">
                                <div class="col-6">
                                    <small class="text-muted d-block">Rate: <span id="sidePerGramRate">৳0.00/g</span></small>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Weight: <span id="sideTotalGrams">0.00g</span></small>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button class="btn btn-outline-secondary btn-sm" onclick="clearSideCalculator()">
                                    <i class="fas fa-eraser"></i> Clear
                                </button>
                            </div>
                            
                            <div class="text-center mt-2">
                                <small class="text-muted" id="sideCalcDetails">Enter weight to calculate</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>





        <!-- Calculator Modal -->
        <div class="modal fade" id="calculatorModal" tabindex="-1" aria-labelledby="calculatorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header calculator-card">
                        <h5 class="modal-title text-white" id="calculatorModalLabel">
                            <i class="fas fa-calculator me-2"></i>
                            Price Calculator
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Main Calculator Inputs -->
                        <div class="row mb-4">
                            <div class="col-md-6 col-lg-6 mb-2">
                                <label for="modalMetalType" class="form-label">Metal:</label>
                                <select class="form-select" id="modalMetalType" onchange="calculateModalPrice()">
                                    <option value="gold">Gold</option>
                                    <option value="silver">Silver</option>
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-6 mb-2">
                                <label for="modalKaratType" class="form-label">Karat:</label>
                                <select class="form-select" id="modalKaratType" onchange="calculateModalPrice()">
                                    <option value="22k">22K</option>
                                    <option value="21k">21K</option>
                                    <option value="18k">18K</option>
                                    <option value="traditional">Traditional</option>
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-6 mb-2">
                                <label for="modalWeightInput" class="form-label">Weight:</label>
                                <input type="number" class="form-control" id="modalWeightInput" placeholder="Enter weight" min="0" step="0.01" oninput="calculateModalPrice()">
                            </div>
                            <div class="col-md-6 col-lg-6 mb-2">
                                <label for="modalWeightUnit" class="form-label">Unit:</label>
                                <select class="form-select" id="modalWeightUnit" onchange="calculateModalPrice()">
                                    <option value="gram">Gram</option>
                                    <option value="tola">Tola</option>
                                    <option value="vhori">Vhori</option>
                                    <option value="ounce">Ounce</option>
                                    <option value="kg">Kilogram</option>
                                </select>
                            </div>
                        </div>

                        <!-- Making Cost Input -->
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label for="modalMakingChargeInput" class="form-label">Making Charge (%):</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="modalMakingChargeInput" placeholder="Auto" min="0" max="50" step="0.1" oninput="calculateModalPrice()">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Leave empty for auto (Gold: 8%, Silver: 5%)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Quick Actions:</label>
                                <div class="d-grid">
                                    <button class="btn btn-outline-secondary btn-sm" onclick="clearModalCalculator()">
                                        <i class="fas fa-eraser me-1"></i>Clear All
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Results Display -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="calculator-result">
                                    <div class="text-center mb-3">
                                        <h6 class="mb-2"><i class="fas fa-gem me-2"></i>Without Making Cost</h6>
                                        <h4 class="mb-0" id="modalBaseCost">৳0.00</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="calculator-result">
                                    <div class="text-center mb-3">
                                        <h6 class="mb-2"><i class="fas fa-tag me-2"></i>With Making Cost</h6>
                                        <h4 class="mb-0" id="modalTotalCost">৳0.00</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Stats Display -->
                        <div class="calculator-stats">
                            <div class="row text-center">
                                <div class="col-3 stat-item">
                                    <div class="text-muted mb-1"><i class="fas fa-weight me-1"></i>Rate</div>
                                    <div class="stat-value" id="modalPerGramRate">৳0.00/g</div>
                                </div>
                                <div class="col-3 stat-item">
                                    <div class="text-muted mb-1"><i class="fas fa-balance-scale me-1"></i>Weight</div>
                                    <div class="stat-value" id="modalTotalGrams">0.00g</div>
                                </div>
                                <div class="col-3 stat-item">
                                    <div class="text-muted mb-1"><i class="fas fa-percentage me-1"></i>Making</div>
                                    <div class="stat-value text-warning" id="modalMakingPercent">0%</div>
                                </div>
                                <div class="col-3 stat-item">
                                    <div class="text-muted mb-1"><i class="fas fa-tools me-1"></i>Cost</div>
                                    <div class="stat-value text-warning" id="modalMakingCost">৳0.00</div>
                                </div>
                            </div>
                            <div class="text-center mt-3">
                                <small class="text-muted" id="modalCalcDetails">Enter weight to calculate price</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Modal -->
        <div class="modal fade" id="chartModal" tabindex="-1" aria-labelledby="chartModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="chartModalLabel">
                            <i class="fas fa-chart-line me-2"></i>
                            Price History Chart
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Chart Controls -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="modalMetalSelect" class="form-label">Metal Type:</label>
                                <select class="form-select" id="modalMetalSelect" onchange="updateModalChart()">
                                    <option value="gold">Gold</option>
                                    <option value="silver">Silver</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="modalKaratSelect" class="form-label">Karat:</label>
                                <select class="form-select" id="modalKaratSelect" onchange="updateModalChart()">
                                    <option value="22k">22K</option>
                                    <option value="21k">21K</option>
                                    <option value="18k">18K</option>
                                    <option value="traditional">Traditional</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="modalDaysSelect" class="form-label">Time Period:</label>
                                <select class="form-select" id="modalDaysSelect" onchange="updateModalChart()">
                                    <option value="7">Last 7 Days</option>
                                    <option value="30" selected>Last 30 Days</option>
                                    <option value="90">Last 3 Months</option>
                                    <option value="365">Last Year</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Chart Canvas -->
                        <div class="chart-container">
                            <canvas id="modalPriceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Modal -->
        <div class="modal fade" id="statsModal" tabindex="-1" aria-labelledby="statsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="statsModalLabel">
                            <i class="fas fa-chart-bar me-2"></i>
                            30-Day Statistics
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php if ($statistics): ?>
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <h6 class="text-primary"><i class="fas fa-crown me-2"></i>Gold Prices</h6>
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Min</th>
                                            <th>Max</th>
                                            <th>Avg</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>22K Gold</strong></td>
                                            <td>৳<?= isset($statistics['min_gold_22k']) ? number_format($statistics['min_gold_22k'], 2) : '0.00' ?></td>
                                            <td>৳<?= isset($statistics['max_gold_22k']) ? number_format($statistics['max_gold_22k'], 2) : '0.00' ?></td>
                                            <td>৳<?= isset($statistics['avg_gold_22k']) ? number_format($statistics['avg_gold_22k'], 2) : '0.00' ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>21K Gold</strong></td>
                                            <td>৳<?= isset($statistics['min_gold_21k']) ? number_format($statistics['min_gold_21k'], 2) : '0.00' ?></td>
                                            <td>৳<?= isset($statistics['max_gold_21k']) ? number_format($statistics['max_gold_21k'], 2) : '0.00' ?></td>
                                            <td>৳<?= isset($statistics['avg_gold_21k']) ? number_format($statistics['avg_gold_21k'], 2) : '0.00' ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>18K Gold</strong></td>
                                            <td>৳<?= isset($statistics['min_gold_18k']) ? number_format($statistics['min_gold_18k'], 2) : '0.00' ?></td>
                                            <td>৳<?= isset($statistics['max_gold_18k']) ? number_format($statistics['max_gold_18k'], 2) : '0.00' ?></td>
                                            <td>৳<?= isset($statistics['avg_gold_18k']) ? number_format($statistics['avg_gold_18k'], 2) : '0.00' ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Traditional Gold</strong></td>
                                            <td>৳<?= isset($statistics['min_gold_traditional']) ? number_format($statistics['min_gold_traditional'], 2) : '0.00' ?></td>
                                            <td>৳<?= isset($statistics['max_gold_traditional']) ? number_format($statistics['max_gold_traditional'], 2) : '0.00' ?></td>
                                            <td>৳<?= isset($statistics['avg_gold_traditional']) ? number_format($statistics['avg_gold_traditional'], 2) : '0.00' ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6 mb-4">
                                <h6 class="text-info"><i class="fas fa-star me-2"></i>Silver Prices</h6>
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Min</th>
                                            <th>Max</th>
                                            <th>Avg</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>22K Silver</strong></td>
                                            <td>৳<?= isset($statistics['min_silver_22k']) ? number_format($statistics['min_silver_22k'], 2) : '0.00' ?></td>
                                            <td>৳<?= isset($statistics['max_silver_22k']) ? number_format($statistics['max_silver_22k'], 2) : '0.00' ?></td>
                                            <td>৳<?= isset($statistics['avg_silver_22k']) ? number_format($statistics['avg_silver_22k'], 2) : '0.00' ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>21K Silver</strong></td>
                                            <td>৳<?= isset($statistics['min_silver_21k']) ? number_format($statistics['min_silver_21k'], 2) : '0.00' ?></td>
                                            <td>৳<?= isset($statistics['max_silver_21k']) ? number_format($statistics['max_silver_21k'], 2) : '0.00' ?></td>
                                            <td>৳<?= isset($statistics['avg_silver_21k']) ? number_format($statistics['avg_silver_21k'], 2) : '0.00' ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>18K Silver</strong></td>
                                            <td>৳<?= isset($statistics['min_silver_18k']) ? number_format($statistics['min_silver_18k'], 2) : '0.00' ?></td>
                                            <td>৳<?= isset($statistics['max_silver_18k']) ? number_format($statistics['max_silver_18k'], 2) : '0.00' ?></td>
                                            <td>৳<?= isset($statistics['avg_silver_18k']) ? number_format($statistics['avg_silver_18k'], 2) : '0.00' ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Traditional Silver</strong></td>
                                            <td>৳<?= isset($statistics['min_silver_traditional']) ? number_format($statistics['min_silver_traditional'], 2) : '0.00' ?></td>
                                            <td>৳<?= isset($statistics['max_silver_traditional']) ? number_format($statistics['max_silver_traditional'], 2) : '0.00' ?></td>
                                            <td>৳<?= isset($statistics['avg_silver_traditional']) ? number_format($statistics['avg_silver_traditional'], 2) : '0.00' ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Based on <strong><?= $statistics['total_records'] ?> records</strong> from the last 30 days
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No statistical data available. Please update rates to see statistics.
                        </div>
                        <?php endif; ?>
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
            priceChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: []
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: {
                                display: true,
                                text: 'Price (BDT)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
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
                        priceChart.data = data;
                        priceChart.update();
                    }
                })
                .catch(error => {
                    console.error('Error updating chart:', error);
                    alert('Failed to load chart data');
                })
                .finally(() => {
                    document.querySelector('.chart-container').classList.remove('loading');
                });
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
            
            // Add to page
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Auto remove after 4 seconds
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }, 4000);
        }

        // Modal Functions
        function openChartModal() {
            const chartModal = new bootstrap.Modal(document.getElementById('chartModal'));
            chartModal.show();
            // Initialize chart after modal is shown
            setTimeout(() => {
                initModalChart();
                updateModalChart();
            }, 300);
        }

        function openStatsModal() {
            const statsModal = new bootstrap.Modal(document.getElementById('statsModal'));
            statsModal.show();
        }

        function openCalculatorModal() {
            const calculatorModal = new bootstrap.Modal(document.getElementById('calculatorModal'));
            calculatorModal.show();
            // Initialize calculator after modal is shown
            setTimeout(() => {
                calculateModalPrice();
            }, 300);
        }

        // Modal Chart Functions
        let modalPriceChart;

        function initModalChart() {
            if (modalPriceChart) {
                modalPriceChart.destroy();
            }
            
            const ctx = document.getElementById('modalPriceChart').getContext('2d');
            modalPriceChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: []
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: {
                                display: true,
                                text: 'Price (BDT)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    }
                }
            });
        }

        function updateModalChart() {
            if (!modalPriceChart) return;
            
            const metal = document.getElementById('modalMetalSelect').value;
            const karat = document.getElementById('modalKaratSelect').value;
            const days = document.getElementById('modalDaysSelect').value;

            fetch(`?action=get_chart_data&metal=${metal}&karat=${karat}&days=${days}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.labels && data.datasets) {
                        modalPriceChart.data = data;
                        modalPriceChart.update();
                    }
                })
                .catch(error => {
                    console.error('Error updating chart:', error);
                });
        }

        // Modal Calculator Function
        function calculateModalPrice() {
            const metalType = document.getElementById('modalMetalType').value;
            const karatType = document.getElementById('modalKaratType').value;
            const weight = parseFloat(document.getElementById('modalWeightInput').value) || 0;
            const unit = document.getElementById('modalWeightUnit').value;
            const customMakingCharge = parseFloat(document.getElementById('modalMakingChargeInput').value);
            
            // Get current rates from PHP
            const rates = <?= json_encode($currentRates) ?>;
            
            // Reset displays if no weight
            if (!rates || weight === 0) {
                document.getElementById('modalBaseCost').textContent = '৳0.00';
                document.getElementById('modalTotalCost').textContent = '৳0.00';
                document.getElementById('modalCalcDetails').textContent = weight === 0 ? 'Enter weight to calculate price' : 'No current rates available';
                document.getElementById('modalPerGramRate').textContent = '৳0.00/g';
                document.getElementById('modalTotalGrams').textContent = '0.00g';
                document.getElementById('modalMakingPercent').textContent = '0%';
                document.getElementById('modalMakingCost').textContent = '৳0.00';
                return;
            }
            
            // Get rate per gram
            const rateKey = metalType + '_' + karatType;
            const ratePerGram = rates[rateKey] || 0;
            
            if (ratePerGram === 0) {
                document.getElementById('modalBaseCost').textContent = '৳0.00';
                document.getElementById('modalTotalCost').textContent = '৳0.00';
                document.getElementById('modalCalcDetails').textContent = 'Rate not available for selected type';
                return;
            }
            
            // Convert weight to grams
            let weightInGrams = weight;
            switch (unit) {
                case 'tola':
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
            const baseCost = weightInGrams * ratePerGram;
            
            // Determine making charge rate
            let makingChargeRate;
            if (!isNaN(customMakingCharge) && customMakingCharge >= 0) {
                makingChargeRate = customMakingCharge / 100;
            } else {
                makingChargeRate = metalType === 'gold' ? 0.08 : 0.05; // 8% for gold, 5% for silver
            }
            
            const makingCost = baseCost * makingChargeRate;
            const totalCost = baseCost + makingCost;
            
            // Update displays
            document.getElementById('modalBaseCost').textContent = '৳' + baseCost.toLocaleString('en-BD', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('modalTotalCost').textContent = '৳' + totalCost.toLocaleString('en-BD', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('modalPerGramRate').textContent = '৳' + ratePerGram.toLocaleString('en-BD', {minimumFractionDigits: 2}) + '/g';
            document.getElementById('modalTotalGrams').textContent = weightInGrams.toFixed(2) + 'g';
            document.getElementById('modalMakingPercent').textContent = (makingChargeRate * 100).toFixed(1) + '%';
            document.getElementById('modalMakingCost').textContent = '৳' + makingCost.toLocaleString('en-BD', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            // Update details
            const unitText = unit === 'gram' ? 'grams' : unit + (weight > 1 ? 's' : '');
            document.getElementById('modalCalcDetails').textContent = `${weight} ${unitText} of ${karatType.toUpperCase()} ${metalType} (${weightInGrams.toFixed(2)}g total)`;
        }

        // Clear modal calculator function
        function clearModalCalculator() {
            document.getElementById('modalWeightInput').value = '';
            document.getElementById('modalMakingChargeInput').value = '';
            document.getElementById('modalMetalType').selectedIndex = 0;
            document.getElementById('modalKaratType').selectedIndex = 0;
            document.getElementById('modalWeightUnit').selectedIndex = 0;
            calculateModalPrice();
        }

        // Side Calculator Function
        function calculateSidePrice() {
            const metalType = document.getElementById('sideMetalType').value;
            const karatType = document.getElementById('sideKaratType').value;
            const weight = parseFloat(document.getElementById('sideWeightInput').value) || 0;
            const unit = document.getElementById('sideWeightUnit').value;
            const customMakingCharge = parseFloat(document.getElementById('sideMakingChargeInput').value);
            const makingType = document.getElementById('sideMakingType').value;
            
            // Get current rates from PHP
            const rates = <?= json_encode($currentRates) ?>;
            
            // Reset displays if no weight
            if (!rates || weight === 0) {
                document.getElementById('sideBaseCost').textContent = '৳0.00';
                document.getElementById('sideTotalCost').textContent = '৳0.00';
                document.getElementById('sideCalcDetails').textContent = weight === 0 ? 'Enter weight to calculate' : 'No current rates available';
                document.getElementById('sidePerGramRate').textContent = '৳0.00/g';
                document.getElementById('sideTotalGrams').textContent = '0.00g';
                return;
            }
            
            // Get rate per gram
            const rateKey = metalType + '_' + karatType;
            const ratePerGram = rates[rateKey] || 0;
            
            if (ratePerGram === 0) {
                document.getElementById('sideBaseCost').textContent = '৳0.00';
                document.getElementById('sideTotalCost').textContent = '৳0.00';
                document.getElementById('sideCalcDetails').textContent = 'Rate not available for selected type';
                return;
            }
            
            // Convert weight to grams
            let weightInGrams = weight;
            switch (unit) {
                case 'tola':
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
            const baseCost = weightInGrams * ratePerGram;
            
            // Determine making cost
            let makingCost;
            if (!isNaN(customMakingCharge) && customMakingCharge >= 0) {
                if (makingType === 'percentage') {
                    makingCost = baseCost * (customMakingCharge / 100);
                } else {
                    // Fixed amount
                    makingCost = customMakingCharge;
                }
            } else {
                // Auto percentage: 8% for gold, 5% for silver
                const autoPercentage = metalType === 'gold' ? 0.08 : 0.05;
                makingCost = baseCost * autoPercentage;
            }
            
            const totalCost = baseCost + makingCost;
            
            // Update displays
            document.getElementById('sideBaseCost').textContent = '৳' + baseCost.toLocaleString('en-BD', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('sideTotalCost').textContent = '৳' + totalCost.toLocaleString('en-BD', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('sidePerGramRate').textContent = '৳' + ratePerGram.toLocaleString('en-BD', {minimumFractionDigits: 2}) + '/g';
            document.getElementById('sideTotalGrams').textContent = weightInGrams.toFixed(2) + 'g';
            
            // Update details
            const unitText = unit === 'gram' ? 'grams' : unit + (weight > 1 ? 's' : '');
            document.getElementById('sideCalcDetails').textContent = `${weight} ${unitText} of ${karatType.toUpperCase()} ${metalType} (${weightInGrams.toFixed(2)}g)`;
        }

        // Clear side calculator function
        function clearSideCalculator() {
            document.getElementById('sideWeightInput').value = '';
            document.getElementById('sideMakingChargeInput').value = '';
            document.getElementById('sideMetalType').selectedIndex = 0;
            document.getElementById('sideKaratType').selectedIndex = 0;
            document.getElementById('sideWeightUnit').selectedIndex = 0;
            document.getElementById('sideMakingType').selectedIndex = 0;
            calculateSidePrice();
        }
    </script>
</body>
</html>