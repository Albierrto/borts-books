<?php
// Test Order Creation Script for Bort's Books
// This creates realistic test orders for testing purposes

require_once 'includes/db.php';

// Test order data
$test_orders = [
    [
        'customer_name' => 'Test Customer 1',
        'customer_email' => 'test1@bortsbooks.com',
        'order_number' => 'BB-TEST-' . date('Ymd') . '-001',
        'total_amount' => 45.97,
        'items' => [
            ['title' => 'One Piece Volume 1', 'price' => 12.99, 'quantity' => 1],
            ['title' => 'Naruto Volume 1', 'price' => 11.99, 'quantity' => 2],
            ['title' => 'Attack on Titan Volume 1', 'price' => 10.99, 'quantity' => 1]
        ],
        'shipping_address' => '123 Test Street, Test City, TC 12345',
        'stripe_session_id' => 'cs_test_' . uniqid()
    ],
    [
        'customer_name' => 'Test Customer 2',
        'customer_email' => 'test2@bortsbooks.com',
        'order_number' => 'BB-TEST-' . date('Ymd') . '-002',
        'total_amount' => 28.98,
        'items' => [
            ['title' => 'Dragon Ball Volume 1', 'price' => 13.99, 'quantity' => 1],
            ['title' => 'My Hero Academia Volume 1', 'price' => 14.99, 'quantity' => 1]
        ],
        'shipping_address' => '456 Manga Lane, Anime City, AC 67890',
        'stripe_session_id' => 'cs_test_' . uniqid()
    ]
];

echo "<h1>🧪 Test Order Creator - Bort's Books</h1>";
echo "<p>This script creates test orders for testing your order tracking and fulfillment system.</p>";

try {
    foreach ($test_orders as $index => $order) {
        echo "<h3>Creating Test Order " . ($index + 1) . "...</h3>";
        
        // Insert order into orders table
        $stmt = $db->prepare("
            INSERT INTO orders (
                order_number, customer_name, customer_email, total_amount, 
                shipping_address, stripe_session_id, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'processing', NOW())
        ");
        
        $stmt->execute([
            $order['order_number'],
            $order['customer_name'],
            $order['customer_email'],
            $order['total_amount'],
            $order['shipping_address'],
            $order['stripe_session_id']
        ]);
        
        $order_id = $db->lastInsertId();
        
        // Insert order items
        foreach ($order['items'] as $item) {
            $stmt = $db->prepare("
                INSERT INTO order_items (
                    order_id, product_title, price, quantity, created_at
                ) VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $order_id,
                $item['title'],
                $item['price'],
                $item['quantity']
            ]);
        }
        
        echo "✅ <strong>Order Created:</strong> {$order['order_number']}<br>";
        echo "📧 <strong>Email:</strong> {$order['customer_email']}<br>";
        echo "💰 <strong>Total:</strong> $" . number_format($order['total_amount'], 2) . "<br>";
        echo "📦 <strong>Items:</strong> " . count($order['items']) . " items<br>";
        echo "<br>";
    }
    
    echo "<h3>🎉 Test Orders Created Successfully!</h3>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>Test order tracking at: <a href='pages/track-order.php'>Track Order Page</a></li>";
    echo "<li>Use email: <code>test1@bortsbooks.com</code> or <code>test2@bortsbooks.com</code></li>";
    echo "<li>Use order numbers shown above</li>";
    echo "<li>Check admin panel for order management</li>";
    echo "</ul>";
    
    echo "<h3>📋 Test Order Numbers:</h3>";
    echo "<ul>";
    foreach ($test_orders as $order) {
        echo "<li><code>{$order['order_number']}</code> - {$order['customer_email']}</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "❌ <strong>Error creating test orders:</strong> " . htmlspecialchars($e->getMessage());
    echo "<br><br><strong>Common issues:</strong>";
    echo "<ul>";
    echo "<li>Make sure the 'orders' and 'order_items' tables exist</li>";
    echo "<li>Check database connection</li>";
    echo "<li>Verify table structure matches the INSERT statements</li>";
    echo "</ul>";
}

echo "<br><hr>";
echo "<p><strong>⚠️ Remember:</strong> Delete this file after testing for security!</p>";
?>

<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
h1 { color: #232946; }
h3 { color: #395aa0; }
code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
ul { margin: 10px 0; }
li { margin: 5px 0; }
</style> 