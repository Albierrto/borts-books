<?php
// Define that files are being included from the app
define('INCLUDED_FROM_APP', true);

require_once 'includes/security.php';
require_once 'includes/config.php';
require_once 'includes/stripe-config.php';
require_once 'includes/cart.php';

// Start secure session
secure_session_start();

// Set security headers
set_security_headers();

// Check rate limiting
if (!check_rate_limit('thankyou_access', 10, 3600)) {
    http_response_code(429);
    die('Too many requests. Please wait before trying again.');
}

// Get and validate the session ID from the URL
$sessionId = sanitize_input($_GET['session_id'] ?? '');

// Validate session ID format (Stripe session IDs have specific format)
if (empty($sessionId) || !preg_match('/^cs_[a-zA-Z0-9]+$/', $sessionId)) {
    log_security_event('invalid_session_id_access', ['session_id' => $sessionId], 'medium');
    header('Location: index.php');
    exit;
}

// Additional security: check if session was recently created (prevent replay attacks)
if (isset($_SESSION['last_checkout_time'])) {
    $time_diff = time() - $_SESSION['last_checkout_time'];
    if ($time_diff > 3600) { // 1 hour timeout
        log_security_event('expired_session_access', ['session_id' => $sessionId], 'medium');
        header('Location: checkout.php');
        exit;
    }
}

// Verify the Stripe session with enhanced security
try {
    $session = verifyStripeSession($sessionId);
    
    if (!$session || $session->payment_status !== 'paid') {
        log_security_event('invalid_stripe_session', ['session_id' => $sessionId], 'high');
        header('Location: checkout.php');
        exit;
    }
} catch (Exception $e) {
    log_security_event('stripe_verification_error', [
        'session_id' => $sessionId,
        'error' => $e->getMessage()
    ], 'high');
    header('Location: checkout.php');
    exit;
}

// Get customer info from session with validation
$customerInfo = $_SESSION['customer_info'] ?? null;

if (!$customerInfo || !is_array($customerInfo)) {
    log_security_event('missing_customer_info', ['session_id' => $sessionId], 'medium');
    header('Location: checkout.php');
    exit;
}

// Validate required customer fields
$required_fields = ['name', 'email'];
foreach ($required_fields as $field) {
    if (empty($customerInfo[$field])) {
        log_security_event('incomplete_customer_info', ['session_id' => $sessionId], 'medium');
        header('Location: checkout.php');
        exit;
    }
}

// Get cart items with validation
$cart = getCart();

if (empty($cart)) {
    log_security_event('empty_cart_on_thankyou', ['session_id' => $sessionId], 'medium');
    header('Location: index.php');
    exit;
}

// Save the order to the database with enhanced security
try {
    $db->beginTransaction();

    // Check for duplicate order (prevent double processing)
    $duplicateCheck = $db->prepare('SELECT id FROM orders WHERE stripe_session_id = ?');
    $duplicateCheck->execute([$sessionId]);
    
    if ($duplicateCheck->fetch()) {
        $db->rollBack();
        log_security_event('duplicate_order_attempt', ['session_id' => $sessionId], 'medium');
        // Still show success page since order was already processed
    } else {
        // Insert order with encrypted data
        require_once 'includes/database-encryption.php';
        $encryption = new DatabaseEncryption();
        
        $orderStmt = $db->prepare('
            INSERT INTO orders (
                stripe_session_id,
                customer_name,
                customer_email,
                shipping_address,
                total_amount,
                payment_status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ');

        // Encrypt sensitive customer data
        $encrypted_name = $encryption->encrypt($customerInfo['name']);
        $encrypted_email = $encryption->encrypt($customerInfo['email']);
        $encrypted_address = $encryption->encrypt(json_encode($session->shipping));
        
        $orderStmt->execute([
            $sessionId,
            $encrypted_name,
            $encrypted_email,
            $encrypted_address,
            $session->amount_total / 100, // Convert from cents
            'paid'
        ]);

        $orderId = $db->lastInsertId();

        // Insert order items with validation
        $itemStmt = $db->prepare('
            INSERT INTO order_items (
                order_id,
                product_id,
                title,
                price,
                quantity,
                image_url
            ) VALUES (?, ?, ?, ?, ?, ?)
        ');

        foreach ($cart as $item) {
            // Validate cart item structure
            if (!isset($item['id'], $item['title'], $item['price'], $item['quantity'])) {
                throw new Exception('Invalid cart item structure');
            }
            
            $itemStmt->execute([
                $orderId,
                (int)$item['id'],
                sanitize_input($item['title']),
                (float)$item['price'],
                (int)$item['quantity'],
                sanitize_input($item['image_url'] ?? '')
            ]);
        }

        $db->commit();
        
        // Log successful order completion
        log_security_event('order_completed', [
            'order_id' => $orderId,
            'session_id' => $sessionId,
            'total_amount' => $session->amount_total / 100
        ], 'low');
    }

    // Clear sensitive session data
    clearCart();
    unset($_SESSION['customer_info']);
    unset($_SESSION['shipping_cost']);
    $_SESSION['last_checkout_time'] = time();

} catch (Exception $e) {
    $db->rollBack();
    error_log('Order save error: ' . $e->getMessage());
    log_security_event('order_save_error', [
        'session_id' => $sessionId,
        'error' => $e->getMessage()
    ], 'high');
    header('Location: checkout.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='grad' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%23667eea;stop-opacity:1' /%3E%3Cstop offset='100%25' style='stop-color:%23764ba2;stop-opacity:1' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='15' fill='url(%23grad)'/%3E%3Cpath d='M25 20h50c2.5 0 4.5 2 4.5 4.5v51c0 2.5-2 4.5-4.5 4.5H25c-2.5 0-4.5-2-4.5-4.5v-51c0-2.5 2-4.5 4.5-4.5z' fill='white'/%3E%3Cpath d='M30 30h40v5H30z' fill='%23667eea'/%3E%3Cpath d='M30 40h35v3H30z' fill='%23999'/%3E%3Cpath d='M30 47h30v3H30z' fill='%23999'/%3E%3Cpath d='M30 54h25v3H30z' fill='%23999'/%3E%3Cpath d='M30 61h20v3H30z' fill='%23999'/%3E%3C/svg%3E">
    <title>Thank You - Order Confirmation</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="thank-you-container">
            <h1>Thank You for Your Order!</h1>
            <div class="order-confirmation">
                <p>Your order has been successfully placed and paid for.</p>
                <p>Order ID: <?php echo htmlspecialchars($orderId); ?></p>
                <p>A confirmation email has been sent to <?php echo htmlspecialchars($customerInfo['email']); ?></p>
            </div>

            <div class="order-details">
                <h2>Order Details</h2>
                <div class="shipping-info">
                    <h3>Shipping Information</h3>
                    <p>Name: <?php echo htmlspecialchars($customerInfo['name']); ?></p>
                    <p>Email: <?php echo htmlspecialchars($customerInfo['email']); ?></p>

                    <p>Address: <?php echo htmlspecialchars($session->shipping->address->line1); ?></p>
                    <?php if ($session->shipping->address->line2): ?>
                        <p>Address 2: <?php echo htmlspecialchars($session->shipping->address->line2); ?></p>
                    <?php endif; ?>
                    <p>City: <?php echo htmlspecialchars($session->shipping->address->city); ?></p>
                    <p>State: <?php echo htmlspecialchars($session->shipping->address->state); ?></p>
                    <p>Postal Code: <?php echo htmlspecialchars($session->shipping->address->postal_code); ?></p>
                    <p>Country: <?php echo htmlspecialchars($session->shipping->address->country); ?></p>
                </div>

                <div class="order-items">
                    <h3>Items Ordered</h3>
                    <?php foreach ($cart as $item): ?>
                        <div class="order-item">
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                            <div class="item-details">
                                <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                                <p>Quantity: <?php echo $item['quantity']; ?></p>
                                <p>Price: $<?php echo number_format($item['price'], 2); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-total">
                    <h3>Total Amount</h3>
                    <p>$<?php echo number_format($session->amount_total / 100, 2); ?></p>
                </div>
            </div>

            <div class="continue-shopping">
                <a href="shop.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 