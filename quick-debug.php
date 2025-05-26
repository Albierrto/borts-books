<?php
// Quick debug for remote server
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Quick Remote Debug</h1>";
echo "<p>Server: " . $_SERVER['HTTP_HOST'] . "</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Test if we can start session
session_start();
echo "<p>✓ Session started</p>";

// Test database
try {
    require_once 'includes/db.php';
    echo "<p>✓ Database connected</p>";
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test USPS shipping class
try {
    require_once 'includes/usps-shipping.php';
    $usps = new USPSShipping();
    echo "<p>✓ USPS shipping class loaded</p>";
} catch (Exception $e) {
    echo "<p>❌ USPS shipping error: " . $e->getMessage() . "</p>";
}

// Test cart
$cart = $_SESSION['cart'] ?? [];
echo "<p>Cart items: " . count($cart) . "</p>";

// If no cart items, create a test
if (empty($cart)) {
    echo "<p>Adding test item to cart...</p>";
    $_SESSION['cart'][1] = 1;
    $cart = $_SESSION['cart'];
}

// Test shipping calculation
if (!empty($cart)) {
    try {
        $ids = implode(',', array_map('intval', array_keys($cart)));
        $stmt = $db->query("SELECT * FROM products WHERE id IN ($ids) LIMIT 1");
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            echo "<p>Testing with product: " . $product['title'] . "</p>";
            
            $usps = new USPSShipping();
            $result = $usps->calculateShipping($product, '90210', 'Ground');
            
            echo "<p>✓ Shipping calculation successful!</p>";
            echo "<p>Rate: $" . $result['rate'] . "</p>";
            echo "<p>Service: " . $result['service'] . "</p>";
        } else {
            echo "<p>❌ No products found in database</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Shipping calculation failed: " . $e->getMessage() . "</p>";
        echo "<p>File: " . $e->getFile() . "</p>";
        echo "<p>Line: " . $e->getLine() . "</p>";
    }
}

echo "<hr>";
echo "<p><a href='checkout.php'>Go to Checkout</a></p>";
echo "<p><a href='simple-test.php'>Simple Test</a></p>";
echo "<p><a href='ajax-test.php'>AJAX Test</a></p>";
?> 