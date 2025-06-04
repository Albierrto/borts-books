<?php
/**
 * Enhanced Admin Authentication System
 * Provides secure admin login functionality with comprehensive security measures
 */

// Only include config if not already loaded
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/config.php';
}
require_once __DIR__ . '/password-security.php';
require_once __DIR__ . '/database-encryption.php';

/**
 * Simple admin password verification function
 */
function verify_admin_password($password) {
    try {
        $adminCredentials = getAdminCredentials();
        if (!$adminCredentials) {
            return false;
        }
        
        return password_verify($password, $adminCredentials['password_hash']);
    } catch (Exception $e) {
        error_log("Admin password verification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if admin is authenticated
 */
function is_admin_logged_in() {
    return isset($_SESSION['admin_logged_in']) && 
           $_SESSION['admin_logged_in'] === true &&
           isset($_SESSION['admin_login_time']) &&
           (time() - $_SESSION['admin_login_time'] < 7200); // 2 hours
}

/**
 * Check admin authentication and redirect if not logged in
 */
function check_admin_auth() {
    if (!is_admin_logged_in()) {
        // Clean any invalid session data
        if (isset($_SESSION['admin_logged_in'])) {
            session_destroy();
            session_start();
        }
        
        header('Location: /pages/admin-login.php');
        exit;
    }
    
    // Update last activity time
    $_SESSION['admin_last_activity'] = time();
    
    // Regenerate session ID every 30 minutes for security
    if (!isset($_SESSION['last_regeneration']) || 
        (time() - $_SESSION['last_regeneration'] > 1800)) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

/**
 * Enhanced admin login with comprehensive security
 */
function admin_login($username, $password) {
    try {
        // Initialize password security system
        require_once __DIR__ . '/db.php';
        $passwordSecurity = new PasswordSecurity($db ?? $pdo);
        
        // Get admin credentials from secure config
        $adminCredentials = getAdminCredentials();
        
        if (!$adminCredentials) {
            log_security_event('admin_login_config_error', ['username' => $username]);
            return false;
        }
        
        // Check if account is locked
        if ($passwordSecurity->isAccountLocked('admin_' . $username)) {
            log_security_event('admin_login_locked', ['username' => $username]);
            return false;
        }
        
        // Validate credentials
        if ($username !== $adminCredentials['username']) {
            $passwordSecurity->recordFailedLogin('admin_' . $username);
            log_security_event('admin_login_invalid_username', ['username' => $username]);
            return false;
        }
        
        if (!password_verify($password, $adminCredentials['password_hash'])) {
            $passwordSecurity->recordFailedLogin('admin_' . $username);
            log_security_event('admin_login_invalid_password', ['username' => $username]);
            return false;
        }
        
        // Check if password needs rehashing
        if ($passwordSecurity->needsRehash($adminCredentials['password_hash'])) {
            updateAdminPassword($username, $password);
        }
        
        // Clear failed login attempts
        $passwordSecurity->clearFailedAttempts('admin_' . $username);
        
        // Successful login - create secure session
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_login_time'] = time();
        $_SESSION['admin_last_activity'] = time();
        $_SESSION['admin_ip'] = get_client_ip();
        $_SESSION['admin_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $_SESSION['last_regeneration'] = time();
        
        // Store login in audit log
        auditAdminLogin($username, 'success');
        
        log_security_event('admin_login_success', [
            'username' => $username,
            'ip' => get_client_ip()
        ]);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Admin login error: " . $e->getMessage());
        log_security_event('admin_login_error', [
            'username' => $username,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * Get admin credentials from secure storage
 */
function getAdminCredentials() {
    try {
        // Try to get from environment variables first
        $username = $_ENV['ADMIN_USERNAME'] ?? null;
        $password_hash = $_ENV['ADMIN_PASSWORD_HASH'] ?? null;
        
        if ($username && $password_hash) {
            return [
                'username' => $username,
                'password_hash' => $password_hash
            ];
        }
        
        // Fallback to config constants
        $username = defined('ADMIN_USERNAME') ? ADMIN_USERNAME : 'admin';
        $password_hash = defined('ADMIN_PASSWORD_HASH') ? ADMIN_PASSWORD_HASH : null;
        
        if (!$password_hash) {
            error_log("Admin password hash not configured");
            return false;
        }
        
        return [
            'username' => $username,
            'password_hash' => $password_hash
        ];
        
    } catch (Exception $e) {
        error_log("Error getting admin credentials: " . $e->getMessage());
        return false;
    }
}

/**
 * Update admin password with new hash
 */
function updateAdminPassword($username, $password) {
    try {
        $passwordSecurity = new PasswordSecurity($db ?? $pdo);
        $newHash = $passwordSecurity->hashPassword($password);
        
        // In a production system, this would update the database
        // For now, we'll just log it
        error_log("Admin password rehash needed for user: $username");
        
    } catch (Exception $e) {
        error_log("Error updating admin password: " . $e->getMessage());
    }
}

/**
 * Audit admin login attempts
 */
function auditAdminLogin($username, $status) {
    try {
        require_once __DIR__ . '/db.php';
        
        $stmt = ($db ?? $pdo)->prepare("
            INSERT INTO admin_login_audit (
                username, 
                status, 
                ip_address, 
                user_agent, 
                attempted_at
            ) VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $username,
            $status,
            get_client_ip(),
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
    } catch (Exception $e) {
        // Create table if it doesn't exist
        try {
            ($db ?? $pdo)->exec("
                CREATE TABLE IF NOT EXISTS admin_login_audit (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(255) NOT NULL,
                    status ENUM('success', 'failure') NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    user_agent TEXT,
                    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_username (username),
                    INDEX idx_attempted_at (attempted_at)
                )
            ");
            
            // Try again
            auditAdminLogin($username, $status);
            
        } catch (Exception $e2) {
            error_log("Failed to create admin audit table: " . $e2->getMessage());
        }
    }
}

/**
 * Enhanced admin logout with cleanup
 */
function admin_logout() {
    // Log logout
    if (isset($_SESSION['admin_username'])) {
        log_security_event('admin_logout', [
            'username' => $_SESSION['admin_username'],
            'session_duration' => time() - ($_SESSION['admin_login_time'] ?? time())
        ]);
    }
    
    // Clear all session data
    $session_id = session_id();
    $_SESSION = [];
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
    
    // Start new session for flash messages
    session_start();
    $_SESSION['logout_message'] = 'You have been successfully logged out.';
    
    header('Location: /pages/admin-login.php');
    exit;
}

/**
 * Check for suspicious admin activity
 */
function checkAdminActivity() {
    if (!is_admin_logged_in()) {
        return;
    }
    
    // Check for IP address changes
    $current_ip = get_client_ip();
    if (isset($_SESSION['admin_ip']) && $_SESSION['admin_ip'] !== $current_ip) {
        log_security_event('admin_ip_change', [
            'username' => $_SESSION['admin_username'] ?? 'unknown',
            'old_ip' => $_SESSION['admin_ip'],
            'new_ip' => $current_ip
        ], 'high');
        
        // Force logout on IP change
        admin_logout();
    }
    
    // Check for user agent changes
    $current_ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    if (isset($_SESSION['admin_user_agent']) && $_SESSION['admin_user_agent'] !== $current_ua) {
        log_security_event('admin_ua_change', [
            'username' => $_SESSION['admin_username'] ?? 'unknown',
            'old_ua' => $_SESSION['admin_user_agent'],
            'new_ua' => $current_ua
        ], 'medium');
    }
    
    // Check for session timeout
    if (isset($_SESSION['admin_last_activity']) && 
        (time() - $_SESSION['admin_last_activity'] > 7200)) {
        
        log_security_event('admin_session_timeout', [
            'username' => $_SESSION['admin_username'] ?? 'unknown',
            'last_activity' => $_SESSION['admin_last_activity']
        ]);
        
        admin_logout();
    }
}

/**
 * Get admin session info for dashboard
 */
function getAdminSessionInfo() {
    if (!is_admin_logged_in()) {
        return null;
    }
    
    return [
        'username' => $_SESSION['admin_username'] ?? 'unknown',
        'login_time' => $_SESSION['admin_login_time'] ?? time(),
        'last_activity' => $_SESSION['admin_last_activity'] ?? time(),
        'ip_address' => $_SESSION['admin_ip'] ?? 'unknown',
        'session_duration' => time() - ($_SESSION['admin_login_time'] ?? time())
    ];
}

/**
 * Initialize admin security monitoring
 */
function initAdminSecurity() {
    // Check admin activity on every admin page load
    if (is_admin_logged_in()) {
        checkAdminActivity();
    }
}

// Auto-initialize admin security
initAdminSecurity(); 