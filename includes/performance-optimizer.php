<?php
/**
 * Performance Optimization System for Bort's Books
 * Comprehensive speed and performance improvements
 */

class PerformanceOptimizer {
    
    private static $cache_dir = '../cache/';
    private static $optimized_images_dir = '../uploads/optimized/';
    
    public static function init() {
        // Create necessary directories
        self::createDirectories();
        
        // Enable output buffering with compression
        if (!ob_get_level()) {
            ob_start('self::compressOutput');
        }
        
        // Set performance headers
        self::setPerformanceHeaders();
    }
    
    private static function createDirectories() {
        $dirs = [self::$cache_dir, self::$optimized_images_dir];
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    public static function setPerformanceHeaders() {
        // Enable GZIP compression
        if (!headers_sent()) {
            header('Content-Encoding: gzip');
            
            // Cache static resources
            $cache_time = 31536000; // 1 year
            header("Cache-Control: public, max-age=$cache_time");
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_time) . ' GMT');
            
            // Security headers
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
    }
    
    public static function compressOutput($buffer) {
        // Minify HTML
        $buffer = self::minifyHTML($buffer);
        
        // Compress if possible
        if (function_exists('gzencode') && !headers_sent()) {
            return gzencode($buffer, 6);
        }
        
        return $buffer;
    }
    
    private static function minifyHTML($html) {
        // Remove comments (except IE conditionals)
        $html = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $html);
        
        // Remove extra whitespace
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/>\s+</', '><', $html);
        
        return trim($html);
    }
    
    public static function optimizeImage($imagePath, $quality = 85) {
        if (!file_exists($imagePath)) {
            return false;
        }
        
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return false;
        }
        
        $optimizedPath = self::$optimized_images_dir . basename($imagePath);
        
        // Check if optimized version already exists and is newer
        if (file_exists($optimizedPath) && filemtime($optimizedPath) > filemtime($imagePath)) {
            return $optimizedPath;
        }
        
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($imagePath);
                imagejpeg($image, $optimizedPath, $quality);
                break;
                
            case 'image/png':
                $image = imagecreatefrompng($imagePath);
                imagepng($image, $optimizedPath, 9);
                break;
                
            case 'image/gif':
                copy($imagePath, $optimizedPath); // GIFs are usually small
                break;
                
            case 'image/webp':
                $image = imagecreatefromwebp($imagePath);
                imagewebp($image, $optimizedPath, $quality);
                break;
                
            default:
                return false;
        }
        
        if (isset($image)) {
            imagedestroy($image);
        }
        
        return file_exists($optimizedPath) ? $optimizedPath : false;
    }
    
    public static function generateWebP($imagePath) {
        if (!function_exists('imagewebp')) {
            return false;
        }
        
        $webpPath = self::$optimized_images_dir . pathinfo($imagePath, PATHINFO_FILENAME) . '.webp';
        
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return false;
        }
        
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($imagePath);
                break;
            default:
                return false;
        }
        
        if (imagewebp($image, $webpPath, 85)) {
            imagedestroy($image);
            return $webpPath;
        }
        
        return false;
    }
    
    public static function lazyLoadImages($html) {
        // Add lazy loading to images
        $html = preg_replace(
            '/<img([^>]*?)src=(["\'])([^"\']*?)\2([^>]*?)>/i',
            '<img$1loading="lazy" src=$2$3$2$4>',
            $html
        );
        
        return $html;
    }
    
    public static function preloadCriticalResources() {
        $critical_resources = [
            '/assets/css/styles.css' => 'style',
            '/assets/fonts/inter.woff2' => 'font',
            '/assets/js/main.js' => 'script'
        ];
        
        foreach ($critical_resources as $resource => $type) {
            echo "<link rel=\"preload\" href=\"$resource\" as=\"$type\">\n";
        }
    }
    
    public static function generateCriticalCSS($page_type = 'home') {
        $critical_css = [
            'home' => '
                body { font-family: Inter, sans-serif; margin: 0; }
                .hero { background: linear-gradient(135deg, #232946 0%, #395aa0 50%, #eebbc3 100%); }
                .header { position: fixed; top: 0; width: 100%; z-index: 1000; }
                .btn-primary { background: #eebbc3; color: #232946; padding: 1rem 2rem; }
            ',
            'shop' => '
                .product-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }
                .product-card { border: 1px solid #ddd; padding: 1rem; }
                .product-image { width: 100%; height: 200px; object-fit: cover; }
            ',
            'product' => '
                .product-details { display: flex; gap: 2rem; }
                .product-images { flex: 1; }
                .product-info { flex: 1; }
                .add-to-cart { background: #232946; color: white; padding: 1rem 2rem; }
            '
        ];
        
        return isset($critical_css[$page_type]) ? $critical_css[$page_type] : $critical_css['home'];
    }
}

/**
 * Database Query Optimization
 */
class DatabaseOptimizer {
    
    public static function optimizeProductQueries($db) {
        // Add indexes for better performance
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_products_price ON products(price)",
            "CREATE INDEX IF NOT EXISTS idx_products_condition ON products(`condition`)",
            "CREATE INDEX IF NOT EXISTS idx_products_created_at ON products(created_at)",
            "CREATE INDEX IF NOT EXISTS idx_product_images_product_id ON product_images(product_id)",
            "CREATE INDEX IF NOT EXISTS idx_product_images_is_primary ON product_images(is_primary)"
        ];
        
        foreach ($indexes as $index) {
            try {
                $db->exec($index);
            } catch (PDOException $e) {
                error_log("Index creation failed: " . $e->getMessage());
            }
        }
    }
    
    public static function getCachedProducts($db, $cache_key, $query, $params = [], $cache_time = 300) {
        $cache_file = PerformanceOptimizer::$cache_dir . md5($cache_key) . '.cache';
        
        // Check if cache exists and is valid
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time) {
            return unserialize(file_get_contents($cache_file));
        }
        
        // Execute query and cache result
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        file_put_contents($cache_file, serialize($result));
        
        return $result;
    }
    
    public static function clearCache($pattern = '*') {
        $cache_files = glob(PerformanceOptimizer::$cache_dir . $pattern . '.cache');
        foreach ($cache_files as $file) {
            unlink($file);
        }
    }
}

/**
 * Resource Minification
 */
class ResourceMinifier {
    
    public static function minifyCSS($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove unnecessary whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        $css = str_replace(['; ', ' {', '{ ', ' }', '} ', ': ', ', '], [';', '{', '{', '}', '}', ':', ','], $css);
        
        return trim($css);
    }
    
    public static function minifyJS($js) {
        // Basic JS minification (for production, use a proper minifier)
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js); // Remove comments
        $js = preg_replace('/\/\/.*$/m', '', $js); // Remove single-line comments
        $js = preg_replace('/\s+/', ' ', $js); // Compress whitespace
        
        return trim($js);
    }
    
    public static function combineFiles($files, $type = 'css') {
        $combined = '';
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                
                if ($type === 'css') {
                    $content = self::minifyCSS($content);
                } elseif ($type === 'js') {
                    $content = self::minifyJS($content);
                }
                
                $combined .= $content . "\n";
            }
        }
        
        return $combined;
    }
}

// Initialize performance optimization
PerformanceOptimizer::init();
?> 