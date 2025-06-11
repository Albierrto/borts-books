<?php
/**
 * Secure Configuration Management
 * Centralized configuration with enhanced security measures
 */

// Load environment variables first
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    if ($envContent !== false) {
        $lines = explode("\n", $envContent);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            
            if (strpos($line, '=') !== false) {
                list($name, $value) = array_map('trim', explode('=', $line, 2));
                $value = trim($value, '"\'');
                $_ENV[$name] = $value;
            }
        }
    }
}

// Application Configuration
define('APP_NAME', $_ENV['APP_NAME'] ?? 'Bort\'s Books');
define('APP_VERSION', $_ENV['APP_VERSION'] ?? '2.0.0');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN));

// Security Configuration
define('SECURITY_KEY', $_ENV['SECURITY_KEY'] ?? (APP_ENV === 'development' ? 'dev_security_key_12345' : ''));
define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? (APP_ENV === 'development' ? 'dev_encryption_key_12345' : ''));
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? (APP_ENV === 'development' ? 'dev_jwt_secret_12345' : ''));

// Admin Configuration (secure)
define('ADMIN_USERNAME', $_ENV['ADMIN_USERNAME'] ?? 'admin');
define('ADMIN_PASSWORD_HASH', $_ENV['ADMIN_PASSWORD_HASH'] ?? (APP_ENV === 'development' ? password_hash('admin123', PASSWORD_ARGON2ID) : ''));
define('ADMIN_EMAIL', $_ENV['ADMIN_EMAIL'] ?? 'admin@bortsbooks.com');

// Email Configuration
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'localhost');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
define('SMTP_ENCRYPTION', $_ENV['SMTP_ENCRYPTION'] ?? 'tls');
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? '');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');
define('FROM_EMAIL', $_ENV['FROM_EMAIL'] ?? 'noreply@bortsbooks.com');
define('FROM_NAME', $_ENV['FROM_NAME'] ?? 'Bort\'s Books');

// Stripe Configuration (secure)
define('STRIPE_PUBLISHABLE_KEY', $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '');
define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY'] ?? '');
define('STRIPE_WEBHOOK_SECRET', $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '');

// File Upload Configuration
define('MAX_UPLOAD_SIZE', $_ENV['MAX_UPLOAD_SIZE'] ?? 5242880); // 5MB
define('UPLOAD_PATH', $_ENV['UPLOAD_PATH'] ?? dirname(__DIR__) . '/uploads/');
define('ALLOWED_UPLOAD_TYPES', $_ENV['ALLOWED_UPLOAD_TYPES'] ?? 'jpg,jpeg,png,pdf');

// Session Configuration
define('SESSION_LIFETIME', $_ENV['SESSION_LIFETIME'] ?? 7200); // 2 hours
define('SESSION_NAME', $_ENV['SESSION_NAME'] ?? 'BORTS_BOOKS_SESSION');

// Rate Limiting Configuration
define('RATE_LIMIT_REQUESTS', $_ENV['RATE_LIMIT_REQUESTS'] ?? 60);
define('RATE_LIMIT_WINDOW', $_ENV['RATE_LIMIT_WINDOW'] ?? 3600); // 1 hour

// Security Settings
define('ENABLE_2FA', filter_var($_ENV['ENABLE_2FA'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('PASSWORD_MIN_LENGTH', $_ENV['PASSWORD_MIN_LENGTH'] ?? 12);
define('MAX_LOGIN_ATTEMPTS', $_ENV['MAX_LOGIN_ATTEMPTS'] ?? 5);
define('ACCOUNT_LOCKOUT_TIME', $_ENV['ACCOUNT_LOCKOUT_TIME'] ?? 900); // 15 minutes

// Monitoring and Logging
define('ENABLE_SECURITY_MONITORING', filter_var($_ENV['ENABLE_SECURITY_MONITORING'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'INFO');
define('LOG_PATH', $_ENV['LOG_PATH'] ?? dirname(__DIR__) . '/logs/');

// Performance Settings
define('ENABLE_CACHING', filter_var($_ENV['ENABLE_CACHING'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('CACHE_LIFETIME', $_ENV['CACHE_LIFETIME'] ?? 3600); // 1 hour

// Business Settings
define('BUSINESS_NAME', $_ENV['BUSINESS_NAME'] ?? 'Bort\'s Books');
define('BUSINESS_EMAIL', $_ENV['BUSINESS_EMAIL'] ?? 'contact@bortsbooks.com');
define('BUSINESS_PHONE', $_ENV['BUSINESS_PHONE'] ?? '');
define('BUSINESS_ADDRESS', $_ENV['BUSINESS_ADDRESS'] ?? '');

// Validation Functions
function validateConfig() {
    $errors = [];
    $warnings = [];
    
    // Check required security keys (only fail in production)
    if (empty(SECURITY_KEY) && APP_ENV === 'production') {
        $errors[] = 'SECURITY_KEY is not configured (required in production)';
    } elseif (empty(SECURITY_KEY)) {
        $warnings[] = 'SECURITY_KEY is not configured (recommended for security)';
    }
    
    if (empty(ENCRYPTION_KEY) && APP_ENV === 'production') {
        $errors[] = 'ENCRYPTION_KEY is not configured (required in production)';
    } elseif (empty(ENCRYPTION_KEY)) {
        $warnings[] = 'ENCRYPTION_KEY is not configured (recommended for security)';
    }
    
    if (empty(ADMIN_PASSWORD_HASH) && APP_ENV === 'production') {
        $errors[] = 'ADMIN_PASSWORD_HASH is not configured (required in production)';
    } elseif (empty(ADMIN_PASSWORD_HASH)) {
        $warnings[] = 'ADMIN_PASSWORD_HASH is not configured (admin access will be limited)';
    }
    
    // Validate email configuration if SMTP is used (warning only)
    if (!empty(SMTP_USERNAME) && empty(SMTP_PASSWORD)) {
        $warnings[] = 'SMTP_PASSWORD is required when SMTP_USERNAME is set';
    }
    
    // Check Stripe configuration if needed (warning only)
    if (!empty(STRIPE_SECRET_KEY) && empty(STRIPE_PUBLISHABLE_KEY)) {
        $warnings[] = 'STRIPE_PUBLISHABLE_KEY is required when STRIPE_SECRET_KEY is set';
    }
    
    // Validate paths (create if missing)
    if (!is_dir(LOG_PATH)) {
        @mkdir(LOG_PATH, 0755, true);
        if (!is_dir(LOG_PATH)) {
            $warnings[] = 'Cannot create log directory: ' . LOG_PATH;
        }
    }
    
    if (!is_dir(UPLOAD_PATH)) {
        @mkdir(UPLOAD_PATH, 0755, true);
        if (!is_dir(UPLOAD_PATH)) {
            $warnings[] = 'Cannot create upload directory: ' . UPLOAD_PATH;
        }
    }
    
    // Log warnings but don't fail
    if (!empty($warnings) && APP_ENV === 'development') {
        error_log('Configuration warnings: ' . implode(', ', $warnings));
    }
    
    return $errors; // Only return critical errors
}

/**
 * Get secure configuration value
 */
function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

/**
 * Check if configuration is secure
 */
function isConfigSecure() {
    // Check if running in production with proper security
    if (APP_ENV === 'production') {
        return !empty(SECURITY_KEY) && 
               !empty(ENCRYPTION_KEY) && 
               !empty(ADMIN_PASSWORD_HASH) &&
               !APP_DEBUG;
    }
    
    return true; // Allow development mode
}

/**
 * Initialize security configuration
 */
function initSecurityConfig() {
    // Validate configuration
    $errors = validateConfig();
    
    if (!empty($errors)) {
        if (APP_ENV === 'development') {
            error_log('Configuration errors: ' . implode(', ', $errors));
            // In development, show detailed errors but don't stop execution
            foreach ($errors as $error) {
                echo "<div style='background: #ffebee; color: #c62828; padding: 10px; margin: 5px; border-radius: 5px;'>Config Warning: $error</div>";
            }
        } else {
            error_log('CRITICAL: Application configuration errors detected');
            if (!headers_sent()) {
                http_response_code(500);
                die('Configuration error. Please contact administrator.');
            }
        }
    }
    
    // Set error reporting based on environment
    if (APP_ENV === 'production') {
        error_reporting(E_ERROR | E_WARNING | E_PARSE);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
    } else {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }
    
    // Set timezone
    if (!empty($_ENV['TIMEZONE'])) {
        date_default_timezone_set($_ENV['TIMEZONE']);
    } else {
        date_default_timezone_set('UTC');
    }
    
    // Initialize security headers and session
    if (!headers_sent()) {
        // Security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Basic CSP for development
        if (APP_ENV === 'development') {
            header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' data: https:;");
        }
    }
}

// Auto-initialize security configuration
initSecurityConfig();

/**
 * Development helper functions
 */
if (APP_ENV === 'development') {
    /**
     * Generate secure random keys for development
     */
    function generateSecureKeys() {
        return [
            'SECURITY_KEY' => bin2hex(random_bytes(32)),
            'ENCRYPTION_KEY' => bin2hex(random_bytes(32)),
            'JWT_SECRET' => bin2hex(random_bytes(32))
        ];
    }
    
    /**
     * Generate admin password hash
     */
    function generateAdminPasswordHash($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
}
?> 