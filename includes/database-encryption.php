<?php
/**
 * Database Encryption System
 * Provides field-level encryption for sensitive data
 */

class DatabaseEncryption {
    private $encryptionKey;
    private $algorithm = 'aes-256-gcm';
    
    public function __construct() {
        $this->initializeEncryption();
    }
    
    private function initializeEncryption() {
        // Get encryption key from environment or secure key management
        $this->encryptionKey = $this->getEncryptionKey();
    }
    
    /**
     * Get encryption key from secure storage
     */
    private function getEncryptionKey() {
        // Priority: Environment variable > Key file
        if (isset($_ENV['DB_ENCRYPTION_KEY'])) {
            return hex2bin($_ENV['DB_ENCRYPTION_KEY']);
        }
        
        $keyFile = __DIR__ . '/encryption.key';
        
        if (file_exists($keyFile)) {
            return hex2bin(file_get_contents($keyFile));
        }
        
        throw new Exception('Encryption key not found');
    }
    
    /**
     * Derive field-specific encryption key
     */
    private function deriveFieldKey($fieldName) {
        // Use a deterministic salt based on the field name
        $salt = hash_hmac('sha256', $fieldName, $this->encryptionKey, true);
        return hash_pbkdf2('sha256', $this->encryptionKey, $salt, 10000, 32, true);
    }
    
    /**
     * Encrypt sensitive data
     */
    public function encrypt($data, $fieldName) {
        if (empty($data)) {
            return $data;
        }
        
        try {
            $key = $this->deriveFieldKey($fieldName);
            $iv = random_bytes(12); // 96-bit IV for GCM
            $tag = '';
            
            $encrypted = openssl_encrypt(
                $data,
                $this->algorithm,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );
            
            if ($encrypted === false) {
                throw new Exception('Encryption failed');
            }
            
            // Return base64 encoded: version + iv + tag + encrypted data
            return base64_encode('v1:' . $iv . $tag . $encrypted);
            
        } catch (Exception $e) {
            error_log("Encryption error: " . $e->getMessage());
            throw new Exception('Data encryption failed');
        }
    }
    
    /**
     * Decrypt sensitive data
     */
    public function decrypt($encryptedData, $fieldName) {
        if (empty($encryptedData)) {
            return $encryptedData;
        }
        
        try {
            $decoded = base64_decode($encryptedData);
            
            // Check version prefix
            if (strpos($decoded, 'v1:') !== 0) {
                // Legacy unencrypted data or different format
                return $encryptedData;
            }
            
            $decoded = substr($decoded, 3); // Remove version prefix
            
            $key = $this->deriveFieldKey($fieldName);
            $iv = substr($decoded, 0, 12);
            $tag = substr($decoded, 12, 16);
            $encrypted = substr($decoded, 28);
            
            $decrypted = openssl_decrypt(
                $encrypted,
                $this->algorithm,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );
            
            if ($decrypted === false) {
                throw new Exception('Decryption failed');
            }
            
            return $decrypted;
            
        } catch (Exception $e) {
            error_log("Decryption error: " . $e->getMessage());
            throw new Exception('Data decryption failed');
        }
    }
    
    /**
     * Encrypt multiple fields in an array
     */
    public function encryptFields($data, $fieldsToEncrypt) {
        $result = $data;
        
        foreach ($fieldsToEncrypt as $field) {
            if (isset($result[$field])) {
                $result[$field] = $this->encrypt($result[$field], $field);
            }
        }
        
        return $result;
    }
    
    /**
     * Decrypt multiple fields in an array
     */
    public function decryptFields($data, $fieldsToDecrypt) {
        $result = $data;
        
        foreach ($fieldsToDecrypt as $field) {
            if (isset($result[$field])) {
                $result[$field] = $this->decrypt($result[$field], $field);
            }
        }
        
        return $result;
    }
    
    /**
     * Create searchable hash for encrypted fields
     */
    public function createSearchHash($data, $fieldName) {
        // Create HMAC for searchable but still secure comparison
        return hash_hmac('sha256', strtolower(trim($data)), $this->deriveFieldKey($fieldName . '_search'));
    }
    
    /**
     * Rotate encryption keys (for key rotation strategy)
     */
    public function rotateKeys() {
        // This would be implemented for production key rotation
        // 1. Generate new key
        // 2. Re-encrypt all data with new key
        // 3. Update key storage
        throw new Exception('Key rotation not implemented yet');
    }
}

/**
 * Secure Configuration Management
 */
class SecureConfig {
    private static $config = null;
    private static $encryptedFields = [
        'database_password',
        'stripe_secret_key',
        'email_password',
        'api_keys'
    ];
    
    public static function load($configFile = null) {
        if (self::$config !== null) {
            return self::$config;
        }
        
        $configFile = $configFile ?: __DIR__ . '/../config/secure.conf';
        
        if (!file_exists($configFile)) {
            throw new Exception('Configuration file not found');
        }
        
        $rawConfig = parse_ini_file($configFile, true);
        
        // Decrypt sensitive fields
        $encryption = new DatabaseEncryption();
        
        foreach (self::$encryptedFields as $field) {
            if (isset($rawConfig['security'][$field])) {
                try {
                    $rawConfig['security'][$field] = $encryption->decrypt(
                        $rawConfig['security'][$field], 
                        'config_' . $field
                    );
                } catch (Exception $e) {
                    // Field might not be encrypted yet
                    error_log("Config decryption warning for $field: " . $e->getMessage());
                }
            }
        }
        
        self::$config = $rawConfig;
        return self::$config;
    }
    
    public static function get($section, $key, $default = null) {
        $config = self::load();
        return $config[$section][$key] ?? $default;
    }
    
    public static function encryptConfigFile($inputFile, $outputFile) {
        $config = parse_ini_file($inputFile, true);
        $encryption = new DatabaseEncryption();
        
        // Encrypt sensitive fields
        foreach (self::$encryptedFields as $field) {
            if (isset($config['security'][$field])) {
                $config['security'][$field] = $encryption->encrypt(
                    $config['security'][$field],
                    'config_' . $field
                );
            }
        }
        
        // Write encrypted config
        $content = ";; Encrypted configuration file\n";
        foreach ($config as $section => $values) {
            $content .= "[$section]\n";
            foreach ($values as $key => $value) {
                $content .= "$key = \"$value\"\n";
            }
            $content .= "\n";
        }
        
        file_put_contents($outputFile, $content);
        chmod($outputFile, 0600);
    }
}

/**
 * Database Activity Monitor
 */
class DatabaseActivityMonitor {
    private $logFile;
    private $sensitiveOperations = ['INSERT', 'UPDATE', 'DELETE'];
    private $sensitiveTables = [
        'customer_requests',
        'sell_submissions', 
        'users',
        'payments',
        'personal_data'
    ];
    
    public function __construct($logFile = null) {
        $this->logFile = $logFile ?: __DIR__ . '/../logs/db_activity.log';
        $this->ensureLogDirectory();
    }
    
    private function ensureLogDirectory() {
        $logDir = dirname($this->logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0750, true);
        }
    }
    
    /**
     * Log database activity
     */
    public function logActivity($operation, $table, $data = [], $userId = null) {
        if (!in_array(strtoupper($operation), $this->sensitiveOperations) && 
            !in_array($table, $this->sensitiveTables)) {
            return; // Only log sensitive operations
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'operation' => $operation,
            'table' => $table,
            'user_id' => $userId ?? $_SESSION['admin_id'] ?? 'anonymous',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'data_hash' => hash('sha256', json_encode($data)),
            'affected_rows' => is_array($data) ? count($data) : 1
        ];
        
        $logLine = json_encode($logEntry) . "\n";
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Analyze logs for suspicious activity
     */
    public function analyzeActivity($timeWindow = 3600) {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $logs = file($this->logFile, FILE_IGNORE_NEW_LINES);
        $recentLogs = [];
        $now = time();
        
        foreach ($logs as $log) {
            $entry = json_decode($log, true);
            if ($entry && (strtotime($entry['timestamp']) > ($now - $timeWindow))) {
                $recentLogs[] = $entry;
            }
        }
        
        return $this->detectAnomalies($recentLogs);
    }
    
    private function detectAnomalies($logs) {
        $anomalies = [];
        $ipCounts = [];
        $operationCounts = [];
        
        foreach ($logs as $log) {
            $ip = $log['ip_address'];
            $operation = $log['operation'];
            
            $ipCounts[$ip] = ($ipCounts[$ip] ?? 0) + 1;
            $operationCounts[$operation] = ($operationCounts[$operation] ?? 0) + 1;
        }
        
        // Detect suspicious patterns
        foreach ($ipCounts as $ip => $count) {
            if ($count > 100) { // More than 100 operations per hour
                $anomalies[] = [
                    'type' => 'high_frequency_ip',
                    'ip' => $ip,
                    'count' => $count,
                    'severity' => 'high'
                ];
            }
        }
        
        foreach ($operationCounts as $operation => $count) {
            if ($operation === 'DELETE' && $count > 10) {
                $anomalies[] = [
                    'type' => 'excessive_deletes',
                    'operation' => $operation,
                    'count' => $count,
                    'severity' => 'medium'
                ];
            }
        }
        
        return $anomalies;
    }
}

/**
 * Row-Level Security Helper
 */
class RowLevelSecurity {
    private $pdo;
    private $currentUserId;
    
    public function __construct($pdo, $userId = null) {
        $this->pdo = $pdo;
        $this->currentUserId = $userId ?? $_SESSION['admin_id'] ?? null;
    }
    
    /**
     * Apply row-level security to queries
     */
    public function secureQuery($baseQuery, $table, $userColumn = 'user_id') {
        if (!$this->currentUserId) {
            throw new Exception('No authenticated user for row-level security');
        }
        
        // Add WHERE clause for user isolation
        if (stripos($baseQuery, 'WHERE') !== false) {
            $secureQuery = $baseQuery . " AND $table.$userColumn = :current_user_id";
        } else {
            $secureQuery = $baseQuery . " WHERE $table.$userColumn = :current_user_id";
        }
        
        return $secureQuery;
    }
    
    /**
     * Execute secure query with user context
     */
    public function executeSecure($query, $params = [], $userColumn = 'user_id') {
        $params['current_user_id'] = $this->currentUserId;
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt;
    }
} 