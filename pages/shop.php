<?php
session_start();
require_once '../includes/db.php';
$pageTitle = "Shop";
$currentPage = "shop";

// Show all products with a non-empty title
$stmt = $db->query("SELECT * FROM products WHERE title IS NOT NULL AND title != ''");
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
        <div class="shop-header">
            <div class="shop-title">Shop Manga</div>
            <div class="shop-search">
                <input type="text" id="searchInput" placeholder="Search manga by title...">
            </div>
        </div>
        <div class="products-grid" id="productsGrid">
            <?php foreach ($products as $product): ?>
                <a href="product.php?id=<?php echo $product['id']; ?>" class="product-card-link">
                <div class="product-card" data-title="<?php echo htmlspecialchars(strtolower($product['title'])); ?>">
                    <img class="product-image" 
                        src="../assets/img/placeholder.png" 
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
    <script>
    // Search filter
    document.getElementById('searchInput').addEventListener('input', function() {
        const val = this.value.toLowerCase();
        document.querySelectorAll('.product-card').forEach(card => {
            card.style.display = card.getAttribute('data-title').includes(val) ? '' : 'none';
        });
    });

    // Kitsu API for all images
    document.querySelectorAll('.product-image').forEach(function(img) {
        const title = img.getAttribute('data-title');
        fetch(`https://kitsu.io/api/edge/manga?filter[text]=${encodeURIComponent(title)}`)
            .then(res => res.json())
            .then(data => {
                if (data.data && data.data.length > 0) {
                    const cover = data.data[0].attributes.posterImage && data.data[0].attributes.posterImage.medium;
                    if (cover) img.src = cover;
                }
            });
    });
    </script>
</body>
</html> 