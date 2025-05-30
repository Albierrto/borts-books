<?php
/**
 * Order-Inventory Integration for Bort's Books
 * Automatically updates inventory when orders are placed
 */

require_once 'inventory-manager.php';
require_once 'email-system.php';

class OrderInventoryIntegration {
    private $db;
    private $inventoryManager;
    private $emailSystem;
    
    public function __construct($database) {
        $this->db = $database;
        $this->inventoryManager = new InventoryManager($database);
        $this->emailSystem = new EmailSystem($database);
    }
    
    /**
     * Process order and update inventory
     */
    public function processOrderInventory($order_data) {
        try {
            $this->db->beginTransaction();
            
            // Generate unique order number
            $order_number = $this->generateOrderNumber();
            
            // Save order to database with inventory tracking
            $order_id = $this->saveOrderWithInventory($order_data, $order_number);
            
            if (!$order_id) {
                throw new Exception("Failed to save order");
            }
            
            // Process cart items and update inventory
            $cart_items = $_SESSION['cart'] ?? [];
            $inventory_updates = [];
            
            foreach ($cart_items as $item) {
                $product_id = $item['id'];
                $quantity = $item['quantity'];
                
                // Check stock availability
                if (!$this->checkStockAvailability($product_id, $quantity)) {
                    throw new Exception("Insufficient stock for product ID: $product_id");
                }
                
                // Record the sale in inventory
                if ($this->inventoryManager->recordSale($product_id, $quantity, $order_number)) {
                    $inventory_updates[] = [
                        'product_id' => $product_id,
                        'quantity' => $quantity,
                        'status' => 'success'
                    ];
                } else {
                    throw new Exception("Failed to update inventory for product ID: $product_id");
                }
            }
            
            // Update order with inventory status
            $this->updateOrderInventoryStatus($order_id, 'processed');
            
            // Send order confirmation email
            $this->sendOrderConfirmationEmail($order_data, $order_number, $cart_items);
            
            // Track customer for email marketing
            if (!empty($order_data['customer_email'])) {
                $this->emailSystem->trackCustomerPurchase(
                    $order_data['customer_email'],
                    $order_data['customer_name'] ?? '',
                    $order_number,
                    $order_data['amount_total'] ?? 0
                );
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'order_id' => $order_id,
                'order_number' => $order_number,
                'inventory_updates' => $inventory_updates
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Order inventory processing failed: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if sufficient stock is available
     */
    private function checkStockAvailability($product_id, $quantity_needed) {
        $stmt = $this->db->prepare("SELECT stock_quantity FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $current_stock = $stmt->fetchColumn();
        
        return $current_stock !== false && $current_stock >= $quantity_needed;
    }
    
    /**
     * Save order with inventory tracking
     */
    private function saveOrderWithInventory($order_data, $order_number) {
        $stmt = $this->db->prepare("
            INSERT INTO orders (
                order_number, 
                stripe_session_id, 
                customer_name, 
                customer_email, 
                total_amount, 
                payment_status,
                inventory_status,
                shipping_address,
                shipping_method,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $shipping_address = json_encode([
            'address' => $order_data['shipping_address'] ?? '',
            'city' => $order_data['shipping_city'] ?? '',
            'state' => $order_data['shipping_state'] ?? '',
            'zip' => $order_data['shipping_zip'] ?? ''
        ]);
        
        $stmt->execute([
            $order_number,
            $order_data['session_id'] ?? '',
            $order_data['customer_name'] ?? '',
            $order_data['customer_email'] ?? '',
            $order_data['amount_total'] ?? 0,
            'paid',
            'pending',
            $shipping_address,
            $order_data['shipping_method'] ?? ''
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update order inventory status
     */
    private function updateOrderInventoryStatus($order_id, $status) {
        $stmt = $this->db->prepare("UPDATE orders SET inventory_status = ? WHERE id = ?");
        return $stmt->execute([$status, $order_id]);
    }
    
    /**
     * Generate unique order number
     */
    private function generateOrderNumber() {
        $prefix = 'BB' . date('y'); // BB24 for 2024
        $random = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        return $prefix . $random;
    }
    
    /**
     * Send order confirmation email
     */
    private function sendOrderConfirmationEmail($order_data, $order_number, $cart_items) {
        try {
            $customer_email = $order_data['customer_email'] ?? '';
            $customer_name = $order_data['customer_name'] ?? 'Customer';
            
            if (empty($customer_email)) {
                return false;
            }
            
            // Build order items HTML
            $items_html = '';
            $subtotal = 0;
            
            foreach ($cart_items as $item) {
                $item_total = $item['price'] * $item['quantity'];
                $subtotal += $item_total;
                
                $items_html .= "
                    <tr>
                        <td style='padding: 10px; border-bottom: 1px solid #eee;'>
                            " . htmlspecialchars($item['title']) . "
                        </td>
                        <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>
                            {$item['quantity']}
                        </td>
                        <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>
                            $" . number_format($item['price'], 2) . "
                        </td>
                        <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>
                            $" . number_format($item_total, 2) . "
                        </td>
                    </tr>
                ";
            }
            
            $shipping_cost = ($order_data['amount_total'] ?? 0) - $subtotal;
            
            $subject = "Order Confirmation - Order #$order_number";
            $message = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                        .content { background: #f9f9f9; padding: 20px; }
                        .order-table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; }
                        .order-table th { background: #667eea; color: white; padding: 12px; text-align: left; }
                        .total-row { font-weight: bold; background: #f0f0f0; }
                        .footer { background: #333; color: white; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; }
                        .btn { background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; margin: 10px 0; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>🎉 Order Confirmed!</h1>
                            <p>Thank you for your order, " . htmlspecialchars($customer_name) . "!</p>
                        </div>
                        
                        <div class='content'>
                            <h2>Order Details</h2>
                            <p><strong>Order Number:</strong> $order_number</p>
                            <p><strong>Order Date:</strong> " . date('F j, Y g:i A') . "</p>
                            <p><strong>Email:</strong> " . htmlspecialchars($customer_email) . "</p>
                            
                            <table class='order-table'>
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Qty</th>
                                        <th>Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    $items_html
                                    <tr>
                                        <td colspan='3' style='padding: 10px; text-align: right; font-weight: bold;'>Subtotal:</td>
                                        <td style='padding: 10px; text-align: right; font-weight: bold;'>$" . number_format($subtotal, 2) . "</td>
                                    </tr>
                                    <tr>
                                        <td colspan='3' style='padding: 10px; text-align: right; font-weight: bold;'>Shipping:</td>
                                        <td style='padding: 10px; text-align: right; font-weight: bold;'>$" . number_format($shipping_cost, 2) . "</td>
                                    </tr>
                                    <tr class='total-row'>
                                        <td colspan='3' style='padding: 15px; text-align: right; font-size: 1.2em;'>Total:</td>
                                        <td style='padding: 15px; text-align: right; font-size: 1.2em;'>$" . number_format($order_data['amount_total'] ?? 0, 2) . "</td>
                                    </tr>
                                </tbody>
                            </table>
                            
                            <h3>What's Next?</h3>
                            <p>✅ <strong>Order Confirmed</strong> - We've received your payment</p>
                            <p>📦 <strong>Processing</strong> - We'll prepare your order within 24 hours</p>
                            <p>🚚 <strong>Shipping</strong> - You'll receive tracking information once shipped</p>
                            <p>📬 <strong>Delivery</strong> - Your order should arrive within 3-5 business days</p>
                            
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='https://bortsbooks.com/pages/track-order.php' class='btn'>Track Your Order</a>
                            </div>
                            
                            <p><strong>Questions?</strong> Reply to this email or contact us at support@bortsbooks.com</p>
                        </div>
                        
                        <div class='footer'>
                            <p><strong>Bort's Books</strong></p>
                            <p>Your trusted source for manga and books</p>
                            <p>🌟 Lowest prices guaranteed | 📚 Authentic products | 🚚 Fast shipping</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: Bort's Books <orders@bortsbooks.com>" . "\r\n";
            $headers .= "Reply-To: support@bortsbooks.com" . "\r\n";
            
            return mail($customer_email, $subject, $message, $headers);
            
        } catch (Exception $e) {
            error_log("Order confirmation email failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get order details with inventory status
     */
    public function getOrderDetails($order_number) {
        $stmt = $this->db->prepare("
            SELECT 
                o.*,
                GROUP_CONCAT(
                    CONCAT(p.title, ' (Qty: ', oi.quantity, ')')
                    SEPARATOR ', '
                ) as items
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE o.order_number = ?
            GROUP BY o.id
        ");
        
        $stmt->execute([$order_number]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get low stock report for admin
     */
    public function getLowStockReport() {
        return $this->inventoryManager->getLowStockProducts();
    }
    
    /**
     * Manual inventory adjustment (for admin use)
     */
    public function adjustInventory($product_id, $quantity_change, $reason, $admin_user) {
        try {
            $this->db->beginTransaction();
            
            // Get current stock
            $stmt = $this->db->prepare("SELECT stock_quantity FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $current_stock = $stmt->fetchColumn();
            
            if ($current_stock === false) {
                throw new Exception("Product not found");
            }
            
            $new_stock = $current_stock + $quantity_change;
            
            if ($new_stock < 0) {
                throw new Exception("Cannot reduce stock below zero");
            }
            
            // Update product stock
            $stmt = $this->db->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
            $stmt->execute([$new_stock, $product_id]);
            
            // Log the adjustment
            $stmt = $this->db->prepare("
                INSERT INTO inventory_logs 
                (product_id, action_type, quantity_change, old_quantity, new_quantity, reason, admin_user) 
                VALUES (?, 'adjustment', ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$product_id, $quantity_change, $current_stock, $new_stock, $reason, $admin_user]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Inventory adjustment failed: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Helper function to process order after successful payment
 */
function processOrderWithInventory($order_data) {
    global $db;
    
    $integration = new OrderInventoryIntegration($db);
    return $integration->processOrderInventory($order_data);
}
?> 