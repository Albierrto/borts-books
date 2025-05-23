<?php
require_once 'includes/config.php';
require_once 'includes/stripe-config.php';
require_once 'includes/cart.php';

// Get the session ID from the URL
$sessionId = $_GET['session_id'] ?? null;

if (!$sessionId) {
    header('Location: index.php');
    exit;
}

// Verify the Stripe session
$session = verifyStripeSession($sessionId);

if (!$session || $session->payment_status !== 'paid') {
    header('Location: checkout.php');
    exit;
}

// Get customer info from session
$customerInfo = $_SESSION['customer_info'] ?? null;

if (!$customerInfo) {
    header('Location: checkout.php');
    exit;
}

// Get cart items
$cart = getCart();

// Save the order to the database
try {
    $db->beginTransaction();

    // Insert order
    $orderStmt = $db->prepare('
        INSERT INTO orders (
            stripe_session_id,
            customer_name,
            customer_email,
            customer_phone,
            shipping_address,
            total_amount,
            payment_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ');

    $shippingAddress = json_encode($session->shipping);
    $orderStmt->execute([
        $sessionId,
        $customerInfo['name'],
        $customerInfo['email'],
        $customerInfo['phone'],
        $shippingAddress,
        $session->amount_total / 100, // Convert from cents
        'paid'
    ]);

    $orderId = $db->lastInsertId();

    // Insert order items
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
        $itemStmt->execute([
            $orderId,
            $item['id'],
            $item['title'],
            $item['price'],
            $item['quantity'],
            $item['image_url']
        ]);
    }

    $db->commit();

    // Clear the cart and customer info
    clearCart();
    unset($_SESSION['customer_info']);

} catch (Exception $e) {
    $db->rollBack();
    error_log('Order save error: ' . $e->getMessage());
    header('Location: checkout.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - Bort's Books</title>
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
                    <p>Phone: <?php echo htmlspecialchars($customerInfo['phone']); ?></p>
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