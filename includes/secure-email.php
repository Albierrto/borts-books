<?php
/**
 * Secure Email System
 * Prevents email injection, implements rate limiting, and provides secure email functionality
 */

class SecureEmailSystem {
    private $fromEmail;
    private $fromName;
    private $dkimPrivateKey;
    private $dkimSelector;
    private $dkimDomain;
    private $rateLimiter;
    
    public function __construct($config = []) {
        $this->fromEmail = $config['from_email'] ?? 'noreply@bortsbooks.com';
        $this->fromName = $config['from_name'] ?? "Bort's Books";
        $this->dkimPrivateKey = $config['dkim_private_key'] ?? null;
        $this->dkimSelector = $config['dkim_selector'] ?? 'default';
        $this->dkimDomain = $config['dkim_domain'] ?? 'bortsbooks.com';
        $this->rateLimiter = new EmailRateLimit();
    }
    
    /**
     * Send secure email with comprehensive validation
     */
    public function sendEmail($to, $subject, $message, $options = []) {
        try {
            // Validate and sanitize inputs
            $validatedTo = $this->validateEmail($to);
            $sanitizedSubject = $this->sanitizeSubject($subject);
            $sanitizedMessage = $this->sanitizeMessage($message);
            
            // Check rate limiting
            if (!$this->rateLimiter->checkLimit($validatedTo)) {
                throw new Exception('Rate limit exceeded for recipient');
            }
            
            // Check sender rate limiting
            $senderKey = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            if (!$this->rateLimiter->checkLimit('sender_' . $senderKey, 50, 3600)) {
                throw new Exception('Rate limit exceeded for sender');
            }
            
            // Prepare email data
            $emailData = [
                'to' => $validatedTo,
                'subject' => $sanitizedSubject,
                'message' => $sanitizedMessage,
                'from_email' => $this->fromEmail,
                'from_name' => $this->fromName,
                'reply_to' => $options['reply_to'] ?? null,
                'cc' => $options['cc'] ?? null,
                'bcc' => $options['bcc'] ?? null,
                'template' => $options['template'] ?? 'default',
                'attachments' => $options['attachments'] ?? []
            ];
            
            // Validate additional recipients
            if ($emailData['cc']) {
                $emailData['cc'] = array_map([$this, 'validateEmail'], (array)$emailData['cc']);
            }
            if ($emailData['bcc']) {
                $emailData['bcc'] = array_map([$this, 'validateEmail'], (array)$emailData['bcc']);
            }
            if ($emailData['reply_to']) {
                $emailData['reply_to'] = $this->validateEmail($emailData['reply_to']);
            }
            
            // Create secure headers
            $headers = $this->createSecureHeaders($emailData);
            
            // Apply email template
            $finalMessage = $this->applyTemplate($emailData['message'], $emailData['template']);
            
            // Send email
            $result = $this->sendSecureEmail($emailData, $headers, $finalMessage);
            
            // Log email activity
            $this->logEmailActivity($emailData, $result);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Secure email error: " . $e->getMessage());
            $this->logEmailActivity(['to' => $to ?? 'unknown'], ['success' => false, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Validate email address against injection attacks
     */
    private function validateEmail($email) {
        if (empty($email)) {
            throw new Exception('Email address is required');
        }
        
        // Remove any whitespace
        $email = trim($email);
        
        // Check for injection patterns
        $injectionPatterns = [
            '/[\r\n]/',          // Line breaks
            '/\bcc:/i',          // CC injection
            '/\bbcc:/i',         // BCC injection
            '/\bto:/i',          // TO injection
            '/\bsubject:/i',     // Subject injection
            '/\bcontent-type:/i', // Content-Type injection
            '/\bmime-version:/i', // MIME-Version injection
            '/\bx-/i',           // X-headers injection
        ];
        
        foreach ($injectionPatterns as $pattern) {
            if (preg_match($pattern, $email)) {
                throw new Exception('Invalid email address: potential injection detected');
            }
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address format');
        }
        
        // Additional security checks
        if (strlen($email) > 254) {
            throw new Exception('Email address too long');
        }
        
        // Check against disposable email domains
        if ($this->isDisposableEmail($email)) {
            throw new Exception('Disposable email addresses not allowed');
        }
        
        return $email;
    }
    
    /**
     * Sanitize email subject
     */
    private function sanitizeSubject($subject) {
        if (empty($subject)) {
            throw new Exception('Email subject is required');
        }
        
        // Remove line breaks and control characters
        $subject = preg_replace('/[\r\n\t]/', ' ', $subject);
        
        // Remove potential injection attempts
        $subject = preg_replace('/\b(content-type|mime-version|x-)/i', '', $subject);
        
        // Limit length
        if (strlen($subject) > 998) {
            $subject = substr($subject, 0, 995) . '...';
        }
        
        return trim($subject);
    }
    
    /**
     * Sanitize email message content
     */
    private function sanitizeMessage($message) {
        if (empty($message)) {
            throw new Exception('Email message is required');
        }
        
        // Remove potentially dangerous content
        $message = preg_replace('/\b(content-type|mime-version|x-)/i', '', $message);
        
        // Ensure proper line endings
        $message = str_replace(["\r\n", "\r"], "\n", $message);
        $message = str_replace("\n", "\r\n", $message);
        
        return $message;
    }
    
    /**
     * Create secure email headers
     */
    private function createSecureHeaders($emailData) {
        $headers = [];
        
        // Basic headers
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: quoted-printable';
        
        // From header with proper encoding
        $fromHeader = $this->encodeHeaderValue($this->fromName) . ' <' . $this->fromEmail . '>';
        $headers[] = 'From: ' . $fromHeader;
        
        // Reply-To if specified
        if ($emailData['reply_to']) {
            $headers[] = 'Reply-To: ' . $emailData['reply_to'];
        }
        
        // CC and BCC
        if ($emailData['cc']) {
            $headers[] = 'Cc: ' . implode(', ', $emailData['cc']);
        }
        if ($emailData['bcc']) {
            $headers[] = 'Bcc: ' . implode(', ', $emailData['bcc']);
        }
        
        // Security headers
        $headers[] = 'X-Mailer: Borts-Books-Secure-Mailer';
        $headers[] = 'X-Priority: 3';
        $headers[] = 'Message-ID: <' . uniqid() . '@' . $this->dkimDomain . '>';
        $headers[] = 'Date: ' . date('r');
        
        // Anti-spam headers
        $headers[] = 'X-Anti-Spam: Generated by Borts Books Security System';
        $headers[] = 'Precedence: bulk';
        
        // DKIM signature if configured
        if ($this->dkimPrivateKey) {
            $dkimHeader = $this->generateDKIMSignature($emailData, $headers);
            if ($dkimHeader) {
                array_unshift($headers, $dkimHeader);
            }
        }
        
        return implode("\r\n", $headers);
    }
    
    /**
     * Encode header values to prevent injection
     */
    private function encodeHeaderValue($value) {
        return mb_encode_mimeheader($value, 'UTF-8', 'Q', "\r\n", 8);
    }
    
    /**
     * Apply email template
     */
    private function applyTemplate($message, $template) {
        $templates = [
            'default' => $this->getDefaultTemplate(),
            'newsletter' => $this->getNewsletterTemplate(),
            'transactional' => $this->getTransactionalTemplate(),
            'notification' => $this->getNotificationTemplate()
        ];
        
        $templateHtml = $templates[$template] ?? $templates['default'];
        
        return str_replace(
            ['{{MESSAGE}}', '{{COMPANY_NAME}}', '{{YEAR}}'],
            [$message, "Bort's Books", date('Y')],
            $templateHtml
        );
    }
    
    /**
     * Generate DKIM signature
     */
    private function generateDKIMSignature($emailData, $headers) {
        if (!$this->dkimPrivateKey || !file_exists($this->dkimPrivateKey)) {
            return null;
        }
        
        try {
            // This is a simplified DKIM implementation
            // In production, use a proper DKIM library
            $canonicalizedHeaders = $this->canonicalizeHeaders($headers);
            $canonicalizedBody = $this->canonicalizeBody($emailData['message']);
            
            $bodyHash = base64_encode(hash('sha256', $canonicalizedBody, true));
            
            $dkimHeader = "DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed; " .
                         "d={$this->dkimDomain}; s={$this->dkimSelector}; " .
                         "h=from:to:subject:date; " .
                         "bh=$bodyHash; " .
                         "b=";
            
            // Generate signature (simplified)
            $privateKey = openssl_pkey_get_private(file_get_contents($this->dkimPrivateKey));
            $signature = '';
            openssl_sign($canonicalizedHeaders . $dkimHeader, $signature, $privateKey, OPENSSL_ALGO_SHA256);
            
            return $dkimHeader . base64_encode($signature);
            
        } catch (Exception $e) {
            error_log("DKIM signature generation failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Canonicalize headers for DKIM
     */
    private function canonicalizeHeaders($headers) {
        $canonical = [];
        foreach ($headers as $header) {
            $parts = explode(':', $header, 2);
            if (count($parts) === 2) {
                $canonical[] = strtolower(trim($parts[0])) . ':' . trim($parts[1]);
            }
        }
        return implode("\r\n", $canonical);
    }
    
    /**
     * Canonicalize body for DKIM
     */
    private function canonicalizeBody($body) {
        // Simplified canonicalization
        return rtrim(str_replace("\n", "\r\n", $body));
    }
    
    /**
     * Send the actual email
     */
    private function sendSecureEmail($emailData, $headers, $message) {
        // Use PHP's mail function with proper parameters
        $success = mail(
            $emailData['to'],
            $emailData['subject'],
            quoted_printable_encode($message),
            $headers,
            '-f' . $this->fromEmail // Set return path
        );
        
        return [
            'success' => $success,
            'message_id' => uniqid() . '@' . $this->dkimDomain,
            'timestamp' => time()
        ];
    }
    
    /**
     * Check if email is from disposable domain
     */
    private function isDisposableEmail($email) {
        $disposableDomains = [
            '10minutemail.com', 'tempmail.org', 'guerrillamail.com',
            'mailinator.com', 'throwaway.email', 'temp-mail.org'
        ];
        
        $domain = strtolower(substr(strrchr($email, '@'), 1));
        return in_array($domain, $disposableDomains);
    }
    
    /**
     * Log email activity
     */
    private function logEmailActivity($emailData, $result) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'to' => $emailData['to'] ?? 'unknown',
            'subject' => $emailData['subject'] ?? 'unknown',
            'success' => $result['success'] ?? false,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'error' => $result['error'] ?? null
        ];
        
        $logFile = __DIR__ . '/../logs/email_activity.log';
        $logDir = dirname($logFile);
        
        if (!file_exists($logDir)) {
            mkdir($logDir, 0750, true);
        }
        
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get default email template
     */
    private function getDefaultTemplate() {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{{COMPANY_NAME}}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; }
                .logo { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
                .unsubscribe { font-size: 12px; color: #999; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="logo">{{COMPANY_NAME}}</div>
                    <p>Your trusted book dealer since 2023</p>
                </div>
                <div class="content">
                    {{MESSAGE}}
                </div>
                <div class="footer">
                    <p><strong>{{COMPANY_NAME}}</strong></p>
                    <p>© {{YEAR}} All rights reserved</p>
                    <div class="unsubscribe">
                        <a href="#" style="color: #999; text-decoration: none;">Unsubscribe</a> | 
                        <a href="#" style="color: #999; text-decoration: none;">Update Preferences</a>
                    </div>
                </div>
            </div>
        </body>
        </html>';
    }
    
    private function getNewsletterTemplate() {
        // Newsletter-specific template
        return $this->getDefaultTemplate();
    }
    
    private function getTransactionalTemplate() {
        // Transactional email template
        return $this->getDefaultTemplate();
    }
    
    private function getNotificationTemplate() {
        // Notification template
        return $this->getDefaultTemplate();
    }
}

/**
 * Email Rate Limiting
 */
class EmailRateLimit {
    private $cacheDir;
    
    public function __construct() {
        $this->cacheDir = sys_get_temp_dir() . '/email_rate_limits/';
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Check rate limit for email sending
     */
    public function checkLimit($identifier, $maxEmails = 10, $timeWindow = 3600) {
        $cacheFile = $this->cacheDir . md5($identifier) . '.json';
        
        $attempts = [];
        if (file_exists($cacheFile)) {
            $attempts = json_decode(file_get_contents($cacheFile), true) ?: [];
        }
        
        // Clean old attempts
        $now = time();
        $attempts = array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        if (count($attempts) >= $maxEmails) {
            return false;
        }
        
        $attempts[] = $now;
        file_put_contents($cacheFile, json_encode($attempts));
        
        return true;
    }
    
    /**
     * Get remaining attempts
     */
    public function getRemainingAttempts($identifier, $maxEmails = 10, $timeWindow = 3600) {
        $cacheFile = $this->cacheDir . md5($identifier) . '.json';
        
        if (!file_exists($cacheFile)) {
            return $maxEmails;
        }
        
        $attempts = json_decode(file_get_contents($cacheFile), true) ?: [];
        
        // Clean old attempts
        $now = time();
        $validAttempts = array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        return max(0, $maxEmails - count($validAttempts));
    }
}

/**
 * Email Validation and Verification
 */
class EmailValidator {
    /**
     * Comprehensive email validation
     */
    public static function validateEmail($email, $checkMX = true) {
        $result = [
            'valid' => false,
            'reason' => '',
            'suggestion' => null
        ];
        
        // Basic validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $result['reason'] = 'Invalid email format';
            return $result;
        }
        
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            $result['reason'] = 'Invalid email structure';
            return $result;
        }
        
        $domain = $parts[1];
        
        // Check domain length
        if (strlen($domain) > 253) {
            $result['reason'] = 'Domain too long';
            return $result;
        }
        
        // Check for MX record if requested
        if ($checkMX && !checkdnsrr($domain, 'MX')) {
            $result['reason'] = 'No mail exchange record found';
            return $result;
        }
        
        $result['valid'] = true;
        return $result;
    }
    
    /**
     * Check if email appears to be legitimate
     */
    public static function isLegitimateEmail($email) {
        $suspiciousPatterns = [
            '/\+.*\+/',          // Multiple plus signs
            '/\.{2,}/',          // Multiple consecutive dots
            '/^[0-9]+@/',        // Starts with numbers only
            '/test.*@/',         // Test emails
            '/fake.*@/',         // Fake emails
            '/noreply@/'         // No-reply emails for forms
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $email)) {
                return false;
            }
        }
        
        return true;
    }
} 