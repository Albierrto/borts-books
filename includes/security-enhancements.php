<?php
/**
 * Enhanced Security System for Bort's Books
 * Advanced protection against various security threats
 */

class SecurityEnhancer {
    
    private static $max_login_attempts = 5;
    private static $lockout_duration = 900; // 15 minutes
    private static $rate_limit_requests = 100; // per hour
    
    public static function init() {
        // Start secure session
        self::startSecureSession();
        
        // Set security headers
        self::setSecurityHeaders();
        
        // Check for suspicious activity
        self::detectSuspiciousActivity();
        
        // Rate limiting
        self::enforceRateLimit();
    }
    
    private static function startSecureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 1);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
            
            // Regenerate session ID periodically
            if (!isset($_SESSION['last_regeneration'])) {
                $_SESSION['last_regeneration'] = time();
            } elseif (time() - $_SESSION['last_regeneration'] > 300) {
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }
    }
    
    private static function setSecurityHeaders() {
        if (!headers_sent()) {
            // Prevent XSS attacks
            header('X-XSS-Protection: 1; mode=block');
            
            // Prevent clickjacking
            header('X-Frame-Options: DENY');
            
            // Prevent MIME type sniffing
            header('X-Content-Type-Options: nosniff');
            
            // Strict Transport Security
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            
            // Content Security Policy
            $csp = "default-src 'self'; ";
            $csp .= "script-src 'self' 'unsafe-inline' https://js.stripe.com https://www.google.com https://www.gstatic.com; ";
            $csp .= "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; ";
            $csp .= "font-src 'self' https://fonts.gstatic.com; ";
            $csp .= "img-src 'self' data: https:; ";
            $csp .= "connect-src 'self' https://api.stripe.com; ";
            $csp .= "frame-src https://js.stripe.com;";
            
            header("Content-Security-Policy: $csp");
            
            // Referrer Policy
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
    }
    
    public static function detectSuspiciousActivity() {
        $ip = self::getClientIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Check for common attack patterns
        $suspicious_patterns = [
            '/\.\./i',                    // Directory traversal
            '/union.*select/i',           // SQL injection
            '/<script/i',                 // XSS attempts
            '/eval\s*\(/i',              // Code injection
            '/base64_decode/i',          // Encoded payloads
            '/system\s*\(/i',            // System calls
            '/exec\s*\(/i',              // Command execution
        ];
        
        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, $request_uri) || preg_match($pattern, $user_agent)) {
                self::logSecurityEvent('suspicious_pattern', $ip, $pattern);
                self::blockIP($ip, 'Suspicious pattern detected');
                return false;
            }
        }
        
        // Check for rapid requests (potential DDoS)
        if (self::isRapidRequests($ip)) {
            self::logSecurityEvent('rapid_requests', $ip);
            self::blockIP($ip, 'Too many rapid requests');
            return false;
        }
        
        return true;
    }
    
    private static function isRapidRequests($ip) {
        $cache_file = '../cache/requests_' . md5($ip) . '.cache';
        $current_time = time();
        $time_window = 60; // 1 minute
        $max_requests = 30; // Max 30 requests per minute
        
        $requests = [];
        if (file_exists($cache_file)) {
            $requests = json_decode(file_get_contents($cache_file), true) ?: [];
        }
        
        // Remove old requests
        $requests = array_filter($requests, function($timestamp) use ($current_time, $time_window) {
            return ($current_time - $timestamp) < $time_window;
        });
        
        // Add current request
        $requests[] = $current_time;
        
        // Save updated requests
        file_put_contents($cache_file, json_encode($requests));
        
        return count($requests) > $max_requests;
    }
    
    public static function enforceRateLimit() {
        $ip = self::getClientIP();
        $cache_file = '../cache/rate_limit_' . md5($ip) . '.cache';
        $current_time = time();
        $time_window = 3600; // 1 hour
        
        $requests = 0;
        if (file_exists($cache_file)) {
            $data = json_decode(file_get_contents($cache_file), true);
            if ($data && ($current_time - $data['timestamp']) < $time_window) {
                $requests = $data['count'];
            }
        }
        
        $requests++;
        
        if ($requests > self::$rate_limit_requests) {
            self::logSecurityEvent('rate_limit_exceeded', $ip);
            http_response_code(429);
            die('Rate limit exceeded. Please try again later.');
        }
        
        file_put_contents($cache_file, json_encode([
            'timestamp' => $current_time,
            'count' => $requests
        ]));
    }
    
    public static function validateLoginAttempt($username, $password) {
        $ip = self::getClientIP();
        $cache_file = '../cache/login_attempts_' . md5($ip) . '.cache';
        
        // Check if IP is locked out
        if (file_exists($cache_file)) {
            $data = json_decode(file_get_contents($cache_file), true);
            if ($data && isset($data['locked_until']) && time() < $data['locked_until']) {
                $remaining = $data['locked_until'] - time();
                throw new Exception("Account locked. Try again in " . ceil($remaining / 60) . " minutes.");
            }
        }
        
        // Validate credentials (implement your actual validation logic)
        $valid = self::validateCredentials($username, $password);
        
        if (!$valid) {
            // Record failed attempt
            $attempts = 0;
            if (file_exists($cache_file)) {
                $data = json_decode(file_get_contents($cache_file), true);
                $attempts = $data['attempts'] ?? 0;
            }
            
            $attempts++;
            
            if ($attempts >= self::$max_login_attempts) {
                // Lock account
                file_put_contents($cache_file, json_encode([
                    'attempts' => $attempts,
                    'locked_until' => time() + self::$lockout_duration
                ]));
                
                self::logSecurityEvent('account_locked', $ip, $username);
                throw new Exception("Too many failed attempts. Account locked for " . (self::$lockout_duration / 60) . " minutes.");
            } else {
                file_put_contents($cache_file, json_encode(['attempts' => $attempts]));
                throw new Exception("Invalid credentials. " . (self::$max_login_attempts - $attempts) . " attempts remaining.");
            }
        } else {
            // Clear failed attempts on successful login
            if (file_exists($cache_file)) {
                unlink($cache_file);
            }
            return true;
        }
    }
    
    private static function validateCredentials($username, $password) {
        // Implement your actual credential validation
        // This is a placeholder - replace with your actual logic
        return false;
    }
    
    public static function sanitizeInput($input, $type = 'string') {
        switch ($type) {
            case 'email':
                return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
            
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            
            case 'url':
                return filter_var(trim($input), FILTER_SANITIZE_URL);
            
            case 'string':
            default:
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }
    
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function encryptSensitiveData($data, $key = null) {
        if (!$key) {
            $key = $_ENV['ENCRYPTION_KEY'] ?? 'default-key-change-this';
        }
        
        $cipher = 'AES-256-GCM';
        $iv = random_bytes(12);
        $tag = '';
        
        $encrypted = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
        
        return base64_encode($iv . $tag . $encrypted);
    }
    
    public static function decryptSensitiveData($encryptedData, $key = null) {
        if (!$key) {
            $key = $_ENV['ENCRYPTION_KEY'] ?? 'default-key-change-this';
        }
        
        $data = base64_decode($encryptedData);
        $cipher = 'AES-256-GCM';
        
        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $encrypted = substr($data, 28);
        
        return openssl_decrypt($encrypted, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
    }
    
    private static function blockIP($ip, $reason) {
        $blocked_file = '../cache/blocked_ips.json';
        $blocked_ips = [];
        
        if (file_exists($blocked_file)) {
            $blocked_ips = json_decode(file_get_contents($blocked_file), true) ?: [];
        }
        
        $blocked_ips[$ip] = [
            'reason' => $reason,
            'timestamp' => time(),
            'expires' => time() + 3600 // Block for 1 hour
        ];
        
        file_put_contents($blocked_file, json_encode($blocked_ips));
        
        http_response_code(403);
        die('Access denied.');
    }
    
    public static function isIPBlocked($ip) {
        $blocked_file = '../cache/blocked_ips.json';
        
        if (!file_exists($blocked_file)) {
            return false;
        }
        
        $blocked_ips = json_decode(file_get_contents($blocked_file), true) ?: [];
        
        if (isset($blocked_ips[$ip])) {
            if (time() > $blocked_ips[$ip]['expires']) {
                // Remove expired block
                unset($blocked_ips[$ip]);
                file_put_contents($blocked_file, json_encode($blocked_ips));
                return false;
            }
            return true;
        }
        
        return false;
    }
    
    private static function logSecurityEvent($event_type, $ip, $details = '') {
        $log_data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event_type' => $event_type,
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'details' => $details
        ];
        
        $log_file = '../logs/security.log';
        if (!file_exists(dirname($log_file))) {
            mkdir(dirname($log_file), 0755, true);
        }
        
        file_put_contents($log_file, json_encode($log_data) . "\n", FILE_APPEND);
    }
    
    private static function getClientIP() {
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    public static function generateSecurePassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return substr(str_shuffle(str_repeat($chars, ceil($length / strlen($chars)))), 0, $length);
    }
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}

/**
 * Input Validation and Sanitization
 */
class InputValidator {
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validatePhone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return strlen($phone) >= 10 && strlen($phone) <= 15;
    }
    
    public static function validateCreditCard($number) {
        $number = preg_replace('/[^0-9]/', '', $number);
        
        // Luhn algorithm
        $sum = 0;
        $length = strlen($number);
        
        for ($i = $length - 1; $i >= 0; $i--) {
            $digit = intval($number[$i]);
            
            if (($length - $i) % 2 == 0) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            
            $sum += $digit;
        }
        
        return $sum % 10 == 0;
    }
    
    public static function validateProductData($data) {
        $errors = [];
        
        if (empty($data['title']) || strlen($data['title']) < 3) {
            $errors[] = 'Product title must be at least 3 characters long';
        }
        
        if (empty($data['price']) || !is_numeric($data['price']) || $data['price'] <= 0) {
            $errors[] = 'Valid price is required';
        }
        
        if (empty($data['condition']) || !in_array($data['condition'], ['new', 'used', 'refurbished'])) {
            $errors[] = 'Valid condition is required';
        }
        
        return $errors;
    }
}

// Initialize security system
SecurityEnhancer::init();
?> 