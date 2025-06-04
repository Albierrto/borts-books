<?php
require_once '../includes/security.php';
require_once '../includes/db.php';

// Start secure session
secure_session_start();

// Set security headers  
set_security_headers();

// Production error settings: do NOT display errors to users
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_error.log');

// Check rate limiting
if (!check_rate_limit('product_view', 50, 3600)) {
    http_response_code(429);
    die('Too many requests. Please wait before trying again.');
}

// Check if user is admin
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Validate product ID
$id = isset($_GET['id']) ? validate_int($_GET['id']) : null;
if (!$id || $id <= 0) {
    header('Location: /pages/shop.php');
    exit;
}

try {
    // Fetch product with prepared statement
    $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        log_security_event('invalid_product_access', ['product_id' => $id], 'low');
        header('Location: /pages/shop.php');
        exit;
    }
    
    // Fetch all images for this product
    $imgStmt = $db->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC');
    $imgStmt->execute([$id]);
    $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    log_security_event('product_page_error', [
        'product_id' => $id,
        'error' => $e->getMessage()
    ], 'medium');
    header('Location: /pages/shop.php');
    exit;
}

// Fetch recommended products with limit for security
try {
$recStmt = $db->prepare("
    SELECT p.*, (
        SELECT image_url FROM product_images 
        WHERE product_id = p.id 
        ORDER BY id ASC LIMIT 1
    ) AS main_image
    FROM products p
    WHERE p.id != ? AND p.title IS NOT NULL AND p.title != '' 
    ORDER BY RAND() LIMIT 7
");
$recStmt->execute([$id]);
$recommended = $recStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recommended = [];
    log_security_event('recommended_products_error', ['error' => $e->getMessage()], 'low');
}

// Initialize cart securely
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$num_items_in_cart = is_array($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='grad' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%23667eea;stop-opacity:1' /%3E%3Cstop offset='100%25' style='stop-color:%23764ba2;stop-opacity:1' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='15' fill='url(%23grad)'/%3E%3Cpath d='M25 20h50c2.5 0 4.5 2 4.5 4.5v51c0 2.5-2 4.5-4.5 4.5H25c-2.5 0-4.5-2-4.5-4.5v-51c0-2.5 2-4.5 4.5-4.5z' fill='white'/%3E%3Cpath d='M30 30h40v5H30z' fill='%23667eea'/%3E%3Cpath d='M30 40h35v3H30z' fill='%23999'/%3E%3Cpath d='M30 47h30v3H30z' fill='%23999'/%3E%3Cpath d='M30 54h25v3H30z' fill='%23999'/%3E%3Cpath d='M30 61h20v3H30z' fill='%23999'/%3E%3C/svg%3E">
    <title><?php echo htmlspecialchars($product['title']); ?> - Bort's Books</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .product-detail-container {
            max-width: 900px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: start;
        }
        
        .product-images-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .main-image-container {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            background: #f4f4f4;
            aspect-ratio: 1;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .product-detail-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            cursor: zoom-in;
        }
        
        .image-gallery-container {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: center;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .thumbnail-image {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 6px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s;
            aspect-ratio: 1;
        }
        
        .thumbnail-image:hover,
        .thumbnail-image.active {
            border-color: #e63946;
            transform: scale(1.05);
        }
        
        .more-images-indicator {
            position: relative;
            width: 70px;
            height: 70px;
            border-radius: 6px;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            aspect-ratio: 1;
        }
        
        .more-images-indicator:hover {
            background: rgba(0,0,0,0.8);
            transform: scale(1.05);
        }
        
        /* Image Modal for Gallery */
        .image-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
            backdrop-filter: blur(5px);
        }
        
        .image-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
            margin: auto;
        }
        
        .modal-image {
            width: 100%;
            height: auto;
            max-height: 90vh;
            object-fit: contain;
            border-radius: 8px;
        }
        
        .modal-close {
            position: absolute;
            top: -40px;
            right: 0;
            color: white;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            background: rgba(0,0,0,0.5);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        
        .modal-nav:hover {
            background: rgba(0,0,0,0.7);
        }
        
        .modal-prev {
            left: -60px;
        }
        
        .modal-next {
            right: -60px;
        }
        
        .modal-counter {
            position: absolute;
            bottom: -40px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            background: rgba(0,0,0,0.5);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .product-info-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .product-detail-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            line-height: 1.2;
        }
        .product-detail-price {
            color: var(--primary, #e63946);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .product-detail-condition {
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 1rem;
        }
        .product-detail-description {
            font-size: 1.05rem;
            color: #333;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            color: #555;
            text-decoration: none;
            font-weight: 500;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 0.5rem 1.2rem;
            background: #f8f8f8;
            transition: background 0.15s;
        }
        .back-link:hover {
            background: #eaeaea;
        }
        
        .recommended-section {
            max-width: 900px;
            margin: 2rem auto 0 auto;
            position: relative;
        }
        
        .carousel-container {
            overflow: hidden;
            border-radius: 8px;
        }
        
        .carousel-track {
            display: flex;
            transition: transform 0.5s cubic-bezier(.4,2,.6,1);
        }
        
        .carousel-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            padding: 1rem;
            width: 200px;
            text-align: center;
            flex-shrink: 0;
            margin-right: 15px;
            transition: transform 0.2s;
        }
        
        .carousel-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }
        
        .rec-manga-img {
            width: 110px;
            height: 160px;
            object-fit: cover;
            border-radius: 4px;
            background: #f4f4f4;
            margin-bottom: 0.5rem;
        }
        
        .carousel-nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: #2a9d8f;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        
        .carousel-nav-btn:hover {
            background: #218c78;
        }
        
        .carousel-nav-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .carousel-prev {
            left: -50px;
        }
        
        .carousel-next {
            right: -50px;
        }
        
        @media (max-width: 768px) {
            .product-detail-container {
                grid-template-columns: 1fr;
                gap: 2rem;
                padding: 1.5rem;
            }
            .carousel-prev {
                left: -30px;
            }
            .carousel-next {
                right: -30px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="/index.php" class="logo">Bort's <span>Books</span></a>
            <nav>
                <ul>
                    <li><a href="/index.php">Home</a></li>
                    <li><a href="/pages/shop.php">Shop</a></li>
                    <li><a href="/pages/collections.php">Collections</a></li>
                    <li><a href="/pages/sell.php">Sell Manga</a></li>
                    <li><a href="/pages/about.php">About</a></li>
                </ul>
            </nav>
            <div class="search-cart">
                <a href="/cart.php" title="Shopping Cart" class="cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $num_items_in_cart; ?></span>
                </a>
            </div>
        </div>
    </header>
    <main>
        <div class="product-detail-container">
            <div class="product-images-section">
                <div class="main-image-container">
            <?php if ($images && count($images) > 0): ?>
                        <img id="mainImage" class="product-detail-image" src="<?php echo htmlspecialchars($images[0]['image_url']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?> cover" onclick="openImageModal(0)">
                    <?php else: ?>
                        <img id="mainImage" class="product-detail-image" src="../assets/img/placeholder.png" alt="<?php echo htmlspecialchars($product['title']); ?> cover">
                    <?php endif; ?>
                </div>
                
                <?php if ($images && count($images) > 1): ?>
                <div class="image-gallery-container">
                    <?php 
                    $maxThumbnails = 5;
                    $visibleImages = array_slice($images, 0, $maxThumbnails);
                    $remainingCount = count($images) - $maxThumbnails;
                    ?>
                    
                    <?php foreach ($visibleImages as $index => $img): ?>
                        <img class="thumbnail-image <?php echo $index === 0 ? 'active' : ''; ?>" 
                             src="<?php echo htmlspecialchars($img['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['title']); ?> view <?php echo $index + 1; ?>"
                             onclick="changeMainImage('<?php echo htmlspecialchars($img['image_url']); ?>', this)"
                             loading="lazy">
                <?php endforeach; ?>
                    
                    <?php if ($remainingCount > 0): ?>
                        <div class="more-images-indicator" onclick="showAllImages()">
                            +<?php echo $remainingCount; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            </div>
            
            <div class="product-info-section">
            <div class="product-detail-title"><?php echo htmlspecialchars($product['title']); ?></div>
            <div class="product-detail-price"><?php echo ($product['price'] > 0) ? '$' . number_format($product['price'], 2) : '<span style="color:#888">Price unavailable</span>'; ?></div>
            <div class="product-detail-condition">Condition: <?php echo htmlspecialchars($product['condition']); ?></div>
                <div class="product-detail-description"><?php echo $product['description'] ? $product['description'] : '<span style="color:#aaa">No description available</span>'; ?></div>
                
            <!-- Add to Cart Button -->
                <div id="addToCartNotification" style="display:none;background:#d4edda;color:#155724;padding:1rem;margin-bottom:1rem;border-radius:8px;border:1px solid #c3e6cb;text-align:center;font-weight:600;">
                    Item added to cart successfully!
                </div>
                <form id="addToCartForm" action="/cart.php" method="POST" style="margin-top:1.5rem;">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <input type="hidden" name="redirect" value="false">
                    <button type="submit" id="addToCartBtn" class="btn" style="background:#e63946;color:#fff;padding:1rem 2.5rem;border-radius:30px;font-weight:800;font-size:1.2rem;box-shadow:0 2px 12px rgba(35,41,70,0.13);transition:all 0.2s;border:none;cursor:pointer;width:100%;">
                        Add to Cart
                    </button>
            </form>
            <a href="/pages/shop.php" class="back-link">&larr; Back to Shop</a>
            </div>
        </div>
        
        <?php if ($recommended): ?>
        <div class="recommended-section">
            <h3 style="margin-bottom:1.5rem;font-size:1.8rem;font-weight:700;">Other Recommended Manga</h3>
            <button id="prevBtn" class="carousel-nav-btn carousel-prev">&lt;</button>
            <div class="carousel-container" style="width:100%;overflow:hidden;">
                <div id="carousel" class="carousel-track">
                    <?php foreach ($recommended as $rec): ?>
                    <div class="carousel-card">
                        <a href="/pages/product.php?id=<?php echo $rec['id']; ?>">
                            <img class="rec-manga-img" 
                                 src="<?php echo $rec['main_image'] ? htmlspecialchars($rec['main_image']) : '../assets/img/placeholder.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($rec['title']); ?> cover">
                        </a>
                        <div style="font-weight:600;font-size:1.05rem;margin-bottom:0.3rem;"><?php echo htmlspecialchars($rec['title']); ?></div>
                        <div style="color:#e63946;font-weight:700;margin-bottom:0.5rem;">$<?php echo number_format($rec['price'],2); ?></div>
                        <a href="/pages/product.php?id=<?php echo $rec['id']; ?>" style="display:inline-block;color:#2a9d8f;font-weight:600;text-decoration:underline;">View Details</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <button id="nextBtn" class="carousel-nav-btn carousel-next">&gt;</button>
        </div>
        <?php endif; ?>
        </main>        <script src="../assets/js/product.js?v=2.1"></script>
        
    <!-- Image Modal -->
    <div id="imageModal" class="image-modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeImageModal()">&times;</button>
            <button class="modal-nav modal-prev" onclick="prevImage()">&lt;</button>
            <img id="modalImage" class="modal-image" src="" alt="">
            <button class="modal-nav modal-next" onclick="nextImage()">&gt;</button>
            <div class="modal-counter">
                <span id="modalCounter">1 / 1</span>
            </div>
        </div>
    </div>
    
    <?php if ($isAdmin): ?>
    <!-- Admin Panel - Only visible to admins -->
    <div id="adminPanel" class="admin-panel">
        <div class="admin-panel-header">
            <h3><i class="fas fa-cog"></i> Admin Tools</h3>
            <button onclick="toggleAdminPanel()" class="admin-toggle">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
        <div class="admin-panel-content">
            <div class="admin-actions">
                <a href="/pages/edit-product-clean.php?id=<?php echo $id; ?>" class="admin-btn admin-btn-primary">
                    <i class="fas fa-edit"></i> Full Edit
                </a>
                <button onclick="quickEditDescription()" class="admin-btn admin-btn-secondary">
                    <i class="fas fa-align-left"></i> Quick Edit Description
                </button>
                <button onclick="managePhotos()" class="admin-btn admin-btn-secondary">
                    <i class="fas fa-images"></i> Manage Photos
                </button>
                <button onclick="quickEditPrice()" class="admin-btn admin-btn-secondary">
                    <i class="fas fa-dollar-sign"></i> Edit Price
                </button>
            </div>
            
            <!-- Quick Edit Forms (hidden by default) -->
            <div id="quickEditDescription" class="quick-edit-form" style="display: none;">
                <h4>Quick Edit Description</h4>
                <div class="editor-toolbar">
                    <button type="button" onclick="insertLink()" class="toolbar-btn" title="Insert Link">
                        <i class="fas fa-link"></i>
                    </button>
                    <button type="button" onclick="formatText('bold')" class="toolbar-btn" title="Bold">
                        <i class="fas fa-bold"></i>
                    </button>
                    <button type="button" onclick="formatText('italic')" class="toolbar-btn" title="Italic">
                        <i class="fas fa-italic"></i>
                    </button>
                    <button type="button" onclick="insertLineBreak()" class="toolbar-btn" title="Line Break">
                        <i class="fas fa-level-down-alt"></i>
                    </button>
                </div>
                <textarea id="descriptionEditor" rows="6" placeholder="Enter description with HTML formatting..."><?php echo htmlspecialchars($product['description']); ?></textarea>
                <div class="quick-edit-actions">
                    <button onclick="saveDescription()" class="admin-btn admin-btn-success">Save</button>
                    <button onclick="cancelQuickEdit('quickEditDescription')" class="admin-btn admin-btn-cancel">Cancel</button>
                </div>
                <div class="editor-help">
                    <small>
                        <strong>HTML Tips:</strong> 
                        &lt;a href="URL"&gt;Link Text&lt;/a&gt; • 
                        &lt;b&gt;Bold&lt;/b&gt; • 
                        &lt;i&gt;Italic&lt;/i&gt; • 
                        &lt;br&gt; for line breaks
                    </small>
                </div>
            </div>
            
            <div id="quickEditPrice" class="quick-edit-form" style="display: none;">
                <h4>Quick Edit Price</h4>
                <input type="number" id="priceEditor" step="0.01" value="<?php echo $product['price']; ?>" placeholder="0.00">
                <div class="quick-edit-actions">
                    <button onclick="savePrice()" class="admin-btn admin-btn-success">Save</button>
                    <button onclick="cancelQuickEdit('quickEditPrice')" class="admin-btn admin-btn-cancel">Cancel</button>
                </div>
            </div>
            
            <div id="photoManager" class="quick-edit-form" style="display: none;">
                <h4>Photo Management</h4>
                <div class="photo-upload-section">
                    <input type="file" id="newPhotos" multiple accept="image/*" style="margin-bottom: 1rem;">
                    <button onclick="uploadPhotos()" class="admin-btn admin-btn-primary">Upload New Photos</button>
                </div>
                <div class="existing-photos">
                    <h5>Current Photos (<?php echo count($images); ?>)</h5>
                    <div class="admin-photo-grid">
                        <?php foreach ($images as $index => $img): ?>
                        <div class="admin-photo-item" data-image-id="<?php echo $img['id']; ?>">
                            <img src="<?php echo htmlspecialchars($img['image_url']); ?>" alt="Photo <?php echo $index + 1; ?>">
                            <div class="admin-photo-actions">
                                <button onclick="deletePhoto(<?php echo $img['id']; ?>)" class="admin-btn-small admin-btn-danger" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <span class="photo-number"><?php echo $index + 1; ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="quick-edit-actions">
                    <button onclick="cancelQuickEdit('photoManager')" class="admin-btn admin-btn-cancel">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .admin-panel {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #fff;
            border: 2px solid #e63946;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            z-index: 1000;
            max-width: 400px;
            min-width: 300px;
        }
        
        .admin-panel-header {
            background: #e63946;
            color: white;
            padding: 0.75rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        
        .admin-panel-header h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .admin-toggle {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 1rem;
            transition: transform 0.2s;
        }
        
        .admin-panel.collapsed .admin-toggle {
            transform: rotate(-90deg);
        }
        
        .admin-panel-content {
            padding: 1rem;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .admin-panel.collapsed .admin-panel-content {
            display: none;
        }
        
        .admin-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .admin-btn {
            padding: 0.5rem 0.75rem;
            border: none;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        
        .admin-btn-primary {
            background: #e63946;
            color: white;
        }
        
        .admin-btn-primary:hover {
            background: #d32f2f;
        }
        
        .admin-btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .admin-btn-secondary:hover {
            background: #e9ecef;
        }
        
        .admin-btn-success {
            background: #28a745;
            color: white;
        }
        
        .admin-btn-success:hover {
            background: #218838;
        }
        
        .admin-btn-cancel {
            background: #6c757d;
            color: white;
        }
        
        .admin-btn-cancel:hover {
            background: #5a6268;
        }
        
        .admin-btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .admin-btn-danger:hover {
            background: #c82333;
        }
        
        .quick-edit-form {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1rem;
        }
        
        .quick-edit-form h4 {
            margin: 0 0 1rem 0;
            font-size: 1.1rem;
            color: #333;
        }
        
        .quick-edit-form textarea,
        .quick-edit-form input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .quick-edit-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .editor-toolbar {
            display: flex;
            gap: 0.25rem;
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .toolbar-btn {
            background: none;
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 0.25rem 0.5rem;
            cursor: pointer;
            font-size: 0.8rem;
            color: #333;
        }
        
        .toolbar-btn:hover {
            background: #f8f9fa;
        }
        
        .editor-help {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #fff3cd;
            border-radius: 4px;
        }
        
        .admin-photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .admin-photo-item {
            position: relative;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .admin-photo-item img {
            width: 100%;
            height: 80px;
            object-fit: cover;
        }
        
        .admin-photo-actions {
            position: absolute;
            top: 2px;
            right: 2px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .admin-btn-small {
            padding: 0.25rem;
            font-size: 0.7rem;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .photo-number {
            background: rgba(0,0,0,0.7);
            color: white;
            font-size: 0.7rem;
            padding: 0.1rem 0.3rem;
            border-radius: 3px;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .admin-panel {
                bottom: 10px;
                right: 10px;
                left: 10px;
                max-width: none;
            }
            
            .admin-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <?php endif; ?>
    
        <script>
        // Pass PHP images array to JavaScript
        const productImages = <?php echo json_encode($images); ?>;
        let currentModalIndex = 0;
        
        // Debug: Log all available images
        console.log('=== PRODUCT IMAGES DEBUG ===');
        console.log('Total images found:', productImages ? productImages.length : 0);
        if (productImages && productImages.length > 0) {
            console.log('All image URLs:');
            productImages.forEach((img, index) => {
                console.log(`${index + 1}:`, img.image_url);
            });
        }
        console.log('=== END DEBUG ===');
        
        <?php if ($isAdmin): ?>
        // Admin Panel Functions
        function toggleAdminPanel() {
            const panel = document.getElementById('adminPanel');
            panel.classList.toggle('collapsed');
        }
        
        function quickEditDescription() {
            hideAllQuickEdits();
            document.getElementById('quickEditDescription').style.display = 'block';
        }
        
        function quickEditPrice() {
            hideAllQuickEdits();
            document.getElementById('quickEditPrice').style.display = 'block';
        }
        
        function managePhotos() {
            hideAllQuickEdits();
            document.getElementById('photoManager').style.display = 'block';
        }
        
        function hideAllQuickEdits() {
            document.getElementById('quickEditDescription').style.display = 'none';
            document.getElementById('quickEditPrice').style.display = 'none';
            document.getElementById('photoManager').style.display = 'none';
        }
        
        function cancelQuickEdit(formId) {
            document.getElementById(formId).style.display = 'none';
        }
        
        // Rich Text Editor Functions
        function insertLink() {
            const url = prompt('Enter URL:');
            const text = prompt('Enter link text:');
            if (url && text) {
                const editor = document.getElementById('descriptionEditor');
                const linkHtml = `<a href="${url}">${text}</a>`;
                insertAtCursor(editor, linkHtml);
            }
        }
        
        function formatText(format) {
            const editor = document.getElementById('descriptionEditor');
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
            const editor = document.getElementById('descriptionEditor');
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
        
        // Save Functions
        function saveDescription() {
            const description = document.getElementById('descriptionEditor').value;
            
            fetch('/pages/admin-quick-edit.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update_description',
                    product_id: <?php echo $id; ?>,
                    description: description
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the description on the page
                    const descElement = document.querySelector('.product-detail-description');
                    if (description.trim()) {
                        descElement.innerHTML = description;
                    } else {
                        descElement.innerHTML = '<span style="color:#aaa">No description available</span>';
                    }
                    cancelQuickEdit('quickEditDescription');
                    showAdminMessage('Description updated successfully!', 'success');
                } else {
                    showAdminMessage('Error updating description: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAdminMessage('Error updating description', 'error');
            });
        }
        
        function savePrice() {
            const price = document.getElementById('priceEditor').value;
            
            fetch('/pages/admin-quick-edit.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update_price',
                    product_id: <?php echo $id; ?>,
                    price: price
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the price on the page
                    const priceElement = document.querySelector('.product-detail-price');
                    priceElement.innerHTML = price > 0 ? '$' + parseFloat(price).toFixed(2) : '<span style="color:#888">Price unavailable</span>';
                    cancelQuickEdit('quickEditPrice');
                    showAdminMessage('Price updated successfully!', 'success');
                } else {
                    showAdminMessage('Error updating price: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAdminMessage('Error updating price', 'error');
            });
        }
        
        function uploadPhotos() {
            const fileInput = document.getElementById('newPhotos');
            const files = fileInput.files;
            
            if (files.length === 0) {
                showAdminMessage('Please select photos to upload', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('product_id', <?php echo $id; ?>);
            for (let i = 0; i < files.length; i++) {
                formData.append('images[]', files[i]);
            }
            
            fetch('/pages/process-upload-images.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAdminMessage('Photos uploaded successfully!', 'success');
                    // Refresh the page to show new photos
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAdminMessage('Error uploading photos: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAdminMessage('Error uploading photos', 'error');
            });
        }
        
        function deletePhoto(imageId) {
            if (!confirm('Are you sure you want to delete this photo?')) {
                return;
            }
            
            fetch('/pages/delete-image.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'image_id=' + imageId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the photo from the admin grid
                    const photoItem = document.querySelector(`[data-image-id="${imageId}"]`);
                    if (photoItem) {
                        photoItem.remove();
                    }
                    showAdminMessage('Photo deleted successfully!', 'success');
                    // Refresh the page to update the main gallery
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAdminMessage('Error deleting photo: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAdminMessage('Error deleting photo', 'error');
            });
        }
        
        function showAdminMessage(message, type) {
            // Create or update admin message
            let messageDiv = document.getElementById('adminMessage');
            if (!messageDiv) {
                messageDiv = document.createElement('div');
                messageDiv.id = 'adminMessage';
                messageDiv.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 1rem;
                    border-radius: 4px;
                    z-index: 1001;
                    font-weight: 600;
                    max-width: 300px;
                `;
                document.body.appendChild(messageDiv);
            }
            
            messageDiv.textContent = message;
            messageDiv.style.background = type === 'success' ? '#d4edda' : '#f8d7da';
            messageDiv.style.color = type === 'success' ? '#155724' : '#721c24';
            messageDiv.style.border = type === 'success' ? '1px solid #c3e6cb' : '1px solid #f5c6cb';
            messageDiv.style.display = 'block';
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 3000);
        }
        <?php endif; ?>
        
        // Test function to verify modal works
        function testModal() {
            console.log('Test modal function called');
            const modal = document.getElementById('imageModal');
            if (modal) {
                modal.classList.add('active');
                console.log('Modal should be visible now');
            } else {
                console.error('Modal element not found');
            }
        }
        
        // Add test button for debugging (remove after fixing)
        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.search.includes('debug=modal')) {
                const testBtn = document.createElement('button');
                testBtn.textContent = 'Test Modal';
                testBtn.onclick = testModal;
                testBtn.style.cssText = 'position:fixed;top:10px;right:10px;z-index:9999;background:red;color:white;padding:10px;';
                document.body.appendChild(testBtn);
            }
            
            // Add debug info if debug mode is on
            if (window.location.search.includes('debug=images')) {
                const debugDiv = document.createElement('div');
                debugDiv.style.cssText = 'position:fixed;top:50px;right:10px;z-index:9999;background:black;color:white;padding:10px;max-width:300px;font-size:12px;';
                debugDiv.innerHTML = `
                    <strong>Images Debug:</strong><br>
                    Total: ${productImages ? productImages.length : 0}<br>
                    <button onclick="openImageModal(0)" style="margin:5px;">Open Modal</button><br>
                    <button onclick="nextImage()" style="margin:5px;">Next Image</button><br>
                    <button onclick="prevImage()" style="margin:5px;">Prev Image</button>
                `;
                document.body.appendChild(debugDiv);
            }
        });
        </script>
    <script src="../assets/js/mobile-nav.js"></script>
</body>
</html> 