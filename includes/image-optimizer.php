<?php
/**
 * Image Optimization System
 * Handles image processing, optimization, and caching
 */

class ImageOptimizer {
    private static $cache_dir = '../cache/images/';
    private static $sizes = [
        'thumbnail' => ['width' => 300, 'height' => 400],
        'medium' => ['width' => 600, 'height' => 800],
        'large' => ['width' => 900, 'height' => 1200]
    ];

    public static function init() {
        if (!file_exists(self::$cache_dir)) {
            mkdir(self::$cache_dir, 0755, true);
        }
    }

    public static function optimizeImage($source_url, $size = 'thumbnail') {
        // Generate cache key from URL
        $cache_key = md5($source_url . $size);
        $cache_path = self::$cache_dir . $cache_key;

        // Check if cached version exists
        if (file_exists($cache_path . '.webp')) {
            return str_replace('../', '/', $cache_path . '.webp');
        }

        // Download image if it's a URL
        if (filter_var($source_url, FILTER_VALIDATE_URL)) {
            $image_data = file_get_contents($source_url);
            $temp_file = tempnam(sys_get_temp_dir(), 'img');
            file_put_contents($temp_file, $image_data);
            $source_url = $temp_file;
        }

        // Load image based on type
        $image_info = getimagesize($source_url);
        switch ($image_info[2]) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($source_url);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($source_url);
                break;
            case IMAGETYPE_WEBP:
                $image = imagecreatefromwebp($source_url);
                break;
            default:
                return false;
        }

        // Resize image
        $target_dimensions = self::$sizes[$size];
        $resized = imagecreatetruecolor($target_dimensions['width'], $target_dimensions['height']);
        
        // Preserve transparency for PNG
        if ($image_info[2] === IMAGETYPE_PNG) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }

        imagecopyresampled(
            $resized, 
            $image, 
            0, 0, 0, 0, 
            $target_dimensions['width'], 
            $target_dimensions['height'], 
            $image_info[0], 
            $image_info[1]
        );

        // Save as WebP
        imagewebp($resized, $cache_path . '.webp', 80);

        // Clean up
        imagedestroy($image);
        imagedestroy($resized);
        if (isset($temp_file)) {
            unlink($temp_file);
        }

        return str_replace('../', '/', $cache_path . '.webp');
    }

    public static function generateSrcSet($source_url) {
        $srcset = [];
        foreach (self::$sizes as $size => $dimensions) {
            $optimized_url = self::optimizeImage($source_url, $size);
            if ($optimized_url) {
                $srcset[] = $optimized_url . ' ' . $dimensions['width'] . 'w';
            }
        }
        return implode(', ', $srcset);
    }

    public static function createThumbnail($sourcePath, $thumbnailPath, $size = 200, $quality = 80) {
        if (!file_exists($sourcePath)) {
            return false;
        }
        
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $type = $imageInfo[2];
        
        // Create square thumbnail
        $cropSize = min($width, $height);
        $cropX = ($width - $cropSize) / 2;
        $cropY = ($height - $cropSize) / 2;
        
        // Create image resource
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) {
            return false;
        }
        
        // Create thumbnail
        $thumbnail = imagecreatetruecolor($size, $size);
        
        // Preserve transparency
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefilledrectangle($thumbnail, 0, 0, $size, $size, $transparent);
        }
        
        // Crop and resize to square thumbnail
        imagecopyresampled(
            $thumbnail, $sourceImage,
            0, 0, $cropX, $cropY,
            $size, $size, $cropSize, $cropSize
        );
        
        // Save thumbnail
        $result = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($thumbnail, $thumbnailPath, $quality);
                break;
            case IMAGETYPE_PNG:
                $pngQuality = 9 - round(($quality / 100) * 9);
                $result = imagepng($thumbnail, $thumbnailPath, $pngQuality);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($thumbnail, $thumbnailPath);
                break;
        }
        
        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);
        
        return $result;
    }
    
    public static function getImageSize($path) {
        if (!file_exists($path)) {
            return 0;
        }
        return filesize($path);
    }
    
    public static function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

// Initialize on include
ImageOptimizer::init();
?> 