<?php
session_start();

try {
    require_once 'includes/db.php';
    require_once 'includes/config.php';
    require_once 'includes/stripe-config.php';
    require_once 'includes/cart.php';
} catch (Exception $e) {
    error_log('Checkout Error: ' . $e->getMessage());
    die('Configuration error. Please check that all required files exist and Stripe is properly configured.');
}

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
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

// Handle order submission
$errors = [];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerInfo = [
        'name' => $_POST['name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
    ];

    // Basic validation
    if (empty($customerInfo['name'])) {
        $errors[] = 'Name is required';
    }
    if (empty($customerInfo['email']) || !filter_var($customerInfo['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    if (empty($customerInfo['phone'])) {
        $errors[] = 'Phone number is required';
    }

    if (empty($errors)) {
        try {
            // Create Stripe Checkout Session
            $session = createStripeCheckoutSession($cart, $customerInfo);
            
            if ($session) {
                // Store customer info in session for later use
                $_SESSION['customer_info'] = $customerInfo;
                
                // Redirect to Stripe Checkout
                header('Location: ' . $session->url);
                exit;
            } else {
                $error = "There was an error creating your checkout session. Please try again.";
            }
        } catch (Exception $e) {
            error_log('Checkout Session Error: ' . $e->getMessage());
            $error = "There was an error processing your request. Please try again or contact support.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Bort's Books</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        .checkout-container { max-width: 900px; margin: 2.5rem auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(35,41,70,0.08); padding: 2rem; }
        .checkout-title { font-size: 2rem; font-weight: 800; margin-bottom: 1.5rem; text-align: center; }
        .checkout-form { max-width: 420px; margin: 0 auto 2rem auto; }
        .checkout-form label { font-weight: 600; margin-bottom: 0.3rem; display: block; }
        .checkout-form input, .checkout-form textarea { width: 100%; padding: 0.7rem; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 1.2rem; font-size: 1rem; }
        .checkout-form textarea { min-height: 80px; }
        .checkout-btn { background: #eebbc3; color: #232946; border: none; border-radius: 30px; padding: 0.9rem 2.2rem; font-weight: 800; font-size: 1.2rem; box-shadow: 0 2px 12px rgba(35,41,70,0.13); transition: background 0.2s; cursor: pointer; }
        .checkout-btn:hover { background: #232946; color: #fff; }
        .order-summary { margin-top: 2rem; }
        .order-summary-title { font-size: 1.3rem; font-weight: 700; margin-bottom: 1rem; }
        .order-summary-table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; }
        .order-summary-table th, .order-summary-table td { padding: 0.7rem 0.5rem; text-align: left; }
        .order-summary-table th { border-bottom: 2px solid #eebbc3; font-size: 1rem; }
        .order-summary-table td { border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
        .order-img { width: 50px; height: 70px; object-fit: cover; border-radius: 6px; background: #f7f7fa; }
        .order-summary-total { text-align: right; font-size: 1.1rem; font-weight: 700; }
        .checkout-errors { background: #ffe0e0; color: #a00; border-radius: 8px; padding: 1em; margin-bottom: 1.5rem; }
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
                    <span class="cart-count"><?php echo array_sum($_SESSION['cart']); ?></span>
                </a>
            </div>
        </div>
    </header>
    <main>
        <div class="checkout-container">
            <div class="checkout-title">Checkout</div>
            
            <?php if (!empty($errors) || !empty($error)): ?>
                <div class="checkout-errors">
                    <?php 
                    if (!empty($errors)) {
                        foreach ($errors as $err) echo htmlspecialchars($err) . '<br>'; 
                    }
                    if (!empty($error)) {
                        echo htmlspecialchars($error);
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <form class="checkout-form" method="POST">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                <button type="submit" class="checkout-btn">Proceed to Payment</button>
            </form>
            
            <div class="order-summary">
                <div class="order-summary-title">Order Summary</div>
                <table class="order-summary-table">
                    <thead>
                        <tr>
                            <th>Cover</th>
                            <th>Title</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $prod): ?>
                        <tr>
                            <td><img class="order-img" src="<?php echo htmlspecialchars($prod['main_image'] ?: 'assets/img/placeholder.png'); ?>" alt="<?php echo htmlspecialchars($prod['title']); ?> cover"></td>
                            <td><?php echo htmlspecialchars($prod['title']); ?></td>
                            <td>$<?php echo number_format($prod['price'], 2); ?></td>
                            <td><?php echo $prod['cart_qty']; ?></td>
                            <td>$<?php echo number_format($prod['cart_total'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="order-summary-total">Total: $<?php echo number_format($subtotal, 2); ?></div>
            </div>
        </div>
    </main>
</body>
</html> 