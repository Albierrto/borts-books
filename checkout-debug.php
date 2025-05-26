<?php
// Debug version of checkout to isolate white screen issue
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting debug...<br>";

try {
    echo "1. Starting session...<br>";
    session_start();
    echo "Session started successfully<br>";
    
    echo "2. Loading database connection...<br>";
    require_once 'includes/db.php';
    echo "Database connection loaded<br>";
    
    echo "3. Loading cart display...<br>";
    require_once 'includes/cart-display.php';
    echo "Cart display loaded<br>";
    
    echo "4. Loading stripe config...<br>";
    require_once 'includes/stripe-config.php';
    echo "Stripe config loaded<br>";
    
    echo "5. Loading USPS shipping...<br>";
    require_once 'includes/usps-shipping.php';
    echo "USPS shipping loaded<br>";
    
    echo "6. Checking cart...<br>";
    $cart = $_SESSION['cart'] ?? [];
    echo "Cart has " . count($cart) . " items<br>";
    
    echo "7. Testing database query...<br>";
    $stmt = $db->query("SELECT COUNT(*) FROM products");
    $count = $stmt->fetchColumn();
    echo "Database has $count products<br>";
    
    echo "<br><strong>All components loaded successfully!</strong><br>";
    echo "The issue may be in the main checkout.php logic.";
    
} catch (Exception $e) {
    echo "<br><strong>Error found:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
}
?> 