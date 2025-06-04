<?php
/**
 * Comprehensive Security Library
 * Provides centralized security functions for the entire application
 */

// Ensure config.php is loaded first for essential constants
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/config.php';
}

require_once __DIR__ . '/security-monitoring.php';

/**
 * Start secure session with enhanced security settings
 */
function secure_session_start() {
    // Configure session security settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', is_https());
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', 3600); // 1 hour
    ini_set('session.cookie_lifetime', 3600); // 1 hour
    
    // Regenerate session ID periodically
    if (!session_id()) {
        session_start();
        
        // Regenerate session ID every 30 minutes
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}

/**
 * Check if connection is HTTPS
 */
function is_https() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
           $_SERVER['SERVER_PORT'] == 443 ||
           (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
}

/**
 * Set comprehensive security headers
 */
function set_security_headers() {
    // Basic security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy
    $csp = "default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' https://js.stripe.com https://cdnjs.cloudflare.com https://api.qrserver.com; " .
           "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; " .
           "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
           "img-src 'self' data: https:; " .
           "connect-src 'self' https://api.stripe.com https://api.pwnedpasswords.com; " .
           "frame-src https://js.stripe.com;";
    
    header("Content-Security-Policy: $csp");
    
    // HSTS header for HTTPS
    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input data
 */
function sanitize_input($input, $type = 'string') {
    if (is_array($input)) {
        return array_map(function($item) use ($type) {
            return sanitize_input($item, $type);
        }, $input);
    }
    
    $input = trim($input);
    
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'html':
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        case 'sql':
            return addslashes($input);
        default:
            return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Validate input data
 */
function validate_input($input, $type = 'string', $options = []) {
    switch ($type) {
        case 'email':
            $email = filter_var($input, FILTER_VALIDATE_EMAIL);
            if (!$email) return false;
            
            // Additional email security checks
            if (strlen($email) > 254) return false;
            if (preg_match('/[\r\n]/', $email)) return false;
            
            return $email;
            
        case 'int':
            $int = filter_var($input, FILTER_VALIDATE_INT, $options);
            return $int !== false ? $int : false;
            
        case 'float':
            $float = filter_var($input, FILTER_VALIDATE_FLOAT, $options);
            return $float !== false ? $float : false;
            
        case 'url':
            return filter_var($input, FILTER_VALIDATE_URL);
            
        case 'ip':
            return filter_var($input, FILTER_VALIDATE_IP);
            
        case 'domain':
            return filter_var($input, FILTER_VALIDATE_DOMAIN);
            
        case 'string':
            // Check for dangerous patterns
            $dangerous_patterns = [
                '/<script[^>]*>.*?<\/script>/i',
                '/javascript:/i',
                '/on\w+\s*=/i',
                '/<iframe[^>]*>/i',
                '/\.(php|js|html|htm)$/i',
                '/[<>"\']/',
                '/\x00-\x1f/',
                '/[\r\n]/'
            ];
            
            foreach ($dangerous_patterns as $pattern) {
                if (preg_match($pattern, $input)) {
                    return false;
                }
            }
            
            return sanitize_input($input, 'string');
            
        default:
            return sanitize_input($input, 'string');
    }
}

/**
 * Validate email with enhanced security checks
 */
function validate_email($email) {
    $email = trim($email);
    
    // Basic validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Length check
    if (strlen($email) > 254) {
        return false;
    }
    
    // Check for injection patterns
    $injection_patterns = [
        '/[\r\n]/',          // Line breaks
        '/\bcc:/i',          // CC injection
        '/\bbcc:/i',         // BCC injection
        '/\bto:/i',          // TO injection
        '/\bsubject:/i',     // Subject injection
        '/\bcontent-type:/i', // Content-Type injection
        '/\bmime-version:/i', // MIME-Version injection
        '/\bx-/i',           // X-headers injection
    ];
    
    foreach ($injection_patterns as $pattern) {
        if (preg_match($pattern, $email)) {
            return false;
        }
    }
    
    return $email;
}

/**
 * Validate integer with range checking
 */
function validate_int($input, $min = null, $max = null) {
    $int = filter_var($input, FILTER_VALIDATE_INT);
    
    if ($int === false) {
        return false;
    }
    
    if ($min !== null && $int < $min) {
        return false;
    }
    
    if ($max !== null && $int > $max) {
        return false;
    }
    
    return $int;
}

/**
 * Validate float with range checking
 */
function validate_float($input, $min = null, $max = null) {
    $float = filter_var($input, FILTER_VALIDATE_FLOAT);
    
    if ($float === false) {
        return false;
    }
    
    if ($min !== null && $float < $min) {
        return false;
    }
    
    if ($max !== null && $float > $max) {
        return false;
    }
    
    return $float;
}

/**
 * Rate limiting implementation
 */
function check_rate_limit($key, $max_requests = 60, $time_window = 3600) {
    $cache_file = sys_get_temp_dir() . '/rate_limit_' . md5($key . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    
    $attempts = [];
    if (file_exists($cache_file)) {
        $attempts = json_decode(file_get_contents($cache_file), true) ?: [];
    }
    
    // Clean old attempts
    $now = time();
    $attempts = array_filter($attempts, function($timestamp) use ($now, $time_window) {
        return ($now - $timestamp) < $time_window;
    });
    
    if (count($attempts) >= $max_requests) {
        // Log rate limit exceeded
        log_security_event('rate_limit_exceeded', [
            'key' => $key,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'attempts' => count($attempts)
        ]);
        return false;
    }
    
    $attempts[] = $now;
    file_put_contents($cache_file, json_encode($attempts), LOCK_EX);
    
    return true;
}

/**
 * Check for honeypot access
 */
function check_honeypot_access() {
    static $monitor = null;
    
    if ($monitor === null) {
        try {
            if (!defined('DB_CONNECTED')) {
                // Use a more robust path resolution
                $db_path = file_exists(__DIR__ . '/db.php') ? __DIR__ . '/db.php' : 'includes/db.php';
                require_once $db_path;
            }
            $monitor = new SecurityMonitor($db ?? $pdo);
        } catch (Exception $e) {
            // If we can't initialize monitoring, just continue
            return;
        }
    }
    
    $monitor->checkHoneypot();
}

/**
 * Log security events
 */
function log_security_event($event_type, $data = [], $severity = 'medium') {
    static $monitor = null;
    
    if ($monitor === null) {
        try {
            if (!defined('DB_CONNECTED')) {
                // Use a more robust path resolution
                $db_path = file_exists(__DIR__ . '/db.php') ? __DIR__ . '/db.php' : 'includes/db.php';
                require_once $db_path;
            }
            $monitor = new SecurityMonitor($db ?? $pdo);
        } catch (Exception $e) {
            // Fallback to file logging
            $log_entry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'event_type' => $event_type,
                'severity' => $severity,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'data' => $data
            ];
            
            $log_file = __DIR__ . '/../logs/security_fallback.log';
            file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
            return;
        }
    }
    
    $monitor->logSecurityEvent($event_type, $data, $severity);
}

/**
 * Analyze request for intrusion attempts
 */
function analyze_request() {
    static $ids = null;
    
    if ($ids === null) {
        try {
            // Ensure we include the correct db.php file
            if (!defined('DB_CONNECTED')) {
                // Use a more robust path resolution
                $db_path = file_exists(__DIR__ . '/db.php') ? __DIR__ . '/db.php' : 'includes/db.php';
                require_once $db_path;
            }
            $monitor = new SecurityMonitor($db ?? $pdo);
            $ids = new IntrusionDetectionSystem($monitor);
        } catch (Exception $e) {
            return; // Fail silently if can't initialize
        }
    }
    
    $threats = $ids->analyzeRequest();
    
    if (!empty($threats)) {
        // Block immediately if high-risk threats detected
        $high_risk_threats = ['sql_injection', 'command_injection', 'path_traversal'];
        foreach ($threats as $threat) {
            if (in_array($threat['type'], $high_risk_threats)) {
                http_response_code(403);
                die('Access Denied');
            }
        }
    }
    
    // Check suspicious user agent
    $ids->checkUserAgent();
}

/**
 * Generate secure random string
 */
function generate_secure_random($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Secure file upload validation
 */
function validate_file_upload($file, $allowed_types = [], $max_size = 5242880) {
    $errors = [];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $errors[] = 'File exceeds maximum upload size';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = 'File exceeds form maximum size';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errors[] = 'File was only partially uploaded';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errors[] = 'No file was uploaded';
                break;
            default:
                $errors[] = 'Upload error occurred';
        }
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        $errors[] = 'File size exceeds maximum allowed size';
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!empty($allowed_types) && !in_array($mime_type, $allowed_types)) {
        $errors[] = 'File type not allowed';
    }
    
    // Check for suspicious content
    $content = file_get_contents($file['tmp_name'], false, null, 0, 8192);
    $suspicious_patterns = [
        '/<script/i',
        '/javascript:/i',
        '/<?php/i',
        '/<\?=/i',
        '/eval\(/i',
        '/exec\(/i'
    ];
    
    foreach ($suspicious_patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            $errors[] = 'File contains suspicious content';
            break;
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'mime_type' => $mime_type
    ];
}

/**
 * Clean filename for secure storage
 */
function clean_filename($filename) {
    // Remove any path information
    $filename = basename($filename);
    
    // Remove dangerous characters
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    
    // Prevent double extensions
    $filename = preg_replace('/\.{2,}/', '.', $filename);
    
    // Limit length
    if (strlen($filename) > 255) {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $name = substr(pathinfo($filename, PATHINFO_FILENAME), 0, 250 - strlen($ext));
        $filename = $name . '.' . $ext;
    }
    
    return $filename;
}

/**
 * Hash password securely
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);
}

/**
 * Verify password
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Get client IP address (handles proxies)
 */
function get_client_ip() {
    $ip_sources = [
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR'
    ];
    
    foreach ($ip_sources as $source) {
        if (!empty($_SERVER[$source])) {
            $ip = $_SERVER[$source];
            
            // Handle comma-separated IPs (X-Forwarded-For)
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            
            // Validate IP
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Initialize security monitoring for all requests
 */
function init_security_monitoring() {
    // Analyze current request
    analyze_request();
    
    // Set security headers
    set_security_headers();
    
    // Log request if suspicious
    $suspicious_patterns = [
        '/\.\.(\/|\\\\)/',
        '/(union|select|insert|update|delete|drop|create|alter)/i',
        '/<script/i',
        '/javascript:/i'
    ];
    
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    foreach ($suspicious_patterns as $pattern) {
        if (preg_match($pattern, $request_uri)) {
            log_security_event('suspicious_request', [
                'uri' => $request_uri,
                'pattern' => $pattern
            ], 'medium');
            break;
        }
    }
}

// Auto-initialize security monitoring on include
init_security_monitoring();
?> 