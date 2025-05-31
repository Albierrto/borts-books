<?php
// Create a clean working version of edit-product.php
echo "<h1>🔧 Creating Clean Edit Product Page</h1>";

// Check if backup already exists
if (file_exists('edit-product-backup.php')) {
    echo "<p>⚠️ Backup already exists. Using existing backup...</p>";
} else {
    // Create backup first
    if (copy('edit-product.php', 'edit-product-backup.php')) {
        echo "<p>✅ Created backup: edit-product-backup.php</p>";
    } else {
        echo "<p>❌ Could not create backup!</p>";
    }
}

echo "<p>🔨 Creating clean version...</p>";

// Create the clean version with minimal, safe modifications
$clean_content = '<?php
session_start();
require_once \'../includes/db.php\';

// Check if user is logged in as admin
if (!isset($_SESSION[\'admin_logged_in\']) || $_SESSION[\'admin_logged_in\'] !== true) {
    header(\'Location: admin.php\');
    exit;
}

$pageTitle = "Edit Product";
$currentPage = "admin";

// Get product ID from URL
$product_id = isset($_GET[\'id\']) ? (int)$_GET[\'id\'] : 0;

// Fetch product details
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header(\'Location: admin.php\');
    exit;
}

// Fetch product images - using basic query for compatibility
$stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC");
$stmt->execute([$product_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Bort\'s Books</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .edit-container {
            max-width: 900px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .edit-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .edit-header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .tab-navigation {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .tab-button {
            flex: 1;
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #6c757d;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .tab-button.active {
            color: #495057;
            background: white;
        }
        
        .tab-button.active::after {
            content: \'\';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary);
        }
        
        .tab-content {
            display: none;
            padding: 2rem;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .image-item {
            position: relative;
            aspect-ratio: 1;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
        }
        
        .image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .image-actions {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            padding: 0.25rem;
        }
        
        .image-actions button {
            border: none;
            background: none;
            color: #dc3545;
            cursor: pointer;
            padding: 0.25rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding: 2rem;
            border-top: 1px solid #dee2e6;
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <div class="edit-header">
            <h1>📝 Edit Product</h1>
            <p><a href="../shop.php?id=<?php echo $product[\'id\']; ?>" class="product-title-link"><?php echo htmlspecialchars($product[\'title\']); ?></a></p>
        </div>
        
        <div class="tab-navigation">
            <button class="tab-button active" onclick="switchTab(\'basic\')">
                <i class="fas fa-info-circle"></i> Basic Info
            </button>
            <button class="tab-button" onclick="switchTab(\'shipping\')">
                <i class="fas fa-shipping-fast"></i> Shipping
            </button>
            <button class="tab-button" onclick="switchTab(\'images\')">
                <i class="fas fa-images"></i> Images
            </button>
        </div>
        
        <form action="process-edit-product.php" method="POST">
            <input type="hidden" name="product_id" value="<?php echo $product[\'id\']; ?>">
            
            <!-- Basic Info Tab -->
            <div id="basic-tab" class="tab-content active">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title">Product Title *</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($product[\'title\']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price ($) *</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $product[\'price\']; ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Product description..."><?php echo htmlspecialchars($product[\'description\']); ?></textarea>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="author">Author</label>
                        <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($product[\'author\']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="manga" <?php echo $product[\'category\'] === \'manga\' ? \'selected\' : \'\'; ?>>Manga</option>
                            <option value="light-novel" <?php echo $product[\'category\'] === \'light-novel\' ? \'selected\' : \'\'; ?>>Light Novel</option>
                            <option value="artbook" <?php echo $product[\'category\'] === \'artbook\' ? \'selected\' : \'\'; ?>>Art Book</option>
                            <option value="figures" <?php echo $product[\'category\'] === \'figures\' ? \'selected\' : \'\'; ?>>Figures</option>
                            <option value="other" <?php echo $product[\'category\'] === \'other\' ? \'selected\' : \'\'; ?>>Other</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Shipping Tab -->
            <div id="shipping-tab" class="tab-content">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Weight</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                            <div>
                                <input type="number" name="weight_lbs" min="0" max="50" step="1" 
                                       value="<?php echo floor(($product[\'weight_oz\'] ?? 0) / 16); ?>" 
                                       placeholder="0">
                                <small style="color: #6c757d; font-size: 0.85rem; text-align: center; display: block;">Pounds</small>
                            </div>
                            <div>
                                <input type="number" name="weight_oz" min="0" max="15" step="1" 
                                       value="<?php echo ($product[\'weight_oz\'] ?? 0) % 16; ?>" 
                                       placeholder="0">
                                <small style="color: #6c757d; font-size: 0.85rem; text-align: center; display: block;">Ounces</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Dimensions (inches)</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.5rem;">
                            <?php 
                            $dimensions = explode(\'x\', $product[\'dimensions\'] ?? \'10x8x6\');
                            $length = $dimensions[0] ?? \'10\';
                            $width = $dimensions[1] ?? \'8\';
                            $height = $dimensions[2] ?? \'6\';
                            ?>
                            <div>
                                <input type="number" name="length" step="0.1" min="0" value="<?php echo $length; ?>" placeholder="10">
                                <small style="color: #6c757d; font-size: 0.85rem; text-align: center; display: block;">Length</small>
                            </div>
                            <div>
                                <input type="number" name="width" step="0.1" min="0" value="<?php echo $width; ?>" placeholder="8">
                                <small style="color: #6c757d; font-size: 0.85rem; text-align: center; display: block;">Width</small>
                            </div>
                            <div>
                                <input type="number" name="height" step="0.1" min="0" value="<?php echo $height; ?>" placeholder="6">
                                <small style="color: #6c757d; font-size: 0.85rem; text-align: center; display: block;">Height</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Images Tab -->
            <div id="images-tab" class="tab-content">
                <h3>Product Images</h3>
                
                <?php if (!empty($images)): ?>
                    <div class="image-grid">
                        <?php foreach ($images as $image): ?>
                            <div class="image-item">
                                <img src="<?php echo htmlspecialchars($image[\'image_url\']); ?>" alt="Product Image">
                                <div class="image-actions">
                                    <button type="button" onclick="deleteImage(<?php echo $image[\'id\']; ?>)" title="Delete Image">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #666; font-style: italic;">No images uploaded yet.</p>
                <?php endif; ?>
                
                <div style="margin-top: 1rem;">
                    <a href="upload-images-production.php?product_id=<?php echo $product[\'id\']; ?>" class="btn btn-secondary">
                        <i class="fas fa-upload"></i> Upload Images
                    </a>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="admin.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
    
    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll(\'.tab-content\').forEach(tab => {
                tab.classList.remove(\'active\');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll(\'.tab-button\').forEach(button => {
                button.classList.remove(\'active\');
            });
            
            // Show selected tab content
            document.getElementById(tabName + \'-tab\').classList.add(\'active\');
            
            // Add active class to clicked button
            event.target.classList.add(\'active\');
        }
        
        function deleteImage(imageId) {
            if (confirm(\'Are you sure you want to delete this image?\')) {
                fetch(\'delete-image.php\', {
                    method: \'POST\',
                    headers: {
                        \'Content-Type\': \'application/x-www-form-urlencoded\',
                    },
                    body: \'image_id=\' + imageId
                })
                .then(response => response.text())
                .then(data => {
                    if (data === \'success\') {
                        location.reload();
                    } else {
                        alert(\'Error deleting image\');
                    }
                });
            }
        }
    </script>
</body>
</html>';

// Write the clean version
if (file_put_contents('edit-product-clean.php', $clean_content)) {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>✅ Success!</h3>";
    echo "<p>Created clean version: <strong>edit-product-clean.php</strong></p>";
    echo "<p>This version:</p>";
    echo "<ul>";
    echo "<li>✅ Removes problematic database schema assumptions</li>";
    echo "<li>✅ Uses simple, compatible image queries</li>";
    echo "<li>✅ Points upload button to working upload-images-production.php</li>";
    echo "<li>✅ Removes complex image path logic that caused errors</li>";
    echo "<li>✅ Uses clean, minimal code</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>🔧 Next Steps</h3>";
    echo "<ol>";
    echo "<li><strong>Test the clean version:</strong> <a href='edit-product-clean.php?id=815' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Test Clean Edit Page</a></li>";
    echo "<li><strong>If it works, replace the original:</strong> Rename edit-product-clean.php to edit-product.php</li>";
    echo "<li><strong>Keep the backup:</strong> edit-product-backup.php is your safety net</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>🎯 What This Fixes</h3>";
    echo "<p>The clean version addresses these issues from your broken edit-product.php:</p>";
    echo "<ul>";
    echo "<li>🔴 <strong>Database Schema Issues:</strong> Uses basic queries compatible with your schema</li>";
    echo "<li>🔴 <strong>Image Path Errors:</strong> Simplified image handling</li>";
    echo "<li>🔴 <strong>Upload Link:</strong> Points to working upload-images-production.php</li>";
    echo "<li>🔴 <strong>Complex Logic:</strong> Removed all problematic modifications</li>";
    echo "</ul>";
    echo "</div>";
    
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>Could not create clean version. Check file permissions.</p>";
    echo "</div>";
}

echo "<p><a href='diagnose-edit-product-error.php'>&larr; Back to Diagnosis</a></p>";
?>';

// Write the file
if (file_put_contents('create-clean-edit-product.php', $clean_content)) {
    echo "✅ Created create-clean-edit-product.php successfully!";
} else {
    echo "❌ Failed to create the file";
}
?> 