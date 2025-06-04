<?php
require_once '../includes/security.php';
require_once '../includes/admin-auth.php';
require_once '../includes/db.php';

// Start secure session
secure_session_start();

// Set security headers
set_security_headers();

// Require admin authentication
if (!admin_is_logged_in()) {
    header('Location: ../admin/admin-login.php');
    exit;
}

// Check rate limiting
if (!check_rate_limit('upload_images_simple', 10, 300)) {
    http_response_code(429);
    die('Too many upload page requests. Please wait before trying again.');
}

// Validate and sanitize product ID
$product_id = isset($_GET['product_id']) ? validate_int($_GET['product_id']) : null;
if (!$product_id || $product_id <= 0) {
    header('Location: admin-dashboard.php');
    exit;
}

// Log access for security monitoring
log_security_event('upload_images_simple_access', ['product_id' => $product_id], 'low');

// Get product details with prepared statement
try {
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        log_security_event('invalid_product_access', ['product_id' => $product_id], 'medium');
        die("Product not found! <a href='admin-dashboard.php'>Go back to admin</a>");
    }
} catch (Exception $e) {
    log_security_event('database_error', ['error' => $e->getMessage()], 'high');
    die("Database error occurred. Please try again later.");
}

// Get existing images with error handling
try {
    $sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$product_id]);
    $existing_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback for older schema
    try {
        $sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$product_id]);
        $existing_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e2) {
        log_security_event('image_query_failed', ['error' => $e2->getMessage()], 'medium');
        $existing_images = [];
    }
}

// Generate CSRF token
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='grad' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%23667eea;stop-opacity:1' /%3E%3Cstop offset='100%25' style='stop-color:%23764ba2;stop-opacity:1' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='15' fill='url(%23grad)'/%3E%3Cpath d='M25 20h50c2.5 0 4.5 2 4.5 4.5v51c0 2.5-2 4.5-4.5 4.5H25c-2.5 0-4.5-2-4.5-4.5v-51c0-2.5 2-4.5 4.5-4.5z' fill='white'/%3E%3Cpath d='M30 30h40v5H30z' fill='%23667eea'/%3E%3Cpath d='M30 40h35v3H30z' fill='%23999'/%3E%3Cpath d='M30 47h30v3H30z' fill='%23999'/%3E%3Cpath d='M30 54h25v3H30z' fill='%23999'/%3E%3C/svg%3E">
    <title>Simple Upload Images - <?php echo htmlspecialchars($product['title']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .upload-area { border: 2px dashed #ccc; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
        .existing-images { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin: 20px 0; }
        .image-item { border: 1px solid #ddd; padding: 10px; border-radius: 8px; background: #f9f9f9; }
        .image-item img { max-width: 100%; height: 100px; object-fit: cover; border-radius: 4px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007cba; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn:hover { background: #005a87; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .alert { padding: 15px; margin: 10px 0; border-radius: 4px; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Upload Images - Simple Version</h1>
        
        <p><a href="edit-product.php?id=<?php echo $product_id; ?>">&larr; Back to Edit Product</a></p>
        
        <div class="alert alert-info">
            <strong>Security Notice:</strong> This is a secure admin-only upload interface with CSRF protection and rate limiting.
        </div>
        
        <h2><?php echo htmlspecialchars($product['title']); ?></h2>
        <p><strong>Price:</strong> $<?php echo number_format($product['price'], 2); ?></p>
        <p><strong>Current Images:</strong> <?php echo count($existing_images); ?></p>
        
        <h3>Upload New Images</h3>
        <form method="post" action="process-upload-images.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
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
                    // Secure image path handling
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
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this image?')">Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <p><em>No images uploaded yet.</em></p>
        <?php endif; ?>
        
        <hr>
        <p>
            <a href="admin-dashboard.php" class="btn">Back to Admin Dashboard</a>
        </p>
    </div>
</body>
</html> 