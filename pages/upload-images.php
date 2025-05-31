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

// Get existing images
$sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC";
$stmt = $db->prepare($sql);
$stmt->execute([$product_id]);
$existing_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Images - <?php echo htmlspecialchars($product['title']); ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .upload-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .upload-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .product-info {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .upload-form {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .drop-zone {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 3rem;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s;
            margin-bottom: 1rem;
        }
        
        .drop-zone.dragover {
            border-color: var(--primary);
            background: #e3f2fd;
        }
        
        .file-input {
            display: none;
        }
        
        .upload-btn {
            background: var(--primary);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 6px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .upload-btn:hover {
            background: #5a6fd8;
        }
        
        .preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .preview-item {
            position: relative;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background: white;
        }
        
        .preview-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .preview-info {
            padding: 0.5rem;
            font-size: 0.9rem;
            color: #666;
        }
        
        .remove-btn {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .existing-images {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .existing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .existing-item {
            position: relative;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .main-badge {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            background: #28a745;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .delete-existing-btn {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .set-main-btn {
            position: absolute;
            bottom: 0.5rem;
            left: 0.5rem;
            background: rgba(40, 167, 69, 0.9);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            cursor: pointer;
        }
        
        .progress-bar {
            width: 100%;
            height: 4px;
            background: #f0f0f0;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 1rem;
            display: none;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--primary);
            width: 0%;
            transition: width 0.3s;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .preview-grid,
            .existing-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .upload-container {
                padding: 0 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <div style="margin-bottom: 1rem;">
            <a href="edit-product.php?id=<?php echo $product_id; ?>" style="display: inline-flex; align-items: center; gap: 0.5rem; color: #666; text-decoration: none; font-weight: 600;">
                <i class="fas fa-arrow-left"></i> Back to Edit Product
            </a>
        </div>
        
        <div class="upload-header">
            <h1><i class="fas fa-images"></i> Upload Images</h1>
            <p>Add high-quality images for better sales</p>
        </div>
        
        <div class="product-info">
            <h3><?php echo htmlspecialchars($product['title']); ?></h3>
            <p><strong>Price:</strong> $<?php echo number_format($product['price'], 2); ?></p>
            <p><strong>Current Images:</strong> <?php echo count($existing_images); ?></p>
        </div>
        
        <div id="message-container"></div>
        
        <div class="upload-form">
            <h3><i class="fas fa-upload"></i> Upload New Images</h3>
            <form id="uploadForm" enctype="multipart/form-data">
                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                
                <div class="drop-zone" id="dropZone">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                    <h4>Drop images here or click to select</h4>
                    <p>Supports JPG, PNG, GIF, WEBP (max 10MB each)</p>
                    <button type="button" class="upload-btn" onclick="document.getElementById('fileInput').click()">
                        <i class="fas fa-folder-open"></i> Select Images
                    </button>
                </div>
                
                <input type="file" id="fileInput" name="images[]" multiple accept="image/*" class="file-input">
                
                <div class="progress-bar" id="progressBar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
            </form>
            
            <div class="preview-grid" id="previewGrid"></div>
        </div>
        
        <?php if (!empty($existing_images)): ?>
        <div class="existing-images">
            <h3><i class="fas fa-image"></i> Existing Images (<?php echo count($existing_images); ?>)</h3>
            <div class="existing-grid">
                <?php foreach ($existing_images as $image): ?>
                <div class="existing-item" data-image-id="<?php echo $image['id']; ?>">
                    <img src="../assets/img/products/<?php echo htmlspecialchars($image['filename']); ?>" 
                         alt="Product image" class="preview-image">
                    <?php if ($image['is_main']): ?>
                        <div class="main-badge">Main Image</div>
                    <?php else: ?>
                        <button class="set-main-btn" onclick="setMainImage(<?php echo $image['id']; ?>)">
                            Set as Main
                        </button>
                    <?php endif; ?>
                    <button class="delete-existing-btn" onclick="deleteExistingImage(<?php echo $image['id']; ?>)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const previewGrid = document.getElementById('previewGrid');
        const progressBar = document.getElementById('progressBar');
        const progressFill = document.getElementById('progressFill');
        const messageContainer = document.getElementById('message-container');
        
        let selectedFiles = [];
        
        // Drag and drop functionality
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
        
        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });
        
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            const files = Array.from(e.dataTransfer.files);
            handleFiles(files);
        });
        
        fileInput.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            handleFiles(files);
        });
        
        function handleFiles(files) {
            const imageFiles = files.filter(file => file.type.startsWith('image/'));
            
            if (imageFiles.length !== files.length) {
                showMessage('Some files were skipped (only images allowed)', 'error');
            }
            
            imageFiles.forEach(file => {
                if (file.size > 10 * 1024 * 1024) { // 10MB limit
                    showMessage(`${file.name} is too large (max 10MB)`, 'error');
                    return;
                }
                
                selectedFiles.push(file);
                createPreview(file);
            });
            
            if (selectedFiles.length > 0) {
                uploadFiles();
            }
        }
        
        function createPreview(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="Preview" class="preview-image">
                    <div class="preview-info">${file.name}</div>
                    <button class="remove-btn" onclick="removePreview(this, '${file.name}')">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                previewGrid.appendChild(previewItem);
            };
            reader.readAsDataURL(file);
        }
        
        function removePreview(button, fileName) {
            selectedFiles = selectedFiles.filter(file => file.name !== fileName);
            button.parentElement.remove();
        }
        
        function uploadFiles() {
            if (selectedFiles.length === 0) return;
            
            const formData = new FormData();
            formData.append('product_id', <?php echo $product_id; ?>);
            
            selectedFiles.forEach(file => {
                formData.append('images[]', file);
            });
            
            progressBar.style.display = 'block';
            
            const xhr = new XMLHttpRequest();
            
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressFill.style.width = percentComplete + '%';
                }
            });
            
            xhr.addEventListener('load', () => {
                progressBar.style.display = 'none';
                
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        showMessage(response.message, 'success');
                        selectedFiles = [];
                        previewGrid.innerHTML = '';
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        showMessage(response.message, 'error');
                    }
                } else {
                    showMessage('Upload failed. Please try again.', 'error');
                }
            });
            
            xhr.addEventListener('error', () => {
                progressBar.style.display = 'none';
                showMessage('Upload failed. Please try again.', 'error');
            });
            
            xhr.open('POST', 'process-upload-images.php');
            xhr.send(formData);
        }
        
        function showMessage(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert ${type}`;
            alertDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
            
            messageContainer.innerHTML = '';
            messageContainer.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
        
        function deleteExistingImage(imageId) {
            if (!confirm('Are you sure you want to delete this image?')) return;
            
            fetch('delete-image.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `image_id=${imageId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector(`[data-image-id="${imageId}"]`).remove();
                    showMessage('Image deleted successfully', 'success');
                } else {
                    showMessage(data.message || 'Failed to delete image', 'error');
                }
            })
            .catch(error => {
                showMessage('Failed to delete image', 'error');
            });
        }
        
        function setMainImage(imageId) {
            fetch('set-main-image.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `image_id=${imageId}&product_id=<?php echo $product_id; ?>`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Main image updated successfully', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage(data.message || 'Failed to set main image', 'error');
                }
            })
            .catch(error => {
                showMessage('Failed to set main image', 'error');
            });
        }
    </script>
</body>
</html> 