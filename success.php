<?php
// Define that files are being included from the app
define('INCLUDED_FROM_APP', true);

require_once 'includes/security.php';

// Start secure session
secure_session_start();

// Set security headers
set_security_headers();

// Check rate limiting
if (!check_rate_limit('success_access', 10, 3600)) {
    http_response_code(429);
    die('Too many requests. Please wait before trying again.');
}

// Get and validate session ID from URL
$session_id = sanitize_input($_GET['session_id'] ?? '');
$order_details = null;
$error = '';
$db_error = false;

// Validate session ID format
if (empty($session_id) || !preg_match('/^cs_[a-zA-Z0-9_]+$/', $session_id)) {
    $error = 'Invalid session ID format.';
    log_security_event('invalid_session_format', ['session_id' => $session_id], 'medium');
}

// Try to load required files with error handling
try {
    require_once 'includes/config.php';
} catch (Exception $e) {
    error_log('Config error in success.php: ' . $e->getMessage());
    log_security_event('config_load_error', ['error' => $e->getMessage()], 'high');
}

try {
    require_once 'includes/db.php';
} catch (Exception $e) {
    $db_error = true;
    error_log('Database connection error in success.php: ' . $e->getMessage());
    log_security_event('db_connection_error', ['error' => $e->getMessage()], 'high');
}

try {
    require_once 'includes/stripe-config.php';
} catch (Exception $e) {
    error_log('Stripe config error in success.php: ' . $e->getMessage());
    $error = 'Payment verification system temporarily unavailable.';
    log_security_event('stripe_config_error', ['error' => $e->getMessage()], 'high');
}

if (empty($session_id) && empty($error)) {
    $error = 'Invalid session. Please contact support if you completed a payment.';
    log_security_event('missing_session_id', [], 'medium');
} elseif (!$error) {
    // Try to verify the payment with Stripe
    try {
        $session = verifyStripePayment($session_id);
        
        if ($session && $session->payment_status === 'paid') {
            // Payment successful, prepare order details with data validation
            $order_details = [
                'session_id' => $session_id,
                'amount_total' => max(0, $session->amount_total / 100), // Convert from cents and validate
                'customer_email' => validate_email($session->customer_details->email ?? $session->customer_email),
                'customer_name' => sanitize_input($session->metadata->customer_name ?? ''),
                'payment_intent' => sanitize_input($session->payment_intent ?? ''),
            ];
            
            // Validate critical order data
            if (!$order_details['customer_email']) {
                $error = 'Invalid customer email in payment session.';
                log_security_event('invalid_customer_email', ['session_id' => $session_id], 'medium');
            }
            
            if ($order_details['amount_total'] <= 0) {
                $error = 'Invalid payment amount.';
                log_security_event('invalid_payment_amount', ['session_id' => $session_id], 'high');
            }
            
            if (empty($error)) {
                // Clear the cart since payment was successful
                $_SESSION['cart'] = [];
                unset($_SESSION['customer_info']);
                unset($_SESSION['shipping_cost']);
                
                // Try to save order to database (optional - don't fail if database is down)
                if (!$db_error) {
                    try {
                        // Check for duplicate order first
                        $duplicateCheck = $db->prepare('SELECT id FROM orders WHERE stripe_session_id = ?');
                        $duplicateCheck->execute([$session_id]);
                        
                        if (!$duplicateCheck->fetch()) {
                            // Encrypt sensitive data before storage
                            require_once 'includes/database-encryption.php';
                            $encryption = new DatabaseEncryption();
                            
                            $encrypted_name = $encryption->encrypt($order_details['customer_name']);
                            $encrypted_email = $encryption->encrypt($order_details['customer_email']);
                            
                            $stmt = $db->prepare('INSERT INTO orders (stripe_session_id, customer_name, customer_email, total_amount, payment_status, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
                            $stmt->execute([
                                $session_id,
                                $encrypted_name,
                                $encrypted_email,
                                $order_details['amount_total'],
                                'paid'
                            ]);
                            
                            log_security_event('order_saved', [
                                'session_id' => $session_id,
                                'order_id' => $db->lastInsertId()
                            ], 'low');
                        }
                    } catch (Exception $e) {
                        // Log error but don't fail the success page
                        error_log('Failed to save order to database: ' . $e->getMessage());
                        log_security_event('order_save_failed', [
                            'session_id' => $session_id,
                            'error' => $e->getMessage()
                        ], 'medium');
                    }
                }
            }
        } else {
            $error = 'Payment verification failed. Please contact support with session ID: ' . htmlspecialchars($session_id);
            log_security_event('payment_verification_failed', ['session_id' => $session_id], 'high');
        }
    } catch (Exception $e) {
        error_log('Stripe verification error: ' . $e->getMessage());
        $error = 'Payment verification failed. Please contact support with session ID: ' . htmlspecialchars($session_id);
        log_security_event('stripe_verification_exception', [
            'session_id' => $session_id,
            'error' => $e->getMessage()
        ], 'high');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='grad' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%23667eea;stop-opacity:1' /%3E%3Cstop offset='100%25' style='stop-color:%23764ba2;stop-opacity:1' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='15' fill='url(%23grad)'/%3E%3Cpath d='M25 20h50c2.5 0 4.5 2 4.5 4.5v51c0 2.5-2 4.5-4.5 4.5H25c-2.5 0-4.5-2-4.5-4.5v-51c0-2.5 2-4.5 4.5-4.5z' fill='white'/%3E%3Cpath d='M30 30h40v5H30z' fill='%23667eea'/%3E%3Cpath d='M30 40h35v3H30z' fill='%23999'/%3E%3Cpath d='M30 47h30v3H30z' fill='%23999'/%3E%3Cpath d='M30 54h25v3H30z' fill='%23999'/%3E%3Cpath d='M30 61h20v3H30z' fill='%23999'/%3E%3C/svg%3E">
    <title><?php echo $error ? 'Payment Issue' : 'Order Success'; ?> - Bort's Books</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .success-container, .error-container { 
            max-width: 700px; 
            margin: 4rem auto; 
            background: #fff; 
            border-radius: 12px; 
            box-shadow: 0 2px 12px rgba(35,41,70,0.08); 
            padding: 3rem; 
            text-align: center; 
        }
        .success-icon { 
            font-size: 4rem; 
            color: #28a745; 
            margin-bottom: 1.5rem; 
        }
        .error-icon { 
            font-size: 4rem; 
            color: #e63946; 
            margin-bottom: 1.5rem; 
        }
        .success-title, .error-title { 
            font-size: 2.5rem; 
            font-weight: 800; 
            margin-bottom: 1rem; 
            color: #232946; 
        }
        .success-message, .error-message { 
            font-size: 1.2rem; 
            color: #666; 
            margin-bottom: 2rem; 
            line-height: 1.6; 
        }
        .order-details { 
            background: #f8f9fa; 
            border-radius: 8px; 
            padding: 2rem; 
            margin: 2rem 0; 
            text-align: left; 
        }
        .order-details h3 { 
            margin-bottom: 1rem; 
            color: #232946; 
            text-align: center;
        }
        .detail-row { 
            display: flex; 
            justify-content: space-between; 
            margin-bottom: 0.8rem; 
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        .detail-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.1rem;
        }
        .detail-label { 
            font-weight: 600; 
        }
        .continue-shopping { 
            display: inline-block; 
            background: #eebbc3; 
            color: #232946; 
            padding: 1rem 2rem; 
            border-radius: 30px; 
            text-decoration: none; 
            font-weight: 700; 
            transition: background 0.2s; 
            margin: 0.5rem; 
        }
        .continue-shopping:hover { 
            background: #232946; 
            color: #fff; 
        }
        .support-info {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
            color: #1565c0;
        }
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
        <?php if ($error): ?>
            <div class="error-container">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="error-title">Payment Verification Issue</div>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                
                <?php if ($session_id): ?>
                <div class="support-info">
                    <strong>Your payment may have been processed successfully.</strong><br>
                    Please save this information for your records:<br><br>
                    <strong>Session ID:</strong> <?php echo htmlspecialchars($session_id); ?><br><br>
                    If you have any questions, please contact our support team with this session ID.
                </div>
                <?php endif; ?>
                
                <a href="/pages/shop.php" class="continue-shopping">Continue Shopping</a>
                <a href="cart.php" class="continue-shopping">View Cart</a>
            </div>
        <?php else: ?>
            <div class="success-container">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="success-title">Payment Successful!</div>
                <div class="success-message">
                    Thank you for your order! Your payment has been processed successfully.<br>
                    You will receive an email confirmation shortly.
                </div>
                
                <?php if ($order_details): ?>
                <div class="order-details">
                    <h3>Order Summary</h3>
                    <?php if ($order_details['customer_name']): ?>
                    <div class="detail-row">
                        <span class="detail-label">Customer:</span>
                        <span><?php echo htmlspecialchars($order_details['customer_name']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span><?php echo htmlspecialchars($order_details['customer_email']); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Order ID:</span>
                        <span><?php echo htmlspecialchars(substr($order_details['session_id'], -12)); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Total Amount:</span>
                        <span>$<?php echo number_format($order_details['amount_total'], 2); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <a href="/pages/shop.php" class="continue-shopping">Continue Shopping</a>
                <a href="/index.php" class="continue-shopping">Return Home</a>
                
                <?php if ($db_error): ?>
                <div style="background: #fff3cd; border-radius: 8px; padding: 1rem; margin-top: 2rem; color: #856404; font-size: 0.9rem;">
                    <i class="fas fa-info-circle"></i> Note: Your payment was successful, but our order tracking system is temporarily unavailable. Your order will be processed normally.
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 