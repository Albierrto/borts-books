<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/email-system.php';

// Initialize EmailSystem
$emailSystem = new EmailSystem($db);

$message = '';
$testOrders = [];
$action = $_GET['action'] ?? '';

// Handle different test actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_test_order':
            $result = createTestOrder();
            $message = $result['message'];
            break;
            
        case 'test_tracking':
            $result = testOrderTracking();
            $message = $result['message'];
            break;
            
        case 'cleanup_test_data':
            $result = cleanupTestData();
            $message = $result['message'];
            break;
    }
}

// Get existing test orders
$testOrders = getTestOrders();

function createTestOrder() {
    global $db, $emailSystem;
    
    try {
        // Generate test order data
        $orderNumber = $emailSystem->generateOrderNumber();
        $testEmail = 'test@bortsbooks.com';
        $testName = 'Test Customer';
        $testAmount = 29.99;
        $testSessionId = 'cs_test_' . bin2hex(random_bytes(16));
        
        // Create test order in database
        $stmt = $db->prepare("
            INSERT INTO orders (
                order_number, 
                stripe_session_id, 
                customer_name, 
                customer_email, 
                total_amount, 
                payment_status, 
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $orderNumber,
            $testSessionId,
            $testName,
            $testEmail,
            $testAmount,
            'paid'
        ]);
        
        if ($result) {
            $orderId = $db->lastInsertId();
            
            // Add test order items
            $stmt = $db->prepare("
                INSERT INTO order_items (
                    order_id, 
                    product_title, 
                    quantity, 
                    price
                ) VALUES (?, ?, ?, ?)
            ");
            
            // Add some test manga items
            $testItems = [
                ['One Piece Vol. 1', 1, 9.99],
                ['Naruto Vol. 5', 2, 10.00],
                ['Attack on Titan Vol. 3', 1, 9.99]
            ];
            
            foreach ($testItems as $item) {
                $stmt->execute(array_merge([$orderId], $item));
            }
            
            return [
                'success' => true,
                'message' => "✅ Test order created successfully!<br>
                            <strong>Order Number:</strong> {$orderNumber}<br>
                            <strong>Email:</strong> {$testEmail}<br>
                            <strong>Amount:</strong> $" . number_format($testAmount, 2) . "<br>
                            <strong>Session ID:</strong> {$testSessionId}"
            ];
        }
        
        return ['success' => false, 'message' => '❌ Failed to create test order'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '❌ Error: ' . $e->getMessage()];
    }
}

function testOrderTracking() {
    global $emailSystem;
    
    try {
        // Test with the most recent test order
        $testEmail = 'test@bortsbooks.com';
        $testOrders = getTestOrders();
        
        if (empty($testOrders)) {
            return ['success' => false, 'message' => '❌ No test orders found. Create a test order first.'];
        }
        
        $latestOrder = $testOrders[0];
        $orderNumber = $latestOrder['order_number'];
        
        // Test the tracking function
        $order = $emailSystem->getOrderByEmailAndNumber($testEmail, $orderNumber);
        
        if ($order) {
            return [
                'success' => true,
                'message' => "✅ Order tracking test successful!<br>
                            <strong>Found Order:</strong> {$order['order_number']}<br>
                            <strong>Status:</strong> {$order['payment_status']}<br>
                            <strong>Total:</strong> $" . number_format($order['total_amount'], 2) . "<br>
                            <strong>Items:</strong> " . ($order['items'] ?: 'No items found')
            ];
        } else {
            return ['success' => false, 'message' => '❌ Order tracking failed - order not found'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '❌ Tracking test error: ' . $e->getMessage()];
    }
}

function getTestOrders() {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT o.*, 
                   GROUP_CONCAT(
                       CONCAT(oi.product_title, ' (', oi.quantity, 'x)')
                       SEPARATOR ', '
                   ) as items
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.customer_email = 'test@bortsbooks.com'
               OR o.stripe_session_id LIKE 'cs_test_%'
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT 10
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        return [];
    }
}

function cleanupTestData() {
    global $db;
    
    try {
        // Delete test order items first (foreign key constraint)
        $stmt = $db->prepare("
            DELETE oi FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.id
            WHERE o.customer_email = 'test@bortsbooks.com'
               OR o.stripe_session_id LIKE 'cs_test_%'
        ");
        $stmt->execute();
        $itemsDeleted = $stmt->rowCount();
        
        // Delete test orders
        $stmt = $db->prepare("
            DELETE FROM orders 
            WHERE customer_email = 'test@bortsbooks.com'
               OR stripe_session_id LIKE 'cs_test_%'
        ");
        $stmt->execute();
        $ordersDeleted = $stmt->rowCount();
        
        return [
            'success' => true,
            'message' => "✅ Cleanup completed!<br>
                        <strong>Orders deleted:</strong> {$ordersDeleted}<br>
                        <strong>Items deleted:</strong> {$itemsDeleted}"
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '❌ Cleanup error: ' . $e->getMessage()];
    }
}

// Check database connection and table structure
function checkDatabaseSetup() {
    global $db;
    
    $issues = [];
    
    try {
        // Check if orders table exists and has required columns
        $stmt = $db->query("DESCRIBE orders");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = ['id', 'order_number', 'customer_email', 'customer_name', 'total_amount', 'payment_status', 'created_at'];
        
        foreach ($requiredColumns as $col) {
            if (!in_array($col, $columns)) {
                $issues[] = "Missing column: orders.{$col}";
            }
        }
        
        // Check if order_items table exists
        $stmt = $db->query("SHOW TABLES LIKE 'order_items'");
        if (!$stmt->fetch()) {
            $issues[] = "Missing table: order_items";
        }
        
    } catch (Exception $e) {
        $issues[] = "Database error: " . $e->getMessage();
    }
    
    return $issues;
}

$dbIssues = checkDatabaseSetup();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tracking Test - Bort's Books</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f7f7fa; }
        .test-container {
            max-width: 1000px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(35,41,70,0.08);
            padding: 2.5rem;
        }
        .test-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eebbc3;
        }
        .test-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #232946;
            margin-bottom: 0.5rem;
        }
        .test-subtitle {
            color: #666;
            font-size: 1.1rem;
        }
        .test-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }
        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #232946;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .test-btn {
            background: #eebbc3;
            color: #232946;
            border: none;
            border-radius: 8px;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0.5rem 0.5rem 0.5rem 0;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .test-btn:hover {
            background: #232946;
            color: #fff;
            transform: translateY(-1px);
        }
        .test-btn.danger {
            background: #e63946;
            color: #fff;
        }
        .test-btn.danger:hover {
            background: #c82333;
        }
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .message.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .orders-table th,
        .orders-table td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .orders-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #232946;
        }
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        .instructions {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 1.5rem;
            margin: 1rem 0;
            border-radius: 0 8px 8px 0;
        }
        .instructions h4 {
            color: #1976d2;
            margin-bottom: 0.5rem;
        }
        .step-list {
            list-style: none;
            padding: 0;
        }
        .step-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }
        .step-number {
            background: #2196f3;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
            flex-shrink: 0;
        }
        .track-link {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        .track-link:hover {
            background: #218838;
            color: white;
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="index.php" class="logo">Bort's <span>Books</span></a>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="pages/shop.php">Shop</a></li>
                    <li><a href="pages/track-order.php">Track Order</a></li>
                    <li><a href="pages/sell.php">Sell Manga</a></li>
                    <li><a href="pages/about.php">About</a></li>
                </ul>
            </nav>
            <div class="search-cart">
                <a href="cart.php" title="Shopping Cart" class="cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count">0</span>
                </a>
            </div>
        </div>
    </header>

    <main>
        <div class="test-container">
            <div class="test-header">
                <h1 class="test-title">Order Tracking Test Suite</h1>
                <p class="test-subtitle">Test the order tracking functionality without making real purchases</p>
            </div>

            <?php if (!empty($dbIssues)): ?>
                <div class="message error">
                    <h4><i class="fas fa-exclamation-triangle"></i> Database Setup Issues:</h4>
                    <ul>
                        <?php foreach ($dbIssues as $issue): ?>
                            <li><?php echo htmlspecialchars($issue); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p><strong>Note:</strong> You may need to run the database setup scripts first.</p>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="instructions">
                <h4><i class="fas fa-info-circle"></i> How to Test Order Tracking</h4>
                <ol class="step-list">
                    <li>
                        <span class="step-number">1</span>
                        <span>Create test orders using the button below (simulates Stripe test payments)</span>
                    </li>
                    <li>
                        <span class="step-number">2</span>
                        <span>Test the tracking functionality to ensure it finds orders correctly</span>
                    </li>
                    <li>
                        <span class="step-number">3</span>
                        <span>Visit the actual tracking page and test with the generated order numbers</span>
                    </li>
                    <li>
                        <span class="step-number">4</span>
                        <span>Clean up test data when finished</span>
                    </li>
                </ol>
            </div>

            <div class="test-section">
                <h3 class="section-title">
                    <i class="fas fa-plus-circle"></i>
                    Create Test Orders
                </h3>
                <p>Generate test orders with Stripe test session IDs to simulate real orders without payment.</p>
                
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="create_test_order">
                    <button type="submit" class="test-btn">
                        <i class="fas fa-plus"></i>
                        Create Test Order
                    </button>
                </form>
                
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="test_tracking">
                    <button type="submit" class="test-btn">
                        <i class="fas fa-search"></i>
                        Test Tracking Function
                    </button>
                </form>
            </div>

            <div class="test-section">
                <h3 class="section-title">
                    <i class="fas fa-list"></i>
                    Test Orders (<?php echo count($testOrders); ?>)
                </h3>
                
                <?php if (empty($testOrders)): ?>
                    <p>No test orders found. Create some test orders to begin testing.</p>
                <?php else: ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order Number</th>
                                <th>Email</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Items</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($testOrders as $order): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                                            <?php echo htmlspecialchars($order['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['items'] ?: 'No items'); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="pages/track-order.php" class="track-link" target="_blank">
                                            <i class="fas fa-external-link-alt"></i>
                                            Test Tracking
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="message warning">
                        <strong>Test Credentials:</strong><br>
                        <strong>Email:</strong> test@bortsbooks.com<br>
                        <strong>Order Number:</strong> Use any order number from the table above
                    </div>
                <?php endif; ?>
            </div>

            <div class="test-section">
                <h3 class="section-title">
                    <i class="fas fa-external-link-alt"></i>
                    Live Testing
                </h3>
                <p>Test the actual order tracking page with the generated test data:</p>
                
                <a href="pages/track-order.php" target="_blank" class="test-btn">
                    <i class="fas fa-search"></i>
                    Open Order Tracking Page
                </a>
                
                <?php if (!empty($testOrders)): ?>
                    <div class="message">
                        <strong>Quick Test:</strong> Use email <code>test@bortsbooks.com</code> and order number <code><?php echo htmlspecialchars($testOrders[0]['order_number']); ?></code>
                    </div>
                <?php endif; ?>
            </div>

            <div class="test-section">
                <h3 class="section-title">
                    <i class="fas fa-trash"></i>
                    Cleanup
                </h3>
                <p>Remove all test orders and data when finished testing.</p>
                
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="cleanup_test_data">
                    <button type="submit" class="test-btn danger" onclick="return confirm('Are you sure you want to delete all test orders?')">
                        <i class="fas fa-trash"></i>
                        Delete All Test Orders
                    </button>
                </form>
            </div>

            <div class="test-section">
                <h3 class="section-title">
                    <i class="fas fa-info"></i>
                    Stripe Test Mode Integration
                </h3>
                <p>This test suite simulates the order tracking workflow that would occur with real Stripe payments:</p>
                <ul>
                    <li><strong>Test Session IDs:</strong> Uses Stripe test session ID format (cs_test_...)</li>
                    <li><strong>Order Numbers:</strong> Generated using the same algorithm as real orders</li>
                    <li><strong>Database Structure:</strong> Identical to production order storage</li>
                    <li><strong>Email Matching:</strong> Tests the exact same lookup logic used in production</li>
                </ul>
                
                <div class="message">
                    <strong>Note:</strong> In production, orders are created automatically when Stripe payments succeed. 
                    This test suite manually creates orders with the same structure to verify the tracking system works correctly.
                </div>
            </div>
        </div>
    </main>

    <footer style="margin-top: 4rem; background: #232946; color: white; padding: 2rem 0; text-align: center;">
        <div class="container">
            <p>&copy; 2024 Bort's Books. All rights reserved.</p>
            <p>Order Tracking Test Suite - For Development Use Only</p>
        </div>
    </footer>
</body>
</html> 