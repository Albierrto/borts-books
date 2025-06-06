<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

$pageTitle = "Edit Product";
$currentPage = "admin";

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch product details
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: admin.php');
    exit;
}

// Fetch product images
$stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC");
$stmt->execute([$product_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Bort's Books</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f7f7fa; }
        
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
        
        .product-title-link {
            color: #fff;
            text-decoration: none;
            opacity: 0.9;
            transition: opacity 0.2s;
        }
        
        .product-title-link:hover {
            opacity: 1;
            text-decoration: underline;
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
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary);
        }
        
        .tab-button:hover:not(.active) {
            background: #e9ecef;
            color: #495057;
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
        
        .form-grid.single {
            grid-template-columns: 1fr;
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
        
        .dimension-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 0.5rem;
        }
        
        .weight-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }
        
        .input-with-label {
            display: flex;
            flex-direction: column;
        }
        
        .input-with-label small {
            color: #6c757d;
            font-size: 0.85rem;
            margin-top: 0.25rem;
            text-align: center;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
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
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding: 2rem;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
        
        .editor-toolbar {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px 8px 0 0;
            flex-wrap: wrap;
        }
        
        .toolbar-btn {
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 0.5rem 0.75rem;
            cursor: pointer;
            font-size: 0.9rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            transition: all 0.2s;
        }
        
        .toolbar-btn:hover {
            background: #e9ecef;
            border-color: #adb5bd;
        }
        
        .editor-help {
            margin-top: 0.5rem;
            padding: 0.75rem;
            background: #fff3cd;
            border: 2px solid #ffeaa7;
            border-radius: 0 0 8px 8px;
            font-size: 0.9rem;
        }
        
        .description-preview {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
        }
        
        .description-preview h4 {
            margin: 0 0 0.5rem 0;
            color: #333;
        }
        
        .preview-content {
            background: white;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 100px;
        }
        
        .message {
            padding: 1rem;
            margin: 1rem 2rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .shipping-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .shipping-info h4 {
            margin: 0 0 0.5rem 0;
            color: #1565c0;
        }
        
        .shipping-info p {
            margin: 0;
            font-size: 0.9rem;
            color: #1976d2;
        }
        
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .image-item {
            position: relative;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .image-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        
        .image-actions {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            display: flex;
            gap: 0.5rem;
        }
        
        .image-actions button {
            background: rgba(0,0,0,0.7);
            color: #fff;
            border: none;
            border-radius: 4px;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .image-actions button:hover {
            background: rgba(0,0,0,0.9);
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .dimension-inputs,
            .weight-inputs {
                grid-template-columns: 1fr;
            }
            
            .tab-navigation {
                flex-direction: column;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="../index.php" class="logo">Bort's <span>Books</span></a>
            <nav>
                <ul>
                    <li><a href="/index.php">Home</a></li>
                    <li><a href="/pages/shop.php">Shop</a></li>
                    <li><a href="/pages/sell.php">Sell Manga</a></li>
                    <li><a href="/pages/about.php">About</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <a href="admin.php" style="display:inline-block;margin-bottom:1.5rem;color:#232946;font-weight:600;text-decoration:underline;"><i class="fas fa-arrow-left"></i> Back to All Products</a>
        
        <div class="edit-container">
            <div class="edit-header">
                <h1>Edit Product</h1>
                <p><a href="product.php?id=<?php echo $product['id']; ?>" class="product-title-link" title="View Product Page">
                    <i class="fas fa-external-link-alt"></i> <?php echo htmlspecialchars($product['title']); ?>
                </a></p>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="message <?php echo $_SESSION['message_type']; ?>">
                    <i class="fas fa-<?php echo $_SESSION['message_type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                    ?>
                </div>
            <?php endif; ?>

            <form action="process-edit-product.php" method="POST" id="editForm">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                
                <div class="tab-navigation">
                    <button type="button" class="tab-button active" onclick="switchTab('basic')">
                        <i class="fas fa-info-circle"></i> Basic Info
                    </button>
                    <button type="button" class="tab-button" onclick="switchTab('shipping')">
                        <i class="fas fa-shipping-fast"></i> Shipping
                    </button>
                    <button type="button" class="tab-button" onclick="switchTab('images')">
                        <i class="fas fa-images"></i> Images
                    </button>
                </div>
                
                <!-- Basic Info Tab -->
                <div id="basic-tab" class="tab-content active">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="title">Title *</label>
                            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($product['title']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="price">Price *</label>
                            <input type="number" id="price" name="price" step="0.01" value="<?php echo $product['price']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="condition">Condition</label>
                            <select id="condition" name="condition">
                                <option value="New" <?php echo $product['condition'] === 'New' ? 'selected' : ''; ?>>New</option>
                                <option value="Like New" <?php echo $product['condition'] === 'Like New' ? 'selected' : ''; ?>>Like New</option>
                                <option value="Very Good" <?php echo $product['condition'] === 'Very Good' ? 'selected' : ''; ?>>Very Good</option>
                                <option value="Good" <?php echo $product['condition'] === 'Good' ? 'selected' : ''; ?>>Good</option>
                                <option value="Acceptable" <?php echo $product['condition'] === 'Acceptable' ? 'selected' : ''; ?>>Acceptable</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Category</label>
                            <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($product['category'] ?? ''); ?>" placeholder="e.g., Manga, Light Novel">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <div class="editor-toolbar">
                            <button type="button" onclick="insertLink()" class="toolbar-btn" title="Insert Link">
                                <i class="fas fa-link"></i> Link
                            </button>
                            <button type="button" onclick="formatText('bold')" class="toolbar-btn" title="Bold">
                                <i class="fas fa-bold"></i> Bold
                            </button>
                            <button type="button" onclick="formatText('italic')" class="toolbar-btn" title="Italic">
                                <i class="fas fa-italic"></i> Italic
                            </button>
                            <button type="button" onclick="insertLineBreak()" class="toolbar-btn" title="Line Break">
                                <i class="fas fa-level-down-alt"></i> Break
                            </button>
                            <button type="button" onclick="previewDescription()" class="toolbar-btn" title="Preview">
                                <i class="fas fa-eye"></i> Preview
                            </button>
                        </div>
                        <textarea id="description" name="description" rows="8" placeholder="Enter description with HTML formatting..."><?php echo htmlspecialchars($product['description']); ?></textarea>
                        <div class="editor-help">
                            <small>
                                <strong>HTML Tips:</strong> 
                                &lt;a href="URL"&gt;Link Text&lt;/a&gt; • 
                                &lt;b&gt;Bold&lt;/b&gt; • 
                                &lt;i&gt;Italic&lt;/i&gt; • 
                                &lt;br&gt; for line breaks • 
                                &lt;p&gt;Paragraphs&lt;/p&gt; • 
                                &lt;ul&gt;&lt;li&gt;Lists&lt;/li&gt;&lt;/ul&gt;
                            </small>
                        </div>
                        <div id="descriptionPreview" class="description-preview" style="display: none;">
                            <h4>Preview:</h4>
                            <div class="preview-content"></div>
                            <button type="button" onclick="hidePreview()" class="btn btn-secondary" style="margin-top: 0.5rem;">Hide Preview</button>
                        </div>
                    </div>
                </div>
                
                <!-- Shipping Tab -->
                <div id="shipping-tab" class="tab-content">
                    <div class="shipping-info">
                        <h4><i class="fas fa-info-circle"></i> Shipping Information</h4>
                        <p>Configure weight, dimensions, and shipping options for accurate USPS rate calculation. For bulk editing, use the <a href="admin-mass-shipping.php" style="color: #1565c0; font-weight: 600;">Mass Shipping Editor</a>.</p>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Weight</label>
                            <?php
                            $total_weight_oz = $product['weight'] ?? 0;
                            $lbs = floor($total_weight_oz / 16);
                            $oz = $total_weight_oz % 16;
                            ?>
                            <div class="weight-inputs">
                                <div class="input-with-label">
                                    <input type="number" id="weight_lbs" name="weight_lbs" min="0" value="<?php echo $lbs; ?>" placeholder="0">
                                    <small>pounds</small>
                                </div>
                                <div class="input-with-label">
                                    <input type="number" id="weight_oz" name="weight_oz" step="0.1" min="0" max="15.9" value="<?php echo number_format($oz, 1); ?>" placeholder="0.0">
                                    <small>ounces</small>
                                </div>
                            </div>
                            <small style="color: #666; font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                                Typical: Manga 6oz, Light Novel 4oz, Omnibus 16oz
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Dimensions (inches)</label>
                            <?php
                            $dims = explode('x', str_replace(' ', '', $product['dimensions'] ?? ''));
                            $length = $dims[0] ?? '';
                            $width = $dims[1] ?? '';
                            $height = $dims[2] ?? '';
                            ?>
                            <div class="dimension-inputs">
                                <div class="input-with-label">
                                    <input type="number" id="length" name="length" step="0.1" value="<?php echo $length; ?>" placeholder="7.5">
                                    <small>length</small>
                                </div>
                                <div class="input-with-label">
                                    <input type="number" id="width" name="width" step="0.1" value="<?php echo $width; ?>" placeholder="5.0">
                                    <small>width</small>
                                </div>
                                <div class="input-with-label">
                                    <input type="number" id="height" name="height" step="0.1" value="<?php echo $height; ?>" placeholder="0.8">
                                    <small>height</small>
                                </div>
                            </div>
                            <small style="color: #666; font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                                Typical manga: 7.5 × 5.0 × 0.8 inches
                            </small>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="shipping_option">Shipping Option</label>
                            <select id="shipping_option" name="shipping_option" onchange="toggleFlatRate()">
                                <option value="calculated" <?php echo ($product['shipping_option'] ?? 'calculated') === 'calculated' ? 'selected' : ''; ?>>Calculated (USPS Media Mail)</option>
                                <option value="free" <?php echo ($product['shipping_option'] ?? '') === 'free' ? 'selected' : ''; ?>>Free Shipping</option>
                                <option value="flat" <?php echo ($product['shipping_option'] ?? '') === 'flat' ? 'selected' : ''; ?>>Flat Rate</option>
                            </select>
                        </div>

                        <div class="form-group" id="flat-rate-group" style="display: <?php echo ($product['shipping_option'] ?? 'calculated') === 'flat' ? 'block' : 'none'; ?>;">
                            <label for="flat_rate">Flat Rate Amount ($)</label>
                            <input type="number" id="flat_rate" name="flat_rate" step="0.01" value="<?php echo $product['flat_rate'] ?? ''; ?>" placeholder="e.g. 5.99">
                        </div>
                    </div>
                </div>
                
                <!-- Images Tab -->
                <div id="images-tab" class="tab-content">
                    <div class="form-group">
                        <label>Product Images</label>
                        <p style="color: #666; margin-bottom: 1rem;">Manage product images. The first image will be used as the main product image.</p>
                        
                        <?php if (!empty($images)): ?>
                            <div class="image-grid">
                                <?php foreach ($images as $image): ?>
                                    <div class="image-item">
                                        <img src="<?php echo htmlspecialchars($image['image_url']); ?>" alt="Product Image">
                                        <div class="image-actions">
                                            <button type="button" onclick="deleteImage(<?php echo $image['id']; ?>)" title="Delete Image">
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
                            <a href="upload-images.php?product_id=<?php echo $product['id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-upload"></i> Upload Images
                            </a>
                        </div>
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
    </div>

    <script>
        // Tab switching functionality
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked button
            document.querySelector(`[onclick="switchTab('${tabName}')"]`).classList.add('active');
        }
        
        function toggleFlatRate() {
            const select = document.getElementById('shipping_option');
            const flatGroup = document.getElementById('flat-rate-group');
            flatGroup.style.display = select.value === 'flat' ? 'block' : 'none';
        }
        
        // Rich Text Editor Functions
        function insertLink() {
            const url = prompt('Enter URL:');
            const text = prompt('Enter link text:');
            if (url && text) {
                const editor = document.getElementById('description');
                const linkHtml = `<a href="${url}">${text}</a>`;
                insertAtCursor(editor, linkHtml);
            }
        }
        
        function formatText(format) {
            const editor = document.getElementById('description');
            const selectedText = getSelectedText(editor);
            if (selectedText) {
                let formattedText;
                switch(format) {
                    case 'bold':
                        formattedText = `<b>${selectedText}</b>`;
                        break;
                    case 'italic':
                        formattedText = `<i>${selectedText}</i>`;
                        break;
                    default:
                        formattedText = selectedText;
                }
                replaceSelectedText(editor, formattedText);
            } else {
                const placeholder = format === 'bold' ? '<b>Bold Text</b>' : '<i>Italic Text</i>';
                insertAtCursor(editor, placeholder);
            }
        }
        
        function insertLineBreak() {
            const editor = document.getElementById('description');
            insertAtCursor(editor, '<br>');
        }
        
        function insertAtCursor(textarea, text) {
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const value = textarea.value;
            textarea.value = value.substring(0, start) + text + value.substring(end);
            textarea.selectionStart = textarea.selectionEnd = start + text.length;
            textarea.focus();
        }
        
        function getSelectedText(textarea) {
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            return textarea.value.substring(start, end);
        }
        
        function replaceSelectedText(textarea, newText) {
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const value = textarea.value;
            textarea.value = value.substring(0, start) + newText + value.substring(end);
            textarea.selectionStart = textarea.selectionEnd = start + newText.length;
            textarea.focus();
        }
        
        function previewDescription() {
            const description = document.getElementById('description').value;
            const preview = document.getElementById('descriptionPreview');
            const content = preview.querySelector('.preview-content');
            
            content.innerHTML = description;
            preview.style.display = 'block';
        }
        
        function hidePreview() {
            document.getElementById('descriptionPreview').style.display = 'none';
        }
        
        function deleteImage(imageId) {
            if (confirm('Are you sure you want to delete this image?')) {
                fetch('delete-image.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'image_id=' + imageId
                })
                .then(response => response.text())
                .then(data => {
                    if (data === 'success') {
                        location.reload();
                    } else {
                        alert('Error deleting image');
                    }
                });
            }
        }
    </script>
</body>
</html> 