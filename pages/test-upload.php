<?php
session_start();
require_once '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Admin access required');
}

$product_id = $_GET['product_id'] ?? 815; // Default to product 815

echo "<h1>Upload Test for Product $product_id</h1>";

// Test 1: Database connection
try {
    $stmt = $db->query("SELECT 1");
    echo "<p>✅ Database connection: OK</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection: " . $e->getMessage() . "</p>";
}

// Test 2: Check product exists
try {
    $stmt = $db->prepare("SELECT title FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    if ($product) {
        echo "<p>✅ Product found: " . htmlspecialchars($product['title']) . "</p>";
    } else {
        echo "<p>❌ Product not found</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Product check error: " . $e->getMessage() . "</p>";
}

// Test 3: Check product_images table structure
try {
    $stmt = $db->query("DESCRIBE product_images");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>✅ product_images table columns:</p><ul>";
    foreach ($columns as $column) {
        echo "<li>" . htmlspecialchars($column['Field']) . " (" . htmlspecialchars($column['Type']) . ")</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p>❌ Table structure error: " . $e->getMessage() . "</p>";
}

// Test 4: Check existing images for this product
try {
    $stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>✅ Found " . count($images) . " existing images for this product</p>";
    
    foreach ($images as $image) {
        echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px;'>";
        echo "<strong>Image ID:</strong> " . $image['id'] . "<br>";
        
        if (!empty($image['filename'])) {
            echo "<strong>Filename:</strong> " . htmlspecialchars($image['filename']) . "<br>";
            $path = "../assets/img/products/" . $image['filename'];
            if (file_exists($path)) {
                echo "<span style='color: green;'>✅ File exists</span><br>";
                echo "<img src='$path' style='max-width: 100px; max-height: 100px;'><br>";
            } else {
                echo "<span style='color: red;'>❌ File missing</span><br>";
            }
        }
        
        if (!empty($image['image_url'])) {
            echo "<strong>Image URL:</strong> " . htmlspecialchars($image['image_url']) . "<br>";
        }
        
        if (isset($image['is_main'])) {
            echo "<strong>Is Main:</strong> " . ($image['is_main'] ? 'Yes' : 'No') . "<br>";
        }
        
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<p>❌ Images check error: " . $e->getMessage() . "</p>";
}

// Test 5: Check directory permissions
$upload_dir = '../assets/img/products/';
echo "<h3>Directory Tests</h3>";

if (file_exists($upload_dir)) {
    echo "<p>✅ Directory exists: $upload_dir</p>";
    
    if (is_writable($upload_dir)) {
        echo "<p>✅ Directory is writable</p>";
        
        // Test file creation
        $test_file = $upload_dir . 'test_' . time() . '.txt';
        if (file_put_contents($test_file, 'test')) {
            echo "<p>✅ Can create files in directory</p>";
            unlink($test_file); // Clean up
        } else {
            echo "<p>❌ Cannot create files in directory</p>";
        }
    } else {
        echo "<p>❌ Directory is not writable</p>";
        
        // Show directory permissions
        $perms = fileperms($upload_dir);
        echo "<p>Directory permissions: " . decoct($perms & 0777) . "</p>";
    }
} else {
    echo "<p>❌ Directory does not exist: $upload_dir</p>";
    
    // Try to create it
    if (mkdir($upload_dir, 0755, true)) {
        echo "<p>✅ Successfully created directory</p>";
    } else {
        echo "<p>❌ Failed to create directory</p>";
    }
}

// Test 6: Check if upload-images.php is accessible
echo "<h3>File Tests</h3>";
$upload_file = 'upload-images.php';
if (file_exists($upload_file)) {
    echo "<p>✅ upload-images.php exists</p>";
    if (is_readable($upload_file)) {
        echo "<p>✅ upload-images.php is readable</p>";
    } else {
        echo "<p>❌ upload-images.php is not readable</p>";
    }
} else {
    echo "<p>❌ upload-images.php does not exist</p>";
}

echo "<hr>";
echo "<p><a href='upload-images.php?product_id=$product_id'>Test Upload Images Page</a></p>";
echo "<p><a href='fix-image-system.php'>Run Database Fix</a></p>";
echo "<p><a href='admin.php'>Back to Admin</a></p>";
?> 