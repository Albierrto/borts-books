<?php
session_start();
require_once 'includes/db.php';

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle add/update/remove actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add to cart
    if (isset($_POST['product_id'])) {
        $pid = (int)$_POST['product_id'];
        $qty = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
        if (isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid] += $qty;
        } else {
            $_SESSION['cart'][$pid] = $qty;
        }
        // Redirect to cart to prevent resubmission
        header('Location: cart.php');
        exit;
    }
    // Update quantities
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] as $pid => $qty) {
            $pid = (int)$pid;
            $qty = max(1, (int)$qty);
            if ($qty > 0) {
                $_SESSION['cart'][$pid] = $qty;
            }
        }
        header('Location: cart.php');
        exit;
    }
    // Remove item
    if (isset($_POST['remove_id'])) {
        $rid = (int)$_POST['remove_id'];
        unset($_SESSION['cart'][$rid]);
        header('Location: cart.php');
        exit;
    }
}

// Fetch products in cart
$cart = $_SESSION['cart'];
$products = [];
$subtotal = 0;
if (!empty($cart)) {
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $stmt = $db->query("SELECT * FROM products WHERE id IN ($ids)");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Attach images
    foreach ($products as &$prod) {
        $imgStmt = $db->prepare('SELECT image_url FROM product_images WHERE product_id = ? ORDER BY id ASC LIMIT 1');
        $imgStmt->execute([$prod['id']]);
        $prod['main_image'] = $imgStmt->fetchColumn();
        $prod['cart_qty'] = $cart[$prod['id']];
        $prod['cart_total'] = $prod['price'] * $prod['cart_qty'];
        $subtotal += $prod['cart_total'];
    }
    unset($prod);
}
// Cart count for header
$num_items_in_cart = array_sum($cart);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Bort's Books</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .cart-container { max-width: 900px; margin: 2.5rem auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(35,41,70,0.08); padding: 2rem; }
        .cart-title { font-size: 2rem; font-weight: 800; margin-bottom: 1.5rem; text-align: center; }
        .cart-table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; }
        .cart-table th, .cart-table td { padding: 1rem 0.5rem; text-align: left; }
        .cart-table th { border-bottom: 2px solid #eebbc3; font-size: 1.1rem; }
        .cart-table td { border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
        .cart-img { width: 70px; height: 100px; object-fit: cover; border-radius: 8px; background: #f7f7fa; }
        .cart-remove-btn { background: none; border: none; color: #e63946; font-weight: 700; cursor: pointer; font-size: 1.1rem; }
        .cart-qty-input { width: 60px; padding: 0.4rem; border-radius: 6px; border: 1px solid #ddd; text-align: center; }
        .cart-summary { text-align: right; font-size: 1.2rem; font-weight: 700; margin-bottom: 1.5rem; }
        .cart-actions { text-align: right; }
        .cart-btn { background: #eebbc3; color: #232946; border: none; border-radius: 30px; padding: 0.8rem 2rem; font-weight: 700; font-size: 1.1rem; cursor: pointer; transition: background 0.2s; margin-left: 1rem; }
        .cart-btn:hover { background: #232946; color: #fff; }
        .empty-cart-msg { text-align: center; color: #888; font-size: 1.2rem; margin: 2.5rem 0; }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="index.php" class="logo">Bort's <span>Books</span></a>
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
                    <span class="cart-count"><?php echo $num_items_in_cart; ?></span>
                </a>
            </div>
        </div>
    </header>
    <main>
        <div class="cart-container">
            <div class="cart-title">Your Shopping Cart</div>
            <?php if (empty($products)): ?>
                <div class="empty-cart-msg">Your cart is empty. <a href="/pages/shop.php">Browse manga</a> to get started!</div>
            <?php else: ?>
            <form method="POST">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Cover</th>
                            <th>Title</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $prod): ?>
                        <tr>
                            <td><img class="cart-img" src="<?php echo htmlspecialchars($prod['main_image'] ?: 'assets/img/placeholder.png'); ?>" alt="<?php echo htmlspecialchars($prod['title']); ?> cover"></td>
                            <td><?php echo htmlspecialchars($prod['title']); ?></td>
                            <td>$<?php echo number_format($prod['price'], 2); ?></td>
                            <td><input class="cart-qty-input" type="number" name="quantities[<?php echo $prod['id']; ?>]" value="<?php echo $prod['cart_qty']; ?>" min="1"></td>
                            <td>$<?php echo number_format($prod['cart_total'], 2); ?></td>
                            <td>
                                <button class="cart-remove-btn" name="remove_id" value="<?php echo $prod['id']; ?>" title="Remove">&times;</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="cart-summary">Subtotal: $<?php echo number_format($subtotal, 2); ?></div>
                <div class="cart-actions">
                    <button type="submit" name="update_cart" class="cart-btn">Update Cart</button>
                    <a href="checkout.php" class="cart-btn" style="background:#e63946;color:#fff;">Proceed to Checkout</a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html> 