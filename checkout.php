<?php
// Enable debugging for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

echo "<!-- DEBUG: Session started -->\n";
echo "<!-- DEBUG: Cart contents: " . json_encode($_SESSION['cart'] ?? []) . " -->\n";

try {
    require_once 'includes/db.php';
    echo "<!-- DEBUG: Database included successfully -->\n";
    
    require_once 'includes/config.php';
    echo "<!-- DEBUG: Config included successfully -->\n";
    
    require_once 'includes/stripe-config.php';
    echo "<!-- DEBUG: Stripe config included successfully -->\n";
    
    require_once 'includes/cart.php';
    echo "<!-- DEBUG: Cart utilities included successfully -->\n";
    
} catch (Exception $e) {
    echo "<!-- DEBUG: Include error: " . $e->getMessage() . " -->\n";
    echo "<!-- DEBUG: File: " . $e->getFile() . " Line: " . $e->getLine() . " -->\n";
    error_log('Checkout Error: ' . $e->getMessage());
    die('Configuration error: ' . $e->getMessage() . '<br>Please check that all required files exist and Stripe is properly configured.');
}

echo "<!-- DEBUG: All includes loaded successfully -->\n";

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    echo "<!-- DEBUG: Cart is empty, redirecting to cart.php -->\n";
    header('Location: cart.php');
    exit;
}

echo "<!-- DEBUG: Cart is not empty, proceeding -->\n";

// Fetch products in cart
$cart = $_SESSION['cart'];
$products = [];
$subtotal = 0;

echo "<!-- DEBUG: Cart data: " . json_encode($cart) . " -->\n";

if (!empty($cart)) {
    try {
        $ids = implode(',', array_map('intval', array_keys($cart)));
        echo "<!-- DEBUG: Product IDs to fetch: " . $ids . " -->\n";
        
        $stmt = $db->query("SELECT * FROM products WHERE id IN ($ids)");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<!-- DEBUG: Found " . count($products) . " products -->\n";
        
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
        
        echo "<!-- DEBUG: Calculated subtotal: $" . number_format($subtotal, 2) . " -->\n";
        
    } catch (Exception $e) {
        echo "<!-- DEBUG: Database error: " . $e->getMessage() . " -->\n";
        die('Database error: ' . $e->getMessage());
    }
}

// Handle order submission
$errors = [];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<!-- DEBUG: Form submitted -->\n";
    
    $customerInfo = [
        'name' => $_POST['name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
    ];
    
    echo "<!-- DEBUG: Customer info: " . json_encode($customerInfo) . " -->\n";

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

    echo "<!-- DEBUG: Validation errors: " . json_encode($errors) . " -->\n";

    if (empty($errors)) {
        try {
            echo "<!-- DEBUG: Attempting to create Stripe checkout session -->\n";
            
            // Create Stripe Checkout Session
            $session = createStripeCheckoutSession($cart, $customerInfo);
            
            echo "<!-- DEBUG: Stripe session result: " . ($session ? 'Success' : 'Failed') . " -->\n";
            
            if ($session) {
                // Store customer info in session for later use
                $_SESSION['customer_info'] = $customerInfo;
                
                echo "<!-- DEBUG: Redirecting to: " . $session->url . " -->\n";
                
                // Redirect to Stripe Checkout
                header('Location: ' . $session->url);
                exit;
            } else {
                $error = "There was an error creating your checkout session. Please try again.";
            }
        } catch (Exception $e) {
            echo "<!-- DEBUG: Stripe checkout error: " . $e->getMessage() . " -->\n";
            error_log('Checkout Session Error: ' . $e->getMessage());
            $error = "There was an error processing your request: " . $e->getMessage();
        }
    }
}

echo "<!-- DEBUG: Rendering page -->\n";
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
        .debug-info { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 1rem; margin-bottom: 1rem; font-size: 0.9rem; color: #495057; }
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
                    <span class="cart-count"><?php echo count($_SESSION['cart']); ?></span>
                </a>
            </div>
        </div>
    </header>
    <main>
        <div class="checkout-container">
            <div class="checkout-title">Checkout</div>
            
                        <!-- Debug Information -->            <div class="debug-info">                <strong>Debug Information:</strong><br>                Cart items: <?php echo count($cart); ?><br>                Products found: <?php echo count($products); ?><br>                Subtotal: $<?php echo number_format($subtotal, 2); ?><br>                <?php if (defined('STRIPE_DISABLED') && STRIPE_DISABLED): ?>                    <span style="color: orange;">⚠️ Stripe is disabled (vendor directory missing)</span><br>                <?php endif; ?>                <?php if (!empty($error)): ?>                    Last error: <?php echo htmlspecialchars($error); ?><br>                <?php endif; ?>            </div>
            
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
                            <td>1</td>
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