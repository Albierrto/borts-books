<?php
// Shipping calculation debug page
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Shipping Calculation Debug</h1>";

try {
    echo "<h2>1. Starting session and loading dependencies</h2>";
    session_start();
    echo "✓ Session started<br>";
    
    require_once 'includes/db.php';
    echo "✓ Database loaded<br>";
    
    require_once 'includes/cart-display.php';
    echo "✓ Cart display loaded<br>";
    
    echo "<h2>2. Checking cart contents</h2>";
    $cart = $_SESSION['cart'] ?? [];
    echo "Cart contents: " . print_r($cart, true) . "<br>";
    echo "Cart count: " . count($cart) . "<br>";
    
    if (empty($cart)) {
        echo "<strong>⚠️ Cart is empty! Adding a test product...</strong><br>";
        // Add a test product to cart
        $_SESSION['cart'][1] = 1; // Assuming product ID 1 exists
        $cart = $_SESSION['cart'];
        echo "Added test product. New cart: " . print_r($cart, true) . "<br>";
    }
    
    echo "<h2>3. Loading products from database</h2>";
    $products = [];
    $subtotal = 0;
    
    if (!empty($cart)) {
        $ids = implode(',', array_map('intval', array_keys($cart)));
        echo "Loading product IDs: $ids<br>";
        
        $stmt = $db->query("SELECT * FROM products WHERE id IN ($ids)");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Found " . count($products) . " products:<br>";
        foreach ($products as $prod) {
            echo "- {$prod['title']} (ID: {$prod['id']}, Price: \${$prod['price']})<br>";
            $subtotal += $prod['price'];
        }
        echo "Subtotal: $" . number_format($subtotal, 2) . "<br>";
    }
    
    echo "<h2>4. Testing USPS Shipping class</h2>";
    require_once 'includes/usps-shipping.php';
    echo "✓ USPS shipping class loaded<br>";
    
    $usps = new USPSShipping();
    echo "✓ USPS object created<br>";
    
    echo "<h2>5. Testing shipping calculation</h2>";
    $test_zip = '90210';
    $test_service = 'Ground';
    
    if (!empty($products)) {
        $first_product = $products[0];
        echo "Testing with product: {$first_product['title']}<br>";
        echo "Test ZIP: $test_zip<br>";
        echo "Test Service: $test_service<br>";
        
        try {
            $shipping_result = $usps->calculateShipping($first_product, $test_zip, $test_service);
            echo "✓ Shipping calculation successful!<br>";
            echo "Result: " . print_r($shipping_result, true) . "<br>";
        } catch (Exception $e) {
            echo "❌ Shipping calculation failed: " . $e->getMessage() . "<br>";
            echo "File: " . $e->getFile() . "<br>";
            echo "Line: " . $e->getLine() . "<br>";
        }
    } else {
        echo "⚠️ No products available for testing<br>";
    }
    
    echo "<h2>6. Testing AJAX simulation</h2>";
    if (!empty($products)) {
        $_POST['calculate_shipping_only'] = '1';
        $_POST['zip'] = $test_zip;
        $_POST['shipping_service'] = $test_service;
        
        echo "Simulating AJAX request with:<br>";
        echo "- ZIP: {$_POST['zip']}<br>";
        echo "- Service: {$_POST['shipping_service']}<br>";
        
        try {
            $calculated_shipping = 0;
            foreach ($products as $product) {
                $usps = new USPSShipping();
                $shipping_result = $usps->calculateShipping($product, $_POST['zip'], $_POST['shipping_service']);
                $calculated_shipping += $shipping_result['rate'];
                echo "Product '{$product['title']}' shipping: \${$shipping_result['rate']}<br>";
            }
            
            $response = [
                'success' => true,
                'shipping_cost' => $calculated_shipping,
                'subtotal' => $subtotal,
                'total' => $subtotal + $calculated_shipping,
                'service' => $_POST['shipping_service']
            ];
            
            echo "✓ AJAX simulation successful!<br>";
            echo "Response would be: " . json_encode($response, JSON_PRETTY_PRINT) . "<br>";
            
        } catch (Exception $e) {
            echo "❌ AJAX simulation failed: " . $e->getMessage() . "<br>";
            echo "File: " . $e->getFile() . "<br>";
            echo "Line: " . $e->getLine() . "<br>";
        }
    }
    
    echo "<h2>7. Testing form submission</h2>";
    echo '<form method="POST" action="">
        <label>ZIP Code: <input type="text" name="test_zip" value="90210"></label><br>
        <label>Service: 
            <select name="test_service">
                <option value="Media">Media Mail</option>
                <option value="Ground" selected>Ground Advantage</option>
                <option value="Priority">Priority Mail</option>
            </select>
        </label><br>
        <button type="submit" name="test_shipping">Test Shipping Calculation</button>
    </form>';
    
    if (isset($_POST['test_shipping'])) {
        echo "<h3>Form Test Results:</h3>";
        $test_zip = $_POST['test_zip'];
        $test_service = $_POST['test_service'];
        
        if (!empty($products)) {
            $total_shipping = 0;
            foreach ($products as $product) {
                try {
                    $usps = new USPSShipping();
                    $result = $usps->calculateShipping($product, $test_zip, $test_service);
                    $total_shipping += $result['rate'];
                    echo "✓ {$product['title']}: \${$result['rate']} ({$result['service']})<br>";
                } catch (Exception $e) {
                    echo "❌ {$product['title']}: Error - {$e->getMessage()}<br>";
                }
            }
            echo "<strong>Total Shipping: \${$total_shipping}</strong><br>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Critical Error:</h2>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "<strong>Trace:</strong><pre>" . $e->getTraceAsString() . "</pre>";
}
?> 