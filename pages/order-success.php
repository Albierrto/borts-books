<?php
$pageTitle = "Order Success";
$currentPage = "order-success";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Bort's Books</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="../index.php" class="logo">Bort's <span>Books</span></a>
            <nav>
                <ul>
                    <li><a href="/index.php">Home</a></li>
                    <li><a href="/pages/shop.php">Shop</a></li>
                    <li><a href="/pages/track-order.php">Track Order</a></li>
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

    <section class="page-header">
        <div class="container">
            <h1>Order Confirmed!</h1>
            <p>Thank you for your purchase</p>
        </div>
    </section>

    <section class="container order-success-container">
        <div class="success-message">
            <div class="success-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <h2>Your order has been placed successfully!</h2>
            <p>Order Number: <span id="order-number"><?php echo isset($_GET['order_id']) ? htmlspecialchars($_GET['order_id']) : 'N/A'; ?></span></p>
            <p>We've sent a confirmation email to your registered email address.</p>
        </div>
        <div class="order-details">
            <h3>Order Summary</h3>
            <div class="summary-items" id="order-items">
                <!-- Order items will be loaded dynamically here -->
            </div>
            <div class="summary-details">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span class="subtotal" id="order-subtotal">$0.00</span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span class="shipping" id="order-shipping">$0.00</span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span class="total-amount" id="order-total">$0.00</span>
                </div>
            </div>
        </div>
        <div class="shipping-info">
            <h3>Shipping Information</h3>
            <div class="shipping-details" id="shipping-details">
                <!-- Shipping details will be loaded dynamically here -->
            </div>
        </div>
        <div class="next-steps">
            <h3>What's Next?</h3>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h4>Order Processing</h4>
                    <p>We'll process your order within 24 hours</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h4>Shipping</h4>
                    <p>You'll receive a tracking number once your order ships</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h4>Delivery</h4>
                    <p>Your order should arrive within 3-5 business days</p>
                </div>
            </div>
        </div>
        <div class="action-buttons">
            <a href="shop.php" class="btn btn-secondary">Continue Shopping</a>
            <a href="../index.php" class="btn">Back to Home</a>
        </div>
    </section>

    <footer>
        <div class="container footer-container">
            <div class="footer-section">
                <h3>Bort's Books</h3>
                <p>Your trusted source for manga collections since 2023.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="/index.php">Home</a></li>
                    <li><a href="/pages/shop.php">Shop</a></li>
                    <li><a href="/pages/track-order.php">Track Order</a></li>
                    <li><a href="/pages/sell.php">Sell Manga</a></li>
                    <li><a href="/pages/about.php">About</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Help</h3>
                <ul>
                    <li><a href="/pages/faq.php">FAQ</a></li>
                    <li><a href="/pages/returns.php">Returns</a></li>
                    <li><a href="/pages/contact.php">Contact Us</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact</h3>
                <ul>
                    <li><i class="fas fa-envelope"></i> bort@bortsbooks.com</li>
                    <li><i class="fas fa-map-marker-alt"></i> 123 Manga St, Anime City, AC 12345</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom container">
            <p>&copy; <?php echo date('Y'); ?> Bort's Books. All rights reserved.</p>
        </div>
    </footer>
</body>
</html> 