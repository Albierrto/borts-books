<?php
echo "Starting upload-images-simple.php...<br>";

session_start();
echo "Session started...<br>";

require_once '../includes/db.php';
echo "Database included...<br>";

// Check if user is admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Please <a href="../admin/login.php">login as admin</a> first.');
}
echo "Admin check passed...<br>";

$product_id = $_GET['product_id'] ?? null;
if (!$product_id) {
    die('No product ID provided. <a href="admin.php">Go back to admin</a>');
}
echo "Product ID: $product_id<br>";

// Get product details
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found! <a href='admin.php'>Go back to admin</a>");
}
echo "Product found: " . htmlspecialchars($product['title']) . "<br>";

// Get existing images - try different approaches
echo "Checking existing images...<br>";
try {
    $sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$product_id]);
    $existing_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($existing_images) . " images using new schema<br>";
} catch (Exception $e) {
    echo "New schema failed: " . $e->getMessage() . "<br>";
    try {
        $sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$product_id]);
        $existing_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Found " . count($existing_images) . " images using basic schema<br>";
    } catch (Exception $e2) {
        die("Both image queries failed: " . $e2->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Upload Images - <?php echo htmlspecialchars($product['title']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .upload-area { border: 2px dashed #ccc; padding: 20px; text-align: center; margin: 20px 0; }
        .existing-images { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin: 20px 0; }
        .image-item { border: 1px solid #ddd; padding: 10px; }
        .image-item img { max-width: 100%; height: 100px; object-fit: cover; }
        .btn { display: inline-block; padding: 10px 20px; background: #007cba; color: white; text-decoration: none; border-radius: 4px; }
        .btn:hover { background: #005a87; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Upload Images - Simple Version</h1>
        
        <p><a href="edit-product.php?id=<?php echo $product_id; ?>">&larr; Back to Edit Product</a></p>
        
        <h2><?php echo htmlspecialchars($product['title']); ?></h2>
        <p><strong>Price:</strong> $<?php echo number_format($product['price'], 2); ?></p>
        <p><strong>Current Images:</strong> <?php echo count($existing_images); ?></p>
        
        <h3>Upload New Images</h3>
        <form method="post" action="process-upload-images.php" enctype="multipart/form-data">
            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
            
            <div class="upload-area">
                <p><strong>Select images to upload:</strong></p>
                <input type="file" name="images[]" multiple accept="image/*" required>
                <p><small>Supports JPG, PNG, GIF, WEBP (max 10MB each)</small></p>
            </div>
            
            <button type="submit" class="btn">Upload Images</button>
        </form>
        
        <?php if (!empty($existing_images)): ?>
        <h3>Existing Images (<?php echo count($existing_images); ?>)</h3>
        <div class="existing-images">
            <?php foreach ($existing_images as $image): ?>
                <div class="image-item">
                    <?php 
                    // Handle both old and new schema
                    if (!empty($image['filename'])) {
                        $image_path = "../assets/img/products/" . htmlspecialchars($image['filename']);
                    } elseif (!empty($image['image_url'])) {
                        $image_path = htmlspecialchars($image['image_url']);
                    } else {
                        $image_path = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2Y4ZjlmYSIvPjx0ZXh0IHg9IjUwIiB5PSI1MCIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjEyIiBmaWxsPSIjNmM3NTdkIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+Tm8gSW1hZ2U8L3RleHQ+PC9zdmc+";
                    }
                    ?>
                    <img src="<?php echo $image_path; ?>" alt="Product Image">
                    
                    <?php if (!empty($image['is_main']) && $image['is_main'] == 1): ?>
                        <p><strong>Main Image</strong></p>
                    <?php endif; ?>
                    
                    <form method="post" action="delete-image.php" style="margin-top: 10px;">
                        <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                        <button type="submit" onclick="return confirm('Delete this image?')" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px;">Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <p><em>No images uploaded yet.</em></p>
        <?php endif; ?>
        
        <hr>
        <p>
            <a href="debug-500.php?product_id=<?php echo $product_id; ?>" class="btn">Run Debug Test</a>
            <a href="admin.php" class="btn">Back to Admin</a>
        </p>
    </div>
</body>
</html> 