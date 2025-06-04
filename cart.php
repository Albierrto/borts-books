<?php
session_start();

// Include configuration first
require_once 'includes/config.php';

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$success_message = '';
$db_error = false;
$db = null;

// Try to connect to database with error handling
try {
    require_once 'includes/db.php';
} catch (Exception $e) {
    $db_error = true;
    error_log('Database connection error in cart.php: ' . $e->getMessage());
    // Continue without database - cart will still work for display
}

// Only try to clean up cart if database is working
if (!$db_error) {
    try {
        require_once 'includes/cart-display.php';
        cleanupCart();
    } catch (Exception $e) {
        error_log('Cart cleanup error: ' . $e->getMessage());
        // Don't let cleanup errors break the cart
    }
}

// Handle add/update/remove actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$db_error) {
    // Apply rate limiting
    $ip = $_SERVER['REMOTE_ADDR'];
    $rate_limit_key = "cart_operations_{$ip}";
    if (!check_rate_limit($rate_limit_key)) {
        http_response_code(429);
        die('Too many cart operations. Please try again later.');
    }
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        http_response_code(403);
        die('Invalid request');
    }
    
    // Add to cart
    if (isset($_POST['product_id']) || isset($_POST['add_to_cart'])) {
        $pid = validate_input($_POST['product_id'] ?? $_POST['add_to_cart'], 'int');
        if ($pid === false) {
            http_response_code(400);
            die('Invalid product ID');
        }
        
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
            echo json_encode([
                'success' => true,
                'message' => $success_message,
                'cart_count' => count($_SESSION['cart']),
                'already_in_cart' => isset($_SESSION['cart'][$pid])
            ]);
            exit;
        } else {
            // Regular redirect for form submission
            header('Location: cart.php?added=1');
            exit;
        }
    }
    
    // Remove item
    if (isset($_POST['remove_id'])) {
        $rid = validate_input($_POST['remove_id'], 'int');
        if ($rid === false) {
            http_response_code(400);
            die('Invalid product ID');
        }
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

if (!empty($cart) && !$db_error) {
    try {
        $ids = array_keys($cart);
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $db->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute($ids);
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
    } catch (Exception $e) {
        $db_error = true;
        error_log('Database error in cart.php: ' . $e->getMessage());
    }
} elseif (!empty($cart) && $db_error) {
    // If database is down but cart has items, show placeholder data
    foreach ($cart as $product_id => $quantity) {
        $products[] = [
            'id' => $product_id,
            'title' => 'Product #' . $product_id . ' (Database Unavailable)',
            'price' => 0.00,
            'main_image' => 'assets/img/placeholder.png',
            'cart_qty' => 1,
            'cart_total' => 0.00
        ];
    }
}

// Cart count for header
$num_items_in_cart = count($cart); // Count unique items instead of sum
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='grad' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%23667eea;stop-opacity:1' /%3E%3Cstop offset='100%25' style='stop-color:%23764ba2;stop-opacity:1' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='15' fill='url(%23grad)'/%3E%3Cpath d='M25 20h50c2.5 0 4.5 2 4.5 4.5v51c0 2.5-2 4.5-4.5 4.5H25c-2.5 0-4.5-2-4.5-4.5v-51c0-2.5 2-4.5 4.5-4.5z' fill='white'/%3E%3Cpath d='M30 30h40v5H30z' fill='%23667eea'/%3E%3Cpath d='M30 40h35v3H30z' fill='%23999'/%3E%3Cpath d='M30 47h30v3H30z' fill='%23999'/%3E%3Cpath d='M30 54h25v3H30z' fill='%23999'/%3E%3Cpath d='M30 61h20v3H30z' fill='%23999'/%3E%3C/svg%3E">
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
        .db-error-msg { background: #ffe0e0; color: #a00; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; text-align: center; }
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
                <a href="cart.php" title="Shopping Cart" class="cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count">0</span>
                </a>
            </div>
        </div>
    </header>
    <main>
        <div class="cart-container">
            <div class="cart-title">Your Shopping Cart</div>
            
            <?php if ($db_error): ?>
                <div class="db-error-msg">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Database temporarily unavailable. Your cart items are preserved. Please try again shortly or contact support if the issue persists.
                </div>
            <?php endif; ?>
            
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
                            <?php if (!$db_error): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <button class="cart-remove-btn" name="remove_id" value="<?php echo $prod['id']; ?>" title="Remove">&times;</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="cart-summary">Total: $<?php echo number_format($subtotal, 2); ?></div>
            <div class="cart-actions">
                <?php if (!$db_error && !empty($products)): ?>
                    <a href="checkout.php" class="cart-btn" style="background:#e63946;color:#fff;">Proceed to Checkout</a>
                <?php elseif ($db_error): ?>
                    <button class="cart-btn" disabled style="background:#ccc;color:#666;">Checkout Unavailable (Database Error)</button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
    <script src="assets/js/mobile-nav.js"></script>
</body>
</html> 