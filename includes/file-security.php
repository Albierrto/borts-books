<?php
/**
 * File Security and Validation System
 * Provides comprehensive protection against malicious file uploads
 */

class FileSecurityValidator {
    
    const ALLOWED_IMAGE_TYPES = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'image/webp' => ['webp']
    ];
    
    const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    const QUARANTINE_DIR = '../quarantine/';
    
    public static function validateImageUpload($file) {
        $errors = [];
        
        // 1. Basic file checks
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'Invalid file upload';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // 2. File size check
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $errors[] = 'File too large. Maximum size is ' . (self::MAX_FILE_SIZE / 1024 / 1024) . 'MB';
        }
        
        // 3. File extension validation
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $validExtensions = [];
        foreach (self::ALLOWED_IMAGE_TYPES as $extensions) {
            $validExtensions = array_merge($validExtensions, $extensions);
        }
        
        if (!in_array($extension, $validExtensions)) {
            $errors[] = 'Invalid file extension. Allowed: ' . implode(', ', $validExtensions);
        }
        
        // 4. MIME type validation (server-side)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actualMimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!array_key_exists($actualMimeType, self::ALLOWED_IMAGE_TYPES)) {
            $errors[] = 'Invalid file type detected: ' . $actualMimeType;
        }
        
        // 5. Image content verification
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $errors[] = 'File is not a valid image';
        }
        
        // 6. Check for embedded executable content
        if (self::hasEmbeddedExecutable($file['tmp_name'])) {
            $errors[] = 'File contains potentially dangerous content';
        }
        
        // 7. Virus scan (if ClamAV is available)
        $virusScanResult = self::scanForViruses($file['tmp_name'], $file['name']);
        if (!$virusScanResult['clean']) {
            $errors[] = 'File failed security scan: ' . $virusScanResult['threat'];
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'mime_type' => $actualMimeType,
            'image_info' => $imageInfo
        ];
    }
    
    private static function hasEmbeddedExecutable($filePath) {
        // Check for common executable signatures
        $content = file_get_contents($filePath, false, null, 0, 1024);
        
        $dangerousSignatures = [
            'MZ',           // Windows PE
            '\x7fELF',      // Linux ELF
            '#!/bin/',      // Shell scripts
            '<?php',        // PHP code
            '<script',      // JavaScript
            'PK\x03\x04',   // ZIP files (could contain executables)
        ];
        
        foreach ($dangerousSignatures as $signature) {
            if (strpos($content, $signature) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private static function scanForViruses($filePath, $fileName) {
        // Option 1: ClamAV command line (if installed)
        if (function_exists('exec') && self::isClamAVAvailable()) {
            $output = [];
            $returnCode = 0;
            exec("clamscan --no-summary " . escapeshellarg($filePath) . " 2>&1", $output, $returnCode);
            
            if ($returnCode === 0) {
                return ['clean' => true, 'threat' => null];
            } else {
                return ['clean' => false, 'threat' => implode(' ', $output)];
            }
        }
        
        // Option 2: VirusTotal API (for production environments)
        // Uncomment and configure if you have VirusTotal API key
        /*
        $vtResult = self::scanWithVirusTotal($filePath);
        if ($vtResult !== null) {
            return $vtResult;
        }
        */
        
        // Option 3: Basic pattern matching (fallback)
        return self::basicVirusScan($filePath);
    }
    
    private static function isClamAVAvailable() {
        $output = [];
        $returnCode = 0;
        exec("which clamscan 2>/dev/null", $output, $returnCode);
        return $returnCode === 0;
    }
    
    private static function basicVirusScan($filePath) {
        $content = file_get_contents($filePath, false, null, 0, 8192);
        
        // Check for common malware patterns
        $malwarePatterns = [
            'eval\s*\(',           // PHP eval
            'base64_decode\s*\(',  // Encoded payloads
            'shell_exec\s*\(',     // Shell execution
            'system\s*\(',         // System calls
            'passthru\s*\(',       // Command execution
            'exec\s*\(',           // Execute commands
            'file_get_contents.*http', // Remote file inclusion
        ];
        
        foreach ($malwarePatterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', $content)) {
                return ['clean' => false, 'threat' => 'Suspicious code pattern detected'];
            }
        }
        
        return ['clean' => true, 'threat' => null];
    }
    
    public static function quarantineFile($filePath, $reason) {
        if (!file_exists(self::QUARANTINE_DIR)) {
            mkdir(self::QUARANTINE_DIR, 0700, true);
        }
        
        $quarantinePath = self::QUARANTINE_DIR . uniqid('quarantine_') . '_' . basename($filePath);
        move_uploaded_file($filePath, $quarantinePath);
        
        // Log the quarantine action
        error_log("File quarantined: $quarantinePath - Reason: $reason");
        
        return $quarantinePath;
    }
    
    public static function sanitizeFileName($fileName) {
        // Remove path traversal attempts
        $fileName = basename($fileName);
        
        // Remove special characters and keep only alphanumeric, dash, underscore, dot
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
        
        // Prevent multiple dots (could be used for extension spoofing)
        $fileName = preg_replace('/\.{2,}/', '.', $fileName);
        
        // Ensure it doesn't start with a dot
        $fileName = ltrim($fileName, '.');
        
        // Limit length
        if (strlen($fileName) > 255) {
            $fileName = substr($fileName, 0, 255);
        }
        
        return $fileName;
    }
    
    public static function generateSecureFileName($originalName) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Create secure base name
        $secureBaseName = preg_replace('/[^a-zA-Z0-9]/', '', $baseName);
        $secureBaseName = substr($secureBaseName, 0, 20); // Limit length
        
        // Generate unique identifier
        $uniqueId = uniqid('img_', true);
        
        return $uniqueId . '_' . $secureBaseName . '.' . $extension;
    }
}

/**
 * SQL Injection Protection Helper
 */
class SQLSecurityHelper {
    
    public static function validateAndSanitizeInput($input, $type = 'string', $maxLength = null) {
        switch ($type) {
            case 'int':
                return filter_var($input, FILTER_VALIDATE_INT);
                
            case 'float':
                return filter_var($input, FILTER_VALIDATE_FLOAT);
                
            case 'email':
                return filter_var(trim($input), FILTER_VALIDATE_EMAIL);
                
            case 'string':
            default:
                $sanitized = trim(strip_tags($input));
                if ($maxLength && strlen($sanitized) > $maxLength) {
                    $sanitized = substr($sanitized, 0, $maxLength);
                }
                return $sanitized;
        }
    }
    
    public static function validateCSRFToken($token, $sessionToken) {
        return hash_equals($sessionToken, $token);
    }
    
    public static function generateCSRFToken() {
        return bin2hex(random_bytes(32));
    }
}
?> 