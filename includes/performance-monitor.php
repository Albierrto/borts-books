<?php
/**
 * Performance Monitor
 * Tracks page load times and helps identify performance bottlenecks
 */

class PerformanceMonitor {
    private static $startTime;
    private static $memoryStart;
    
    public static function start() {
        self::$startTime = microtime(true);
        self::$memoryStart = memory_get_usage();
    }
    
    public static function end($pageName = '') {
        $endTime = microtime(true);
        $memoryEnd = memory_get_usage();
        
        $loadTime = round(($endTime - self::$startTime) * 1000, 2); // Convert to milliseconds
        $memoryUsed = round(($memoryEnd - self::$memoryStart) / 1024 / 1024, 2); // Convert to MB
        $peakMemory = round(memory_get_peak_usage() / 1024 / 1024, 2); // Convert to MB
        
        return [
            'page' => $pageName,
            'load_time_ms' => $loadTime,
            'memory_used_mb' => $memoryUsed,
            'peak_memory_mb' => $peakMemory,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    public static function logPerformance($pageName = '') {
        $stats = self::end($pageName);
        
        // Log to file if load time is over 1 second
        if ($stats['load_time_ms'] > 1000) {
            $logEntry = sprintf(
                "[%s] SLOW PAGE: %s - Load Time: %sms, Memory: %sMB, Peak: %sMB\n",
                $stats['timestamp'],
                $stats['page'],
                $stats['load_time_ms'],
                $stats['memory_used_mb'],
                $stats['peak_memory_mb']
            );
            
            error_log($logEntry, 3, __DIR__ . '/../logs/performance.log');
        }
        
        return $stats;
    }
    
    public static function addPerformanceHeaders($stats) {
        if (!headers_sent()) {
            header('X-Page-Load-Time: ' . $stats['load_time_ms'] . 'ms');
            header('X-Memory-Usage: ' . $stats['memory_used_mb'] . 'MB');
            header('X-Peak-Memory: ' . $stats['peak_memory_mb'] . 'MB');
        }
    }
    
    public static function getPerformanceHTML($stats) {
        if (isset($_GET['debug']) && $_GET['debug'] === 'performance') {
            return sprintf(
                '<div style="position:fixed;bottom:10px;right:10px;background:rgba(0,0,0,0.8);color:white;padding:10px;border-radius:5px;font-size:12px;z-index:9999;">
                    <strong>Performance Debug</strong><br>
                    Page: %s<br>
                    Load Time: %sms<br>
                    Memory Used: %sMB<br>
                    Peak Memory: %sMB
                </div>',
                htmlspecialchars($stats['page']),
                $stats['load_time_ms'],
                $stats['memory_used_mb'],
                $stats['peak_memory_mb']
            );
        }
        return '';
    }
}

// Auto-start performance monitoring
PerformanceMonitor::start();
?> 