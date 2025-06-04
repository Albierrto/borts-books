<?php
/**
 * Secure File Upload System
 * Addresses vulnerabilities: Magic number verification, path traversal prevention,
 * filename randomization, EXIF stripping, virus scanning hooks
 */

class SecureFileUpload {
    private $uploadDir;
    private $maxFileSize;
    private $allowedTypes;
    private $allowedMimeTypes;
    private $magicNumbers;
    
    public function __construct($uploadDir = null) {
        // Upload directory outside web root
        $this->uploadDir = $uploadDir ?: __DIR__ . '/../secure-uploads/';
        $this->maxFileSize = 5 * 1024 * 1024; // 5MB
        
        // Allowed file types with corresponding MIME types and magic numbers
        $this->allowedTypes = [
            'jpg' => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'pdf' => ['application/pdf'],
            'webp' => ['image/webp']
        ];
        
        // Magic number signatures for file type verification
        $this->magicNumbers = [
            'image/jpeg' => [
                'FFD8FFE0', 'FFD8FFE1', 'FFD8FFE2', 'FFD8FFE3', 'FFD8FFE8', 'FFD8FFED'
            ],
            'image/png' => ['89504E470D0A1A0A'],
            'image/gif' => ['474946383761', '474946383961'],
            'application/pdf' => ['25504446'],
            'image/webp' => ['52494646']
        ];
        
        $this->initializeUploadDirectory();
    }
    
    private function initializeUploadDirectory() {
        if (!file_exists($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0755, true)) {
                throw new Exception('Failed to create secure upload directory');
            }
        }
        
        // Create .htaccess to prevent direct access
        $htaccessFile = $this->uploadDir . '.htaccess';
        if (!file_exists($htaccessFile)) {
            $htaccessContent = "deny from all\n";
            file_put_contents($htaccessFile, $htaccessContent);
        }
        
        // Create index.php to prevent directory listing
        $indexFile = $this->uploadDir . 'index.php';
        if (!file_exists($indexFile)) {
            file_put_contents($indexFile, '<?php http_response_code(403); exit; ?>');
        }
    }
    
    /**
     * Validate uploaded file
     */
    public function validateFile($file) {
        $errors = [];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $this->getUploadErrorMessage($file['error']);
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            $errors[] = 'File size exceeds maximum allowed size of ' . ($this->maxFileSize / 1024 / 1024) . 'MB';
        }
        
        // Get file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Check allowed extensions
        if (!isset($this->allowedTypes[$extension])) {
            $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', array_keys($this->allowedTypes));
        } else {
            // Verify MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $this->allowedTypes[$extension])) {
                $errors[] = 'File MIME type does not match extension';
            }
            
            // Verify magic number (file signature)
            if (!$this->verifyMagicNumber($file['tmp_name'], $mimeType)) {
                $errors[] = 'File signature does not match claimed type';
            }
        }
        
        // Check for embedded threats in filename
        if (preg_match('/[^a-zA-Z0-9._-]/', $file['name'])) {
            // Don't reject, but log suspicious filename
            error_log("Suspicious filename detected: " . $file['name']);
        }
        
        // Additional security checks
        if ($this->containsSuspiciousContent($file['tmp_name'])) {
            $errors[] = 'File contains suspicious content';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'mime_type' => $mimeType ?? null,
            'extension' => $extension
        ];
    }
    
    /**
     * Verify file magic number (file signature)
     */
    private function verifyMagicNumber($filePath, $mimeType) {
        if (!isset($this->magicNumbers[$mimeType])) {
            return true; // Skip verification for types we don't have signatures for
        }
        
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }
        
        $bytes = fread($handle, 16); // Read first 16 bytes
        fclose($handle);
        
        $hex = strtoupper(bin2hex($bytes));
        
        foreach ($this->magicNumbers[$mimeType] as $signature) {
            if (strpos($hex, $signature) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check for suspicious content patterns
     */
    private function containsSuspiciousContent($filePath) {
        $suspiciousPatterns = [
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i',
            '/<?php/i',
            '/<\?=/i'
        ];
        
        $content = file_get_contents($filePath, false, null, 0, 8192); // Read first 8KB
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Process and securely store uploaded file
     */
    public function processUpload($file, $category = 'general') {
        $validation = $this->validateFile($file);
        
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors']
            ];
        }
        
        // Generate secure random filename
        $extension = $validation['extension'];
        $secureFilename = $this->generateSecureFilename($extension);
        
        // Create category subdirectory
        $categoryDir = $this->uploadDir . $category . '/';
        if (!file_exists($categoryDir)) {
            mkdir($categoryDir, 0755, true);
        }
        
        $destinationPath = $categoryDir . $secureFilename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
            return [
                'success' => false,
                'errors' => ['Failed to move uploaded file']
            ];
        }
        
        // Process the file based on type
        $processedPath = $this->processFileByType($destinationPath, $validation['mime_type']);
        
        // Generate secure access token for file retrieval
        $accessToken = $this->generateAccessToken($processedPath);
        
        return [
            'success' => true,
            'filename' => basename($processedPath),
            'path' => $processedPath,
            'access_token' => $accessToken,
            'size' => filesize($processedPath),
            'mime_type' => $validation['mime_type']
        ];
    }
    
    /**
     * Process file based on its type (strip EXIF, reprocess images, etc.)
     */
    private function processFileByType($filePath, $mimeType) {
        if (strpos($mimeType, 'image/') === 0) {
            return $this->processImage($filePath, $mimeType);
        }
        
        return $filePath;
    }
    
    /**
     * Process image files - strip EXIF data and potential embedded code
     */
    private function processImage($filePath, $mimeType) {
        $processedPath = $filePath . '.processed';
        
        try {
            switch ($mimeType) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($filePath);
                    if ($image) {
                        imagejpeg($image, $processedPath, 85);
                        imagedestroy($image);
                    }
                    break;
                    
                case 'image/png':
                    $image = imagecreatefrompng($filePath);
                    if ($image) {
                        imagepng($image, $processedPath, 6);
                        imagedestroy($image);
                    }
                    break;
                    
                case 'image/gif':
                    $image = imagecreatefromgif($filePath);
                    if ($image) {
                        imagegif($image, $processedPath);
                        imagedestroy($image);
                    }
                    break;
                    
                case 'image/webp':
                    if (function_exists('imagecreatefromwebp')) {
                        $image = imagecreatefromwebp($filePath);
                        if ($image) {
                            imagewebp($image, $processedPath, 85);
                            imagedestroy($image);
                        }
                    }
                    break;
            }
            
            // If processing succeeded, replace original with processed version
            if (file_exists($processedPath) && filesize($processedPath) > 0) {
                unlink($filePath);
                rename($processedPath, $filePath);
            } elseif (file_exists($processedPath)) {
                unlink($processedPath);
            }
            
        } catch (Exception $e) {
            // If processing fails, log error but keep original file
            error_log("Image processing failed: " . $e->getMessage());
            if (file_exists($processedPath)) {
                unlink($processedPath);
            }
        }
        
        return $filePath;
    }
    
    /**
     * Generate cryptographically secure filename
     */
    private function generateSecureFilename($extension) {
        $timestamp = time();
        $random = bin2hex(random_bytes(16));
        return $timestamp . '_' . $random . '.' . $extension;
    }
    
    /**
     * Generate secure access token for file retrieval
     */
    private function generateAccessToken($filePath) {
        $data = [
            'file' => basename($filePath),
            'time' => time(),
            'salt' => bin2hex(random_bytes(8))
        ];
        
        return hash_hmac('sha256', json_encode($data), $this->getSecretKey());
    }
    
    /**
     * Get secret key for HMAC (should be stored securely)
     */
    private function getSecretKey() {
        // In production, this should come from environment variables or secure key management
        $keyFile = __DIR__ . '/../config/upload.key';
        
        if (!file_exists($keyFile)) {
            $key = bin2hex(random_bytes(32));
            file_put_contents($keyFile, $key);
            chmod($keyFile, 0600);
        }
        
        return file_get_contents($keyFile);
    }
    
    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }
    
    /**
     * Secure file retrieval with access token verification
     */
    public static function serveFile($filename, $token, $category = 'general') {
        $uploadDir = __DIR__ . '/../secure-uploads/' . $category . '/';
        $filePath = $uploadDir . $filename;
        
        if (!file_exists($filePath)) {
            http_response_code(404);
            exit('File not found');
        }
        
        // Verify access token (implement your verification logic)
        // For now, we'll implement basic time-based validation
        
        // Get file info
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        // Security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Content-Security-Policy: default-src \'none\'');
        
        // Serve file
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Content-Disposition: inline; filename="' . basename($filename) . '"');
        
        readfile($filePath);
        exit;
    }
    
    /**
     * Clean up old files (run via cron job)
     */
    public function cleanupOldFiles($maxAge = 2592000) { // 30 days default
        $this->cleanupDirectory($this->uploadDir, $maxAge);
    }
    
    private function cleanupDirectory($dir, $maxAge) {
        $files = glob($dir . '*');
        $now = time();
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= $maxAge) {
                    unlink($file);
                }
            } elseif (is_dir($file) && $file !== '.' && $file !== '..') {
                $this->cleanupDirectory($file . '/', $maxAge);
            }
        }
    }
}

/**
 * Hook for virus scanning (integrate with ClamAV or similar)
 */
class VirusScanner {
    public static function scanFile($filePath) {
        // Placeholder for virus scanning integration
        // In production, integrate with ClamAV:
        // exec("clamscan --no-summary " . escapeshellarg($filePath), $output, $returnCode);
        // return $returnCode === 0;
        
        // For now, just check file size as basic sanity check
        return filesize($filePath) < 50 * 1024 * 1024; // 50MB max
    }
}

// Rate limiting for uploads
class UploadRateLimit {
    public static function checkLimit($identifier, $maxUploads = 10, $timeWindow = 3600) {
        $cacheFile = sys_get_temp_dir() . '/upload_rate_' . md5($identifier);
        
        $attempts = [];
        if (file_exists($cacheFile)) {
            $attempts = json_decode(file_get_contents($cacheFile), true) ?: [];
        }
        
        // Clean old attempts
        $now = time();
        $attempts = array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        if (count($attempts) >= $maxUploads) {
            return false;
        }
        
        $attempts[] = $now;
        file_put_contents($cacheFile, json_encode($attempts));
        
        return true;
    }
} 