# Bangladesh Gold Rate Tracker

A comprehensive PHP web application that tracks and displays current gold and silver rates in Bangladesh by scraping data from the Bangladesh Jewellers Association (BAJUS) website.

## Features

- **Real-time Rate Tracking**: Scrapes current gold and silver rates from BAJUS website
- **Historical Data**: Stores and displays historical price data with interactive charts
- **Multiple Karat Types**: Tracks 22K, 21K, 18K, and Traditional gold prices
- **Silver Rates**: Also tracks silver prices across different categories
- **Price Comparison**: Shows daily price changes with visual indicators
- **Interactive Charts**: Chart.js powered visualizations with customizable time periods
- **Automated Updates**: Daily scraping via cron jobs
- **Responsive Design**: Mobile-friendly Bootstrap interface
- **Statistics**: Min, max, and average price calculations
- **Activity Logs**: Tracks scraping success/failure with detailed logs

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- PHP Extensions:
  - PDO MySQL
  - cURL
  - DOM
  - libxml

## Installation

### Method 1: Using the Web Installer (Recommended)

1. **Download/Clone** the project to your XAMPP htdocs directory:
   ```bash
   cd /Applications/XAMPP/xamppfiles/htdocs/
   git clone [repository-url] Gold_Rate_with_History
   ```

2. **Start XAMPP** services (Apache + MySQL)

3. **Run the installer** by visiting:
   ```
   http://localhost/Gold_Rate_with_History/install.php
   ```

4. **Follow the installation steps**:
   - System requirements check
   - Database setup
   - Initial data scraping
   - Configuration completion

### Method 2: Manual Installation

1. **Database Setup**:
   ```bash
   mysql -u root -p < database.sql
   ```

2. **Configure Database** in `config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USERNAME', 'root');
   define('DB_PASSWORD', '');
   define('DB_NAME', 'gold_rate_tracker');
   ```

3. **Test the scraper**:
   ```bash
   php daily_scraper.php test
   ```

4. **Initial data scraping**:
   ```bash
   php daily_scraper.php run
   ```

## Usage

### Accessing the Application

Visit `http://localhost/Gold_Rate_with_History/` to view:

- Current gold and silver rates
- Historical price charts
- Statistics and trends
- Scraping activity logs

### Manual Rate Updates

Click the "Update Rates" button in the web interface to manually fetch the latest rates from BAJUS.

### Automated Daily Updates

Set up a cron job for daily automatic updates:

```bash
# Edit crontab
crontab -e

# Add this line to run daily at 10:00 AM
0 10 * * * /usr/bin/php /Applications/XAMPP/xamppfiles/htdocs/Gold_Rate_with_History/daily_scraper.php
```

### Command Line Usage

```bash
# Run daily scraper
php daily_scraper.php run

# Test scraping without saving
php daily_scraper.php test

# Health check
php daily_scraper.php health
```

## File Structure

```
Gold_Rate_with_History/
├── config.php              # Database and scraping configuration
├── database.sql             # Database schema
├── scraper.php             # Main scraping class
├── database_functions.php   # Database operations
├── index.php               # Main web interface
├── daily_scraper.php       # Automated scraping script
├── install.php             # Web-based installer
├── logs/                   # Scraping logs directory
└── README.md               # This file
```

## API Endpoints

The application provides AJAX endpoints for dynamic data:

- `?action=get_current_rates` - Get latest rates
- `?action=get_chart_data&days=30&metal=gold&karat=22k` - Chart data
- `?action=manual_scrape` - Trigger manual scraping

## Database Schema

### `gold_rates` Table
- Stores daily gold and silver rates
- Unique constraint on date
- Tracks all karat types for both metals

### `scraping_logs` Table
- Logs all scraping attempts
- Status tracking (success/failed/partial)
- Error message storage

## Configuration

### Scraping Configuration
```php
// In config.php
define('BAJUS_URL', 'https://www.bajus.org/gold-price');
define('USER_AGENT', 'Mozilla/5.0...');
```

### Database Configuration
```php
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'gold_rate_tracker');
```

## Troubleshooting

### Common Issues

1. **Scraping Fails**:
   - Check internet connection
   - Verify BAJUS website is accessible
   - Check logs in `/logs/daily_scraper.log`

2. **Database Connection Error**:
   - Verify MySQL is running
   - Check database credentials in `config.php`
   - Ensure database exists

3. **Charts Not Loading**:
   - Check browser console for JavaScript errors
   - Ensure sufficient historical data exists
   - Verify AJAX endpoints are responding

### Debug Mode

Enable detailed error reporting by adding to `config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Data Source

This application scrapes data from:
- **Website**: https://www.bajus.org/gold-price
- **Organization**: Bangladesh Jewellers Association (BAJUS)

*Note: This application is for informational purposes only. Always verify rates with official sources before making financial decisions.*

## Maintenance

### Regular Tasks

1. **Monitor Logs**: Check scraping logs for failures
2. **Database Cleanup**: Old records are auto-cleaned (configurable)
3. **Backup**: Regular database backups recommended
4. **Update Dependencies**: Keep PHP and libraries updated

### Performance Optimization

- Database indexes are included for common queries
- Chart data is optimized for frontend consumption
- Old logs and records are automatically cleaned

## License

This project is open source. Please respect the data source (BAJUS) terms of use.

## Support

For issues and questions:
1. Check the logs in `/logs/` directory
2. Verify all requirements are met
3. Test scraping manually with `php daily_scraper.php test`

---

**Disclaimer**: This application scrapes publicly available data for informational purposes. Users should verify rates with official sources and respect the data provider's terms of service.