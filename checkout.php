<?php
session_start();

$db_error = false;
$config_error = false;
$stripe_error = false;
$error_messages = [];

try {
    require_once 'includes/db.php';
} catch (Exception $e) {
    $db_error = true;
    $error_messages[] = 'Database connection failed: ' . $e->getMessage();
}

try {
    require_once 'includes/config.php';
} catch (Exception $e) {
    $config_error = true;
    $error_messages[] = 'Configuration error: ' . $e->getMessage();
}

try {
    require_once 'includes/stripe-config.php';
} catch (Exception $e) {
    $stripe_error = true;
    $error_messages[] = 'Stripe configuration error: ' . $e->getMessage();
}

// Only try to clean up cart if database is working
if (!$db_error) {
    try {
        require_once 'includes/cart-display.php';
        cleanupCart();
    } catch (Exception $e) {
        error_log('Cart cleanup error in checkout: ' . $e->getMessage());
        // Don't let cleanup errors break checkout
    }
}

// If there are critical errors, display them
if ($db_error || $config_error) {
    $critical_error = 'System temporarily unavailable. ';
    if ($db_error) $critical_error .= 'Database connection failed. ';
    if ($config_error) $critical_error .= 'Configuration error. ';
    $critical_error .= 'Please try again later or contact support.';
    
    // Show error page instead of crashing
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Checkout Error - Bort's Books</title>
        <link rel="stylesheet" href="assets/css/styles.css">
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
            </div>
        </header>
        <main>
            <div style="max-width: 600px; margin: 4rem auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(35,41,70,0.08); padding: 3rem; text-align: center;">
                <div style="font-size: 3rem; color: #e63946; margin-bottom: 1.5rem;">⚠️</div>
                <h1 style="color: #232946; margin-bottom: 1rem;">Checkout Temporarily Unavailable</h1>
                <p style="color: #666; margin-bottom: 2rem;"><?php echo htmlspecialchars($critical_error); ?></p>
                <p style="color: #666; margin-bottom: 2rem;">Your cart items are preserved. Please try again in a few minutes.</p>
                <a href="cart.php" style="background: #eebbc3; color: #232946; padding: 1rem 2rem; border-radius: 30px; text-decoration: none; font-weight: 700;">Return to Cart</a>
            </div>
        </main>
    </body>
    </html>
    <?php
    exit;
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

if (!empty($cart) && !$db_error) {
    try {
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
        
    } catch (Exception $e) {
        $db_error = true;
        $error_messages[] = 'Error loading cart items: ' . $e->getMessage();
    }
}

// Handle AJAX shipping calculation FIRST
if (isset($_POST['calculate_shipping_only']) && !empty($_POST['zip']) && !empty($products)) {
    try {
        require_once 'includes/usps-shipping.php';
        $service = $_POST['shipping_service'] ?? 'Ground';
        $calculated_shipping = 0;
        
        foreach ($products as $product) {
            $usps = new USPSShipping();
            $shipping_result = $usps->calculateShipping($product, $_POST['zip'], $service);
            $calculated_shipping += $shipping_result['rate'];
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'shipping_cost' => $calculated_shipping,
            'subtotal' => $subtotal,
            'total' => $subtotal + $calculated_shipping,
            'service' => $service
        ]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Unable to calculate shipping: ' . $e->getMessage(),
            'shipping_cost' => 5.00,
            'subtotal' => $subtotal,
            'total' => $subtotal + 5.00
        ]);
        exit;
    }
}

// Handle order submission and shipping calculation
$errors = [];
$error = '';
$shipping_cost = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check if Stripe is available
    if ($stripe_error) {
        $error = 'Payment system temporarily unavailable. Please try again later.';
    } else {
        $customerInfo = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'address' => $_POST['address'] ?? '',
            'city' => $_POST['city'] ?? '',
            'state' => $_POST['state'] ?? '',
            'zip' => $_POST['zip'] ?? '',
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
        if (empty($customerInfo['address'])) {
            $errors[] = 'Address is required';
        }
        if (empty($customerInfo['city'])) {
            $errors[] = 'City is required';
        }
        if (empty($customerInfo['state'])) {
            $errors[] = 'State is required';
        }
        if (empty($customerInfo['zip']) || !preg_match('/^\d{5}(-\d{4})?$/', $customerInfo['zip'])) {
            $errors[] = 'Valid ZIP code is required';
        }
        
        // Calculate shipping if address is provided
        if (empty($errors) && !empty($customerInfo['zip']) && !empty($products)) {
            try {
                require_once 'includes/usps-shipping.php';
                $selected_service = $_POST['shipping_service'] ?? 'Ground';
                $total_shipping = 0;
                
                foreach ($products as $product) {
                    $usps = new USPSShipping();
                    $shipping_result = $usps->calculateShipping($product, $customerInfo['zip'], $selected_service);
                    $total_shipping += $shipping_result['rate'];
                }
                
                $shipping_cost = $total_shipping;
            } catch (Exception $e) {
                // Fallback to flat rate if shipping calculation fails
                $shipping_cost = 5.00;
                error_log('Shipping calculation error: ' . $e->getMessage());
            }
        } else {
            // Default shipping cost
            $shipping_cost = 5.00;
        }

        if (empty($errors)) {
            try {
                // Create Stripe Checkout Session with calculated shipping
                $session = createStripeCheckoutSession($cart, $customerInfo, $shipping_cost);
                
                if ($session) {
                    // Store customer info in session for later use
                    $_SESSION['customer_info'] = $customerInfo;
                    $_SESSION['shipping_cost'] = $shipping_cost;
                    
                    // Redirect to Stripe Checkout
                    header('Location: ' . $session->url);
                    exit;
                } else {
                    $error = "There was an error creating your checkout session. Please try again.";
                }
            } catch (Exception $e) {
                error_log('Checkout Session Error: ' . $e->getMessage());
                $error = "There was an error processing your request: " . $e->getMessage();
            }
        }
    }
}

// Calculate shipping for display if ZIP is provided but not processing order
if (!empty($_POST['zip']) && !empty($products) && empty($_POST['calculate_shipping_only'])) {
    try {
        require_once 'includes/usps-shipping.php';
        $service = $_POST['shipping_service'] ?? 'Ground';
        $calculated_shipping = 0;
        
        foreach ($products as $product) {
            $usps = new USPSShipping();
            $shipping_result = $usps->calculateShipping($product, $_POST['zip'], $service);
            $calculated_shipping += $shipping_result['rate'];
        }
        
        $shipping_cost = $calculated_shipping;
    } catch (Exception $e) {
        $shipping_cost = 5.00; // fallback
    }
} elseif (empty($_POST['zip'])) {
    $shipping_cost = 0; // Show "Enter ZIP to calculate"
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
        .checkout-form input, .checkout-form textarea, .checkout-form select { width: 100%; padding: 0.7rem; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 1.2rem; font-size: 1rem; }
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
        .system-status { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; color: #856404; }
        .address-row { display: flex; gap: 1rem; }
        .address-col { flex: 1; }
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
                    <span class="cart-count"><?php echo count($_SESSION['cart'] ?? []); ?></span>
                </a>
            </div>
        </div>
    </header>
    <main>
        <div class="checkout-container">
            <div class="checkout-title">Checkout</div>
            
            <?php if (!empty($error_messages)): ?>
                <div class="system-status">
                    <strong><i class="fas fa-exclamation-triangle"></i> System Status:</strong><br>
                    <?php foreach ($error_messages as $msg): ?>
                        • <?php echo htmlspecialchars($msg); ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
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
            
            <?php if ($stripe_error): ?>
                <div class="checkout-errors">
                    <strong>Payment system temporarily unavailable.</strong><br>
                    Please try again later or contact support if the problem persists.
                    <br><br>
                    <a href="cart.php" style="color: #232946; font-weight: bold;">← Return to Cart</a>
                </div>
            <?php else: ?>
            
            <form method="POST" class="checkout-form">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                
                <label for="phone">Phone Number *</label>
                <input type="tel" id="phone" name="phone" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                
                <h3 style="margin-top: 2rem; margin-bottom: 1rem; color: #232946;">Shipping Address</h3>
                
                <label for="address">Address *</label>
                <input type="text" id="address" name="address" required value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                
                <label for="city">City *</label>
                <input type="text" id="city" name="city" required value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                
                <div class="address-row">
                    <div class="address-col">
                        <label for="state">State *</label>
                        <input type="text" id="state" name="state" required value="<?php echo htmlspecialchars($_POST['state'] ?? ''); ?>" placeholder="e.g. CA">
                    </div>
                    <div class="address-col">
                        <label for="zip">ZIP Code *</label>
                        <input type="text" id="zip" name="zip" required value="<?php echo htmlspecialchars($_POST['zip'] ?? ''); ?>" placeholder="e.g. 90210">
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 1.5rem;">
                    <label for="shipping_service">Shipping Method *</label>
                    <select id="shipping_service" name="shipping_service" required>
                        <option value="">Select shipping method</option>
                        <option value="Media" <?php echo ($_POST['shipping_service'] ?? '') === 'Media' ? 'selected' : ''; ?>>Media Mail (2-8 days) - Books & Educational Materials</option>
                        <option value="Ground" <?php echo ($_POST['shipping_service'] ?? 'Ground') === 'Ground' ? 'selected' : ''; ?>>USPS Ground Advantage (3-5 days)</option>
                        <option value="Priority" <?php echo ($_POST['shipping_service'] ?? '') === 'Priority' ? 'selected' : ''; ?>>Priority Mail (1-3 days)</option>
                    </select>
                </div>
                
                <button type="submit" class="checkout-btn">Calculate Shipping & Proceed</button>
            </form>
            
            <script>
            // Auto-calculate shipping when ZIP is entered or service is changed
            document.getElementById('zip').addEventListener('blur', calculateShippingFromForm);
            document.getElementById('shipping_service').addEventListener('change', calculateShippingFromForm);
            
            function calculateShippingFromForm() {
                const zip = document.getElementById('zip').value;
                const service = document.getElementById('shipping_service').value;
                
                if (zip && zip.match(/^\d{5}(-\d{4})?$/) && service) {
                    calculateShipping(zip, service);
                }
            }
            
            function calculateShipping(zip, service) {
                // Show loading state
                const shippingElements = document.querySelectorAll('.order-summary-total');
                if (shippingElements.length >= 2) {
                    shippingElements[1].innerHTML = 'Shipping: Calculating...';
                }
                
                // Create form data
                const formData = new FormData();
                formData.append('zip', zip);
                formData.append('shipping_service', service || 'Ground');
                formData.append('calculate_shipping_only', '1');
                
                                 fetch('checkout.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Shipping calculation response:', data); // Debug log
                    const shippingElements = document.querySelectorAll('.order-summary-total');
                    if (data.success && shippingElements.length >= 3) {
                        shippingElements[1].innerHTML = 'Shipping (' + data.service + '): $' + data.shipping_cost.toFixed(2);
                        shippingElements[2].innerHTML = 'Total: $' + data.total.toFixed(2);
                    } else {
                        shippingElements[1].innerHTML = 'Shipping: Error - ' + (data.error || 'Unknown error');
                        console.error('Shipping calculation failed:', data);
                    }
                })
                .catch(error => {
                    console.error('Error calculating shipping:', error);
                    const shippingElements = document.querySelectorAll('.order-summary-total');
                    if (shippingElements.length >= 2) {
                        shippingElements[1].innerHTML = 'Shipping: Network error';
                    }
                });
            }
            </script>
            
            <?php endif; ?>
            
            <div class="order-summary">
                <div class="order-summary-title">Order Summary</div>
                <table class="order-summary-table">
                    <thead>
                        <tr>
                            <th>Cover</th>
                            <th>Title</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($products)): ?>
                            <?php foreach ($products as $prod): ?>
                            <tr>
                                <td><img class="order-img" src="<?php echo htmlspecialchars($prod['main_image'] ?: 'assets/img/placeholder.png'); ?>" alt=""></td>
                                <td><?php echo htmlspecialchars($prod['title']); ?></td>
                                <td>$<?php echo number_format($prod['price'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: #666; padding: 2rem;">
                                    <?php if ($db_error): ?>
                                        Unable to load cart items (database error)
                                    <?php else: ?>
                                        No items in cart
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="order-summary-total">Subtotal: $<?php echo number_format($subtotal, 2); ?></div>
                <div class="order-summary-total">Shipping: <?php echo (!empty($_POST['zip']) && $shipping_cost > 0) ? '$' . number_format($shipping_cost, 2) : 'Enter ZIP to calculate'; ?></div>
                <div class="order-summary-total" style="border-top: 2px solid #eebbc3; padding-top: 0.5rem; margin-top: 0.5rem;">
                    Total: $<?php echo number_format($subtotal + $shipping_cost, 2); ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html> 