<?php
// Step 1: Basic PHP test
echo "Step 1: Basic PHP is working<br>";

// Step 2: Test session
session_start();
echo "Step 2: Session started<br>";

// Step 3: Test database include
try {
    require_once '../includes/db.php';
    echo "Step 3: Database include successful<br>";
} catch (Exception $e) {
    die("Step 3 FAILED: Database include error - " . $e->getMessage());
}

// Step 4: Test database connection
try {
    $stmt = $db->query("SELECT 1");
    echo "Step 4: Database connection successful<br>";
} catch (Exception $e) {
    die("Step 4 FAILED: Database connection error - " . $e->getMessage());
}

// Step 5: Test admin check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "Step 5: Not logged in as admin - this is expected<br>";
} else {
    echo "Step 5: Admin logged in<br>";
}

// Step 6: Test product_id parameter
$product_id = $_GET['product_id'] ?? null;
if (!$product_id) {
    echo "Step 6: No product_id parameter<br>";
} else {
    echo "Step 6: Product ID = $product_id<br>";
}

// Step 7: Test product query
if ($product_id) {
    try {
        $sql = "SELECT * FROM products WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            echo "Step 7: Product found - " . htmlspecialchars($product['title']) . "<br>";
        } else {
            echo "Step 7: Product not found<br>";
        }
    } catch (Exception $e) {
        die("Step 7 FAILED: Product query error - " . $e->getMessage());
    }
}

// Step 8: Test product_images query
if ($product_id) {
    try {
        $sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$product_id]);
        $existing_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Step 8: Found " . count($existing_images) . " images<br>";
    } catch (Exception $e) {
        echo "Step 8 WARNING: Image query error - " . $e->getMessage() . "<br>";
        // Try alternative query
        try {
            $sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute([$product_id]);
            $existing_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "Step 8b: Alternative query found " . count($existing_images) . " images<br>";
        } catch (Exception $e2) {
            die("Step 8 FAILED: Both image queries failed - " . $e2->getMessage());
        }
    }
}

// Step 9: Test directory access
$upload_dir = '../assets/img/products/';
if (file_exists($upload_dir)) {
    echo "Step 9: Upload directory exists<br>";
    if (is_writable($upload_dir)) {
        echo "Step 9b: Directory is writable<br>";
    } else {
        echo "Step 9b: Directory is NOT writable<br>";
    }
} else {
    echo "Step 9: Upload directory does NOT exist<br>";
}

// Step 10: Test HTML output
echo "Step 10: About to output HTML...<br>";

echo "<h2>All Tests Passed!</h2>";
echo "<p>If you see this message, the basic functionality is working.</p>";
echo "<p><a href='upload-images.php?product_id=815'>Try Upload Images Page</a></p>";
echo "<p><a href='test-upload.php?product_id=815'>Try Test Upload Page</a></p>";
?> 