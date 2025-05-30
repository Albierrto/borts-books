<?php
/**
 * Inventory Management System for Bort's Books
 * Handles stock tracking, low stock alerts, and inventory analytics
 */

class InventoryManager {
    private $db;
    private $low_stock_threshold = 5; // Default threshold
    
    public function __construct($database) {
        $this->db = $database;
        $this->createInventoryTables();
    }
    
    /**
     * Create necessary inventory tables
     */
    private function createInventoryTables() {
        $tables = [
            'inventory_settings' => "
                CREATE TABLE IF NOT EXISTS inventory_settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    product_id INT NOT NULL,
                    low_stock_threshold INT DEFAULT 5,
                    reorder_point INT DEFAULT 10,
                    max_stock_level INT DEFAULT 100,
                    supplier_info TEXT,
                    last_restock_date DATE,
                    auto_reorder BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_product (product_id)
                )
            ",
            
            'inventory_logs' => "
                CREATE TABLE IF NOT EXISTS inventory_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    product_id INT NOT NULL,
                    action_type ENUM('sale', 'restock', 'adjustment', 'return') NOT NULL,
                    quantity_change INT NOT NULL,
                    old_quantity INT NOT NULL,
                    new_quantity INT NOT NULL,
                    reason TEXT,
                    admin_user VARCHAR(100),
                    order_id VARCHAR(50),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                    INDEX idx_product_date (product_id, created_at),
                    INDEX idx_action_type (action_type)
                )
            ",
            
            'low_stock_alerts' => "
                CREATE TABLE IF NOT EXISTS low_stock_alerts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    product_id INT NOT NULL,
                    current_stock INT NOT NULL,
                    threshold_level INT NOT NULL,
                    alert_sent BOOLEAN DEFAULT FALSE,
                    alert_sent_at TIMESTAMP NULL,
                    resolved BOOLEAN DEFAULT FALSE,
                    resolved_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                    INDEX idx_product_alert (product_id, alert_sent),
                    INDEX idx_resolved (resolved)
                )
            "
        ];
        
        foreach ($tables as $table_name => $sql) {
            try {
                $this->db->exec($sql);
            } catch (PDOException $e) {
                error_log("Inventory table creation failed for $table_name: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Update product stock after sale
     */
    public function recordSale($product_id, $quantity_sold, $order_id = null) {
        try {
            $this->db->beginTransaction();
            
            // Get current stock
            $stmt = $this->db->prepare("SELECT stock_quantity FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $current_stock = $stmt->fetchColumn();
            
            if ($current_stock === false) {
                throw new Exception("Product not found");
            }
            
            if ($current_stock < $quantity_sold) {
                throw new Exception("Insufficient stock");
            }
            
            // Update product stock
            $new_stock = $current_stock - $quantity_sold;
            $stmt = $this->db->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
            $stmt->execute([$new_stock, $product_id]);
            
            // Log the transaction
            $this->logInventoryChange(
                $product_id, 
                'sale', 
                -$quantity_sold, 
                $current_stock, 
                $new_stock, 
                "Sale of $quantity_sold units",
                null,
                $order_id
            );
            
            // Check for low stock
            $this->checkLowStock($product_id, $new_stock);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Sale recording failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Restock product inventory
     */
    public function restockProduct($product_id, $quantity_added, $reason = "Manual restock", $admin_user = null) {
        try {
            $this->db->beginTransaction();
            
            // Get current stock
            $stmt = $this->db->prepare("SELECT stock_quantity FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $current_stock = $stmt->fetchColumn();
            
            if ($current_stock === false) {
                throw new Exception("Product not found");
            }
            
            // Update product stock
            $new_stock = $current_stock + $quantity_added;
            $stmt = $this->db->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
            $stmt->execute([$new_stock, $product_id]);
            
            // Log the transaction
            $this->logInventoryChange(
                $product_id, 
                'restock', 
                $quantity_added, 
                $current_stock, 
                $new_stock, 
                $reason,
                $admin_user
            );
            
            // Update last restock date
            $stmt = $this->db->prepare("
                INSERT INTO inventory_settings (product_id, last_restock_date) 
                VALUES (?, CURDATE()) 
                ON DUPLICATE KEY UPDATE last_restock_date = CURDATE()
            ");
            $stmt->execute([$product_id]);
            
            // Resolve low stock alerts
            $this->resolveLowStockAlert($product_id);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Restock failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log inventory changes
     */
    private function logInventoryChange($product_id, $action_type, $quantity_change, $old_quantity, $new_quantity, $reason = null, $admin_user = null, $order_id = null) {
        $stmt = $this->db->prepare("
            INSERT INTO inventory_logs 
            (product_id, action_type, quantity_change, old_quantity, new_quantity, reason, admin_user, order_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $product_id, 
            $action_type, 
            $quantity_change, 
            $old_quantity, 
            $new_quantity, 
            $reason, 
            $admin_user, 
            $order_id
        ]);
    }
    
    /**
     * Check for low stock and create alerts
     */
    private function checkLowStock($product_id, $current_stock) {
        // Get threshold for this product
        $stmt = $this->db->prepare("
            SELECT low_stock_threshold 
            FROM inventory_settings 
            WHERE product_id = ?
        ");
        $stmt->execute([$product_id]);
        $threshold = $stmt->fetchColumn();
        
        if (!$threshold) {
            $threshold = $this->low_stock_threshold;
        }
        
        if ($current_stock <= $threshold) {
            // Check if alert already exists
            $stmt = $this->db->prepare("
                SELECT id FROM low_stock_alerts 
                WHERE product_id = ? AND resolved = FALSE
            ");
            $stmt->execute([$product_id]);
            
            if (!$stmt->fetchColumn()) {
                // Create new alert
                $stmt = $this->db->prepare("
                    INSERT INTO low_stock_alerts 
                    (product_id, current_stock, threshold_level) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$product_id, $current_stock, $threshold]);
                
                // Send notification
                $this->sendLowStockNotification($product_id, $current_stock, $threshold);
            }
        }
    }
    
    /**
     * Send low stock notification
     */
    private function sendLowStockNotification($product_id, $current_stock, $threshold) {
        try {
            // Get product details
            $stmt = $this->db->prepare("SELECT title, price FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) return;
            
            // Create email content
            $subject = "🚨 LOW STOCK ALERT - " . $product['title'];
            $message = "
                <h2>Low Stock Alert</h2>
                <p><strong>Product:</strong> {$product['title']}</p>
                <p><strong>Current Stock:</strong> {$current_stock} units</p>
                <p><strong>Threshold:</strong> {$threshold} units</p>
                <p><strong>Price:</strong> $" . number_format($product['price'], 2) . "</p>
                <p><strong>Action Required:</strong> Consider restocking this item soon.</p>
                <p><a href='" . $_SERVER['HTTP_HOST'] . "/pages/admin.php?product_id={$product_id}'>Manage Product</a></p>
            ";
            
            // Send email (you'll need to implement your email system)
            $this->sendEmail('admin@bortsbooks.com', $subject, $message);
            
            // Mark alert as sent
            $stmt = $this->db->prepare("
                UPDATE low_stock_alerts 
                SET alert_sent = TRUE, alert_sent_at = NOW() 
                WHERE product_id = ? AND resolved = FALSE
            ");
            $stmt->execute([$product_id]);
            
        } catch (Exception $e) {
            error_log("Low stock notification failed: " . $e->getMessage());
        }
    }
    
    /**
     * Resolve low stock alert
     */
    private function resolveLowStockAlert($product_id) {
        $stmt = $this->db->prepare("
            UPDATE low_stock_alerts 
            SET resolved = TRUE, resolved_at = NOW() 
            WHERE product_id = ? AND resolved = FALSE
        ");
        $stmt->execute([$product_id]);
    }
    
    /**
     * Get low stock products
     */
    public function getLowStockProducts($limit = 50) {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.title,
                p.price,
                p.stock_quantity,
                COALESCE(inv.low_stock_threshold, ?) as threshold,
                p.stock_quantity <= COALESCE(inv.low_stock_threshold, ?) as is_low_stock,
                inv.last_restock_date
            FROM products p
            LEFT JOIN inventory_settings inv ON p.id = inv.product_id
            WHERE p.stock_quantity <= COALESCE(inv.low_stock_threshold, ?)
            ORDER BY p.stock_quantity ASC, p.title ASC
            LIMIT ?
        ");
        
        $stmt->execute([$this->low_stock_threshold, $this->low_stock_threshold, $this->low_stock_threshold, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get inventory analytics
     */
    public function getInventoryAnalytics($days = 30) {
        $analytics = [];
        
        // Total products and stock value
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_products,
                SUM(stock_quantity) as total_units,
                SUM(stock_quantity * price) as total_value,
                AVG(stock_quantity) as avg_stock_per_product
            FROM products 
            WHERE stock_quantity > 0
        ");
        $stmt->execute();
        $analytics['overview'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Low stock count
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as low_stock_count
            FROM products p
            LEFT JOIN inventory_settings inv ON p.id = inv.product_id
            WHERE p.stock_quantity <= COALESCE(inv.low_stock_threshold, ?)
        ");
        $stmt->execute([$this->low_stock_threshold]);
        $analytics['low_stock_count'] = $stmt->fetchColumn();
        
        // Recent sales activity
        $stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) as sale_date,
                SUM(ABS(quantity_change)) as units_sold,
                COUNT(*) as transactions
            FROM inventory_logs 
            WHERE action_type = 'sale' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY sale_date DESC
        ");
        $stmt->execute([$days]);
        $analytics['recent_sales'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Top selling products
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.title,
                p.price,
                SUM(ABS(il.quantity_change)) as units_sold,
                SUM(ABS(il.quantity_change) * p.price) as revenue
            FROM inventory_logs il
            JOIN products p ON il.product_id = p.id
            WHERE il.action_type = 'sale' 
            AND il.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY p.id, p.title, p.price
            ORDER BY units_sold DESC
            LIMIT 10
        ");
        $stmt->execute([$days]);
        $analytics['top_sellers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $analytics;
    }
    
    /**
     * Set inventory settings for a product
     */
    public function setProductInventorySettings($product_id, $settings) {
        $stmt = $this->db->prepare("
            INSERT INTO inventory_settings 
            (product_id, low_stock_threshold, reorder_point, max_stock_level, supplier_info, auto_reorder) 
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                low_stock_threshold = VALUES(low_stock_threshold),
                reorder_point = VALUES(reorder_point),
                max_stock_level = VALUES(max_stock_level),
                supplier_info = VALUES(supplier_info),
                auto_reorder = VALUES(auto_reorder)
        ");
        
        return $stmt->execute([
            $product_id,
            $settings['low_stock_threshold'] ?? $this->low_stock_threshold,
            $settings['reorder_point'] ?? 10,
            $settings['max_stock_level'] ?? 100,
            $settings['supplier_info'] ?? null,
            $settings['auto_reorder'] ?? false
        ]);
    }
    
    /**
     * Get inventory history for a product
     */
    public function getProductInventoryHistory($product_id, $limit = 50) {
        $stmt = $this->db->prepare("
            SELECT 
                action_type,
                quantity_change,
                old_quantity,
                new_quantity,
                reason,
                admin_user,
                order_id,
                created_at
            FROM inventory_logs 
            WHERE product_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        
        $stmt->execute([$product_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Simple email function (replace with your email system)
     */
    private function sendEmail($to, $subject, $message) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Bort's Books <noreply@bortsbooks.com>" . "\r\n";
        
        return mail($to, $subject, $message, $headers);
    }
    
    /**
     * Export inventory data to CSV
     */
    public function exportInventoryCSV() {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.title,
                p.price,
                p.stock_quantity,
                p.condition_type,
                COALESCE(inv.low_stock_threshold, ?) as threshold,
                inv.reorder_point,
                inv.last_restock_date,
                p.created_at
            FROM products p
            LEFT JOIN inventory_settings inv ON p.id = inv.product_id
            ORDER BY p.title ASC
        ");
        
        $stmt->execute([$this->low_stock_threshold]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filename = 'inventory_export_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = '../exports/' . $filename;
        
        // Create exports directory if it doesn't exist
        if (!file_exists('../exports/')) {
            mkdir('../exports/', 0755, true);
        }
        
        $file = fopen($filepath, 'w');
        
        // Add CSV headers
        fputcsv($file, [
            'Product ID', 'Title', 'Price', 'Stock Quantity', 'Condition', 
            'Low Stock Threshold', 'Reorder Point', 'Last Restock', 'Created Date'
        ]);
        
        // Add data rows
        foreach ($products as $product) {
            fputcsv($file, [
                $product['id'],
                $product['title'],
                $product['price'],
                $product['stock_quantity'],
                $product['condition_type'],
                $product['threshold'],
                $product['reorder_point'],
                $product['last_restock_date'],
                $product['created_at']
            ]);
        }
        
        fclose($file);
        return $filepath;
    }
}
?> 