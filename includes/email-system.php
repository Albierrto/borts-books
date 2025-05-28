<?php
/**
 * Email Marketing & Customer Tracking System
 * Handles newsletter subscriptions, customer tracking, and email campaigns
 */

class EmailSystem {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Add subscriber to newsletter
     */
    public function addSubscriber($email, $name = null, $source = 'homepage') {
        try {
            // Generate unsubscribe token
            $unsubscribe_token = bin2hex(random_bytes(32));
            
            $stmt = $this->db->prepare("
                INSERT INTO newsletter_subscribers (email, name, source, unsubscribe_token) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    name = COALESCE(VALUES(name), name),
                    is_active = TRUE,
                    unsubscribed_at = NULL
            ");
            
            $result = $stmt->execute([$email, $name, $source, $unsubscribe_token]);
            
            if ($result) {
                error_log("Newsletter signup: $email from $source");
                return ['success' => true, 'message' => 'Successfully subscribed to newsletter!'];
            }
            
            return ['success' => false, 'message' => 'Failed to subscribe. Please try again.'];
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                return ['success' => true, 'message' => 'You are already subscribed to our newsletter!'];
            }
            error_log("Newsletter signup error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }
    
    /**
     * Unsubscribe user from newsletter
     */
    public function unsubscribe($token) {
        try {
            $stmt = $this->db->prepare("
                UPDATE newsletter_subscribers 
                SET is_active = FALSE, unsubscribed_at = NOW() 
                WHERE unsubscribe_token = ?
            ");
            
            $result = $stmt->execute([$token]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'You have been unsubscribed from our newsletter.'];
            }
            
            return ['success' => false, 'message' => 'Invalid unsubscribe link.'];
            
        } catch (PDOException $e) {
            error_log("Unsubscribe error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during unsubscription.'];
        }
    }
    
    /**
     * Track customer purchase and update records
     */
    public function trackCustomerPurchase($email, $name, $orderTotal, $shippingAddress = null) {
        try {
            // Check if customer exists
            $stmt = $this->db->prepare("SELECT * FROM customer_emails WHERE email = ?");
            $stmt->execute([$email]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($customer) {
                // Update existing customer
                $stmt = $this->db->prepare("
                    UPDATE customer_emails 
                    SET last_purchase_date = NOW(),
                        total_orders = total_orders + 1,
                        total_spent = total_spent + ?,
                        name = COALESCE(?, name),
                        preferred_shipping_address = COALESCE(?, preferred_shipping_address)
                    WHERE email = ?
                ");
                $stmt->execute([$orderTotal, $name, $shippingAddress, $email]);
            } else {
                // Create new customer record
                $stmt = $this->db->prepare("
                    INSERT INTO customer_emails 
                    (email, name, total_spent, preferred_shipping_address) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$email, $name, $orderTotal, $shippingAddress]);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Customer tracking error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate unique order number
     */
    public function generateOrderNumber() {
        $prefix = 'BB'; // Bort's Books
        $timestamp = time();
        $random = rand(100, 999);
        
        do {
            $orderNumber = $prefix . date('y', $timestamp) . sprintf('%05d', $random);
            
            // Check if this order number already exists
            $stmt = $this->db->prepare("SELECT id FROM orders WHERE order_number = ?");
            $stmt->execute([$orderNumber]);
            
            if (!$stmt->fetch()) {
                return $orderNumber; // Unique number found
            }
            
            $random = rand(100, 999); // Try different random number
        } while ($random < 10000); // Safety limit
        
        // Fallback to timestamp-based number
        return $prefix . date('ymdHis');
    }
    
    /**
     * Get order by email and order number
     */
    public function getOrderByEmailAndNumber($email, $orderNumber) {
        try {
            $stmt = $this->db->prepare("
                SELECT o.*, 
                       GROUP_CONCAT(
                           CONCAT(oi.product_title, ' (', oi.quantity, 'x $', oi.price, ')')
                           SEPARATOR ', '
                       ) as items
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.customer_email = ? AND o.order_number = ?
                GROUP BY o.id
            ");
            
            $stmt->execute([$email, $orderNumber]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Order lookup error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get customer purchase history by email
     */
    public function getCustomerHistory($email, $limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT o.*, 
                       COUNT(oi.id) as item_count,
                       SUM(oi.quantity) as total_items
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.customer_email = ?
                GROUP BY o.id
                ORDER BY o.created_at DESC
                LIMIT ?
            ");
            
            $stmt->execute([$email, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Customer history error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get newsletter subscriber stats
     */
    public function getSubscriberStats() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total_subscribers,
                    COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_subscribers,
                    COUNT(CASE WHEN is_active = 0 THEN 1 END) as unsubscribed,
                    COUNT(CASE WHEN DATE(subscribed_at) = CURDATE() THEN 1 END) as today_signups,
                    COUNT(CASE WHEN DATE(subscribed_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as week_signups
                FROM newsletter_subscribers
            ");
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Subscriber stats error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get active subscribers for email campaigns
     */
    public function getActiveSubscribers($limit = null) {
        try {
            $sql = "SELECT email, name FROM newsletter_subscribers WHERE is_active = 1 ORDER BY subscribed_at DESC";
            if ($limit) {
                $sql .= " LIMIT " . intval($limit);
            }
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Active subscribers error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Send order confirmation email (basic version)
     */
    public function sendOrderConfirmation($customerEmail, $orderNumber, $orderTotal, $items) {
        // This is a placeholder for now - in production you'd integrate with SendGrid, Mailgun, etc.
        $subject = "Order Confirmation - $orderNumber - Bort's Books";
        
        $message = "
        <h2>Thank you for your order!</h2>
        <p><strong>Order Number:</strong> $orderNumber</p>
        <p><strong>Total:</strong> $$orderTotal</p>
        
        <h3>Items Ordered:</h3>
        <ul>$items</ul>
        
        <p>You can track your order anytime by visiting: <a href='" . $_SERVER['HTTP_HOST'] . "/pages/track-order.php'>Track My Order</a></p>
        
        <p>Thank you for shopping with Bort's Books!</p>
        ";
        
        // Log the email (replace with actual email sending in production)
        error_log("Order confirmation email for $customerEmail: $subject");
        
        return true; // Return true for now
    }
}

/**
 * Handle AJAX newsletter signup
 */
if (isset($_POST['action']) && $_POST['action'] === 'newsletter_signup') {
    require_once 'db.php';
    
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $name = trim($_POST['name'] ?? '');
    $source = $_POST['source'] ?? 'homepage';
    
    if (!$email) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        exit;
    }
    
    $emailSystem = new EmailSystem($db);
    $result = $emailSystem->addSubscriber($email, $name, $source);
    
    echo json_encode($result);
    exit;
}

/**
 * Handle unsubscribe requests
 */
if (isset($_GET['unsubscribe']) && isset($_GET['token'])) {
    require_once 'db.php';
    
    $emailSystem = new EmailSystem($db);
    $result = $emailSystem->unsubscribe($_GET['token']);
    
    // Show unsubscribe page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Unsubscribe - Bort's Books</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 4rem auto; padding: 2rem; text-align: center; }
            .success { color: #28a745; }
            .error { color: #dc3545; }
        </style>
    </head>
    <body>
        <h1>Newsletter Unsubscribe</h1>
        <p class="<?php echo $result['success'] ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($result['message']); ?>
        </p>
        <p><a href="/index.php">Return to Bort's Books</a></p>
    </body>
    </html>
    <?php
    exit;
}
?> 