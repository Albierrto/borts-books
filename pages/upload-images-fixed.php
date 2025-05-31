<?php
session_start();
require_once '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin/login.php');
    exit;
}

$product_id = $_GET['product_id'] ?? null;
if (!$product_id) {
    header('Location: admin.php');
    exit;
}

// Get product details
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "Product not found!";
    exit;
}

// Get existing images with fallback for schema compatibility
try {
    $sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$product_id]);
    $existing_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback for older schema
    $sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$product_id]);
    $existing_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Images - <?php echo htmlspecialchars($product['title']); ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .header { background: #007cba; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .form-section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .drop-zone { border: 2px dashed #ccc; padding: 40px; text-align: center; border-radius: 8px; }
        .drop-zone:hover { border-color: #007cba; background: #f0f8ff; }
        .file-input { margin: 10px 0; }
        .btn { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #005a87; }
        .image-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; }
        .image-item { border: 1px solid #ddd; border-radius: 8px; padding: 10px; text-align: center; }
        .image-item img { max-width: 100%; height: 150px; object-fit: cover; border-radius: 4px; }
        .main-badge { background: #28a745; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
        .btn-small { padding: 5px 10px; font-size: 12px; margin: 2px; }
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
        .alert { padding: 15px; margin: 10px 0; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <a href="edit-product.php?id=<?php echo $product_id; ?>" style="color: #007cba; text-decoration: none;">← Back to Edit Product</a>
        
        <div class="header">
            <h1>📸 Upload Images</h1>
            <p>Product: <?php echo htmlspecialchars($product['title']); ?></p>
        </div>
        
        <div id="message"></div>
        
        <!-- Upload Form -->
        <div class="form-section">
            <h3>Upload New Images</h3>
            <form id="uploadForm" enctype="multipart/form-data">
                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                
                <div class="drop-zone" onclick="document.getElementById('fileInput').click()">
                    <p><strong>Click here to select images</strong></p>
                    <p>Supports JPG, PNG, GIF, WEBP (max 10MB each)</p>
                </div>
                
                <input type="file" id="fileInput" name="images[]" multiple accept="image/*" style="display: none;">
                
                <div id="preview" style="margin-top: 15px;"></div>
                
                <button type="button" id="uploadBtn" class="btn" style="margin-top: 15px; display: none;">Upload Images</button>
            </form>
        </div>
        
        <!-- Existing Images -->
        <?php if (!empty($existing_images)): ?>
        <div class="form-section">
            <h3>Existing Images (<?php echo count($existing_images); ?>)</h3>
            <div class="image-grid">
                <?php foreach ($existing_images as $image): ?>
                <div class="image-item" id="image-<?php echo $image['id']; ?>">
                    <?php 
                    $filename = !empty($image['filename']) ? $image['filename'] : $image['image_url'];
                    ?>
                    <img src="../assets/img/products/<?php echo htmlspecialchars($filename); ?>" alt="Product image">
                    <div style="margin-top: 8px;">
                        <?php if (isset($image['is_main']) && $image['is_main']): ?>
                            <span class="main-badge">Main Image</span>
                        <?php else: ?>
                            <button class="btn btn-success btn-small" onclick="setMainImage(<?php echo $image['id']; ?>)">Set Main</button>
                        <?php endif; ?>
                        <button class="btn btn-danger btn-small" onclick="deleteImage(<?php echo $image['id']; ?>)">Delete</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        const fileInput = document.getElementById('fileInput');
        const preview = document.getElementById('preview');
        const uploadBtn = document.getElementById('uploadBtn');
        const messageDiv = document.getElementById('message');
        
        let selectedFiles = [];
        
        fileInput.addEventListener('change', function(e) {
            selectedFiles = Array.from(e.target.files);
            showPreview();
        });
        
        function showPreview() {
            preview.innerHTML = '';
            if (selectedFiles.length > 0) {
                uploadBtn.style.display = 'inline-block';
                selectedFiles.forEach((file, index) => {
                    const div = document.createElement('div');
                    div.style.cssText = 'display: inline-block; margin: 5px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;';
                    div.innerHTML = `
                        <div>${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)</div>
                        <button type="button" onclick="removeFile(${index})" style="background: #dc3545; color: white; border: none; padding: 2px 6px; border-radius: 2px; margin-top: 4px;">Remove</button>
                    `;
                    preview.appendChild(div);
                });
            } else {
                uploadBtn.style.display = 'none';
            }
        }
        
        function removeFile(index) {
            selectedFiles.splice(index, 1);
            showPreview();
        }
        
        uploadBtn.addEventListener('click', function() {
            if (selectedFiles.length === 0) return;
            
            const formData = new FormData();
            formData.append('product_id', <?php echo $product_id; ?>);
            
            selectedFiles.forEach(file => {
                formData.append('images[]', file);
            });
            
            uploadBtn.disabled = true;
            uploadBtn.textContent = 'Uploading...';
            
            fetch('process-upload-images.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Upload failed. Please try again.', 'error');
            })
            .finally(() => {
                uploadBtn.disabled = false;
                uploadBtn.textContent = 'Upload Images';
            });
        });
        
        function showMessage(text, type) {
            messageDiv.innerHTML = `<div class="alert alert-${type}">${text}</div>`;
            setTimeout(() => messageDiv.innerHTML = '', 5000);
        }
        
        function deleteImage(imageId) {
            if (!confirm('Delete this image?')) return;
            
            fetch('delete-image.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `image_id=${imageId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('image-' + imageId).remove();
                    showMessage('Image deleted', 'success');
                } else {
                    showMessage(data.message || 'Delete failed', 'error');
                }
            });
        }
        
        function setMainImage(imageId) {
            fetch('set-main-image.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `image_id=${imageId}&product_id=<?php echo $product_id; ?>`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Main image updated', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMessage(data.message || 'Update failed', 'error');
                }
            });
        }
    </script>
</body>
</html> 