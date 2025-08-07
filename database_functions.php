<?php
require_once 'config.php';

class GoldRateDatabase {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    /**
     * Get current/latest gold rates
     */
    public function getCurrentRates() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM gold_rates 
                ORDER BY date DESC 
                LIMIT 1
            ");
            $stmt->execute();
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error fetching current rates: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get historical rates for a specific period
     */
    public function getHistoricalRates($days = 30) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM gold_rates 
                WHERE date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                ORDER BY date ASC
            ");
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error fetching historical rates: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get rates for chart data (optimized for frontend)
     */
    public function getChartData($days = 30, $metal = 'gold', $karat = '22k') {
        try {
            $column = $metal . '_' . $karat;
            
            $stmt = $this->pdo->prepare("
                SELECT date, $column as price 
                FROM gold_rates 
                WHERE date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                ORDER BY date ASC
            ");
            $stmt->execute([$days]);
            $data = $stmt->fetchAll();
            
            // Format for Chart.js
            $chartData = [
                'labels' => [],
                'datasets' => [[
                    'label' => ucfirst($metal) . ' ' . strtoupper($karat) . ' Price (BDT)',
                    'data' => [],
                    'borderColor' => $metal === 'gold' ? '#FFD700' : '#C0C0C0',
                    'backgroundColor' => $metal === 'gold' ? 'rgba(255, 215, 0, 0.1)' : 'rgba(192, 192, 192, 0.1)',
                    'borderWidth' => 2,
                    'fill' => true
                ]]
            ];
            
            foreach ($data as $row) {
                $chartData['labels'][] = date('M d', strtotime($row['date']));
                $chartData['datasets'][0]['data'][] = floatval($row['price']);
            }
            
            return $chartData;
        } catch (Exception $e) {
            error_log("Error fetching chart data: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get price comparison (today vs yesterday)
     */
    public function getPriceComparison() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    today.date as today_date,
                    today.gold_22k as today_gold_22k,
                    today.gold_21k as today_gold_21k,
                    today.gold_18k as today_gold_18k,
                    today.gold_traditional as today_gold_traditional,
                    yesterday.gold_22k as yesterday_gold_22k,
                    yesterday.gold_21k as yesterday_gold_21k,
                    yesterday.gold_18k as yesterday_gold_18k,
                    yesterday.gold_traditional as yesterday_gold_traditional
                FROM 
                    (SELECT * FROM gold_rates ORDER BY date DESC LIMIT 1) today
                LEFT JOIN 
                    (SELECT * FROM gold_rates ORDER BY date DESC LIMIT 1 OFFSET 1) yesterday
                ON 1=1
            ");
            $stmt->execute();
            $result = $stmt->fetch();
            
            if ($result && $result['yesterday_gold_22k']) {
                $comparison = [
                    'gold_22k_change' => $result['today_gold_22k'] - $result['yesterday_gold_22k'],
                    'gold_21k_change' => $result['today_gold_21k'] - $result['yesterday_gold_21k'],
                    'gold_18k_change' => $result['today_gold_18k'] - $result['yesterday_gold_18k'],
                    'gold_traditional_change' => $result['today_gold_traditional'] - $result['yesterday_gold_traditional'],
                ];
                
                $result['changes'] = $comparison;
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error fetching price comparison: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get statistics (min, max, average for a period)
     */
    public function getStatistics($days = 30) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    MIN(gold_22k) as min_gold_22k,
                    MAX(gold_22k) as max_gold_22k,
                    AVG(gold_22k) as avg_gold_22k,
                    MIN(gold_21k) as min_gold_21k,
                    MAX(gold_21k) as max_gold_21k,
                    AVG(gold_21k) as avg_gold_21k,
                    MIN(gold_18k) as min_gold_18k,
                    MAX(gold_18k) as max_gold_18k,
                    AVG(gold_18k) as avg_gold_18k,
                    MIN(gold_traditional) as min_gold_traditional,
                    MAX(gold_traditional) as max_gold_traditional,
                    AVG(gold_traditional) as avg_gold_traditional,
                    MIN(silver_22k) as min_silver_22k,
                    MAX(silver_22k) as max_silver_22k,
                    AVG(silver_22k) as avg_silver_22k,
                    MIN(silver_21k) as min_silver_21k,
                    MAX(silver_21k) as max_silver_21k,
                    AVG(silver_21k) as avg_silver_21k,
                    MIN(silver_18k) as min_silver_18k,
                    MAX(silver_18k) as max_silver_18k,
                    AVG(silver_18k) as avg_silver_18k,
                    MIN(silver_traditional) as min_silver_traditional,
                    MAX(silver_traditional) as max_silver_traditional,
                    AVG(silver_traditional) as avg_silver_traditional,
                    COUNT(*) as total_records
                FROM gold_rates 
                WHERE date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            ");
            $stmt->execute([$days]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error fetching statistics: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get recent scraping logs
     */
    public function getScrapingLogs($limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM scraping_logs 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error fetching scraping logs: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Search rates by date range
     */
    public function getRatesByDateRange($startDate, $endDate) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM gold_rates 
                WHERE date BETWEEN ? AND ?
                ORDER BY date ASC
            ");
            $stmt->execute([$startDate, $endDate]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error fetching rates by date range: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get highest and lowest prices with dates
     */
    public function getExtremeValues($days = 365) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    'highest_gold_22k' as type,
                    date,
                    gold_22k as value
                FROM gold_rates 
                WHERE date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                ORDER BY gold_22k DESC 
                LIMIT 1
                
                UNION ALL
                
                SELECT 
                    'lowest_gold_22k' as type,
                    date,
                    gold_22k as value
                FROM gold_rates 
                WHERE date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                ORDER BY gold_22k ASC 
                LIMIT 1
            ");
            $stmt->execute([$days, $days]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error fetching extreme values: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete old records (for maintenance)
     */
    public function cleanOldRecords($keepDays = 365) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM gold_rates 
                WHERE date < DATE_SUB(CURDATE(), INTERVAL ? DAY)
            ");
            $stmt->execute([$keepDays]);
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Error cleaning old records: " . $e->getMessage());
            return false;
        }
    }
}
?>