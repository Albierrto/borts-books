<?php
// Define that files are being included from the app
define('INCLUDED_FROM_APP', true);

require_once 'includes/security.php';
require_once 'includes/secure-email.php';
require_once 'includes/database-encryption.php';

// Start secure session
secure_session_start();

// Set security headers
set_security_headers();

$db_error = false;
$config_error = false;
$stripe_error = false;
$error_messages = [];

try {
    require_once 'includes/db.php';
} catch (Exception $e) {
    $db_error = true;
    $error_messages[] = 'Database connection failed';
    log_security_event('db_connection_failed', ['error' => $e->getMessage()], 'high');
}

try {
    require_once 'includes/config.php';
} catch (Exception $e) {
    $config_error = true;
    $error_messages[] = 'Configuration error';
    log_security_event('config_error', ['error' => $e->getMessage()], 'high');
}

try {
    require_once 'includes/stripe-config.php';
} catch (Exception $e) {
    $stripe_error = true;
    $error_messages[] = 'Payment system configuration error';
    log_security_event('stripe_config_error', ['error' => $e->getMessage()], 'high');
}

// Initialize security components
$encryption = new DatabaseEncryption();
$emailSystem = new SecureEmailSystem();

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
        <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='grad' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%23667eea;stop-opacity:1' /%3E%3Cstop offset='100%25' style='stop-color:%23764ba2;stop-opacity:1' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='15' fill='url(%23grad)'/%3E%3Cpath d='M25 20h50c2.5 0 4.5 2 4.5 4.5v51c0 2.5-2 4.5-4.5 4.5H25c-2.5 0-4.5-2-4.5-4.5v-51c0-2.5 2-4.5 4.5-4.5z' fill='white'/%3E%3Cpath d='M30 30h40v5H30z' fill='%23667eea'/%3E%3Cpath d='M30 40h35v3H30z' fill='%23999'/%3E%3Cpath d='M30 47h30v3H30z' fill='%23999'/%3E%3Cpath d='M30 54h25v3H30z' fill='%23999'/%3E%3Cpath d='M30 61h20v3H30z' fill='%23999'/%3E%3C/svg%3E">
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
        $error_messages[] = 'Error loading cart items';
        log_security_event('cart_load_error', ['error' => $e->getMessage()], 'medium');
    }
}

// Handle AJAX shipping calculation FIRST with security
if (isset($_POST['calculate_shipping_only'])) {
    header('Content-Type: application/json');
    
    // Verify CSRF token for AJAX requests
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        echo json_encode(['error' => 'Invalid request token']);
        log_security_event('csrf_failure', ['page' => 'checkout_shipping'], 'medium');
        exit;
    }
    
    // Check rate limiting
    if (!check_rate_limit('shipping_calc', 20, 3600)) {
        echo json_encode(['error' => 'Too many shipping calculations. Please wait.']);
        log_security_event('rate_limit_exceeded', ['page' => 'checkout_shipping'], 'medium');
        exit;
    }
    
    // Validate ZIP code
    $zip = sanitize_input($_POST['zip'] ?? '');
    if (!preg_match('/^\d{5}(-\d{4})?$/', $zip)) {
        echo json_encode(['error' => 'Please enter a valid ZIP code']);
        exit;
    }
    
    try {
        // Check if we have products
        if (empty($products)) {
            echo json_encode(['error' => 'No products in cart for shipping calculation']);
            exit;
        }
        
        require_once 'includes/usps-shipping.php';
        $service = sanitize_input($_POST['shipping_service'] ?? 'Ground');
        
        // Validate shipping service
        $allowed_services = ['Ground', 'Priority', 'Express'];
        if (!in_array($service, $allowed_services)) {
            $service = 'Ground';
        }
        
        $calculated_shipping = 0;
        $warnings = [];
        $api_status = [];
        
        foreach ($products as $product) {
            $usps = new USPSShipping();
            $shipping_result = $usps->calculateShipping($product, $zip, $service);
            $calculated_shipping += $shipping_result['rate'];
            
            // Collect any warnings or API status info
            if (isset($shipping_result['warning'])) {
                $warnings[] = $shipping_result['warning'];
            }
            
            if ($usps->getLastError()) {
                $warnings[] = $usps->getLastError();
            }
            
            $api_status[] = isset($shipping_result['usps_api']) ? 'USPS API' : 'Estimated';
        }
        
        // Remove duplicate warnings
        $warnings = array_unique($warnings);
        
        $response = [
            'success' => true,
            'shipping_cost' => $calculated_shipping,
            'formatted_shipping' => '$' . number_format($calculated_shipping, 2),
            'subtotal' => $subtotal,
            'total' => $subtotal + $calculated_shipping,
            'formatted_total' => '$' . number_format($subtotal + $calculated_shipping, 2),
            'api_status' => array_unique($api_status)
        ];
        
        // Add warnings if any
        if (!empty($warnings)) {
            $response['warnings'] = $warnings;
        }
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        error_log('Shipping calculation error: ' . $e->getMessage());
        echo json_encode([
            'error' => 'Shipping calculation failed. Please try again.',
            'fallback_message' => 'We apologize for the inconvenience. Please contact us for shipping rates.',
            'fallback_shipping' => 5.00
        ]);
    }
    
    exit;
}

// Handle order submission and shipping calculation
$errors = [];
$error = '';
$shipping_cost = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['calculate_shipping_only'])) {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please refresh the page and try again.';
        log_security_event('csrf_failure', ['page' => 'checkout'], 'medium');
    } else {
        // Check rate limiting for order submissions
        if (!check_rate_limit('checkout_submission', 3, 3600)) {
            $error = 'Too many checkout attempts. Please wait before trying again.';
            log_security_event('rate_limit_exceeded', ['page' => 'checkout'], 'medium');
        } else {
            // Sanitize and validate all inputs
            $first_name = sanitize_input($_POST['first_name'] ?? '');
            $last_name = sanitize_input($_POST['last_name'] ?? '');
            $email = validate_email($_POST['email'] ?? '');
            $phone = sanitize_input($_POST['phone'] ?? '');
            $address = sanitize_input($_POST['address'] ?? '');
            $city = sanitize_input($_POST['city'] ?? '');
            $state = sanitize_input($_POST['state'] ?? '');
            $zip = sanitize_input($_POST['zip'] ?? '');
            $shipping_service = sanitize_input($_POST['shipping_service'] ?? 'Ground');
            
            // Enhanced validation
            $validation_errors = [];
            
            if (empty($first_name) || strlen($first_name) < 2) {
                $validation_errors[] = 'Please enter a valid first name.';
            } elseif (strlen($first_name) > 50) {
                $validation_errors[] = 'First name is too long.';
            }
            
            if (empty($last_name) || strlen($last_name) < 2) {
                $validation_errors[] = 'Please enter a valid last name.';
            } elseif (strlen($last_name) > 50) {
                $validation_errors[] = 'Last name is too long.';
            }
            
            if (!$email) {
                $validation_errors[] = 'Please enter a valid email address.';
            }
            
            if (!empty($phone) && !preg_match('/^[\d\s\-\(\)\+\.]{10,20}$/', $phone)) {
                $validation_errors[] = 'Please enter a valid phone number.';
            }
            
            if (empty($address) || strlen($address) < 5) {
                $validation_errors[] = 'Please enter a valid address.';
            } elseif (strlen($address) > 100) {
                $validation_errors[] = 'Address is too long.';
            }
            
            if (empty($city) || strlen($city) < 2) {
                $validation_errors[] = 'Please enter a valid city.';
            } elseif (strlen($city) > 50) {
                $validation_errors[] = 'City name is too long.';
            }
            
            if (empty($state) || strlen($state) != 2) {
                $validation_errors[] = 'Please select a valid state.';
            }
            
            if (!preg_match('/^\d{5}(-\d{4})?$/', $zip)) {
                $validation_errors[] = 'Please enter a valid ZIP code.';
            }
            
            // Validate shipping service
            $allowed_services = ['Ground', 'Priority', 'Express'];
            if (!in_array($shipping_service, $allowed_services)) {
                $validation_errors[] = 'Please select a valid shipping service.';
                $shipping_service = 'Ground';
            }
            
            if (!empty($validation_errors)) {
                $error = 'Please fix the following errors: ' . implode(' ', $validation_errors);
            } else {
                try {
                    // Calculate shipping if address is provided
                    if (empty($errors) && !empty($zip) && !empty($products)) {
                        try {
                            require_once 'includes/usps-shipping.php';
                            $total_shipping = 0;
                            
                            foreach ($products as $product) {
                                $usps = new USPSShipping();
                                $shipping_result = $usps->calculateShipping($product, $zip, $shipping_service);
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
                            $session = createStripeCheckoutSession($cart, [
                                'name' => $first_name . ' ' . $last_name,
                                'email' => $email,
                                'address' => $address,
                                'city' => $city,
                                'state' => $state,
                                'zip' => $zip
                            ], $shipping_cost);
                            
                            if ($session) {
                                // Store customer info in session for later use
                                $_SESSION['customer_info'] = [
                                    'name' => $first_name . ' ' . $last_name,
                                    'email' => $email,
                                    'address' => $address,
                                    'city' => $city,
                                    'state' => $state,
                                    'zip' => $zip
                                ];
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
                } catch (Exception $e) {
                    error_log('Shipping calculation error: ' . $e->getMessage());
                    $error = "There was an error calculating shipping. Please try again later or contact support.";
                }
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
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='grad' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%23667eea;stop-opacity:1' /%3E%3Cstop offset='100%25' style='stop-color:%23764ba2;stop-opacity:1' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='15' fill='url(%23grad)'/%3E%3Cpath d='M25 20h50c2.5 0 4.5 2 4.5 4.5v51c0 2.5-2 4.5-4.5 4.5H25c-2.5 0-4.5-2-4.5-4.5v-51c0-2.5 2-4.5 4.5-4.5z' fill='white'/%3E%3Cpath d='M30 30h40v5H30z' fill='%23667eea'/%3E%3Cpath d='M30 40h35v3H30z' fill='%23999'/%3E%3Cpath d='M30 47h30v3H30z' fill='%23999'/%3E%3Cpath d='M30 54h25v3H30z' fill='%23999'/%3E%3Cpath d='M30 61h20v3H30z' fill='%23999'/%3E%3C/svg%3E">
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
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                
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
                    shippingElements[2].innerHTML = 'Total: Calculating...';
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
                    if (data.error) {
                        console.error('Shipping calculation error:', data.error);
                        const fallback = data.fallback_shipping || 5.00;
                        updateShippingDisplay(fallback, fallback);
                    } else {
                        updateShippingDisplay(data.shipping_cost, data.total);
                    }
                })
                .catch(error => {
                    console.error('Network error calculating shipping:', error);
                    const shippingElements = document.querySelectorAll('.order-summary-total');
                    if (shippingElements.length >= 2) {
                        shippingElements[1].innerHTML = 'Shipping: Error - using fallback $5.00';
                        shippingElements[2].innerHTML = 'Total: $' + (<?php echo $subtotal; ?> + 5.00).toFixed(2);
                    }
                });
            }
            
            function updateShippingDisplay(shippingCost, totalCost) {
                const shippingElements = document.querySelectorAll('.order-summary-total');
                if (shippingElements.length >= 2) {
                    shippingElements[1].innerHTML = 'Shipping: $' + parseFloat(shippingCost).toFixed(2);
                    shippingElements[2].innerHTML = 'Total: $' + parseFloat(totalCost).toFixed(2);
                }
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