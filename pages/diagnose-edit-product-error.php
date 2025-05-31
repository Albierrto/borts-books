<?php
// Comprehensive Edit Product Error Diagnosis
echo "<h1>🔍 Edit Product Error Diagnosis</h1>";
echo "<p>Let's systematically find what broke the edit-product.php page...</p>";

// Test 1: Basic PHP and environment
echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #17a2b8;'>";
echo "<h3>Test 1: Environment Check</h3>";
echo "✅ PHP Version: " . PHP_VERSION . "<br>";
echo "✅ Current Time: " . date('Y-m-d H:i:s') . "<br>";
echo "✅ Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "✅ Upload Max: " . ini_get('upload_max_filesize') . "<br>";
echo "</div>";

// Test 2: Session and Database
echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745;'>";
echo "<h3>Test 2: Core Dependencies</h3>";
try {
    session_start();
    echo "✅ Session started<br>";
} catch (Exception $e) {
    echo "❌ Session error: " . $e->getMessage() . "<br>";
}

try {
    require_once '../includes/db.php';
    echo "✅ Database connected<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}
echo "</div>";

// Test 3: File Analysis
echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107;'>";
echo "<h3>Test 3: File Analysis</h3>";

if (file_exists('edit-product.php')) {
    echo "✅ File exists<br>";
    echo "📁 Size: " . number_format(filesize('edit-product.php')) . " bytes<br>";
    
    // Read file content for analysis
    $content = file_get_contents('edit-product.php');
    
    // Check for syntax issues
    if (strpos($content, '<?php') === 0) {
        echo "✅ Starts with PHP tag<br>";
    } else {
        echo "❌ Doesn't start with PHP tag<br>";
    }
    
    // Check for common syntax errors
    $php_open_tags = substr_count($content, '<?php');
    $php_close_tags = substr_count($content, '?>');
    echo "📊 PHP tags: {$php_open_tags} open, {$php_close_tags} close<br>";
    
    // Check for specific problematic code patterns
    $issues = [];
    
    if (strpos($content, '$image_path') !== false) {
        $issues[] = "Complex image path logic detected";
    }
    
    if (strpos($content, 'file_meta') !== false) {
        $issues[] = "New schema fields being used";
    }
    
    if (strpos($content, 'is_main DESC') !== false) {
        $issues[] = "Modified database query detected";
    }
    
    if (!empty($issues)) {
        echo "⚠️ Potential issues found:<br>";
        foreach ($issues as $issue) {
            echo "  • $issue<br>";
        }
    } else {
        echo "✅ No obvious syntax issues detected<br>";
    }
    
} else {
    echo "❌ File doesn't exist<br>";
}
echo "</div>";

// Test 4: Database Schema Check
echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545;'>";
echo "<h3>Test 4: Database Schema Check</h3>";

try {
    // Check product table
    $stmt = $db->prepare("DESCRIBE products");
    $stmt->execute();
    echo "✅ Products table accessible<br>";
    
    // Check product_images table structure
    $stmt = $db->prepare("DESCRIBE product_images");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $has_filename = false;
    $has_is_main = false;
    $has_image_url = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'filename') $has_filename = true;
        if ($column['Field'] === 'is_main') $has_is_main = true;
        if ($column['Field'] === 'image_url') $has_image_url = true;
    }
    
    echo "📊 Product Images Schema:<br>";
    echo ($has_filename ? "✅" : "❌") . " filename column<br>";
    echo ($has_is_main ? "✅" : "❌") . " is_main column<br>";
    echo ($has_image_url ? "✅" : "❌") . " image_url column<br>";
    
    if (!$has_filename || !$has_is_main) {
        echo "⚠️ Missing new schema columns - this may cause errors<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database schema error: " . $e->getMessage() . "<br>";
}
echo "</div>";

// Test 5: Product Data Check
echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #6f42c1;'>";
echo "<h3>Test 5: Product Data Check</h3>";

try {
    $product_id = 815;
    
    // Basic product query
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo "✅ Product 815 found: " . htmlspecialchars($product['title']) . "<br>";
    } else {
        echo "⚠️ Product 815 not found<br>";
    }
    
    // Image query with fallback
    try {
        $stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC");
        $stmt->execute([$product_id]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "✅ New schema image query: " . count($images) . " images<br>";
    } catch (Exception $e) {
        echo "⚠️ New schema failed, trying basic query...<br>";
        try {
            $stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC");
            $stmt->execute([$product_id]);
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "✅ Basic image query: " . count($images) . " images<br>";
        } catch (Exception $e2) {
            echo "❌ Both image queries failed: " . $e2->getMessage() . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Product data error: " . $e->getMessage() . "<br>";
}
echo "</div>";

// Test 6: Try to execute edit-product.php safely
echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #fd7e14;'>";
echo "<h3>Test 6: Safe Execution Test</h3>";

// Use output buffering to catch any errors
ob_start();
$execution_error = null;

try {
    // Don't include, but validate syntax
    $temp_file = tempnam(sys_get_temp_dir(), 'syntax_check');
    file_put_contents($temp_file, file_get_contents('edit-product.php'));
    
    $output = shell_exec("php -l $temp_file 2>&1");
    unlink($temp_file);
    
    if (strpos($output, 'No syntax errors') !== false) {
        echo "✅ No syntax errors detected<br>";
    } else {
        echo "❌ Syntax errors found:<br>";
        echo "<pre style='background: #f8d7da; padding: 10px; border-radius: 4px;'>" . htmlspecialchars($output) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "⚠️ Cannot check syntax: " . $e->getMessage() . "<br>";
}

$output = ob_get_clean();
echo $output;
echo "</div>";

// Solutions
echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745;'>";
echo "<h3>🛠️ Recommended Solutions</h3>";

echo "<h4>Immediate Actions:</h4>";
echo "<ol>";
echo "<li><strong>Create Clean Backup:</strong> <a href='create-clean-edit-product.php' style='background: #28a745; color: white; padding: 4px 8px; text-decoration: none; border-radius: 3px;'>Create Clean Version</a></li>";
echo "<li><strong>Test Minimal Version:</strong> <a href='test-minimal-edit.php?id=815' style='background: #007cba; color: white; padding: 4px 8px; text-decoration: none; border-radius: 3px;'>Test Basic Edit</a></li>";
echo "<li><strong>Use Working Upload:</strong> <a href='upload-images-production.php?product_id=815' style='background: #ffc107; color: black; padding: 4px 8px; text-decoration: none; border-radius: 3px;'>Direct Upload</a></li>";
echo "</ol>";

echo "<h4>Root Cause Analysis:</h4>";
echo "<p>Based on the diagnostic above, the most likely causes are:</p>";
echo "<ul>";
echo "<li>🔴 <strong>Database Schema Mismatch:</strong> Code expects new columns that don't exist</li>";
echo "<li>🔴 <strong>Complex Image Logic:</strong> Added image path handling has syntax errors</li>";
echo "<li>🔴 <strong>Query Modifications:</strong> Modified ORDER BY clause incompatible with schema</li>";
echo "<li>🔴 <strong>Property Access Errors:</strong> Accessing undefined object properties</li>";
echo "</ul>";

echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107;'>";
echo "<h3>📋 Next Steps</h3>";
echo "<p>1. <strong>First:</strong> Check the diagnostic results above</p>";
echo "<p>2. <strong>Then:</strong> Click 'Create Clean Version' to get a working edit page</p>";
echo "<p>3. <strong>Finally:</strong> Test the clean version before making any modifications</p>";
echo "</div>";
?> 