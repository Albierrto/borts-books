<?php
session_start();

echo "<h1>Debug Information</h1>";

// Check if includes/db.php exists
if (file_exists('includes/db.php')) {
    echo "<p>✓ includes/db.php found</p>";
    
    try {
        require_once 'includes/db.php';
        echo "<p>✓ Database connection successful</p>";
        
        // Check products table
        $stmt = $db->query('SELECT COUNT(*) as count FROM products');
        $productCount = $stmt->fetchColumn();
        echo "<p>Products in database: {$productCount}</p>";
        
        // Check product_images table
        $stmt = $db->query('SELECT COUNT(*) as count FROM product_images');
        $imageCount = $stmt->fetchColumn();
        echo "<p>Images in database: {$imageCount}</p>";
        
        // Check session cart
        echo "<p>Cart session: " . (isset($_SESSION['cart']) ? 'exists' : 'not set') . "</p>";
        if (isset($_SESSION['cart'])) {
            echo "<p>Cart contents: " . json_encode($_SESSION['cart']) . "</p>";
        }
        
        // Show first few products
        $stmt = $db->query('SELECT id, title, price FROM products LIMIT 3');
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($products) {
            echo "<h3>Sample products:</h3>";
            foreach ($products as $product) {
                echo "<p>ID: {$product['id']}, Title: {$product['title']}, Price: \${$product['price']}</p>";
            }
        } else {
            echo "<p><strong>No products found in database!</strong></p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ includes/db.php not found</p>";
}

// Check if .env exists
if (file_exists('.env')) {
    echo "<p>✓ .env file found</p>";
} else {
    echo "<p>❌ .env file not found - you need to create this for database connection</p>";
}
?> 