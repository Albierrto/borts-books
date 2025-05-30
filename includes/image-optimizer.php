<?php
/**
 * Image Optimization Utility
 * Helps compress and resize images for better web performance
 */

class ImageOptimizer {
    
    public static function optimizeImage($sourcePath, $destinationPath, $maxWidth = 800, $quality = 85) {
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
        
        // Calculate new dimensions while maintaining aspect ratio
        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = ($height * $maxWidth) / $width;
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }
        
        // Create image resource based on type
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
        
        // Create new image with optimized dimensions
        $optimizedImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagealphablending($optimizedImage, false);
            imagesavealpha($optimizedImage, true);
            $transparent = imagecolorallocatealpha($optimizedImage, 255, 255, 255, 127);
            imagefilledrectangle($optimizedImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Resize the image
        imagecopyresampled(
            $optimizedImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight, $width, $height
        );
        
        // Save optimized image
        $result = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($optimizedImage, $destinationPath, $quality);
                break;
            case IMAGETYPE_PNG:
                // PNG quality is 0-9, convert from 0-100
                $pngQuality = 9 - round(($quality / 100) * 9);
                $result = imagepng($optimizedImage, $destinationPath, $pngQuality);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($optimizedImage, $destinationPath);
                break;
        }
        
        // Clean up memory
        imagedestroy($sourceImage);
        imagedestroy($optimizedImage);
        
        return $result;
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
?> 