<?php
// Diagnostic test for edit-product-clean.php
echo "<h1>Edit Product Diagnostic Test</h1>";

// Test 1: Basic PHP functionality
echo "<h3>Test 1: Basic PHP</h3>";
echo "✅ PHP is working - Time: " . date('Y-m-d H:i:s') . "<br><br>";

// Test 2: File existence
echo "<h3>Test 2: File Check</h3>";
if (file_exists('edit-product-clean.php')) {
    echo "✅ edit-product-clean.php exists<br>";
    echo "File size: " . number_format(filesize('edit-product-clean.php')) . " bytes<br>";
} else {
    echo "❌ edit-product-clean.php not found<br>";
}
echo "<br>";

// Test 3: Session and database
echo "<h3>Test 3: Session & Database</h3>";
try {
    session_start();
    echo "✅ Session started<br>";
} catch (Exception $e) {
    echo "❌ Session error: " . $e->getMessage() . "<br>";
}

try {
    require_once '../includes/db.php';
    echo "✅ Database connection successful<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}
echo "<br>";

// Test 4: Admin check
echo "<h3>Test 4: Admin Status</h3>";
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    echo "✅ Logged in as admin<br>";
} else {
    echo "⚠️ Not logged in as admin<br>";
    echo "<a href='admin.php'>Login as admin first</a><br>";
}
echo "<br>";

// Test 5: Product check
echo "<h3>Test 5: Product Check</h3>";
$product_id = 815;
try {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo "✅ Product found: " . htmlspecialchars($product['title']) . "<br>";
    } else {
        echo "⚠️ Product ID 815 not found<br>";
    }
} catch (Exception $e) {
    echo "❌ Product query error: " . $e->getMessage() . "<br>";
}
echo "<br>";

// Test 6: Image check
echo "<h3>Test 6: Image Check</h3>";
try {
    $stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC");
    $stmt->execute([$product_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Image query successful - found " . count($images) . " images<br>";
} catch (Exception $e) {
    echo "⚠️ Image query error: " . $e->getMessage() . "<br>";
}
echo "<br>";

// Test 7: Try to include edit-product-clean.php
echo "<h3>Test 7: Include Test</h3>";
echo "Attempting to include edit-product-clean.php...<br>";

// Capture any output/errors
ob_start();
$error = '';
try {
    // Don't actually include it to avoid header issues, just check syntax
    $content = file_get_contents('edit-product-clean.php');
    if ($content !== false) {
        echo "✅ File content readable<br>";
        
        // Check for common syntax issues
        if (strpos($content, '<?php') === 0) {
            echo "✅ File starts with PHP tag<br>";
        } else {
            echo "⚠️ File doesn't start with PHP tag<br>";
        }
        
        if (substr_count($content, '<?php') === substr_count($content, '?>') + 1) {
            echo "✅ PHP tags appear balanced<br>";
        } else {
            echo "⚠️ PHP tags might be unbalanced<br>";
        }
        
        // Check if our upload link fix is there
        if (strpos($content, 'upload-images-production.php') !== false) {
            echo "✅ Upload link appears to be fixed<br>";
        } else if (strpos($content, 'upload-images.php') !== false) {
            echo "⚠️ Upload link still points to broken file<br>";
        }
        
    } else {
        echo "❌ Cannot read file content<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
$output = ob_get_clean();
echo $output;

echo "<br><h3>🔧 Solutions</h3>";
echo "<p><a href='edit-product-clean.php?id=815' target='_blank'>Try to load edit-product-clean.php directly</a></p>";
echo "<p><a href='upload-images-production.php?product_id=815' target='_blank'>Try upload images directly</a></p>";
echo "<p><a href='debug-500-remote.php?product_id=815' target='_blank'>Run 500 error diagnostic</a></p>";

echo "<br><h3>📊 Server Info</h3>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "Upload Max: " . ini_get('upload_max_filesize') . "<br>";
echo "Current Time: " . date('Y-m-d H:i:s T') . "<br>";
?> 