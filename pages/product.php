<?php
// --- Production error settings: do NOT display errors to users ---
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_error.log');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db.php';
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /pages/shop.php');
    exit;
}
$id = (int)$_GET['id'];

try {
    // Fetch product
    $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        echo '<p>Product not found. <a href="/pages/shop.php">Back to shop</a></p>';
        exit;
    }
    // Fetch all images for this product
    $imgStmt = $db->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC');
    $imgStmt->execute([$id]);
    $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo '<p>There was an error loading this product.</p>';
    exit;
}

// Fetch up to 7 random recommended products (excluding current) with their images
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

// Initialize cart if not set and get cart count
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$num_items_in_cart = array_sum($_SESSION['cart']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        }
        
        .product-detail-image {
            width: 100%;
            height: 500px;
            object-fit: cover;
            display: block;
        }
        
        .image-gallery-container {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .thumbnail-image {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color 0.2s;
        }
        
        .thumbnail-image:hover,
        .thumbnail-image.active {
            border-color: #e63946;
        }
        
        .more-images-indicator {
            position: relative;
            width: 60px;
            height: 80px;
            border-radius: 4px;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
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
                <a href="/pages/search.php" title="Search"><i class="fas fa-search"></i></a>
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
                        <img id="mainImage" class="product-detail-image" src="<?php echo htmlspecialchars($images[0]['image_url']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?> cover">
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
                             onclick="changeMainImage('<?php echo htmlspecialchars($img['image_url']); ?>', this)">
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
                <div class="product-detail-description"><?php echo $product['description'] ? htmlspecialchars($product['description']) : '<span style="color:#aaa">No description available</span>'; ?></div>
                
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
        </main>        <script src="../assets/js/product.js"></script>
</body>
</html> 