<?php
session_start();

// Define constant for secure database access
define('INCLUDED_FROM_APP', true);

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Edit Product Debug Page</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } pre { background: #f5f5f5; padding: 10px; border-radius: 4px; }</style>";

// Test 1: Check session
echo "<h2>1. Session Check</h2>";
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "<p class='error'>❌ Admin not logged in</p>";
    echo "<p><a href='admin-login.php'>Go to Admin Login</a></p>";
} else {
    echo "<p class='success'>✅ Admin logged in successfully</p>";
}

// Test 2: Check product ID
echo "<h2>2. Product ID Check</h2>";
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    echo "<p class='error'>❌ Invalid product ID: " . htmlspecialchars($_GET['id'] ?? 'none') . "</p>";
    echo "<p>Try: <a href='?id=1'>debug-edit-product.php?id=1</a></p>";
} else {
    echo "<p class='success'>✅ Product ID: $product_id</p>";
}

// Test 3: Check database connection
echo "<h2>3. Database Connection</h2>";
try {
    require_once '../includes/db.php';
    echo "<p class='success'>✅ Database connection included successfully</p>";
    
    if (isset($db) && $db instanceof PDO) {
        echo "<p class='success'>✅ PDO connection object exists</p>";
        
        // Test connection
        $test = $db->query("SELECT 1");
        if ($test) {
            echo "<p class='success'>✅ Database connection working</p>";
        } else {
            echo "<p class='error'>❌ Database query failed</p>";
        }
    } else {
        echo "<p class='error'>❌ PDO connection object not found</p>";
        echo "<p>Available variables: " . implode(', ', array_keys(get_defined_vars())) . "</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// Test 4: Check if products table exists
if (isset($db) && $product_id > 0) {
    echo "<h2>4. Products Table Check</h2>";
    try {
        $stmt = $db->prepare("SHOW TABLES LIKE 'products'");
        $stmt->execute();
        $table_exists = $stmt->fetch();
        
        if ($table_exists) {
            echo "<p class='success'>✅ Products table exists</p>";
            
            // Check table structure
            $stmt = $db->prepare("DESCRIBE products");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Table Structure:</h3>";
            echo "<pre>";
            foreach ($columns as $column) {
                echo $column['Field'] . " - " . $column['Type'] . "\n";
            }
            echo "</pre>";
            
        } else {
            echo "<p class='error'>❌ Products table does not exist</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error checking products table: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Test 5: Try to fetch the product
if (isset($db) && $product_id > 0) {
    echo "<h2>5. Product Fetch Test</h2>";
    try {
        $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            echo "<p class='success'>✅ Product found</p>";
            echo "<h3>Product Data:</h3>";
            echo "<pre>" . htmlspecialchars(print_r($product, true)) . "</pre>";
        } else {
            echo "<p class='error'>❌ Product not found with ID: $product_id</p>";
            
            // Show available products
            $stmt = $db->prepare("SELECT id, title FROM products LIMIT 10");
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($products) {
                echo "<h3>Available Products:</h3>";
                echo "<ul>";
                foreach ($products as $p) {
                    echo "<li><a href='?id=" . $p['id'] . "'>" . htmlspecialchars($p['title']) . " (ID: " . $p['id'] . ")</a></li>";
                }
                echo "</ul>";
            }
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error fetching product: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
}

// Test 6: Check product_images table
if (isset($db) && $product_id > 0) {
    echo "<h2>6. Product Images Check</h2>";
    try {
        $stmt = $db->prepare("SHOW TABLES LIKE 'product_images'");
        $stmt->execute();
        $table_exists = $stmt->fetch();
        
        if ($table_exists) {
            echo "<p class='success'>✅ Product_images table exists</p>";
            
            // Check table structure first
            $stmt = $db->prepare("DESCRIBE product_images");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Product Images Table Structure:</h3>";
            echo "<pre>";
            $has_is_main = false;
            foreach ($columns as $column) {
                echo $column['Field'] . " - " . $column['Type'] . "\n";
                if ($column['Field'] === 'is_main') {
                    $has_is_main = true;
                }
            }
            echo "</pre>";
            
            if (!$has_is_main) {
                echo "<p class='error'>❌ Missing 'is_main' column in product_images table</p>";
                echo "<p><strong>Fix Available:</strong></p>";
                echo "<p><a href='?id=$product_id&fix_table=1' class='success'>Click here to add missing is_main column</a></p>";
            } else {
                echo "<p class='success'>✅ is_main column exists</p>";
            }
            
            // Handle the fix
            if (isset($_GET['fix_table']) && $_GET['fix_table'] == '1') {
                try {
                    $db->exec("ALTER TABLE product_images ADD COLUMN is_main TINYINT(1) DEFAULT 0");
                    echo "<p class='success'>✅ Added is_main column successfully!</p>";
                    echo "<p><a href='?id=$product_id'>Refresh page</a></p>";
                } catch (Exception $e) {
                    echo "<p class='error'>❌ Error adding column: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            }
            
            // Try to get images with safe query
            try {
                if ($has_is_main || isset($_GET['fix_table'])) {
                    $stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC");
                } else {
                    $stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC");
                }
                $stmt->execute([$product_id]);
                $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<p class='info'>Found " . count($images) . " images for this product</p>";
                if (!empty($images)) {
                    echo "<pre>" . htmlspecialchars(print_r($images, true)) . "</pre>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>❌ Error fetching images: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            
        } else {
            echo "<p class='error'>❌ Product_images table does not exist</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error checking product_images: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Test 7: PHP Info
echo "<h2>7. PHP Environment</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Memory Limit: " . ini_get('memory_limit') . "</p>";
echo "<p>Max Execution Time: " . ini_get('max_execution_time') . "</p>";

// Test 8: File includes test
echo "<h2>8. File Include Test</h2>";
$includes_to_test = [
    '../includes/db.php',
    '../includes/config.php',
    '../assets/css/styles.css'
];

foreach ($includes_to_test as $file) {
    if (file_exists($file)) {
        echo "<p class='success'>✅ $file exists</p>";
    } else {
        echo "<p class='error'>❌ $file not found</p>";
    }
}

echo "<hr>";
echo "<p><a href='edit-product.php?id=$product_id'>Try actual edit-product.php</a> | <a href='admin-inventory.php'>Back to Inventory</a></p>";
?> 