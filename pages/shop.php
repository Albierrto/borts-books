<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../includes/db.php';

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cart_count = count($_SESSION['cart']);

$pageTitle = "Shop";
$currentPage = "shop";

// Build filter conditions
$where = [];
$params = [];

if (!empty($_GET['title'])) {
    $where[] = "p.title LIKE ?";
    $params[] = '%' . $_GET['title'] . '%';
}
if (!empty($_GET['min_price'])) {
    $where[] = "p.price >= ?";
    $params[] = $_GET['min_price'];
}
if (!empty($_GET['max_price'])) {
    $where[] = "p.price <= ?";
    $params[] = $_GET['max_price'];
}
if (!empty($_GET['condition'])) {
    $where[] = "p.condition = ?";
    $params[] = $_GET['condition'];
}

$sql = "
    SELECT p.*, (
        SELECT image_url FROM product_images 
        WHERE product_id = p.id 
        ORDER BY id ASC LIMIT 1
    ) AS main_image
    FROM products p
    WHERE p.title IS NOT NULL AND p.title != ''
";
if ($where) {
    $sql .= " AND " . implode(" AND ", $where);
}

$sort = $_GET['sort'] ?? '';
if ($sort == 'price_asc') $sql .= " ORDER BY p.price ASC";
elseif ($sort == 'price_desc') $sql .= " ORDER BY p.price DESC";
elseif ($sort == 'date_asc') $sql .= " ORDER BY p.created_at ASC";
elseif ($sort == 'date_desc') $sql .= " ORDER BY p.created_at DESC";
else $sql .= " ORDER BY p.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Bort's Books</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .shop-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .shop-title {
            font-size: 2.5rem;
            font-weight: 700;
        }
        .shop-search {
            max-width: 350px;
            width: 100%;
        }
        .shop-search input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .mass-edit-link {
            margin-bottom: 2rem;
            display: inline-block;
            background: #e63946;
            color: #fff;
            padding: 0.7rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.15s;
        }
        .mass-edit-link:hover {
            background: #b71c2b;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }
        .product-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: box-shadow 0.2s, transform 0.2s;
            position: relative;
        }
        .product-card:hover {
            box-shadow: 0 6px 24px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        .product-image {
            width: 160px;
            height: 230px;
            object-fit: cover;
            border-radius: 4px;
            background: #f4f4f4;
            margin-bottom: 1rem;
            cursor: pointer;
        }
        .product-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-align: center;
        }
        .product-title a {
            color: inherit;
            text-decoration: none;
        }
        .product-title a:hover {
            color: #e63946;
        }
        .product-price {
            color: var(--primary, #e63946);
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .product-description {
            font-size: 0.98rem;
            color: #444;
            margin-bottom: 1.5rem;
            text-align: center;
            flex-grow: 1;
        }
        .product-actions {
            display: flex;
            gap: 0.5rem;
            width: 100%;
        }
        .view-details-btn {
            flex: 1;
            background: #f8f9fa;
            color: #495057;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 0.6rem 1rem;
            text-decoration: none;
            text-align: center;
            font-weight: 600;
            transition: all 0.2s;
        }
        .view-details-btn:hover {
            background: #e9ecef;
            transform: translateY(-1px);
        }
        .add-to-cart-btn {
            flex: 1;
            background: #e63946;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.6rem 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .add-to-cart-btn:hover {
            background: #d32f3f;
            transform: translateY(-1px);
        }
        /* Price Comparison Banner */
        .price-comparison-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
        }

        .price-comparison-banner h2 {
            margin: 0 0 1rem 0;
            font-size: clamp(1.5rem, 4vw, 2rem);
            font-weight: 800;
        }

        .price-comparison-banner p {
            margin: 0 0 1.5rem 0;
            font-size: clamp(1rem, 2.5vw, 1.2rem);
            opacity: 0.95;
        }

        .competitor-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .competitor-logo {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .competitor-logo:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Hide price banner on mobile */
        @media (max-width: 768px) {
            .price-comparison-banner {
                display: none;
            }
        }

        /* Price Comparison Banner */
        .price-comparison-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
        }

        .price-comparison-banner h2 {
            margin: 0 0 1rem 0;
            font-size: clamp(1.5rem, 4vw, 2rem);
            font-weight: 800;
        }

        .price-comparison-banner p {
            margin: 0 0 1.5rem 0;
            font-size: clamp(1rem, 2.5vw, 1.2rem);
            opacity: 0.95;
        }

        .add-to-cart-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            z-index: 1000;
            display: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .notification.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .notification.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        @media (max-width: 600px) {
            .shop-header { flex-direction: column; gap: 1rem; }
            .shop-title { font-size: 2rem; }
            .products-grid { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
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
                    <li><a href="/pages/track-order.php">Track Order</a></li>
                    <li><a href="/pages/sell.php">Sell Manga</a></li>
                    <li><a href="/pages/about.php">About</a></li>
                </ul>
            </nav>
            <div class="search-cart">
                <a href="/cart.php" title="Shopping Cart" class="cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </a>
            </div>
        </div>
    </header>
    
    <!-- Notification for cart actions -->
    <div id="cartNotification" class="notification"></div>
    
    <main class="container">
        <!-- Price Comparison Banner -->
        <div class="price-comparison-banner">
            <h2><i class="fas fa-trophy"></i> Lowest Prices Guaranteed!</h2>
            <p>We consistently beat our competitors' prices. Compare us to anyone!</p>
            <div class="competitor-logos">
                <div class="competitor-logo">Amazon</div>
                <div class="competitor-logo">eBay</div>
                <div class="competitor-logo">Crunchyroll</div>
                <div class="competitor-logo">Barnes & Noble</div>
                <div class="competitor-logo">RightStuf</div>
            </div>
        </div>

        <div class="shop-header" style="flex-direction:column;align-items:stretch;gap:1.5rem;">
            <div class="shop-title">Shop Manga</div>
            <form id="shopFilters" method="get" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center;background:#fff;border-radius:2em;box-shadow:var(--shadow);padding:1em 1.5em 0.5em 1.5em;margin-bottom:0.5em;">
                <input type="text" name="title" placeholder="Title" value="<?php echo htmlspecialchars($_GET['title'] ?? ''); ?>" style="border-radius:2em;padding:0.5em 1.2em;border:1px solid var(--gray-200);background:var(--gray-100);font-size:1em;">
                <input type="number" name="min_price" placeholder="Min Price" step="0.01" value="<?php echo htmlspecialchars($_GET['min_price'] ?? ''); ?>" style="border-radius:2em;padding:0.5em 1.2em;border:1px solid var(--gray-200);background:var(--gray-100);width:110px;">
                <input type="number" name="max_price" placeholder="Max Price" step="0.01" value="<?php echo htmlspecialchars($_GET['max_price'] ?? ''); ?>" style="border-radius:2em;padding:0.5em 1.2em;border:1px solid var(--gray-200);background:var(--gray-100);width:110px;">
                <select name="condition" style="border-radius:2em;padding:0.5em 1.2em;border:1px solid var(--gray-200);background:var(--gray-100);">
                    <option value="">Any Condition</option>
                    <option value="New" <?php if(($_GET['condition'] ?? '')=='New') echo 'selected'; ?>>New</option>
                    <option value="Like New" <?php if(($_GET['condition'] ?? '')=='Like New') echo 'selected'; ?>>Like New</option>
                    <option value="Very Good" <?php if(($_GET['condition'] ?? '')=='Very Good') echo 'selected'; ?>>Very Good</option>
                    <option value="Good" <?php if(($_GET['condition'] ?? '')=='Good') echo 'selected'; ?>>Good</option>
                    <option value="Acceptable" <?php if(($_GET['condition'] ?? '')=='Acceptable') echo 'selected'; ?>>Acceptable</option>
                </select>
                <select name="sort" style="border-radius:2em;padding:0.5em 1.2em;border:1px solid var(--gray-200);background:var(--gray-100);">
                    <option value="">Sort By</option>
                    <option value="price_asc" <?php if(($_GET['sort'] ?? '')=='price_asc') echo 'selected'; ?>>Price: Low to High</option>
                    <option value="price_desc" <?php if(($_GET['sort'] ?? '')=='price_desc') echo 'selected'; ?>>Price: High to Low</option>
                    <option value="date_desc" <?php if(($_GET['sort'] ?? '')=='date_desc') echo 'selected'; ?>>Newest</option>
                    <option value="date_asc" <?php if(($_GET['sort'] ?? '')=='date_asc') echo 'selected'; ?>>Oldest</option>
                </select>
                <button type="submit" style="border-radius:2em;padding:0.5em 1.5em;background:var(--primary);color:#fff;border:none;font-weight:600;box-shadow:var(--shadow-sm);transition:background 0.2s;">Filter</button>
            </form>
        </div>
        <div class="products-grid" id="productsGrid">
            <?php foreach ($products as $product): ?>
                <div class="product-card" data-title="<?php echo htmlspecialchars(strtolower($product['title'])); ?>">
                    <img class="product-image" 
                        src="<?php echo $product['main_image'] ? htmlspecialchars($product['main_image']) : '../assets/img/placeholder.png'; ?>" 
                        alt="<?php echo htmlspecialchars($product['title']); ?> cover"
                        onclick="window.location.href='product.php?id=<?php echo $product['id']; ?>'"
                        data-title="<?php echo htmlspecialchars($product['title']); ?>">
                    <div class="product-title">
                        <a href="product.php?id=<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['title']); ?></a>
                    </div>
                    <div class="product-price"><?php echo ($product['price'] > 0) ? '$' . number_format($product['price'], 2) : '<span style="color:#888">Price unavailable</span>'; ?></div>
                    <div class="product-description"><?php echo $product['description'] ? htmlspecialchars($product['description']) : '<span style="color:#aaa">No description</span>'; ?></div>
                    
                    <div class="product-actions">
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="view-details-btn">View Details</a>
                        <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>, this)" 
                                <?php echo isset($_SESSION['cart'][$product['id']]) ? 'disabled' : ''; ?>>
                            <?php echo isset($_SESSION['cart'][$product['id']]) ? 'In Cart' : 'Add to Cart'; ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
    
    <script>
        function addToCart(productId, button) {
            // Disable button and show loading state
            button.disabled = true;
            button.textContent = 'Adding...';
            
            // Create form data
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('redirect', 'false');
            
            fetch('/cart.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show notification
                    showNotification(data.message, data.already_in_cart ? 'warning' : 'success');
                    
                    // Update cart count in header
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    }
                    
                    // Update button state
                    button.textContent = 'In Cart';
                    button.disabled = true;
                } else {
                    throw new Error('Failed to add item to cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                button.disabled = false;
                button.textContent = 'Add to Cart';
                showNotification('Error adding item to cart. Please try again.', 'error');
            });
        }
        
        function showNotification(message, type) {
            const notification = document.getElementById('cartNotification');
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.style.display = 'block';
            
            // Hide after 3 seconds
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html> 