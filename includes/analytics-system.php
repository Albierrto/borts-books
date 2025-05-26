<?php
/**
 * Analytics & Business Intelligence System for Bort's Books
 * Comprehensive tracking and reporting for business optimization
 */

class AnalyticsSystem {
    
    private static $db;
    private static $tracking_enabled = true;
    
    public static function init($database) {
        self::$db = $database;
        self::createAnalyticsTables();
        self::startSession();
    }
    
    private static function createAnalyticsTables() {
        $tables = [
            'page_views' => "
                CREATE TABLE IF NOT EXISTS page_views (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    session_id VARCHAR(255),
                    user_id INT NULL,
                    page_url VARCHAR(500),
                    page_title VARCHAR(255),
                    referrer VARCHAR(500),
                    user_agent TEXT,
                    ip_address VARCHAR(45),
                    device_type VARCHAR(50),
                    browser VARCHAR(100),
                    country VARCHAR(100),
                    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_session (session_id),
                    INDEX idx_user (user_id),
                    INDEX idx_timestamp (timestamp)
                )
            ",
            'user_events' => "
                CREATE TABLE IF NOT EXISTS user_events (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    session_id VARCHAR(255),
                    user_id INT NULL,
                    event_type VARCHAR(100),
                    event_data JSON,
                    page_url VARCHAR(500),
                    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_session (session_id),
                    INDEX idx_event_type (event_type),
                    INDEX idx_timestamp (timestamp)
                )
            ",
            'conversion_funnel' => "
                CREATE TABLE IF NOT EXISTS conversion_funnel (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    session_id VARCHAR(255),
                    user_id INT NULL,
                    step VARCHAR(100),
                    product_id INT NULL,
                    value DECIMAL(10,2) NULL,
                    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_session (session_id),
                    INDEX idx_step (step),
                    INDEX idx_timestamp (timestamp)
                )
            ",
            'sales_analytics' => "
                CREATE TABLE IF NOT EXISTS sales_analytics (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT,
                    user_id INT NULL,
                    product_id INT,
                    quantity INT,
                    unit_price DECIMAL(10,2),
                    total_price DECIMAL(10,2),
                    category VARCHAR(100),
                    payment_method VARCHAR(50),
                    shipping_method VARCHAR(50),
                    discount_amount DECIMAL(10,2) DEFAULT 0,
                    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_order (order_id),
                    INDEX idx_product (product_id),
                    INDEX idx_timestamp (timestamp)
                )
            "
        ];
        
        foreach ($tables as $table_name => $sql) {
            try {
                self::$db->exec($sql);
            } catch (PDOException $e) {
                error_log("Analytics table creation failed for $table_name: " . $e->getMessage());
            }
        }
    }
    
    private static function startSession() {
        if (!isset($_SESSION['analytics_session_id'])) {
            $_SESSION['analytics_session_id'] = bin2hex(random_bytes(16));
            $_SESSION['session_start_time'] = time();
        }
    }
    
    public static function trackPageView($page_title = '', $additional_data = []) {
        if (!self::$tracking_enabled) return;
        
        $data = [
            'session_id' => $_SESSION['analytics_session_id'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null,
            'page_url' => $_SERVER['REQUEST_URI'] ?? '',
            'page_title' => $page_title ?: ($_SERVER['REQUEST_URI'] ?? 'Unknown'),
            'referrer' => $_SERVER['HTTP_REFERER'] ?? 'direct',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip_address' => self::getClientIP(),
            'device_type' => self::getDeviceType(),
            'browser' => self::getBrowser(),
            'country' => self::getCountry()
        ];
        
        try {
            $stmt = self::$db->prepare("
                INSERT INTO page_views (session_id, user_id, page_url, page_title, referrer, 
                                      user_agent, ip_address, device_type, browser, country)
                VALUES (:session_id, :user_id, :page_url, :page_title, :referrer, 
                        :user_agent, :ip_address, :device_type, :browser, :country)
            ");
            $stmt->execute($data);
        } catch (PDOException $e) {
            error_log("Analytics tracking failed: " . $e->getMessage());
        }
    }
    
    public static function trackEvent($event_type, $event_data = [], $page_url = '') {
        if (!self::$tracking_enabled) return;
        
        $data = [
            'session_id' => $_SESSION['analytics_session_id'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null,
            'event_type' => $event_type,
            'event_data' => json_encode($event_data),
            'page_url' => $page_url ?: ($_SERVER['REQUEST_URI'] ?? '')
        ];
        
        try {
            $stmt = self::$db->prepare("
                INSERT INTO user_events (session_id, user_id, event_type, event_data, page_url)
                VALUES (:session_id, :user_id, :event_type, :event_data, :page_url)
            ");
            $stmt->execute($data);
        } catch (PDOException $e) {
            error_log("Event tracking failed: " . $e->getMessage());
        }
    }
    
    public static function trackConversionStep($step, $product_id = null, $value = null) {
        if (!self::$tracking_enabled) return;
        
        $data = [
            'session_id' => $_SESSION['analytics_session_id'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null,
            'step' => $step,
            'product_id' => $product_id,
            'value' => $value
        ];
        
        try {
            $stmt = self::$db->prepare("
                INSERT INTO conversion_funnel (session_id, user_id, step, product_id, value)
                VALUES (:session_id, :user_id, :step, :product_id, :value)
            ");
            $stmt->execute($data);
        } catch (PDOException $e) {
            error_log("Conversion tracking failed: " . $e->getMessage());
        }
    }
    
    public static function trackSale($order_data) {
        if (!self::$tracking_enabled) return;
        
        foreach ($order_data['items'] as $item) {
            $data = [
                'order_id' => $order_data['order_id'],
                'user_id' => $order_data['user_id'] ?? null,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['total_price'],
                'category' => $item['category'] ?? 'manga',
                'payment_method' => $order_data['payment_method'] ?? 'stripe',
                'shipping_method' => $order_data['shipping_method'] ?? 'standard',
                'discount_amount' => $order_data['discount_amount'] ?? 0
            ];
            
            try {
                $stmt = self::$db->prepare("
                    INSERT INTO sales_analytics (order_id, user_id, product_id, quantity, unit_price, 
                                               total_price, category, payment_method, shipping_method, discount_amount)
                    VALUES (:order_id, :user_id, :product_id, :quantity, :unit_price, 
                            :total_price, :category, :payment_method, :shipping_method, :discount_amount)
                ");
                $stmt->execute($data);
            } catch (PDOException $e) {
                error_log("Sales tracking failed: " . $e->getMessage());
            }
        }
    }
    
    public static function getDashboardMetrics($days = 30) {
        $start_date = date('Y-m-d', strtotime("-$days days"));
        
        $metrics = [];
        
        // Page views
        $stmt = self::$db->prepare("
            SELECT COUNT(*) as total_views, COUNT(DISTINCT session_id) as unique_sessions
            FROM page_views 
            WHERE timestamp >= ?
        ");
        $stmt->execute([$start_date]);
        $metrics['traffic'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Top pages
        $stmt = self::$db->prepare("
            SELECT page_url, COUNT(*) as views
            FROM page_views 
            WHERE timestamp >= ?
            GROUP BY page_url
            ORDER BY views DESC
            LIMIT 10
        ");
        $stmt->execute([$start_date]);
        $metrics['top_pages'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Device breakdown
        $stmt = self::$db->prepare("
            SELECT device_type, COUNT(*) as count
            FROM page_views 
            WHERE timestamp >= ?
            GROUP BY device_type
        ");
        $stmt->execute([$start_date]);
        $metrics['devices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Sales metrics
        $stmt = self::$db->prepare("
            SELECT 
                COUNT(DISTINCT order_id) as total_orders,
                SUM(total_price) as total_revenue,
                AVG(total_price) as avg_order_value,
                SUM(quantity) as total_items_sold
            FROM sales_analytics 
            WHERE timestamp >= ?
        ");
        $stmt->execute([$start_date]);
        $metrics['sales'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Top products
        $stmt = self::$db->prepare("
            SELECT 
                sa.product_id,
                p.title,
                SUM(sa.quantity) as total_sold,
                SUM(sa.total_price) as total_revenue
            FROM sales_analytics sa
            LEFT JOIN products p ON sa.product_id = p.id
            WHERE sa.timestamp >= ?
            GROUP BY sa.product_id
            ORDER BY total_sold DESC
            LIMIT 10
        ");
        $stmt->execute([$start_date]);
        $metrics['top_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Conversion funnel
        $stmt = self::$db->prepare("
            SELECT step, COUNT(DISTINCT session_id) as sessions
            FROM conversion_funnel 
            WHERE timestamp >= ?
            GROUP BY step
            ORDER BY 
                CASE step
                    WHEN 'product_view' THEN 1
                    WHEN 'add_to_cart' THEN 2
                    WHEN 'checkout_start' THEN 3
                    WHEN 'payment_info' THEN 4
                    WHEN 'purchase' THEN 5
                    ELSE 6
                END
        ");
        $stmt->execute([$start_date]);
        $metrics['conversion_funnel'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $metrics;
    }
    
    public static function getRealtimeMetrics() {
        $metrics = [];
        
        // Active users (last 30 minutes)
        $stmt = self::$db->prepare("
            SELECT COUNT(DISTINCT session_id) as active_users
            FROM page_views 
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ");
        $stmt->execute();
        $metrics['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['active_users'];
        
        // Recent events
        $stmt = self::$db->prepare("
            SELECT event_type, COUNT(*) as count
            FROM user_events 
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY event_type
            ORDER BY count DESC
            LIMIT 5
        ");
        $stmt->execute();
        $metrics['recent_events'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Today's sales
        $stmt = self::$db->prepare("
            SELECT 
                COUNT(DISTINCT order_id) as orders_today,
                SUM(total_price) as revenue_today
            FROM sales_analytics 
            WHERE DATE(timestamp) = CURDATE()
        ");
        $stmt->execute();
        $metrics['today_sales'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $metrics;
    }
    
    public static function generateReport($type, $start_date, $end_date) {
        switch ($type) {
            case 'traffic':
                return self::generateTrafficReport($start_date, $end_date);
            case 'sales':
                return self::generateSalesReport($start_date, $end_date);
            case 'products':
                return self::generateProductReport($start_date, $end_date);
            case 'users':
                return self::generateUserReport($start_date, $end_date);
            default:
                return [];
        }
    }
    
    private static function generateTrafficReport($start_date, $end_date) {
        $report = [];
        
        // Daily traffic
        $stmt = self::$db->prepare("
            SELECT 
                DATE(timestamp) as date,
                COUNT(*) as page_views,
                COUNT(DISTINCT session_id) as unique_sessions,
                COUNT(DISTINCT ip_address) as unique_visitors
            FROM page_views 
            WHERE timestamp BETWEEN ? AND ?
            GROUP BY DATE(timestamp)
            ORDER BY date
        ");
        $stmt->execute([$start_date, $end_date]);
        $report['daily_traffic'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Traffic sources
        $stmt = self::$db->prepare("
            SELECT 
                CASE 
                    WHEN referrer = 'direct' THEN 'Direct'
                    WHEN referrer LIKE '%google%' THEN 'Google'
                    WHEN referrer LIKE '%facebook%' THEN 'Facebook'
                    WHEN referrer LIKE '%twitter%' THEN 'Twitter'
                    ELSE 'Other'
                END as source,
                COUNT(*) as visits
            FROM page_views 
            WHERE timestamp BETWEEN ? AND ?
            GROUP BY source
            ORDER BY visits DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $report['traffic_sources'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $report;
    }
    
    private static function generateSalesReport($start_date, $end_date) {
        $report = [];
        
        // Daily sales
        $stmt = self::$db->prepare("
            SELECT 
                DATE(timestamp) as date,
                COUNT(DISTINCT order_id) as orders,
                SUM(total_price) as revenue,
                AVG(total_price) as avg_order_value
            FROM sales_analytics 
            WHERE timestamp BETWEEN ? AND ?
            GROUP BY DATE(timestamp)
            ORDER BY date
        ");
        $stmt->execute([$start_date, $end_date]);
        $report['daily_sales'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Payment methods
        $stmt = self::$db->prepare("
            SELECT 
                payment_method,
                COUNT(DISTINCT order_id) as orders,
                SUM(total_price) as revenue
            FROM sales_analytics 
            WHERE timestamp BETWEEN ? AND ?
            GROUP BY payment_method
        ");
        $stmt->execute([$start_date, $end_date]);
        $report['payment_methods'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $report;
    }
    
    private static function getClientIP() {
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                return trim($ips[0]);
            }
        }
        
        return 'unknown';
    }
    
    private static function getDeviceType() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (preg_match('/mobile|android|iphone|ipad/i', $user_agent)) {
            return 'mobile';
        } elseif (preg_match('/tablet|ipad/i', $user_agent)) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }
    
    private static function getBrowser() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (strpos($user_agent, 'Chrome') !== false) return 'Chrome';
        if (strpos($user_agent, 'Firefox') !== false) return 'Firefox';
        if (strpos($user_agent, 'Safari') !== false) return 'Safari';
        if (strpos($user_agent, 'Edge') !== false) return 'Edge';
        if (strpos($user_agent, 'Opera') !== false) return 'Opera';
        
        return 'Other';
    }
    
    private static function getCountry() {
        // Simple IP-based country detection (you might want to use a service like MaxMind)
        $ip = self::getClientIP();
        
        // For now, return 'Unknown' - implement proper geolocation if needed
        return 'Unknown';
    }
    
    public static function generateAnalyticsJS() {
        return '
        <script>
        // Custom Analytics Tracking
        (function() {
            // Track page view
            fetch("/includes/track-analytics.php", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({
                    type: "page_view",
                    page_title: document.title,
                    timestamp: Date.now()
                })
            });
            
            // Track clicks
            document.addEventListener("click", function(e) {
                const target = e.target;
                
                if (target.matches(".product-card, .product-link")) {
                    const productId = target.dataset.productId;
                    trackEvent("product_click", {product_id: productId});
                }
                
                if (target.matches(".add-to-cart")) {
                    const productId = target.dataset.productId;
                    trackEvent("add_to_cart", {product_id: productId});
                    trackConversionStep("add_to_cart", productId);
                }
                
                if (target.matches(".checkout-btn")) {
                    trackEvent("checkout_start");
                    trackConversionStep("checkout_start");
                }
            });
            
            // Track form submissions
            document.addEventListener("submit", function(e) {
                const form = e.target;
                
                if (form.matches(".search-form")) {
                    const query = form.querySelector("input[name=q]").value;
                    trackEvent("search", {query: query});
                }
                
                if (form.matches(".newsletter-form")) {
                    trackEvent("newsletter_signup");
                }
            });
            
            // Track scroll depth
            let maxScroll = 0;
            window.addEventListener("scroll", function() {
                const scrollPercent = Math.round((window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100);
                if (scrollPercent > maxScroll) {
                    maxScroll = scrollPercent;
                    if (maxScroll >= 25 && maxScroll < 50) {
                        trackEvent("scroll_depth", {depth: "25%"});
                    } else if (maxScroll >= 50 && maxScroll < 75) {
                        trackEvent("scroll_depth", {depth: "50%"});
                    } else if (maxScroll >= 75) {
                        trackEvent("scroll_depth", {depth: "75%"});
                    }
                }
            });
            
            // Track time on page
            let startTime = Date.now();
            window.addEventListener("beforeunload", function() {
                const timeOnPage = Math.round((Date.now() - startTime) / 1000);
                trackEvent("time_on_page", {seconds: timeOnPage});
            });
            
            function trackEvent(eventType, data = {}) {
                fetch("/includes/track-analytics.php", {
                    method: "POST",
                    headers: {"Content-Type": "application/json"},
                    body: JSON.stringify({
                        type: "event",
                        event_type: eventType,
                        event_data: data,
                        timestamp: Date.now()
                    })
                });
            }
            
            function trackConversionStep(step, productId = null, value = null) {
                fetch("/includes/track-analytics.php", {
                    method: "POST",
                    headers: {"Content-Type": "application/json"},
                    body: JSON.stringify({
                        type: "conversion",
                        step: step,
                        product_id: productId,
                        value: value,
                        timestamp: Date.now()
                    })
                });
            }
            
            // Make functions globally available
            window.trackEvent = trackEvent;
            window.trackConversionStep = trackConversionStep;
        })();
        </script>
        ';
    }
}

/**
 * A/B Testing System
 */
class ABTestingSystem {
    
    private static $tests = [];
    
    public static function createTest($test_name, $variants, $traffic_split = 50) {
        self::$tests[$test_name] = [
            'variants' => $variants,
            'traffic_split' => $traffic_split
        ];
        
        if (!isset($_SESSION['ab_tests'])) {
            $_SESSION['ab_tests'] = [];
        }
        
        if (!isset($_SESSION['ab_tests'][$test_name])) {
            $random = rand(1, 100);
            $_SESSION['ab_tests'][$test_name] = $random <= $traffic_split ? 'variant' : 'control';
        }
        
        return $_SESSION['ab_tests'][$test_name];
    }
    
    public static function getVariant($test_name) {
        return $_SESSION['ab_tests'][$test_name] ?? 'control';
    }
    
    public static function trackConversion($test_name, $conversion_type = 'purchase') {
        $variant = self::getVariant($test_name);
        
        AnalyticsSystem::trackEvent('ab_test_conversion', [
            'test_name' => $test_name,
            'variant' => $variant,
            'conversion_type' => $conversion_type
        ]);
    }
}
?> 