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
// Fetch up to 7 random recommended products (excluding current)
$recStmt = $db->prepare("SELECT * FROM products WHERE id != ? AND title IS NOT NULL AND title != '' ORDER BY RAND() LIMIT 7");
$recStmt->execute([$id]);
$recommended = $recStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['title']); ?> - Bort's Books</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .product-detail-container {
            max-width: 700px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .product-detail-image {
            width: 220px;
            height: 320px;
            object-fit: cover;
            border-radius: 6px;
            background: #f4f4f4;
            margin-bottom: 1.5rem;
        }
        .product-detail-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-align: center;
        }
        .product-detail-price {
            color: var(--primary, #e63946);
            font-size: 1.3rem;
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
            text-align: center;
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
        </div>
    </header>
    <main>
        <div class="product-detail-container">
            <?php if ($images && count($images) > 0): ?>
                <div style="display:flex;gap:10px;justify-content:center;margin-bottom:1.5rem;flex-wrap:wrap;">
                <?php foreach ($images as $img): ?>
                    <img class="product-detail-image" src="<?php echo htmlspecialchars($img['image_url']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?> cover">
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <img class="product-detail-image" src="../assets/img/placeholder.png" alt="<?php echo htmlspecialchars($product['title']); ?> cover">
            <?php endif; ?>
            <div class="product-detail-title"><?php echo htmlspecialchars($product['title']); ?></div>
            <div class="product-detail-price"><?php echo ($product['price'] > 0) ? '$' . number_format($product['price'], 2) : '<span style="color:#888">Price unavailable</span>'; ?></div>
            <div class="product-detail-condition">Condition: <?php echo htmlspecialchars($product['condition']); ?></div>
            <div class="product-detail-description"><?php echo $product['description'] ? htmlspecialchars($product['description']) : '<span style="color:#aaa">No description</span>'; ?></div>
            <!-- Add to Cart Button -->
            <form action="/pages/cart.php" method="POST" style="margin-top:1.5rem;">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <button type="submit" name="add_to_cart" class="btn" style="background:#e63946;color:#fff;padding:0.9rem 2.2rem;border-radius:30px;font-weight:800;font-size:1.2rem;box-shadow:0 2px 12px rgba(35,41,70,0.13);transition:background 0.2s;">Add to Cart</button>
            </form>
            <a href="/pages/shop.php" class="back-link">&larr; Back to Shop</a>
        </div>
        <?php if ($recommended): ?>
        <div style="max-width:660px;margin:2rem auto 0 auto;position:relative;">
            <h3 style="margin-bottom:1rem;">Other Recommended Manga</h3>
            <button id="prevBtn" style="position:absolute;left:-50px;top:50%;transform:translateY(-50%);background:#2a9d8f;color:#fff;border:none;border-radius:50%;width:40px;height:40px;cursor:pointer;z-index:10;box-shadow:0 2px 8px rgba(0,0,0,0.12);">&lt;</button>
            <div style="width:660px;overflow:hidden;display:inline-block;">
                <div id="carousel" style="display:flex;transition:transform 0.5s cubic-bezier(.4,2,.6,1);">
                    <?php foreach ($recommended as $rec): ?>
                    <div class="carousel-card" style="background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.07);padding:1rem;width:200px;text-align:center;flex-shrink:0;margin-right:10px;">
                        <a href="/pages/product.php?id=<?php echo $rec['id']; ?>">
                            <img class="rec-manga-img" data-title="<?php echo htmlspecialchars($rec['title']); ?>" src="<?php echo htmlspecialchars($rec['main_image'] ?? '../assets/img/placeholder.png'); ?>" alt="<?php echo htmlspecialchars($rec['title']); ?> cover" style="width:110px;height:160px;object-fit:cover;border-radius:4px;background:#f4f4f4;margin-bottom:0.5rem;">
                        </a>
                        <div style="font-weight:600;font-size:1.05rem;margin-bottom:0.3rem;"><?php echo htmlspecialchars($rec['title']); ?></div>
                        <div style="color:#e63946;font-weight:700;">$<?php echo number_format($rec['price'],2); ?></div>
                        <a href="/pages/product.php?id=<?php echo $rec['id']; ?>" style="display:inline-block;margin-top:0.5rem;color:#2a9d8f;font-weight:600;text-decoration:underline;">View</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <button id="nextBtn" style="position:absolute;right:-50px;top:50%;transform:translateY(-50%);background:#2a9d8f;color:#fff;border:none;border-radius:50%;width:40px;height:40px;cursor:pointer;z-index:10;box-shadow:0 2px 8px rgba(0,0,0,0.12);">&gt;</button>
        </div>
        <script>
        const visibleCards = 3;
        const cardWidth = 210; // 200px + 10px margin
        let currentIndex = 0;
        const carousel = document.getElementById('carousel');
        const items = carousel.children;
        const totalItems = items.length;

        function updateCarousel() {
            carousel.style.transform = `translateX(-${currentIndex * cardWidth}px)`;
        }
        document.getElementById('prevBtn').addEventListener('click', function() {
            if (currentIndex > 0) {
                currentIndex--;
                updateCarousel();
            }
        });
        document.getElementById('nextBtn').addEventListener('click', function() {
            if (currentIndex < totalItems - visibleCards) {
                currentIndex++;
                updateCarousel();
            }
        });
        </script>
        <?php endif; ?>
    </main>
</body>
</html> 