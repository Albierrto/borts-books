<?php
/**
 * Customer Retention & Loyalty System for Bort's Books
 * Comprehensive system to increase customer lifetime value and repeat purchases
 */

class CustomerRetentionSystem {
    
    private static $db;
    private static $loyalty_tiers = [
        'bronze' => ['min_spent' => 0, 'discount' => 5, 'points_multiplier' => 1],
        'silver' => ['min_spent' => 100, 'discount' => 10, 'points_multiplier' => 1.5],
        'gold' => ['min_spent' => 250, 'discount' => 15, 'points_multiplier' => 2],
        'platinum' => ['min_spent' => 500, 'discount' => 20, 'points_multiplier' => 2.5]
    ];
    
    public static function init($database) {
        self::$db = $database;
        self::createRetentionTables();
    }
    
    private static function createRetentionTables() {
        $tables = [
            'customer_loyalty' => "
                CREATE TABLE IF NOT EXISTS customer_loyalty (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNIQUE,
                    points INT DEFAULT 0,
                    tier VARCHAR(20) DEFAULT 'bronze',
                    total_spent DECIMAL(10,2) DEFAULT 0,
                    total_orders INT DEFAULT 0,
                    last_purchase DATE NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_user (user_id),
                    INDEX idx_tier (tier)
                )
            ",
            'customer_preferences' => "
                CREATE TABLE IF NOT EXISTS customer_preferences (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    category VARCHAR(100),
                    preference_score DECIMAL(3,2) DEFAULT 0,
                    last_interaction DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user (user_id),
                    INDEX idx_category (category),
                    UNIQUE KEY unique_user_category (user_id, category)
                )
            ",
            'email_campaigns' => "
                CREATE TABLE IF NOT EXISTS email_campaigns (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255),
                    subject VARCHAR(255),
                    content TEXT,
                    campaign_type VARCHAR(50),
                    target_segment VARCHAR(100),
                    status VARCHAR(20) DEFAULT 'draft',
                    sent_count INT DEFAULT 0,
                    open_count INT DEFAULT 0,
                    click_count INT DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    sent_at DATETIME NULL
                )
            ",
            'customer_segments' => "
                CREATE TABLE IF NOT EXISTS customer_segments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    segment_name VARCHAR(100),
                    segment_value VARCHAR(255),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user (user_id),
                    INDEX idx_segment (segment_name)
                )
            ",
            'wishlist' => "
                CREATE TABLE IF NOT EXISTS wishlist (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    product_id INT,
                    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    notified BOOLEAN DEFAULT FALSE,
                    INDEX idx_user (user_id),
                    INDEX idx_product (product_id),
                    UNIQUE KEY unique_user_product (user_id, product_id)
                )
            ",
            'customer_reviews' => "
                CREATE TABLE IF NOT EXISTS customer_reviews (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    product_id INT,
                    order_id INT,
                    rating INT CHECK (rating >= 1 AND rating <= 5),
                    review_text TEXT,
                    helpful_votes INT DEFAULT 0,
                    verified_purchase BOOLEAN DEFAULT FALSE,
                    status VARCHAR(20) DEFAULT 'pending',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user (user_id),
                    INDEX idx_product (product_id),
                    INDEX idx_rating (rating)
                )
            "
        ];
        
        foreach ($tables as $table_name => $sql) {
            try {
                self::$db->exec($sql);
            } catch (PDOException $e) {
                error_log("Retention table creation failed for $table_name: " . $e->getMessage());
            }
        }
    }
    
    public static function updateCustomerLoyalty($user_id, $order_amount) {
        // Get or create loyalty record
        $stmt = self::$db->prepare("
            INSERT INTO customer_loyalty (user_id, points, total_spent, total_orders, last_purchase)
            VALUES (?, ?, ?, 1, CURDATE())
            ON DUPLICATE KEY UPDATE
                points = points + ?,
                total_spent = total_spent + ?,
                total_orders = total_orders + 1,
                last_purchase = CURDATE()
        ");
        
        $points_earned = floor($order_amount); // 1 point per dollar
        $stmt->execute([$user_id, $points_earned, $order_amount, $points_earned, $order_amount]);
        
        // Update tier
        self::updateCustomerTier($user_id);
        
        return $points_earned;
    }
    
    private static function updateCustomerTier($user_id) {
        $stmt = self::$db->prepare("SELECT total_spent FROM customer_loyalty WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) return;
        
        $total_spent = $customer['total_spent'];
        $new_tier = 'bronze';
        
        foreach (array_reverse(self::$loyalty_tiers, true) as $tier => $requirements) {
            if ($total_spent >= $requirements['min_spent']) {
                $new_tier = $tier;
                break;
            }
        }
        
        $stmt = self::$db->prepare("UPDATE customer_loyalty SET tier = ? WHERE user_id = ?");
        $stmt->execute([$new_tier, $user_id]);
    }
    
    public static function getCustomerLoyalty($user_id) {
        $stmt = self::$db->prepare("SELECT * FROM customer_loyalty WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $loyalty = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$loyalty) {
            return [
                'points' => 0,
                'tier' => 'bronze',
                'total_spent' => 0,
                'total_orders' => 0,
                'discount_percentage' => 5
            ];
        }
        
        $loyalty['discount_percentage'] = self::$loyalty_tiers[$loyalty['tier']]['discount'];
        $loyalty['points_multiplier'] = self::$loyalty_tiers[$loyalty['tier']]['points_multiplier'];
        
        return $loyalty;
    }
    
    public static function redeemPoints($user_id, $points_to_redeem) {
        $loyalty = self::getCustomerLoyalty($user_id);
        
        if ($loyalty['points'] < $points_to_redeem) {
            return false;
        }
        
        $stmt = self::$db->prepare("UPDATE customer_loyalty SET points = points - ? WHERE user_id = ?");
        $stmt->execute([$points_to_redeem, $user_id]);
        
        return true;
    }
    
    public static function updateCustomerPreferences($user_id, $product_id, $interaction_type = 'view') {
        // Get product category
        $stmt = self::$db->prepare("SELECT category FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) return;
        
        $score_increment = [
            'view' => 0.1,
            'cart' => 0.3,
            'purchase' => 1.0,
            'review' => 0.5
        ][$interaction_type] ?? 0.1;
        
        $stmt = self::$db->prepare("
            INSERT INTO customer_preferences (user_id, category, preference_score, last_interaction)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                preference_score = LEAST(preference_score + ?, 5.0),
                last_interaction = NOW()
        ");
        $stmt->execute([$user_id, $product['category'], $score_increment, $score_increment]);
    }
    
    public static function getPersonalizedRecommendations($user_id, $limit = 10) {
        // Get user preferences
        $stmt = self::$db->prepare("
            SELECT category, preference_score
            FROM customer_preferences
            WHERE user_id = ?
            ORDER BY preference_score DESC
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $preferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($preferences)) {
            // Return popular products for new users
            return self::getPopularProducts($limit);
        }
        
        // Get products from preferred categories
        $category_list = "'" . implode("','", array_column($preferences, 'category')) . "'";
        
        $stmt = self::$db->prepare("
            SELECT p.*, 
                   COALESCE(AVG(cr.rating), 0) as avg_rating,
                   COUNT(cr.id) as review_count
            FROM products p
            LEFT JOIN customer_reviews cr ON p.id = cr.product_id AND cr.status = 'approved'
            WHERE p.category IN ($category_list)
            AND p.id NOT IN (
                SELECT product_id FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                WHERE o.user_id = ?
            )
            GROUP BY p.id
            ORDER BY avg_rating DESC, review_count DESC
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private static function getPopularProducts($limit) {
        $stmt = self::$db->prepare("
            SELECT p.*, 
                   COUNT(oi.product_id) as sales_count,
                   COALESCE(AVG(cr.rating), 0) as avg_rating
            FROM products p
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN customer_reviews cr ON p.id = cr.product_id AND cr.status = 'approved'
            GROUP BY p.id
            ORDER BY sales_count DESC, avg_rating DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function segmentCustomers() {
        // Clear existing segments
        self::$db->exec("DELETE FROM customer_segments");
        
        // High-value customers
        $stmt = self::$db->prepare("
            INSERT INTO customer_segments (user_id, segment_name, segment_value)
            SELECT user_id, 'high_value', CONCAT('$', total_spent)
            FROM customer_loyalty
            WHERE total_spent > 200
        ");
        $stmt->execute();
        
        // Frequent buyers
        $stmt = self::$db->prepare("
            INSERT INTO customer_segments (user_id, segment_name, segment_value)
            SELECT user_id, 'frequent_buyer', total_orders
            FROM customer_loyalty
            WHERE total_orders >= 5
        ");
        $stmt->execute();
        
        // At-risk customers (no purchase in 60 days)
        $stmt = self::$db->prepare("
            INSERT INTO customer_segments (user_id, segment_name, segment_value)
            SELECT user_id, 'at_risk', DATEDIFF(CURDATE(), last_purchase)
            FROM customer_loyalty
            WHERE last_purchase < DATE_SUB(CURDATE(), INTERVAL 60 DAY)
        ");
        $stmt->execute();
        
        // New customers (first purchase within 30 days)
        $stmt = self::$db->prepare("
            INSERT INTO customer_segments (user_id, segment_name, segment_value)
            SELECT user_id, 'new_customer', DATEDIFF(CURDATE(), last_purchase)
            FROM customer_loyalty
            WHERE last_purchase >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            AND total_orders = 1
        ");
        $stmt->execute();
    }
    
    public static function createEmailCampaign($name, $subject, $content, $campaign_type, $target_segment) {
        $stmt = self::$db->prepare("
            INSERT INTO email_campaigns (name, subject, content, campaign_type, target_segment)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $subject, $content, $campaign_type, $target_segment]);
        
        return self::$db->lastInsertId();
    }
    
    public static function sendAutomatedEmails() {
        // Welcome email for new customers
        self::sendWelcomeEmails();
        
        // Win-back emails for at-risk customers
        self::sendWinBackEmails();
        
        // Wishlist notifications
        self::sendWishlistNotifications();
        
        // Review requests
        self::sendReviewRequests();
    }
    
    private static function sendWelcomeEmails() {
        $stmt = self::$db->prepare("
            SELECT DISTINCT u.id, u.email, u.first_name
            FROM users u
            JOIN customer_segments cs ON u.id = cs.user_id
            WHERE cs.segment_name = 'new_customer'
            AND u.id NOT IN (
                SELECT user_id FROM email_log 
                WHERE email_type = 'welcome' 
                AND sent_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            )
        ");
        $stmt->execute();
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($customers as $customer) {
            $subject = "Welcome to Bort's Books, " . $customer['first_name'] . "!";
            $content = self::generateWelcomeEmail($customer);
            
            // Send email (implement your email sending logic)
            self::sendEmail($customer['email'], $subject, $content);
            self::logEmail($customer['id'], 'welcome', $subject);
        }
    }
    
    private static function sendWinBackEmails() {
        $stmt = self::$db->prepare("
            SELECT DISTINCT u.id, u.email, u.first_name, cl.total_spent
            FROM users u
            JOIN customer_segments cs ON u.id = cs.user_id
            JOIN customer_loyalty cl ON u.id = cl.user_id
            WHERE cs.segment_name = 'at_risk'
            AND u.id NOT IN (
                SELECT user_id FROM email_log 
                WHERE email_type = 'winback' 
                AND sent_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            )
        ");
        $stmt->execute();
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($customers as $customer) {
            $discount = $customer['total_spent'] > 100 ? 20 : 15;
            $subject = "We miss you! Here's " . $discount . "% off your next order";
            $content = self::generateWinBackEmail($customer, $discount);
            
            self::sendEmail($customer['email'], $subject, $content);
            self::logEmail($customer['id'], 'winback', $subject);
        }
    }
    
    private static function sendWishlistNotifications() {
        $stmt = self::$db->prepare("
            SELECT DISTINCT u.id, u.email, u.first_name, p.title, p.price
            FROM users u
            JOIN wishlist w ON u.id = w.user_id
            JOIN products p ON w.product_id = p.id
            WHERE w.notified = FALSE
            AND p.stock_quantity > 0
            AND w.added_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($notifications as $notification) {
            $subject = "Good news! " . $notification['title'] . " is back in stock";
            $content = self::generateWishlistEmail($notification);
            
            self::sendEmail($notification['email'], $subject, $content);
            
            // Mark as notified
            $stmt = self::$db->prepare("UPDATE wishlist SET notified = TRUE WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$notification['id'], $notification['product_id']]);
        }
    }
    
    private static function generateWelcomeEmail($customer) {
        return "
        <h2>Welcome to Bort's Books, {$customer['first_name']}!</h2>
        <p>Thank you for joining our manga community. We're excited to help you discover amazing manga titles!</p>
        
        <h3>Your Welcome Bonus:</h3>
        <ul>
            <li>10% off your next purchase with code: WELCOME10</li>
            <li>Free shipping on orders over $50</li>
            <li>Exclusive access to rare manga releases</li>
        </ul>
        
        <h3>Start Exploring:</h3>
        <p>Check out our most popular manga series and discover your next favorite read!</p>
        
        <a href='" . SITE_URL . "/pages/shop.php' style='background: #232946; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;'>Shop Now</a>
        ";
    }
    
    private static function generateWinBackEmail($customer, $discount) {
        return "
        <h2>We miss you, {$customer['first_name']}!</h2>
        <p>It's been a while since your last visit to Bort's Books. We have some exciting new manga arrivals we think you'll love!</p>
        
        <h3>Special Offer Just for You:</h3>
        <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;'>
            <h2 style='color: #232946;'>{$discount}% OFF</h2>
            <p>Use code: COMEBACK{$discount}</p>
            <p>Valid for the next 7 days</p>
        </div>
        
        <h3>New Arrivals You Might Like:</h3>
        <p>Based on your previous purchases, we've curated some recommendations just for you.</p>
        
        <a href='" . SITE_URL . "/pages/shop.php?discount=COMEBACK{$discount}' style='background: #232946; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;'>Shop Now</a>
        ";
    }
    
    private static function sendEmail($to, $subject, $content) {
        // Implement your email sending logic here
        // This could use PHPMailer, SendGrid, or another email service
        
        $headers = [
                            'From: Bort\'s Books <bort@bortsbooks.com>',
            'Content-Type: text/html; charset=UTF-8',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        return mail($to, $subject, $content, implode("\r\n", $headers));
    }
    
    private static function logEmail($user_id, $email_type, $subject) {
        $stmt = self::$db->prepare("
            INSERT INTO email_log (user_id, email_type, subject, sent_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $email_type, $subject]);
    }
    
    public static function addToWishlist($user_id, $product_id) {
        $stmt = self::$db->prepare("
            INSERT IGNORE INTO wishlist (user_id, product_id)
            VALUES (?, ?)
        ");
        return $stmt->execute([$user_id, $product_id]);
    }
    
    public static function removeFromWishlist($user_id, $product_id) {
        $stmt = self::$db->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        return $stmt->execute([$user_id, $product_id]);
    }
    
    public static function getWishlist($user_id) {
        $stmt = self::$db->prepare("
            SELECT p.*, w.added_at
            FROM wishlist w
            JOIN products p ON w.product_id = p.id
            WHERE w.user_id = ?
            ORDER BY w.added_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function addReview($user_id, $product_id, $order_id, $rating, $review_text) {
        $stmt = self::$db->prepare("
            INSERT INTO customer_reviews (user_id, product_id, order_id, rating, review_text, verified_purchase)
            VALUES (?, ?, ?, ?, ?, TRUE)
        ");
        $result = $stmt->execute([$user_id, $product_id, $order_id, $rating, $review_text]);
        
        if ($result) {
            // Update customer preferences
            self::updateCustomerPreferences($user_id, $product_id, 'review');
            
            // Award loyalty points for review
            $stmt = self::$db->prepare("UPDATE customer_loyalty SET points = points + 10 WHERE user_id = ?");
            $stmt->execute([$user_id]);
        }
        
        return $result;
    }
    
    public static function getCustomerInsights($user_id) {
        $insights = [];
        
        // Loyalty status
        $insights['loyalty'] = self::getCustomerLoyalty($user_id);
        
        // Purchase history insights
        $stmt = self::$db->prepare("
            SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                SUM(oi.quantity * oi.price) as total_spent,
                AVG(oi.quantity * oi.price) as avg_order_value,
                MAX(o.created_at) as last_order_date,
                COUNT(DISTINCT oi.product_id) as unique_products
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $insights['purchase_history'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Favorite categories
        $stmt = self::$db->prepare("
            SELECT category, preference_score
            FROM customer_preferences
            WHERE user_id = ?
            ORDER BY preference_score DESC
            LIMIT 3
        ");
        $stmt->execute([$user_id]);
        $insights['favorite_categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Recommendations
        $insights['recommendations'] = self::getPersonalizedRecommendations($user_id, 5);
        
        return $insights;
    }
}

// Create email log table
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            email_type VARCHAR(50),
            subject VARCHAR(255),
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_type (email_type)
        )
    ");
} catch (PDOException $e) {
    error_log("Email log table creation failed: " . $e->getMessage());
}
?> 