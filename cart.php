<?php
session_start();
require_once 'includes/db.php';

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$success_message = '';

// Handle add/update/remove actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add to cart
    if (isset($_POST['product_id']) || isset($_POST['add_to_cart'])) {
        $pid = (int)$_POST['product_id'];
        
        // Get product details for success message
        $stmt = $db->prepare('SELECT title FROM products WHERE id = ?');
        $stmt->execute([$pid]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            if (isset($_SESSION['cart'][$pid])) {
                // Item already in cart - don't add again
                $success_message = $product['title'] . ' is already in your cart!';
            } else {
                $_SESSION['cart'][$pid] = 1; // Always set quantity to 1
                $success_message = $product['title'] . ' has been added to your cart!';
            }
        }
        
        // Check if this is an AJAX request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        if ($isAjax || (isset($_POST['redirect']) && $_POST['redirect'] === 'false')) {
            // Return JSON response for AJAX
            header('Content-Type: application/json');
            echo json_encode([                'success' => true,                'message' => $success_message,                'cart_count' => count($_SESSION['cart']),                'already_in_cart' => isset($_SESSION['cart'][$pid])            ]);
            exit;
        } else {
            // Regular redirect for form submission
            header('Location: cart.php?added=1');
            exit;
        }
    }
    
    // Remove item (we're removing the update quantity functionality)
    if (isset($_POST['remove_id'])) {
        $rid = (int)$_POST['remove_id'];
        unset($_SESSION['cart'][$rid]);
        header('Location: cart.php');
        exit;
    }
}

// Check for success message from redirect
if (isset($_GET['added']) && $_GET['added'] == '1') {
    $success_message = 'Item successfully added to your cart!';
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
        $prod['cart_qty'] = 1; // Always 1 since we only allow one of each item
        $prod['cart_total'] = $prod['price']; // Price * 1
        $subtotal += $prod['cart_total'];
    }
    unset($prod);
}
// Cart count for header
$num_items_in_cart = count($cart); // Count unique items instead of sum
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Bort's Books</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .cart-container { max-width: 900px; margin: 2.5rem auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(35,41,70,0.08); padding: 2rem; }
        .cart-title { font-size: 2rem; font-weight: 800; margin-bottom: 1.5rem; text-align: center; }
        .cart-table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; }
        .cart-table th, .cart-table td { padding: 1rem 0.5rem; text-align: left; }
        .cart-table th { border-bottom: 2px solid #eebbc3; font-size: 1.1rem; }
        .cart-table td { border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
        .cart-img { width: 70px; height: 100px; object-fit: cover; border-radius: 8px; background: #f7f7fa; }
        .cart-remove-btn { background: none; border: none; color: #e63946; font-weight: 700; cursor: pointer; font-size: 1.1rem; }
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
            
            <?php if ($success_message): ?>
                <div style="background: #d4edda; color: #155724; padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; border: 1px solid #c3e6cb; text-align: center; font-weight: 600;">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($products)): ?>
                <div class="empty-cart-msg">Your cart is empty. <a href="/pages/shop.php">Browse manga</a> to get started!</div>
            <?php else: ?>
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
                        <td>1</td>
                        <td>$<?php echo number_format($prod['cart_total'], 2); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <button class="cart-remove-btn" name="remove_id" value="<?php echo $prod['id']; ?>" title="Remove">&times;</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="cart-summary">Total: $<?php echo number_format($subtotal, 2); ?></div>
            <div class="cart-actions">
                <a href="checkout.php" class="cart-btn" style="background:#e63946;color:#fff;">Proceed to Checkout</a>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html> 