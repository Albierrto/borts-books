<?php
/**
 * Comprehensive Security Monitoring System
 * Implements intrusion detection, anomaly detection, and security event analysis
 */

class SecurityMonitor {
    private $logFile;
    private $alertThresholds;
    private $honeypots;
    private $pdo;
    
    public function __construct($pdo, $logFile = null) {
        $this->pdo = $pdo;
        $this->logFile = $logFile ?: __DIR__ . '/../logs/security_monitor.log';
        $this->initializeMonitoring();
        $this->setupAlertThresholds();
        $this->initializeHoneypots();
    }
    
    private function initializeMonitoring() {
        // Ensure log directory exists
        $logDir = dirname($this->logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0750, true);
        }
        
        // Create security events table
        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS security_events (
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
                )
            ");
            
            // Create threat intelligence table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS threat_intelligence (
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
                )
            ");
            
        } catch (PDOException $e) {
            error_log("Failed to initialize security monitoring tables: " . $e->getMessage());
        }
    }
    
    private function setupAlertThresholds() {
        $this->alertThresholds = [
            'failed_login' => ['count' => 5, 'window' => 300, 'severity' => 'medium'],
            'sql_injection' => ['count' => 1, 'window' => 60, 'severity' => 'high'],
            'xss_attempt' => ['count' => 3, 'window' => 600, 'severity' => 'medium'],
            'path_traversal' => ['count' => 2, 'window' => 300, 'severity' => 'high'],
            'brute_force' => ['count' => 10, 'window' => 600, 'severity' => 'high'],
            'suspicious_upload' => ['count' => 3, 'window' => 1800, 'severity' => 'medium'],
            'rate_limit_exceeded' => ['count' => 20, 'window' => 3600, 'severity' => 'low'],
            'honeypot_triggered' => ['count' => 1, 'window' => 60, 'severity' => 'critical']
        ];
    }
    
    private function initializeHoneypots() {
        $this->honeypots = [
            '/admin.php', '/wp-admin/', '/phpmyadmin/', '/administrator/',
            '/config.php', '/.env', '/backup.sql', '/database.sql',
            '/api/admin', '/api/debug', '/api/test', '/debug.php'
        ];
    }
    
    /**
     * Log security event
     */
    public function logSecurityEvent($eventType, $data = [], $severity = 'medium') {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $requestUri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        
        // Calculate threat score
        $threatScore = $this->calculateThreatScore($eventType, $data, $ipAddress);
        
        // Store in database
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO security_events 
                (event_type, severity, ip_address, user_agent, request_uri, event_data, threat_score, blocked)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $blocked = $this->shouldBlockImmediately($eventType, $threatScore);
            
            $stmt->execute([
                $eventType,
                $severity,
                $ipAddress,
                $userAgent,
                $requestUri,
                json_encode($data),
                $threatScore,
                $blocked
            ]);
            
            // Log to file
            $logEntry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'event_type' => $eventType,
                'severity' => $severity,
                'ip' => $ipAddress,
                'threat_score' => $threatScore,
                'data' => $data
            ];
            
            file_put_contents($this->logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
            
            // Check for immediate blocking
            if ($blocked) {
                $this->blockIP($ipAddress, $eventType);
            }
            
            // Check alert thresholds
            $this->checkAlertThresholds($eventType, $ipAddress);
            
            // Update threat intelligence
            $this->updateThreatIntelligence($ipAddress, $eventType, $threatScore);
            
        } catch (PDOException $e) {
            error_log("Failed to log security event: " . $e->getMessage());
        }
    }
    
    /**
     * Calculate threat score based on event type and context
     */
    private function calculateThreatScore($eventType, $data, $ipAddress) {
        $baseScores = [
            'sql_injection' => 80,
            'xss_attempt' => 60,
            'path_traversal' => 70,
            'honeypot_triggered' => 90,
            'failed_login' => 20,
            'brute_force' => 75,
            'suspicious_upload' => 50,
            'csrf_failure' => 40,
            'rate_limit_exceeded' => 15
        ];
        
        $score = $baseScores[$eventType] ?? 10;
        
        // Adjust based on IP reputation
        $ipReputation = $this->getIPReputation($ipAddress);
        $score += $ipReputation['threat_modifier'];
        
        // Adjust based on frequency
        $recentEvents = $this->getRecentEventCount($eventType, $ipAddress, 3600);
        $score += min($recentEvents * 5, 30);
        
        // Geographic risk adjustment
        $geoRisk = $this->getGeographicRisk($ipAddress);
        $score += $geoRisk;
        
        return min($score, 100);
    }
    
    /**
     * Get IP reputation information
     */
    private function getIPReputation($ipAddress) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT threat_type, confidence_score, blocked 
                FROM threat_intelligence 
                WHERE ip_address = ?
            ");
            $stmt->execute([$ipAddress]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'threat_type' => $result['threat_type'],
                    'confidence' => $result['confidence_score'],
                    'blocked' => $result['blocked'],
                    'threat_modifier' => $result['confidence_score'] / 5
                ];
            }
            
            return ['threat_modifier' => 0];
            
        } catch (PDOException $e) {
            return ['threat_modifier' => 0];
        }
    }
    
    /**
     * Get count of recent events for IP
     */
    private function getRecentEventCount($eventType, $ipAddress, $timeWindow) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM security_events 
                WHERE event_type = ? AND ip_address = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$eventType, $ipAddress, $timeWindow]);
            
            return $stmt->fetchColumn() ?: 0;
            
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Get geographic risk score
     */
    private function getGeographicRisk($ipAddress) {
        // This would integrate with a GeoIP service
        // For now, return basic assessment
        
        // Check if IP is from high-risk regions
        $highRiskCountries = ['CN', 'RU', 'KP', 'IR'];
        
        // Simple IP-based country detection (placeholder)
        // In production, use MaxMind GeoIP or similar
        return 0; // Implement proper geo-risk assessment
    }
    
    /**
     * Check if IP should be blocked immediately
     */
    private function shouldBlockImmediately($eventType, $threatScore) {
        $criticalEvents = ['sql_injection', 'honeypot_triggered', 'path_traversal'];
        
        return in_array($eventType, $criticalEvents) || $threatScore >= 85;
    }
    
    /**
     * Block IP address
     */
    private function blockIP($ipAddress, $reason) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO threat_intelligence 
                (ip_address, threat_type, confidence_score, source, blocked, notes)
                VALUES (?, ?, ?, ?, TRUE, ?)
                ON DUPLICATE KEY UPDATE 
                blocked = TRUE,
                confidence_score = GREATEST(confidence_score, VALUES(confidence_score)),
                last_seen = NOW(),
                notes = CONCAT(notes, '; Auto-blocked: ', VALUES(notes))
            ");
            
            $stmt->execute([
                $ipAddress,
                $reason,
                95, // High confidence for auto-block
                'security_monitor',
                "Auto-blocked due to $reason"
            ]);
            
            // Create .htaccess rule or use iptables (depending on setup)
            $this->createFirewallRule($ipAddress);
            
            // Send alert
            $this->sendSecurityAlert("IP $ipAddress blocked automatically", [
                'ip' => $ipAddress,
                'reason' => $reason,
                'action' => 'auto_blocked'
            ], 'critical');
            
        } catch (PDOException $e) {
            error_log("Failed to block IP $ipAddress: " . $e->getMessage());
        }
    }
    
    /**
     * Create firewall rule (placeholder - implement based on server setup)
     */
    private function createFirewallRule($ipAddress) {
        // Create .htaccess rule
        $htaccessFile = __DIR__ . '/../.htaccess.security';
        $rule = "deny from $ipAddress\n";
        file_put_contents($htaccessFile, $rule, FILE_APPEND | LOCK_EX);
        
        // In production, you might use:
        // exec("iptables -A INPUT -s $ipAddress -j DROP");
        // or integrate with Cloudflare API
    }
    
    /**
     * Check alert thresholds and send alerts
     */
    private function checkAlertThresholds($eventType, $ipAddress) {
        if (!isset($this->alertThresholds[$eventType])) {
            return;
        }
        
        $threshold = $this->alertThresholds[$eventType];
        $recentCount = $this->getRecentEventCount($eventType, $ipAddress, $threshold['window']);
        
        if ($recentCount >= $threshold['count']) {
            $this->sendSecurityAlert(
                "Alert threshold exceeded for $eventType",
                [
                    'event_type' => $eventType,
                    'ip_address' => $ipAddress,
                    'count' => $recentCount,
                    'threshold' => $threshold['count'],
                    'time_window' => $threshold['window']
                ],
                $threshold['severity']
            );
        }
    }
    
    /**
     * Update threat intelligence
     */
    private function updateThreatIntelligence($ipAddress, $eventType, $threatScore) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO threat_intelligence 
                (ip_address, threat_type, confidence_score, source)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                confidence_score = GREATEST(confidence_score, VALUES(confidence_score)),
                last_seen = NOW()
            ");
            
            $stmt->execute([
                $ipAddress,
                $eventType,
                $threatScore,
                'security_monitor'
            ]);
            
        } catch (PDOException $e) {
            error_log("Failed to update threat intelligence: " . $e->getMessage());
        }
    }
    
    /**
     * Send security alert
     */
    private function sendSecurityAlert($message, $data, $severity) {
        $alertData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $message,
            'severity' => $severity,
            'data' => $data,
            'server' => $_SERVER['SERVER_NAME'] ?? 'unknown'
        ];
        
        // Log alert
        $alertFile = dirname($this->logFile) . '/security_alerts.log';
        file_put_contents($alertFile, json_encode($alertData) . "\n", FILE_APPEND | LOCK_EX);
        
        // Send email alert for high/critical severity
        if (in_array($severity, ['high', 'critical'])) {
            $this->sendEmailAlert($alertData);
        }
        
        // Send to SIEM/monitoring system (placeholder)
        $this->sendToSIEM($alertData);
    }
    
    /**
     * Send email alert
     */
    private function sendEmailAlert($alertData) {
        try {
            require_once __DIR__ . '/secure-email.php';
            $emailSystem = new SecureEmailSystem();
            
            $subject = "Security Alert [{$alertData['severity']}] - {$alertData['message']}";
            $body = "
                <h2>Security Alert</h2>
                <p><strong>Severity:</strong> {$alertData['severity']}</p>
                <p><strong>Message:</strong> {$alertData['message']}</p>
                <p><strong>Timestamp:</strong> {$alertData['timestamp']}</p>
                <p><strong>Server:</strong> {$alertData['server']}</p>
                <p><strong>Details:</strong></p>
                <pre>" . json_encode($alertData['data'], JSON_PRETTY_PRINT) . "</pre>
            ";
            
            // Send to admin email (from config)
            $adminEmail = 'admin@bortsbooks.com'; // Should come from config
            $emailSystem->sendEmail($adminEmail, $subject, $body, ['template' => 'notification']);
            
        } catch (Exception $e) {
            error_log("Failed to send security alert email: " . $e->getMessage());
        }
    }
    
    /**
     * Send to SIEM/monitoring system
     */
    private function sendToSIEM($alertData) {
        // Placeholder for SIEM integration
        // Could send to Splunk, ELK Stack, Datadog, etc.
        
        // Example: Send to webhook
        /*
        $webhookUrl = 'https://your-siem-system.com/webhook';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $webhookUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($alertData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
        */
    }
    
    /**
     * Check for honeypot access
     */
    public function checkHoneypot() {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        
        foreach ($this->honeypots as $honeypot) {
            if (strpos($requestUri, $honeypot) !== false) {
                $this->logSecurityEvent('honeypot_triggered', [
                    'honeypot_path' => $honeypot,
                    'requested_uri' => $requestUri
                ], 'critical');
                
                // Immediate response
                http_response_code(404);
                exit('Not Found');
            }
        }
    }
    
    /**
     * Analyze recent security events
     */
    public function analyzeSecurityEvents($timeWindow = 3600) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    event_type,
                    ip_address,
                    COUNT(*) as event_count,
                    AVG(threat_score) as avg_threat_score,
                    MAX(threat_score) as max_threat_score,
                    severity
                FROM security_events 
                WHERE created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
                GROUP BY event_type, ip_address, severity
                HAVING event_count > 1
                ORDER BY max_threat_score DESC, event_count DESC
            ");
            
            $stmt->execute([$timeWindow]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $analysis = [
                'time_window' => $timeWindow,
                'total_unique_threats' => count($results),
                'high_risk_ips' => [],
                'trending_attacks' => [],
                'recommendations' => []
            ];
            
            foreach ($results as $result) {
                if ($result['max_threat_score'] >= 70) {
                    $analysis['high_risk_ips'][] = $result;
                }
                
                if ($result['event_count'] >= 5) {
                    $analysis['trending_attacks'][] = $result;
                }
            }
            
            // Generate recommendations
            $analysis['recommendations'] = $this->generateSecurityRecommendations($analysis);
            
            return $analysis;
            
        } catch (PDOException $e) {
            error_log("Failed to analyze security events: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate security recommendations
     */
    private function generateSecurityRecommendations($analysis) {
        $recommendations = [];
        
        if (count($analysis['high_risk_ips']) > 10) {
            $recommendations[] = "Consider implementing stricter rate limiting - multiple high-risk IPs detected";
        }
        
        if (count($analysis['trending_attacks']) > 5) {
            $recommendations[] = "Trending attacks detected - review and update security rules";
        }
        
        foreach ($analysis['trending_attacks'] as $attack) {
            if ($attack['event_type'] === 'sql_injection') {
                $recommendations[] = "SQL injection attempts trending - review input validation";
            } elseif ($attack['event_type'] === 'xss_attempt') {
                $recommendations[] = "XSS attempts increasing - strengthen output encoding";
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Get security dashboard data
     */
    public function getSecurityDashboard($timeWindow = 86400) {
        try {
            // Event summary
            $stmt = $this->pdo->prepare("
                SELECT 
                    event_type,
                    severity,
                    COUNT(*) as count,
                    AVG(threat_score) as avg_threat
                FROM security_events 
                WHERE created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
                GROUP BY event_type, severity
                ORDER BY count DESC
            ");
            $stmt->execute([$timeWindow]);
            $eventSummary = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Top threat IPs
            $stmt = $this->pdo->prepare("
                SELECT 
                    ip_address,
                    COUNT(*) as event_count,
                    MAX(threat_score) as max_threat,
                    GROUP_CONCAT(DISTINCT event_type) as event_types
                FROM security_events 
                WHERE created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
                GROUP BY ip_address
                ORDER BY max_threat DESC, event_count DESC
                LIMIT 20
            ");
            $stmt->execute([$timeWindow]);
            $topThreats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Blocked IPs
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as blocked_count 
                FROM threat_intelligence 
                WHERE blocked = TRUE
            ");
            $blockedCount = $stmt->fetchColumn();
            
            return [
                'time_window' => $timeWindow,
                'event_summary' => $eventSummary,
                'top_threats' => $topThreats,
                'blocked_ips' => $blockedCount,
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (PDOException $e) {
            error_log("Failed to generate security dashboard: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clean up old security events
     */
    public function cleanupOldEvents($retentionDays = 30) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM security_events 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$retentionDays]);
            
            $deletedCount = $stmt->rowCount();
            error_log("Cleaned up $deletedCount old security events");
            
        } catch (PDOException $e) {
            error_log("Failed to cleanup old security events: " . $e->getMessage());
        }
    }
}

/**
 * Intrusion Detection System
 */
class IntrusionDetectionSystem {
    private $monitor;
    private $patterns;
    
    public function __construct(SecurityMonitor $monitor) {
        $this->monitor = $monitor;
        $this->initializePatterns();
    }
    
    private function initializePatterns() {
        $this->patterns = [
            'sql_injection' => [
                '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC)\b)/i',
                '/(\bunion\b.*\bselect\b)/i',
                '/(\'|\")(\s)*(;|\||&)/i',
                '/(\b(or|and)\b\s+\d+\s*=\s*\d+)/i'
            ],
            'xss_attempt' => [
                '/<script[^>]*>.*?<\/script>/i',
                '/javascript:/i',
                '/on\w+\s*=/i',
                '/<iframe[^>]*>/i'
            ],
            'path_traversal' => [
                '/\.\.(\/|\\\\)/i',
                '/\.\.\%2f/i',
                '/\%2e\%2e/i',
                '/(\/|\\\\)(etc|var|usr|bin)(\/|\\\\)/i'
            ],
            'command_injection' => [
                '/[;&|`$(){}]/i',
                '/\b(cat|ls|pwd|id|whoami|uname)\b/i'
            ]
        ];
    }
    
    /**
     * Analyze HTTP request for threats
     */
    public function analyzeRequest($input = null) {
        $threats = [];
        
        // Get input data
        if ($input === null) {
            $input = array_merge($_GET, $_POST, $_COOKIE);
            $input['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $input['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '';
        }
        
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $subThreats = $this->analyzeRequest($value);
                $threats = array_merge($threats, $subThreats);
                continue;
            }
            
            $value = (string)$value;
            
            foreach ($this->patterns as $threatType => $patterns) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        $threats[] = [
                            'type' => $threatType,
                            'field' => $key,
                            'pattern' => $pattern,
                            'value' => substr($value, 0, 200) // Limit for logging
                        ];
                        
                        // Log the threat
                        $this->monitor->logSecurityEvent($threatType, [
                            'field' => $key,
                            'pattern_matched' => $pattern,
                            'suspicious_value' => substr($value, 0, 100)
                        ], 'high');
                        
                        break 2; // Move to next field after first match
                    }
                }
            }
        }
        
        return $threats;
    }
    
    /**
     * Check for suspicious user agents
     */
    public function checkUserAgent() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $suspiciousPatterns = [
            '/sqlmap/i',
            '/nikto/i',
            '/nmap/i',
            '/dirb/i',
            '/wget/i',
            '/curl/i',
            '/scanner/i',
            '/bot.*crawler/i'
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                $this->monitor->logSecurityEvent('suspicious_user_agent', [
                    'user_agent' => $userAgent,
                    'pattern' => $pattern
                ], 'medium');
                return true;
            }
        }
        
        return false;
    }
} 