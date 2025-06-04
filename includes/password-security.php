<?php
/**
 * Enhanced Password Security System
 * Implements strong password policies, history tracking, breach checking, and 2FA support
 */

class PasswordSecurity {
    private $minLength = 12;
    private $maxLength = 128;
    private $historyLimit = 12; // Remember last 12 passwords
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->initializePasswordTables();
    }
    
    /**
     * Initialize password-related database tables
     */
    private function initializePasswordTables() {
        try {
            // Password history table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS password_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id VARCHAR(255) NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_created_at (created_at)
                )
            ");
            
            // Failed login attempts table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS failed_login_attempts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    identifier VARCHAR(255) NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    user_agent TEXT,
                    INDEX idx_identifier (identifier),
                    INDEX idx_ip_address (ip_address),
                    INDEX idx_attempted_at (attempted_at)
                )
            ");
            
            // 2FA secrets table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS two_factor_auth (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id VARCHAR(255) NOT NULL UNIQUE,
                    secret VARCHAR(255) NOT NULL,
                    backup_codes TEXT,
                    enabled BOOLEAN DEFAULT FALSE,
                    enabled_at TIMESTAMP NULL,
                    last_used TIMESTAMP NULL,
                    INDEX idx_user_id (user_id)
                )
            ");
            
        } catch (PDOException $e) {
            error_log("Failed to initialize password tables: " . $e->getMessage());
        }
    }
    
    /**
     * Validate password strength
     */
    public function validatePasswordStrength($password) {
        $errors = [];
        $score = 0;
        $requirements = [];
        
        // Length check
        if (strlen($password) < $this->minLength) {
            $errors[] = "Password must be at least {$this->minLength} characters long";
        } elseif (strlen($password) >= $this->minLength) {
            $score += 10;
            $requirements['length'] = true;
        }
        
        if (strlen($password) > $this->maxLength) {
            $errors[] = "Password must not exceed {$this->maxLength} characters";
        }
        
        // Character type requirements
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        } else {
            $score += 10;
            $requirements['lowercase'] = true;
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        } else {
            $score += 10;
            $requirements['uppercase'] = true;
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        } else {
            $score += 10;
            $requirements['numbers'] = true;
        }
        
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        } else {
            $score += 15;
            $requirements['special'] = true;
        }
        
        // Additional strength checks
        if (strlen($password) >= 16) {
            $score += 10; // Bonus for longer passwords
        }
        
        // Check for repeated characters
        if (preg_match('/(.)\1{2,}/', $password)) {
            $score -= 10;
            $errors[] = 'Password should not contain repeated characters';
        }
        
        // Check for sequential characters
        if ($this->hasSequentialChars($password)) {
            $score -= 10;
            $errors[] = 'Password should not contain sequential characters';
        }
        
        // Check for common patterns
        if ($this->hasCommonPatterns($password)) {
            $score -= 15;
            $errors[] = 'Password contains common patterns';
        }
        
        // Check against common passwords
        if ($this->isCommonPassword($password)) {
            $errors[] = 'Password is too common and easily guessable';
            $score -= 20;
        }
        
        // Calculate final score
        $score = max(0, min(100, $score));
        
        $strength = 'Very Weak';
        if ($score >= 80) $strength = 'Very Strong';
        elseif ($score >= 60) $strength = 'Strong';
        elseif ($score >= 40) $strength = 'Moderate';
        elseif ($score >= 20) $strength = 'Weak';
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'score' => $score,
            'strength' => $strength,
            'requirements' => $requirements
        ];
    }
    
    /**
     * Check for sequential characters
     */
    private function hasSequentialChars($password) {
        $sequences = [
            'abcdefghijklmnopqrstuvwxyz',
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            '0123456789',
            'qwertyuiop',
            'asdfghjkl',
            'zxcvbnm'
        ];
        
        foreach ($sequences as $sequence) {
            for ($i = 0; $i <= strlen($sequence) - 3; $i++) {
                $subseq = substr($sequence, $i, 3);
                if (strpos($password, $subseq) !== false) {
                    return true;
                }
                // Check reverse sequence
                if (strpos($password, strrev($subseq)) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check for common password patterns
     */
    private function hasCommonPatterns($password) {
        $patterns = [
            '/^[a-zA-Z]+[0-9]+$/',           // Letters followed by numbers
            '/^[0-9]+[a-zA-Z]+$/',           // Numbers followed by letters
            '/^[a-zA-Z]+[0-9]+[!@#$%^&*]+$/', // Letters + numbers + symbols
            '/(.+)\1+/',                      // Repeated substrings
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $password)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check against common passwords list
     */
    private function isCommonPassword($password) {
        $commonPasswords = [
            'password', '123456', '123456789', '12345678', '12345',
            'password1', 'admin', 'welcome', 'monkey', 'dragon',
            'qwerty', 'abc123', '111111', 'iloveyou', 'password123',
            'admin123', 'root', 'toor', 'pass', 'test',
            // Add more common passwords as needed
        ];
        
        $lowerPassword = strtolower($password);
        
        foreach ($commonPasswords as $common) {
            if ($lowerPassword === $common || 
                levenshtein($lowerPassword, $common) <= 2) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check password against known breaches (integration with HaveIBeenPwned API)
     */
    public function checkPasswordBreach($password) {
        try {
            $hash = strtoupper(sha1($password));
            $prefix = substr($hash, 0, 5);
            $suffix = substr($hash, 5);
            
            // Query HaveIBeenPwned API
            $url = "https://api.pwnedpasswords.com/range/$prefix";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'user_agent' => 'Borts-Books-Security-Check/1.0'
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                // API unavailable, don't block password
                return ['breached' => false, 'count' => 0, 'api_available' => false];
            }
            
            $lines = explode("\n", $response);
            foreach ($lines as $line) {
                list($hashSuffix, $count) = explode(':', trim($line));
                if (strtoupper($hashSuffix) === $suffix) {
                    return [
                        'breached' => true,
                        'count' => (int)$count,
                        'api_available' => true
                    ];
                }
            }
            
            return ['breached' => false, 'count' => 0, 'api_available' => true];
            
        } catch (Exception $e) {
            error_log("Password breach check failed: " . $e->getMessage());
            return ['breached' => false, 'count' => 0, 'api_available' => false];
        }
    }
    
    /**
     * Hash password securely using Argon2id
     */
    public function hashPassword($password) {
        // Use Argon2id if available, fallback to Argon2i, then bcrypt
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($password, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536, // 64MB
                'time_cost' => 4,       // 4 iterations
                'threads' => 3          // 3 threads
            ]);
        } elseif (defined('PASSWORD_ARGON2I')) {
            return password_hash($password, PASSWORD_ARGON2I, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 3
            ]);
        } else {
            return password_hash($password, PASSWORD_BCRYPT, [
                'cost' => 12
            ]);
        }
    }
    
    /**
     * Verify password
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if password needs rehashing
     */
    public function needsRehash($hash) {
        if (defined('PASSWORD_ARGON2ID')) {
            return password_needs_rehash($hash, PASSWORD_ARGON2ID);
        } elseif (defined('PASSWORD_ARGON2I')) {
            return password_needs_rehash($hash, PASSWORD_ARGON2I);
        } else {
            return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
        }
    }
    
    /**
     * Store password in history
     */
    public function storePasswordHistory($userId, $passwordHash) {
        try {
            // Insert new password hash
            $stmt = $this->pdo->prepare("
                INSERT INTO password_history (user_id, password_hash) 
                VALUES (?, ?)
            ");
            $stmt->execute([$userId, $passwordHash]);
            
            // Clean up old password history (keep only last N passwords)
            $stmt = $this->pdo->prepare("
                DELETE FROM password_history 
                WHERE user_id = ? 
                AND id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM password_history 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC 
                        LIMIT ?
                    ) AS recent
                )
            ");
            $stmt->execute([$userId, $userId, $this->historyLimit]);
            
        } catch (PDOException $e) {
            error_log("Failed to store password history: " . $e->getMessage());
        }
    }
    
    /**
     * Check if password was previously used
     */
    public function isPasswordReused($userId, $password) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT password_hash FROM password_history 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$userId, $this->historyLimit]);
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (password_verify($password, $row['password_hash'])) {
                    return true;
                }
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Failed to check password history: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Record failed login attempt
     */
    public function recordFailedLogin($identifier, $ipAddress = null, $userAgent = null) {
        try {
            $ipAddress = $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $userAgent = $userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
            
            $stmt = $this->pdo->prepare("
                INSERT INTO failed_login_attempts (identifier, ip_address, user_agent) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$identifier, $ipAddress, $userAgent]);
            
            // Clean up old attempts (older than 24 hours)
            $this->pdo->exec("
                DELETE FROM failed_login_attempts 
                WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            
        } catch (PDOException $e) {
            error_log("Failed to record failed login: " . $e->getMessage());
        }
    }
    
    /**
     * Check if account is locked due to too many failed attempts
     */
    public function isAccountLocked($identifier, $maxAttempts = 5, $lockoutTime = 900) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as attempt_count,
                       MAX(attempted_at) as last_attempt
                FROM failed_login_attempts 
                WHERE identifier = ? 
                AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$identifier, $lockoutTime]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['attempt_count'] >= $maxAttempts;
            
        } catch (PDOException $e) {
            error_log("Failed to check account lock status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear failed login attempts (after successful login)
     */
    public function clearFailedAttempts($identifier) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM failed_login_attempts 
                WHERE identifier = ?
            ");
            $stmt->execute([$identifier]);
            
        } catch (PDOException $e) {
            error_log("Failed to clear failed attempts: " . $e->getMessage());
        }
    }
    
    /**
     * Generate secure password
     */
    public function generateSecurePassword($length = 16) {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $password = '';
        
        // Ensure at least one character from each category
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];
        
        // Fill the rest randomly
        $allChars = $uppercase . $lowercase . $numbers . $symbols;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Shuffle the password
        return str_shuffle($password);
    }
    
    /**
     * Generate password strength meter HTML
     */
    public function getPasswordStrengthMeter() {
        return '
        <div id="password-strength-meter" style="margin-top: 10px; display: none;">
            <div class="strength-bar">
                <div id="strength-fill" style="height: 6px; transition: all 0.3s ease; border-radius: 3px;"></div>
            </div>
            <div id="strength-text" style="margin-top: 5px; font-size: 14px;"></div>
            <div id="strength-requirements" style="margin-top: 10px; font-size: 12px;">
                <div id="req-length">✗ At least 12 characters</div>
                <div id="req-uppercase">✗ One uppercase letter</div>
                <div id="req-lowercase">✗ One lowercase letter</div>
                <div id="req-numbers">✗ One number</div>
                <div id="req-special">✗ One special character</div>
            </div>
        </div>
        
        <script>
        function checkPasswordStrength(password) {
            fetch("/api/check-password-strength", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({password: password})
            })
            .then(response => response.json())
            .then(data => {
                updateStrengthMeter(data);
            });
        }
        
        function updateStrengthMeter(data) {
            const meter = document.getElementById("password-strength-meter");
            const fill = document.getElementById("strength-fill");
            const text = document.getElementById("strength-text");
            
            meter.style.display = "block";
            
            // Update strength bar
            fill.style.width = data.score + "%";
            
            // Color coding
            if (data.score >= 80) {
                fill.style.backgroundColor = "#4CAF50";
                text.style.color = "#4CAF50";
            } else if (data.score >= 60) {
                fill.style.backgroundColor = "#8BC34A";
                text.style.color = "#8BC34A";
            } else if (data.score >= 40) {
                fill.style.backgroundColor = "#FFC107";
                text.style.color = "#FFC107";
            } else if (data.score >= 20) {
                fill.style.backgroundColor = "#FF9800";
                text.style.color = "#FF9800";
            } else {
                fill.style.backgroundColor = "#F44336";
                text.style.color = "#F44336";
            }
            
            text.textContent = data.strength + " (" + data.score + "/100)";
            
            // Update requirements
            Object.keys(data.requirements).forEach(req => {
                const element = document.getElementById("req-" + req);
                if (element) {
                    element.innerHTML = data.requirements[req] ? "✓" : "✗";
                    element.style.color = data.requirements[req] ? "#4CAF50" : "#F44336";
                }
            });
        }
        </script>
        ';
    }
}

/**
 * Two-Factor Authentication System
 */
class TwoFactorAuth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Generate TOTP secret
     */
    public function generateSecret() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 32; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $secret;
    }
    
    /**
     * Generate QR code URL for TOTP setup
     */
    public function getQRCodeUrl($secret, $label, $issuer = "Bort's Books") {
        $url = 'otpauth://totp/' . urlencode($label) . 
               '?secret=' . $secret . 
               '&issuer=' . urlencode($issuer);
        
        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($url);
    }
    
    /**
     * Verify TOTP code
     */
    public function verifyTOTP($secret, $code, $timeWindow = 1) {
        $timeStep = 30; // 30-second time step
        $currentTime = floor(time() / $timeStep);
        
        for ($i = -$timeWindow; $i <= $timeWindow; $i++) {
            $calculatedCode = $this->calculateTOTP($secret, $currentTime + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Calculate TOTP code
     */
    private function calculateTOTP($secret, $timeCounter) {
        $key = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeCounter);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset + 0]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Base32 decode
     */
    private function base32Decode($secret) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper($secret);
        $bits = '';
        
        for ($i = 0; $i < strlen($secret); $i++) {
            $char = $secret[$i];
            $pos = strpos($chars, $char);
            if ($pos !== false) {
                $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
            }
        }
        
        $bytes = '';
        for ($i = 0; $i < strlen($bits); $i += 8) {
            $byte = substr($bits, $i, 8);
            if (strlen($byte) === 8) {
                $bytes .= chr(bindec($byte));
            }
        }
        
        return $bytes;
    }
    
    /**
     * Generate backup codes
     */
    public function generateBackupCodes($count = 10) {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }
    
    /**
     * Enable 2FA for user
     */
    public function enable2FA($userId, $secret, $backupCodes = null) {
        try {
            $backupCodes = $backupCodes ?? $this->generateBackupCodes();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO two_factor_auth (user_id, secret, backup_codes, enabled, enabled_at) 
                VALUES (?, ?, ?, TRUE, NOW())
                ON DUPLICATE KEY UPDATE 
                secret = VALUES(secret),
                backup_codes = VALUES(backup_codes),
                enabled = TRUE,
                enabled_at = NOW()
            ");
            
            $stmt->execute([
                $userId, 
                $secret, 
                json_encode($backupCodes)
            ]);
            
            return $backupCodes;
            
        } catch (PDOException $e) {
            error_log("Failed to enable 2FA: " . $e->getMessage());
            throw new Exception("Failed to enable two-factor authentication");
        }
    }
    
    /**
     * Verify backup code
     */
    public function verifyBackupCode($userId, $code) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT backup_codes FROM two_factor_auth 
                WHERE user_id = ? AND enabled = TRUE
            ");
            $stmt->execute([$userId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                return false;
            }
            
            $backupCodes = json_decode($result['backup_codes'], true);
            $codeIndex = array_search(strtoupper($code), $backupCodes);
            
            if ($codeIndex !== false) {
                // Remove used backup code
                unset($backupCodes[$codeIndex]);
                
                $stmt = $this->pdo->prepare("
                    UPDATE two_factor_auth 
                    SET backup_codes = ?, last_used = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([json_encode(array_values($backupCodes)), $userId]);
                
                return true;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Failed to verify backup code: " . $e->getMessage());
            return false;
        }
    }
} 