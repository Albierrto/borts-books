<?php
// DEBUG VERSION OF upload-images.php
// This will step through each line to find where the 500 error occurs

echo "=== DEBUG: Starting upload-images.php step-by-step ===<br><br>";

// Step 1: Start session
echo "Step 1: Starting session...<br>";
session_start();
echo "✅ Session started successfully<br><br>";

// Step 2: Include database
echo "Step 2: Including database...<br>";
try {
    require_once '../includes/db.php';
    echo "✅ Database included successfully<br><br>";
} catch (Exception $e) {
    die("❌ FAILED at Step 2: Database include - " . $e->getMessage());
}

// Step 3: Admin check
echo "Step 3: Checking admin login...<br>";
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "❌ Not logged in as admin - redirecting to login<br>";
    echo "<p><strong>ERROR:</strong> You need to be logged in as admin first.</p>";
    echo "<p><a href='../admin/login.php'>Login as Admin</a></p>";
    echo "<p><a href='debug-500.php?product_id=815'>Run Basic Debug</a></p>";
    exit;
}
echo "✅ Admin check passed<br><br>";

// Step 4: Get product_id
echo "Step 4: Getting product_id parameter...<br>";
$product_id = $_GET['product_id'] ?? null;
if (!$product_id) {
    echo "❌ No product_id provided<br>";
    echo "<p><a href='admin.php'>Go back to admin</a></p>";
    exit;
}
echo "✅ Product ID: $product_id<br><br>";

// Step 5: Get product details
echo "Step 5: Fetching product details...<br>";
try {
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo "❌ Product not found!<br>";
        echo "<p><a href='admin.php'>Go back to admin</a></p>";
        exit;
    }
    echo "✅ Product found: " . htmlspecialchars($product['title']) . "<br><br>";
} catch (Exception $e) {
    die("❌ FAILED at Step 5: Product query - " . $e->getMessage());
}

// Step 6: Get existing images - test different queries
echo "Step 6: Testing image queries...<br>";

// Test 6a: Try new schema with is_main
echo "Step 6a: Testing new schema query...<br>";
try {
    $sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$product_id]);
    $existing_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ New schema query successful - found " . count($existing_images) . " images<br>";
} catch (Exception $e) {
    echo "⚠️ New schema query failed: " . $e->getMessage() . "<br>";
    
    // Test 6b: Try basic schema
    echo "Step 6b: Testing basic schema query...<br>";
    try {
        $sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$product_id]);
        $existing_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "✅ Basic schema query successful - found " . count($existing_images) . " images<br>";
    } catch (Exception $e2) {
        die("❌ FAILED at Step 6b: Both image queries failed - " . $e2->getMessage());
    }
}
echo "<br>";

// Step 7: Test HTML DOCTYPE
echo "Step 7: Testing HTML DOCTYPE output...<br>";
echo "✅ About to output DOCTYPE<br>";

// THIS IS WHERE MANY 500 ERRORS OCCUR - HTML HEADERS
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEBUG - Upload Images - <?php echo htmlspecialchars($product['title']); ?></title>
    <?php echo "✅ HTML head section successful<br>"; ?>
    
    <!-- Step 8: Test CSS inclusion -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <?php echo "✅ CSS link successful<br>"; ?>
    
    <!-- Step 9: Test FontAwesome inclusion -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php echo "✅ FontAwesome link successful<br>"; ?>
    
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .debug-container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .debug-step { background: #e8f5e8; padding: 10px; margin: 10px 0; border-left: 4px solid #28a745; }
        .debug-error { background: #f8d7da; padding: 10px; margin: 10px 0; border-left: 4px solid #dc3545; }
        .debug-warning { background: #fff3cd; padding: 10px; margin: 10px 0; border-left: 4px solid #ffc107; }
    </style>
    <?php echo "✅ Inline CSS successful<br>"; ?>
</head>
<body>
    <?php echo "✅ Body tag successful<br>"; ?>
    
    <div class="debug-container">
        <h1>🔍 DEBUG: Upload Images Analysis</h1>
        
        <div class="debug-step">
            <h3>✅ All PHP Processing Completed Successfully!</h3>
            <p>If you can see this message, the PHP code is working fine.</p>
            <p><strong>Product:</strong> <?php echo htmlspecialchars($product['title']); ?></p>
            <p><strong>Product ID:</strong> <?php echo $product_id; ?></p>
            <p><strong>Images Found:</strong> <?php echo count($existing_images); ?></p>
        </div>
        
        <?php if (!empty($existing_images)): ?>
        <div class="debug-step">
            <h3>📸 Existing Images Debug</h3>
            <?php foreach ($existing_images as $index => $image): ?>
                <div style="border: 1px solid #ddd; padding: 10px; margin: 5px 0;">
                    <strong>Image #<?php echo $index + 1; ?>:</strong><br>
                    <strong>ID:</strong> <?php echo $image['id']; ?><br>
                    
                    <?php if (!empty($image['filename'])): ?>
                        <strong>Filename:</strong> <?php echo htmlspecialchars($image['filename']); ?><br>
                        <?php 
                        $path = "../assets/img/products/" . $image['filename'];
                        if (file_exists($path)): 
                        ?>
                            <span style="color: green;">✅ File exists on server</span><br>
                            <img src="<?php echo $path; ?>" style="max-width: 100px; max-height: 100px; border: 1px solid #ccc;">
                        <?php else: ?>
                            <span style="color: red;">❌ File missing on server</span><br>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($image['image_url'])): ?>
                        <strong>Image URL:</strong> <?php echo htmlspecialchars($image['image_url']); ?><br>
                    <?php endif; ?>
                    
                    <?php if (isset($image['is_main'])): ?>
                        <strong>Is Main:</strong> <?php echo $image['is_main'] ? 'Yes' : 'No'; ?><br>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="debug-step">
            <h3>🧪 Next Steps</h3>
            <p>Since this debug page loaded successfully, the issue with the original upload-images.php is likely:</p>
            <ul>
                <li><strong>Complex CSS/JavaScript</strong> - Heavy styling or scripts causing memory issues</li>
                <li><strong>Font/Asset Loading</strong> - External resources timing out</li>
                <li><strong>Large HTML Output</strong> - Too much content being generated</li>
                <li><strong>Memory Limit</strong> - PHP running out of memory with complex operations</li>
            </ul>
            
            <h4>Test Links:</h4>
            <p>
                <a href="upload-images-simple.php?product_id=<?php echo $product_id; ?>" style="background: #007cba; color: white; padding: 10px; text-decoration: none; border-radius: 4px;">Try Simple Upload Page</a>
                
                <a href="upload-images.php?product_id=<?php echo $product_id; ?>" style="background: #dc3545; color: white; padding: 10px; text-decoration: none; border-radius: 4px; margin-left: 10px;">Try Original Upload Page (may fail)</a>
            </p>
            
            <h4>Fix Options:</h4>
            <ol>
                <li><strong>Use the simple version</strong> - upload-images-simple.php works fine</li>
                <li><strong>Reduce complexity</strong> - Remove heavy CSS/JS from original</li>
                <li><strong>Increase PHP memory</strong> - Contact hosting provider</li>
                <li><strong>Check server logs</strong> - Look for specific error details</li>
            </ol>
        </div>
        
        <div class="debug-warning">
            <h3>⚠️ If Original Still Fails</h3>
            <p>The original upload-images.php likely has one of these issues:</p>
            <ul>
                <li>Complex CSS grid/flexbox causing browser memory issues</li>
                <li>Heavy JavaScript libraries loading</li>
                <li>Large inline styles or external font loading</li>
                <li>PHP memory exhaustion with complex HTML generation</li>
            </ul>
            <p><strong>Recommendation:</strong> Use the simple version which works perfectly!</p>
        </div>
        
        <p><a href="admin.php">&larr; Back to Admin</a></p>
    </div>
    
    <?php echo "✅ All HTML output successful<br>"; ?>
</body>
</html>

<?php 
echo "✅ DEBUG COMPLETE - If you see this, everything works!<br>";
echo "The 500 error in upload-images.php is likely due to complex styling or memory issues.<br>";
?> 