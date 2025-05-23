<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../includes/db.php';
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
        ORDER BY is_primary DESC, id ASC LIMIT 1
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
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 2rem;
        }
        .product-card-link {
            text-decoration: none;
            color: inherit;
            display: block;
            transition: transform 0.12s;
        }
        .product-card-link:hover .product-card {
            box-shadow: 0 6px 24px rgba(0,0,0,0.18);
            transform: translateY(-4px) scale(1.03);
        }
        .product-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: box-shadow 0.2s;
        }
        .product-image {
            width: 160px;
            height: 230px;
            object-fit: cover;
            border-radius: 4px;
            background: #f4f4f4;
            margin-bottom: 1rem;
        }
        .product-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-align: center;
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
            margin-bottom: 0.5rem;
            text-align: center;
        }
        @media (max-width: 600px) {
            .shop-header { flex-direction: column; gap: 1rem; }
            .shop-title { font-size: 2rem; }
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
            <div class="search-cart">
                <a href="search.php" title="Search"><i class="fas fa-search"></i></a>
                <a href="cart.php" title="Shopping Cart" class="cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count">0</span>
                </a>
            </div>
        </div>
    </header>
    <main class="container">
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
                <a href="product.php?id=<?php echo $product['id']; ?>" class="product-card-link">
                <div class="product-card" data-title="<?php echo htmlspecialchars(strtolower($product['title'])); ?>">
                    <img class="product-image" 
                        src="<?php echo $product['main_image'] ? htmlspecialchars($product['main_image']) : '../assets/img/placeholder.png'; ?>" 
                        alt="<?php echo htmlspecialchars($product['title']); ?> cover"
                        data-title="<?php echo htmlspecialchars($product['title']); ?>">
                    <div class="product-title"><?php echo htmlspecialchars($product['title']); ?></div>
                    <div class="product-price"><?php echo ($product['price'] > 0) ? '$' . number_format($product['price'], 2) : '<span style="color:#888">Price unavailable</span>'; ?></div>
                    <div class="product-description"><?php echo $product['description'] ? htmlspecialchars($product['description']) : '<span style="color:#aaa">No description</span>'; ?></div>
                </div>
                </a>
            <?php endforeach; ?>
        </div>
    </main>
    <!-- No JS needed for filtering; handled by PHP form -->
</body>
</html> 