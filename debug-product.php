<?php
// PRODUCT PAGE DEBUG - Find what's causing white screen
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>Product Page Debug</h1>";
echo "<p>Testing the exact same process as pages/product.php</p>";

// Step 1: Test includes
echo "<h2>Step 1: File Includes</h2>";
try {
    echo "Including security.php...<br>";
    require_once __DIR__ . '/includes/security.php';
    echo "✅ Security.php included<br>";
    
    echo "Including db.php...<br>";
    require_once __DIR__ . '/includes/db.php';
    echo "✅ db.php included<br>";
    
} catch (Throwable $e) {
    echo "❌ Include failed: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

// Step 2: Start session
echo "<h2>Step 2: Session Start</h2>";
try {
    secure_session_start();
    echo "✅ Session started<br>";
    echo "Session ID: " . session_id() . "<br>";
} catch (Throwable $e) {
    echo "❌ Session failed: " . $e->getMessage() . "<br>";
    exit;
}

// Step 3: Security headers
echo "<h2>Step 3: Security Headers</h2>";
try {
    set_security_headers();
    echo "✅ Security headers set<br>";
} catch (Throwable $e) {
    echo "❌ Security headers failed: " . $e->getMessage() . "<br>";
    exit;
}

// Step 4: Rate limiting check
echo "<h2>Step 4: Rate Limiting</h2>";
try {
    $rate_ok = check_rate_limit('product_view', 50, 3600);
    echo "Rate limit check: " . ($rate_ok ? 'PASSED' : 'BLOCKED') . "<br>";
    if (!$rate_ok) {
        echo "⚠️ Would return 429 Too Many Requests<br>";
    }
} catch (Throwable $e) {
    echo "❌ Rate limiting failed: " . $e->getMessage() . "<br>";
    exit;
}

// Step 5: Admin check
echo "<h2>Step 5: Admin Check</h2>";
try {
    $isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    echo "Is admin: " . ($isAdmin ? 'YES' : 'NO') . "<br>";
} catch (Throwable $e) {
    echo "❌ Admin check failed: " . $e->getMessage() . "<br>";
    exit;
}

// Step 6: Product ID validation
echo "<h2>Step 6: Product ID Validation</h2>";
try {
    echo "GET parameters: " . print_r($_GET, true) . "<br>";
    
    $id = isset($_GET['id']) ? validate_int($_GET['id']) : null;
    echo "Product ID: " . ($id ? $id : 'NULL') . "<br>";
    
    if (!$id || $id <= 0) {
        echo "⚠️ Invalid product ID - would redirect to shop.php<br>";
        // Don't actually redirect in debug mode
    } else {
        echo "✅ Valid product ID: $id<br>";
    }
} catch (Throwable $e) {
    echo "❌ Product ID validation failed: " . $e->getMessage() . "<br>";
    exit;
}

// Step 7: Database connection test
echo "<h2>Step 7: Database Connection</h2>";
try {
    if (isset($db) && $db instanceof PDO) {
        echo "✅ Database connection exists<br>";
        
        // Test a simple query
        $testStmt = $db->prepare('SELECT COUNT(*) FROM products');
        $testStmt->execute();
        $count = $testStmt->fetchColumn();
        echo "✅ Database query works - found $count products<br>";
    } else {
        echo "❌ Database connection not available<br>";
        exit;
    }
} catch (Throwable $e) {
    echo "❌ Database test failed: " . $e->getMessage() . "<br>";
    exit;
}

// Step 8: Product data fetch (if we have a valid ID)
echo "<h2>Step 8: Product Data Fetch</h2>";
if ($id && $id > 0) {
    try {
        echo "Fetching product with ID: $id<br>";
        
        $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            echo "✅ Product found: " . htmlspecialchars($product['title'] ?? 'No title') . "<br>";
            echo "Product data keys: " . implode(', ', array_keys($product)) . "<br>";
        } else {
            echo "❌ Product not found in database<br>";
            echo "⚠️ Would redirect to shop.php<br>";
        }
        
    } catch (Throwable $e) {
        echo "❌ Product fetch failed: " . $e->getMessage() . "<br>";
        echo "SQL State: " . ($e instanceof PDOException ? $e->errorInfo[0] : 'N/A') . "<br>";
        exit;
    }
} else {
    echo "⚠️ Skipping product fetch (no valid ID)<br>";
}

// Step 9: Product images fetch (if we have a product)
echo "<h2>Step 9: Product Images Fetch</h2>";
if (isset($product) && $product) {
    try {
        echo "Fetching images for product ID: $id<br>";
        
        $imgStmt = $db->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC');
        $imgStmt->execute([$id]);
        $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "✅ Found " . count($images) . " images<br>";
        if (count($images) > 0) {
            echo "Image paths: ";
            foreach ($images as $img) {
                echo htmlspecialchars($img['image_url'] ?? 'no_url') . " ";
            }
            echo "<br>";
        }
        
    } catch (Throwable $e) {
        echo "❌ Images fetch failed: " . $e->getMessage() . "<br>";
        exit;
    }
} else {
    echo "⚠️ Skipping images fetch (no product)<br>";
}

// Step 10: Recommended products fetch
echo "<h2>Step 10: Recommended Products Fetch</h2>";
if ($id && $id > 0) {
    try {
        echo "Fetching recommended products...<br>";
        
        $recStmt = $db->prepare("
            SELECT p.*, (
                SELECT image_url FROM product_images 
                WHERE product_id = p.id 
                ORDER BY id ASC LIMIT 1
            ) AS main_image
            FROM products p
            WHERE p.id != ? AND p.title IS NOT NULL AND p.title != '' 
            ORDER BY RAND() LIMIT 7
        ");
        $recStmt->execute([$id]);
        $recommended = $recStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "✅ Found " . count($recommended) . " recommended products<br>";
        
    } catch (Throwable $e) {
        echo "❌ Recommended products fetch failed: " . $e->getMessage() . "<br>";
        echo "⚠️ Setting recommended to empty array<br>";
        $recommended = [];
    }
} else {
    echo "⚠️ Skipping recommended products (no valid ID)<br>";
    $recommended = [];
}

// Step 11: Cart initialization
echo "<h2>Step 11: Cart Initialization</h2>";
try {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    $num_items_in_cart = is_array($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
    echo "✅ Cart initialized - $num_items_in_cart items<br>";
} catch (Throwable $e) {
    echo "❌ Cart initialization failed: " . $e->getMessage() . "<br>";
    exit;
}

// Step 12: HTML output test
echo "<h2>Step 12: HTML Output Test</h2>";
try {
    if (isset($product) && $product) {
        echo "Testing HTML generation...<br>";
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title><?php echo htmlspecialchars($product['title']); ?> - Bort's Books</title>
        </head>
        <body>
            <h1>Product: <?php echo htmlspecialchars($product['title']); ?></h1>
            <p>Price: $<?php echo htmlspecialchars($product['price'] ?? '0.00'); ?></p>
        </body>
        </html>
        <?php
        $html_output = ob_get_clean();
        
        echo "✅ HTML generated successfully (" . strlen($html_output) . " characters)<br>";
        
    } else {
        echo "⚠️ Skipping HTML test (no product data)<br>";
    }
} catch (Throwable $e) {
    echo "❌ HTML generation failed: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h2>Debug Summary</h2>";
echo "<p><strong>If all steps above passed:</strong></p>";
echo "<ul>";
echo "<li>✅ The product page logic works fine</li>";
echo "<li>⚠️ The issue might be in the CSS/JavaScript rendering</li>";
echo "<li>⚠️ Or there might be a specific product causing issues</li>";
echo "</ul>";

echo "<p><strong>Test with a specific product:</strong></p>";
echo "<a href='?id=1'>Test with Product ID 1</a><br>";
echo "<a href='?id=2'>Test with Product ID 2</a><br>";
echo "<a href='?id=999'>Test with Invalid ID (999)</a><br>";

echo "<p><strong>Direct comparison:</strong></p>";
if (isset($product) && $product) {
    echo "<a href='pages/product.php?id=" . $id . "' target='_blank'>View actual product page (may show white screen)</a><br>";
}

echo "<h2>Available Products</h2>";
try {
    $allProductsStmt = $db->prepare('SELECT id, title FROM products ORDER BY id ASC LIMIT 10');
    $allProductsStmt->execute();
    $allProducts = $allProductsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    foreach ($allProducts as $prod) {
        echo "<li><a href='?id=" . $prod['id'] . "'>" . htmlspecialchars($prod['title']) . " (ID: " . $prod['id'] . ")</a></li>";
    }
    echo "</ul>";
} catch (Throwable $e) {
    echo "❌ Could not fetch product list: " . $e->getMessage() . "<br>";
}

?> 