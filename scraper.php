<?php
require_once 'config.php';

class GoldRateScraper {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    /**
     * Scrape gold rates from BAJUS website
     */
    public function scrapeRates() {
        try {
            // Initialize cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, BAJUS_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, USER_AGENT);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_error($ch)) {
                throw new Exception("cURL Error: " . curl_error($ch));
            }
            
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception("HTTP Error: " . $httpCode);
            }
            
            if (!$html) {
                throw new Exception("Empty response from website");
            }
            
            // Parse the HTML
            return $this->parseHTML($html);
            
        } catch (Exception $e) {
            $this->logError("Scraping failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Parse HTML to extract gold and silver rates
     */
    private function parseHTML($html) {
        // Create DOMDocument
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        
        $rates = [
            'gold_22k' => 0,
            'gold_21k' => 0,
            'gold_18k' => 0,
            'gold_traditional' => 0,
            'silver_22k' => 0,
            'silver_21k' => 0,
            'silver_18k' => 0,
            'silver_traditional' => 0
        ];
        
        // Method 1: Look for table rows with price data
        $xpath = new DOMXPath($dom);
        
        // Try to find tables containing gold and silver prices
        $tables = $xpath->query('//table');
        
        foreach ($tables as $table) {
            $rows = $xpath->query('.//tr', $table);
            
            foreach ($rows as $row) {
                $cells = $xpath->query('.//td', $row);
                if ($cells->length >= 3) {
                    $product = trim($cells->item(0)->textContent);
                    $price = trim($cells->item(2)->textContent);
                    
                    // Map products to our rate keys
                    $this->mapProductToRate($product, $price, $rates);
                }
            }
        }
        
        // Method 2: Fallback - search for price patterns in the entire HTML
        if (array_sum($rates) == 0) {
            $rates = $this->extractPricesFromText($html);
        }
        
        // Validate that we got some data
        if (array_sum($rates) == 0) {
            throw new Exception("No price data found on the website");
        }
        
        return $rates;
    }
    
    /**
     * Map product names to rate keys
     */
    private function mapProductToRate($product, $price, &$rates) {
        $product = strtolower($product);
        $cleanPrice = cleanPrice($price);
        
        if ($cleanPrice <= 0) return;
        
        if (strpos($product, '22 karat gold') !== false || strpos($product, '২২ ক্যা') !== false) {
            $rates['gold_22k'] = $cleanPrice;
        } elseif (strpos($product, '21 karat gold') !== false || strpos($product, '২১ ক্যা') !== false) {
            $rates['gold_21k'] = $cleanPrice;
        } elseif (strpos($product, '18 karat gold') !== false || strpos($product, '১৮ ক্যা') !== false) {
            $rates['gold_18k'] = $cleanPrice;
        } elseif (strpos($product, 'traditional gold') !== false || strpos($product, 'সনাতন') !== false) {
            $rates['gold_traditional'] = $cleanPrice;
        } elseif (strpos($product, '22 karat silver') !== false) {
            $rates['silver_22k'] = $cleanPrice;
        } elseif (strpos($product, '21 karat silver') !== false) {
            $rates['silver_21k'] = $cleanPrice;
        } elseif (strpos($product, '18 karat silver') !== false) {
            $rates['silver_18k'] = $cleanPrice;
        } elseif (strpos($product, 'traditional silver') !== false) {
            $rates['silver_traditional'] = $cleanPrice;
        }
    }
    
    /**
     * Extract prices using regex patterns
     */
    private function extractPricesFromText($html) {
        $rates = [
            'gold_22k' => 0,
            'gold_21k' => 0,
            'gold_18k' => 0,
            'gold_traditional' => 0,
            'silver_22k' => 0,
            'silver_21k' => 0,
            'silver_18k' => 0,
            'silver_traditional' => 0
        ];
        
        // Patterns for different price formats (including Bengali numerals)
        $patterns = [
            // Bengali patterns
            '/২২ ক্যা.*?([০-৯১-৯]{4,5})/u' => 'gold_22k',
            '/২১ ক্যা.*?([০-৯১-৯]{4,5})/u' => 'gold_21k',
            '/১৮ ক্যা.*?([০-৯১-৯]{4,5})/u' => 'gold_18k',
            '/সনাতন.*?([০-৯১-৯]{4,5})/u' => 'gold_traditional',
            // English patterns
            '/22 KARAT Gold.*?(\d{5})/i' => 'gold_22k',
            '/21 KARAT Gold.*?(\d{5})/i' => 'gold_21k',
            '/18 KARAT Gold.*?(\d{5})/i' => 'gold_18k',
            '/TRADITIONAL Gold.*?(\d{4,5})/i' => 'gold_traditional',
            // Mixed patterns
            '/২২ ক্যা.*?(\d{4,5})/u' => 'gold_22k',
            '/২১ ক্যা.*?(\d{4,5})/u' => 'gold_21k',
            '/১৮ ক্যা.*?(\d{4,5})/u' => 'gold_18k',
            // Silver patterns
            '/২২ ক্যা.*?রূপার.*?([০-৯১-৯]{2,3})/u' => 'silver_22k',
            '/২১ ক্যা.*?রূপার.*?([০-৯১-৯]{2,3})/u' => 'silver_21k',
            '/১৮ ক্যা.*?রূপার.*?([০-৯১-৯]{2,3})/u' => 'silver_18k',
            '/সনাতন.*?রূপার.*?([০-৯১-৯]{2,3})/u' => 'silver_traditional'
        ];
        
        foreach ($patterns as $pattern => $key) {
            if (preg_match($pattern, $html, $matches)) {
                $price = cleanPrice($matches[1]);
                if ($price > 0) {
                    $rates[$key] = $price;
                }
            }
        }
        
        return $rates;
    }
    
    /**
     * Save rates to database
     */
    public function saveRates($rates) {
        try {
            $today = date('Y-m-d');
            
            // Check if today's rate already exists
            $stmt = $this->pdo->prepare("SELECT id FROM gold_rates WHERE date = ?");
            $stmt->execute([$today]);
            
            if ($stmt->fetch()) {
                // Update existing record
                $sql = "UPDATE gold_rates SET 
                        gold_22k = ?, gold_21k = ?, gold_18k = ?, gold_traditional = ?,
                        silver_22k = ?, silver_21k = ?, silver_18k = ?, silver_traditional = ?,
                        updated_at = CURRENT_TIMESTAMP
                        WHERE date = ?";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    $rates['gold_22k'], $rates['gold_21k'], $rates['gold_18k'], $rates['gold_traditional'],
                    $rates['silver_22k'], $rates['silver_21k'], $rates['silver_18k'], $rates['silver_traditional'],
                    $today
                ]);
            } else {
                // Insert new record
                $sql = "INSERT INTO gold_rates 
                        (date, gold_22k, gold_21k, gold_18k, gold_traditional, silver_22k, silver_21k, silver_18k, silver_traditional)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    $today,
                    $rates['gold_22k'], $rates['gold_21k'], $rates['gold_18k'], $rates['gold_traditional'],
                    $rates['silver_22k'], $rates['silver_21k'], $rates['silver_18k'], $rates['silver_traditional']
                ]);
            }
            
            $this->logSuccess("Rates updated successfully for " . $today);
            return true;
            
        } catch (Exception $e) {
            $this->logError("Database save failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log successful scraping
     */
    private function logSuccess($message) {
        $stmt = $this->pdo->prepare("INSERT INTO scraping_logs (scrape_date, status, message) VALUES (?, 'success', ?)");
        $stmt->execute([date('Y-m-d'), $message]);
    }
    
    /**
     * Log scraping errors
     */
    private function logError($message) {
        $stmt = $this->pdo->prepare("INSERT INTO scraping_logs (scrape_date, status, message) VALUES (?, 'failed', ?)");
        $stmt->execute([date('Y-m-d'), $message]);
    }
    
    /**
     * Get latest rates from database
     */
    public function getLatestRates() {
        $stmt = $this->pdo->prepare("SELECT * FROM gold_rates ORDER BY date DESC LIMIT 1");
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Run the complete scraping process
     */
    public function run($silent = false) {
        if (!$silent) echo "Starting gold rate scraping...\n";
        
        $rates = $this->scrapeRates();
        if ($rates === false) {
            if (!$silent) echo "Scraping failed. Check logs for details.\n";
            return false;
        }
        
        if (!$silent) {
            echo "Scraped rates: \n";
            foreach ($rates as $key => $value) {
                echo "  $key: $value BDT\n";
            }
        }
        
        if ($this->saveRates($rates)) {
            if (!$silent) echo "Rates saved successfully!\n";
            return true;
        } else {
            if (!$silent) echo "Failed to save rates to database.\n";
            return false;
        }
    }
    
    /**
     * Run scraping silently for AJAX calls
     */
    public function runSilent() {
        return $this->run(true);
    }
}

// Command line execution
if (php_sapi_name() === 'cli') {
    $scraper = new GoldRateScraper();
    $scraper->run();
}
?>