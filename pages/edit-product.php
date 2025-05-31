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
        .edit-container {
            max-width: 800px;
            margin: 2rem auto;
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-group textarea {
            min-height: 100px;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .btn:hover {
            background: var(--primary-dark);
        }
        .image-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #ddd;
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
            border-radius: 4px;
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
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .message.success {
            background: #e9f7ef;
            color: #1b5e20;
        }
        .message.error {
            background: #fdecea;
            color: #b71c1c;
        }
        .product-title-link {
            color: #eebbc3;
            text-decoration: none;
            transition: color 0.2s;
        }
        .product-title-link:hover {
            color: #232946;
            text-decoration: underline;
        }
        
        .editor-toolbar {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
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
        
        .toolbar-btn:active {
            background: #dee2e6;
        }
        
        .editor-help {
            margin-top: 0.5rem;
            padding: 0.75rem;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .description-preview {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
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
            <h1>Edit Product: <a href="product.php?id=<?php echo $product['id']; ?>" class="product-title-link" title="View Product Page"><?php echo htmlspecialchars($product['title']); ?></a></h1>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="message <?php echo $_SESSION['message_type']; ?>">
                    <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                    ?>
                </div>
            <?php endif; ?>

            <form action="process-edit-product.php" method="POST">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($product['title']); ?>" required>
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
                        <button type="button" onclick="hidePreview()" class="btn" style="margin-top: 0.5rem;">Hide Preview</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="price">Price</label>
                    <input type="number" id="price" name="price" step="0.01" value="<?php echo $product['price']; ?>" required>
                </div>

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

                                <div class="form-group">                    <label>Weight</label>                    <?php                     $total_weight_oz = $product['weight'] ?? 0;                    $lbs = floor($total_weight_oz / 16);                    $oz = $total_weight_oz % 16;                    ?>                    <div style="display: flex; gap: 1rem; align-items: center;">                        <div style="flex: 1;">                            <input type="number" id="weight_lbs" name="weight_lbs" min="0" value="<?php echo $lbs; ?>" placeholder="0">                            <small style="color: #666; font-size: 0.9rem;">lbs</small>                        </div>                        <div style="flex: 1;">                            <input type="number" id="weight_oz" name="weight_oz" step="0.1" min="0" max="15.9" value="<?php echo number_format($oz, 1); ?>" placeholder="0.0">                            <small style="color: #666; font-size: 0.9rem;">oz</small>                        </div>                    </div>                    <small style="color: #666; font-size: 0.9rem;">Weight for shipping calculation</small>                </div>                <div class="form-group">                    <label>Dimensions (inches)</label>                    <?php                     $dims = explode(' x ', $product['dimensions'] ?? '');                    $length = $dims[0] ?? '';                    $width = $dims[1] ?? '';                    $height = $dims[2] ?? '';                    ?>                    <div style="display: flex; gap: 1rem;">                        <div style="flex: 1;">                            <input type="number" id="length" name="length" step="0.1" value="<?php echo $length; ?>" placeholder="7.5">                            <small style="color: #666; font-size: 0.9rem;">Length</small>                        </div>                        <div style="flex: 1;">                            <input type="number" id="width" name="width" step="0.1" value="<?php echo $width; ?>" placeholder="5.0">                            <small style="color: #666; font-size: 0.9rem;">Width</small>                        </div>                        <div style="flex: 1;">                            <input type="number" id="height" name="height" step="0.1" value="<?php echo $height; ?>" placeholder="0.8">                            <small style="color: #666; font-size: 0.9rem;">Height</small>                        </div>                    </div>                    <small style="color: #666; font-size: 0.9rem;">Length x Width x Height for shipping calculation</small>                </div>

                <div class="form-group">
                    <label for="shipping_option">Shipping Option</label>
                    <select id="shipping_option" name="shipping_option" onchange="toggleFlatRate()">
                        <option value="calculated" <?php echo ($product['shipping_option'] ?? 'calculated') === 'calculated' ? 'selected' : ''; ?>>Calculated (USPS)</option>
                        <option value="free" <?php echo ($product['shipping_option'] ?? '') === 'free' ? 'selected' : ''; ?>>Free Shipping</option>
                        <option value="flat" <?php echo ($product['shipping_option'] ?? '') === 'flat' ? 'selected' : ''; ?>>Flat Rate</option>
                    </select>
                </div>

                <div class="form-group" id="flat-rate-group" style="display: <?php echo ($product['shipping_option'] ?? 'calculated') === 'flat' ? 'block' : 'none'; ?>;">
                    <label for="flat_rate">Flat Rate Amount ($)</label>
                    <input type="number" id="flat_rate" name="flat_rate" step="0.01" value="<?php echo $product['flat_rate'] ?? ''; ?>" placeholder="e.g. 5.99">
                </div>

                <button type="submit" class="btn">Save Changes</button>
                
                <script>
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
                    const previewContent = preview.querySelector('.preview-content');
                    
                    if (description.trim()) {
                        previewContent.innerHTML = description;
                        preview.style.display = 'block';
                    } else {
                        previewContent.innerHTML = '<em>No description to preview</em>';
                        preview.style.display = 'block';
                    }
                }
                
                function hidePreview() {
                    document.getElementById('descriptionPreview').style.display = 'none';
                }
                </script>
            </form>

            <div class="image-section">
                <h2>Product Images</h2>
                
                <!-- Upload new images -->
                <form action="process-upload-images.php" method="POST" enctype="multipart/form-data" style="margin-bottom: 2rem;">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <div class="form-group">
                        <label for="images">Upload New Images</label>
                        <input type="file" id="images" name="images[]" multiple accept="image/*">
                    </div>
                    <button type="submit" class="btn">Upload Images</button>
                </form>

                <!-- Display existing images -->
                <div class="image-grid">
                    <?php foreach ($images as $image): ?>
                        <div class="image-item">
                            <img src="<?php echo htmlspecialchars($image['image_url']); ?>" alt="Product image">
                            <div class="image-actions">
                                <form action="delete-image.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                    <button type="submit" title="Delete image"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 