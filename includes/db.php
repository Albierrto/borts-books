<?php
/**
 * Secure Database Connection
 * Enhanced database connectivity with security measures and monitoring
 */

// Load environment variables securely
$envPath = dirname(__DIR__) . '/.env';
if (!file_exists($envPath)) {
    error_log('CRITICAL: .env file not found at ' . $envPath);
    die('Database configuration error. Please contact administrator.');
}

$envContent = file_get_contents($envPath);
if ($envContent === false) {
    error_log('CRITICAL: Cannot read .env file');
    die('Database configuration error. Please contact administrator.');
}

$lines = explode("\n", $envContent);
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || strpos($line, '#') === 0) continue;
    
    if (strpos($line, '=') !== false) {
        list($name, $value) = array_map('trim', explode('=', $line, 2));
        // Remove quotes if present
        $value = trim($value, '"\'');
        $_ENV[$name] = $value;
    }
}

// Database configuration with security defaults
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? '';
$user = $_ENV['DB_USER'] ?? '';
$pass = $_ENV['DB_PASS'] ?? '';
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
$port = $_ENV['DB_PORT'] ?? 3306;

// Validate database configuration
if (empty($dbname) || empty($user)) {
    error_log('CRITICAL: Database credentials not properly configured');
    die('Database configuration error. Please contact administrator.');
}

// Enhanced DSN with security options
$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";

// Basic PDO options for security and performance
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_PERSISTENT         => false, // Disable persistent connections for security
    PDO::ATTR_AUTOCOMMIT         => false, // Disable autocommit for transaction control
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'",
];

// Add SSL verification if the constant is available (PHP 7.1+)
if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false; // Set to true in production with SSL
}

// Add SSL configuration if available
if (!empty($_ENV['DB_SSL_CA'])) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = $_ENV['DB_SSL_CA'];
}
if (!empty($_ENV['DB_SSL_CERT'])) {
    $options[PDO::MYSQL_ATTR_SSL_CERT] = $_ENV['DB_SSL_CERT'];
}
if (!empty($_ENV['DB_SSL_KEY'])) {
    $options[PDO::MYSQL_ATTR_SSL_KEY] = $_ENV['DB_SSL_KEY'];
}

try {
    // Make database connections globally accessible
    global $db, $pdo;
    
    $db = new PDO($dsn, $user, $pass, $options);
    $pdo = $db; // Alias for compatibility
    
    // Set additional security settings
    $db->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
    $db->exec("SET SESSION autocommit = 1");
    
    // Connection successful - log if debug mode
    if ($_ENV['DB_DEBUG'] ?? false) {
        error_log("Database connection established successfully");
    }
    
} catch (PDOException $e) {
    // Log detailed error for debugging (not shown to user)
    error_log('Database connection failed: ' . $e->getMessage() . ' | DSN: ' . $dsn);
    
    // Show generic error to user
    if (defined('APP_ENV') && APP_ENV === 'development') {
        die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
    } else {
        die('Database connection error. Please try again later or contact support.');
    }
}

/**
 * Enhanced database initialization with security considerations
 */
function initializeSecureDatabase($db) {
    try {
        // Check if required security tables exist
        $requiredTables = [
            'security_events',
            'threat_intelligence', 
            'password_history',
            'failed_login_attempts',
            'two_factor_auth',
            'admin_login_audit'
        ];
        
        foreach ($requiredTables as $table) {
            $stmt = $db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            
            if (!$stmt->fetch()) {
                // Table doesn't exist, create it
                createSecurityTable($db, $table);
            }
        }
        
        // Update existing tables with security enhancements
        enhanceExistingTables($db);
        
    } catch (PDOException $e) {
        error_log("Database initialization error: " . $e->getMessage());
    }
}

/**
 * Create security-related tables
 */
function createSecurityTable($db, $tableName) {
    $tables = [
        'security_events' => "
            CREATE TABLE security_events (
                id INT AUTO_INCREMENT PRIMARY KEY,
                event_type VARCHAR(100) NOT NULL,
                severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT,
                request_uri TEXT,
                event_data JSON,
                threat_score INT DEFAULT 0,
                blocked BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_event_type (event_type),
                INDEX idx_severity (severity),
                INDEX idx_ip_address (ip_address),
                INDEX idx_created_at (created_at),
                INDEX idx_threat_score (threat_score)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        'threat_intelligence' => "
            CREATE TABLE threat_intelligence (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL UNIQUE,
                threat_type VARCHAR(50) NOT NULL,
                confidence_score INT DEFAULT 0,
                source VARCHAR(100),
                first_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                blocked BOOLEAN DEFAULT FALSE,
                notes TEXT,
                INDEX idx_ip_address (ip_address),
                INDEX idx_threat_type (threat_type),
                INDEX idx_confidence_score (confidence_score)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        'admin_login_audit' => "
            CREATE TABLE admin_login_audit (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) NOT NULL,
                status ENUM('success', 'failure') NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT,
                attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_username (username),
                INDEX idx_attempted_at (attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        "
    ];
    
    if (isset($tables[$tableName])) {
        try {
            $db->exec($tables[$tableName]);
            error_log("Created security table: $tableName");
        } catch (PDOException $e) {
            error_log("Failed to create table $tableName: " . $e->getMessage());
        }
    }
}

/**
 * Enhance existing tables with security fields
 */
function enhanceExistingTables($db) {
    $enhancements = [
        'customer_requests' => [
            'ADD COLUMN IF NOT EXISTS email_hash VARCHAR(64)',
            'ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45)',
            'ADD COLUMN IF NOT EXISTS user_agent TEXT',
            'ADD INDEX IF NOT EXISTS idx_email_hash (email_hash)',
            'ADD INDEX IF NOT EXISTS idx_ip_address (ip_address)'
        ],
        'sell_submissions' => [
            'ADD COLUMN IF NOT EXISTS email_hash VARCHAR(64)',
            'ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45)',
            'ADD COLUMN IF NOT EXISTS user_agent TEXT',
            'ADD INDEX IF NOT EXISTS idx_email_hash (email_hash)'
        ],
        'products' => [
            'ADD COLUMN IF NOT EXISTS ebay_item_id VARCHAR(255)',
            'ADD INDEX IF NOT EXISTS idx_ebay_item_id (ebay_item_id)'
        ]
    ];
    
    foreach ($enhancements as $table => $alterations) {
        foreach ($alterations as $alteration) {
            try {
                $db->exec("ALTER TABLE $table $alteration");
            } catch (PDOException $e) {
                // Ignore errors for columns/indexes that already exist
                if (strpos($e->getMessage(), 'Duplicate') === false) {
                    error_log("Table enhancement warning for $table: " . $e->getMessage());
                }
            }
        }
    }
}

/**
 * Get database statistics for monitoring
 */
function getDatabaseStats($db) {
    try {
        $stats = [];
        
        // Connection count
        $stmt = $db->query("SHOW STATUS LIKE 'Threads_connected'");
        $result = $stmt->fetch();
        $stats['connections'] = $result['Value'] ?? 0;
        
        // Query cache hit rate
        $stmt = $db->query("SHOW STATUS LIKE 'Qcache_hits'");
        $hits = $stmt->fetch()['Value'] ?? 0;
        
        $stmt = $db->query("SHOW STATUS LIKE 'Qcache_inserts'");
        $inserts = $stmt->fetch()['Value'] ?? 0;
        
        $stats['cache_hit_rate'] = ($hits + $inserts) > 0 ? round($hits / ($hits + $inserts) * 100, 2) : 0;
        
        return $stats;
        
    } catch (PDOException $e) {
        error_log("Database stats error: " . $e->getMessage());
        return [];
    }
}

// Initialize secure database features
initializeSecureDatabase($db);

// Define that this file has been included properly
define('DB_CONNECTED', true);
