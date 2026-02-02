<?php
/**
 * Database Configuration and Connection - ENHANCED VERSION
 * 
 * IMPROVEMENTS:
 * - Better error handling
 * - Environment-based configuration support
 * - Connection testing
 * - Security improvements
 */

// ============================================
// DATABASE CONFIGURATION
// ============================================

// For localhost development (XAMPP / WAMP / MAMP)
define('DB_HOST', 'localhost'); // ⚠️ replace XXX with your actual server number
define('DB_NAME', 'NP03CS4A240013');
define('DB_USER', 'NP03CS4A240013');
define('DB_PASS', 'coytVfilvj');
define('DB_CHARSET', 'utf8mb4');

// Optional: Set these for production
// define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
// define('DB_NAME', getenv('DB_NAME') ?: 'course_management');
// define('DB_USER', getenv('DB_USER') ?: 'root');
// define('DB_PASS', getenv('DB_PASS') ?: '');

// ============================================
// PDO OPTIONS - Security Settings
// ============================================
$options = [
    // Throw exceptions on errors (better than silent failures)
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    
    // Return associative arrays by default
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    
    // Disable emulated prepares for better security
    PDO::ATTR_EMULATE_PREPARES   => false,
    
    // Set connection timeout (5 seconds)
    PDO::ATTR_TIMEOUT            => 5,
    
    // Persistent connections (optional, comment out if causing issues)
    // PDO::ATTR_PERSISTENT         => true,
];

// ============================================
// CREATE PDO CONNECTION
// ============================================
try {
    // Build DSN (Data Source Name)
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    // Create PDO instance
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Optional: Set MySQL modes for stricter error handling
    $pdo->exec("SET sql_mode='STRICT_ALL_TABLES'");
    
    // Success - Connection established
    // Uncomment below line for debugging only
    // error_log("Database connection successful");
    
} catch (PDOException $e) {
    // ============================================
    // ERROR HANDLING
    // ============================================
    
    // Log the error (make sure error logging is enabled)
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Development vs Production error messages
    $isDevelopment = (getenv('APP_ENV') === 'development') || (!getenv('APP_ENV'));
    
    if ($isDevelopment) {
        // Detailed error in development
        $errorMessage = "<h2>Database Connection Failed</h2>";
        $errorMessage .= "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        $errorMessage .= "<h3>Common Solutions:</h3>";
        $errorMessage .= "<ul>";
        $errorMessage .= "<li>Make sure MySQL/MariaDB is running (check XAMPP/WAMP control panel)</li>";
        $errorMessage .= "<li>Verify database name '<strong>" . DB_NAME . "</strong>' exists</li>";
        $errorMessage .= "<li>Check username '<strong>" . DB_USER . "</strong>' has access</li>";
        $errorMessage .= "<li>Import the database schema file (database_schema.sql)</li>";
        $errorMessage .= "<li>Verify credentials in config/db.php are correct</li>";
        $errorMessage .= "</ul>";
        $errorMessage .= "<h3>To create the database:</h3>";
        $errorMessage .= "<ol>";
        $errorMessage .= "<li>Open phpMyAdmin (http://localhost/phpmyadmin)</li>";
        $errorMessage .= "<li>Click 'Import' tab</li>";
        $errorMessage .= "<li>Choose the 'database_schema.sql' file</li>";
        $errorMessage .= "<li>Click 'Go' to import</li>";
        $errorMessage .= "</ol>";
        
        // Display friendly error page
        die("<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Database Connection Error</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    max-width: 800px;
                    margin: 50px auto;
                    padding: 20px;
                    background-color: #f8f9fa;
                }
                h2 { color: #dc3545; }
                h3 { color: #0056b3; margin-top: 20px; }
                ul, ol { line-height: 1.8; }
                strong { color: #000; }
                .error-box {
                    background-color: #fff;
                    border-left: 4px solid #dc3545;
                    padding: 20px;
                    border-radius: 5px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
            </style>
        </head>
        <body>
            <div class='error-box'>
                $errorMessage
            </div>
        </body>
        </html>");
    } else {
        // Generic error in production (don't expose details)
        http_response_code(503);
        die("<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Service Unavailable</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    text-align: center;
                    padding: 50px;
                    background-color: #f8f9fa;
                }
                h1 { color: #dc3545; }
                p { color: #6c757d; }
            </style>
        </head>
        <body>
            <h1>Service Temporarily Unavailable</h1>
            <p>We're experiencing technical difficulties. Please try again later.</p>
            <p>If the problem persists, please contact support.</p>
        </body>
        </html>");
    }
}

// ============================================
// HELPER FUNCTIONS (Optional)
// ============================================

/**
 * Test database connection
 * @return bool True if connection is alive
 */
function testDatabaseConnection() {
    global $pdo;
    try {
        $pdo->query("SELECT 1");
        return true;
    } catch (PDOException $e) {
        error_log("Database connection test failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get database connection info (for debugging only - remove in production)
 */
function getDatabaseInfo() {
    global $pdo;
    return [
        'host' => DB_HOST,
        'database' => DB_NAME,
        'user' => DB_USER,
        'charset' => DB_CHARSET,
        'server_version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
        'driver' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
    ];
}