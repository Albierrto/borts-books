<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/config.php';
require_once 'includes/stripe-config.php';

$session_id = $_GET['session_id'] ?? '';
$order_details = null;
$error = '';

if (empty($session_id)) {
    $error = 'Invalid session. Please contact support.';
} else {
    // Verify the payment with Stripe
    $session = verifyStripePayment($session_id);
    
    if ($session && $session->payment_status === 'paid') {
        // Payment successful, process the order
        $order_details = [
            'session_id' => $session_id,
            'amount_total' => $session->amount_total / 100, // Convert from cents
            'customer_email' => $session->customer_details->email ?? $session->customer_email,
            'customer_name' => $session->metadata->customer_name ?? '',
            'customer_phone' => $session->metadata->customer_phone ?? '',
        ];
        
        // Clear the cart
        $_SESSION['cart'] = [];
        
        // You could save order to database here
        // saveOrderToDatabase($order_details, $_SESSION['customer_info'] ?? []);
        
    } else {
        $error = 'Payment verification failed. Please contact support with session ID: ' . $session_id;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - Bort's Books</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .success-container { 
            max-width: 600px; 
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
        .success-title { 
            font-size: 2.5rem; 
            font-weight: 800; 
            margin-bottom: 1rem; 
            color: #232946; 
        }
        .success-message { 
            font-size: 1.2rem; 
            color: #666; 
            margin-bottom: 2rem; 
            line-height: 1.6; 
        }
        .order-details { 
            background: #f8f9fa; 
            border-radius: 8px; 
            padding: 1.5rem; 
            margin: 2rem 0; 
            text-align: left; 
        }
        .order-details h3 { 
            margin-bottom: 1rem; 
            color: #232946; 
        }
        .detail-row { 
            display: flex; 
            justify-content: space-between; 
            margin-bottom: 0.5rem; 
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
            margin-top: 1rem; 
        }
        .continue-shopping:hover { 
            background: #232946; 
            color: #fff; 
        }
        .error-container { 
            max-width: 600px; 
            margin: 4rem auto; 
            background: #fff; 
            border-radius: 12px; 
            box-shadow: 0 2px 12px rgba(35,41,70,0.08); 
            padding: 3rem; 
            text-align: center; 
        }
        .error-icon { 
            font-size: 4rem; 
            color: #dc3545; 
            margin-bottom: 1.5rem; 
        }
        .error-title { 
            font-size: 2rem; 
            font-weight: 800; 
            margin-bottom: 1rem; 
            color: #232946; 
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
                <a href="search.php" title="Search"><i class="fas fa-search"></i></a>
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
                <div class="error-title">Payment Issue</div>
                <div class="success-message"><?php echo htmlspecialchars($error); ?></div>
                <a href="cart.php" class="continue-shopping">Return to Cart</a>
            </div>
        <?php else: ?>
            <div class="success-container">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="success-title">Order Successful!</div>
                <div class="success-message">
                    Thank you for your order! Your payment has been processed successfully.
                    You will receive an email confirmation shortly.
                </div>
                
                <?php if ($order_details): ?>
                <div class="order-details">
                    <h3>Order Details</h3>
                    <div class="detail-row">
                        <span class="detail-label">Order ID:</span>
                        <span><?php echo htmlspecialchars($order_details['session_id']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Total Amount:</span>
                        <span>$<?php echo number_format($order_details['amount_total'], 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Customer Email:</span>
                        <span><?php echo htmlspecialchars($order_details['customer_email']); ?></span>
                    </div>
                    <?php if ($order_details['customer_name']): ?>
                    <div class="detail-row">
                        <span class="detail-label">Customer Name:</span>
                        <span><?php echo htmlspecialchars($order_details['customer_name']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <a href="/pages/shop.php" class="continue-shopping">Continue Shopping</a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html> 