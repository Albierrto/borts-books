<?php
$pageTitle = "Your Cart";
$currentPage = "cart";
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
                    <li><a href="/borts-books/index.php">Home</a></li>
                    <li><a href="/borts-books/pages/shop.php">Shop</a></li>
                    <li><a href="/borts-books/pages/collections.php">Collections</a></li>
                    <li><a href="/borts-books/pages/sell.php">Sell Manga</a></li>
                    <li><a href="/borts-books/pages/about.php">About</a></li>
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

    <section class="page-header">
        <div class="container">
            <h1>Your Cart</h1>
            <p>Review and manage your items</p>
        </div>
    </section>

    <section class="container cart-container">
        <div class="cart-items" id="cart-items">
            <!-- Cart items will be loaded dynamically here -->
        </div>
        <div class="cart-summary">
            <div class="summary-header">
                <h3>Order Summary</h3>
            </div>
            <div class="summary-details">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span class="subtotal" id="cart-subtotal">$0.00</span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span class="shipping" id="cart-shipping">$0.00</span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span class="total-amount" id="cart-total">$0.00</span>
                </div>
            </div>
            <div class="summary-actions">
                <button class="btn btn-secondary" id="continue-shopping">Continue Shopping</button>
                <button class="btn" id="proceed-checkout">Proceed to Checkout</button>
            </div>
        </div>
    </section>

    <div class="empty-cart" id="empty-cart" style="display: none;">
        <div class="container">
            <div class="empty-cart-content">
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any items to your cart yet.</p>
                <a href="shop.php" class="btn">Start Shopping</a>
            </div>
        </div>
    </div>

    <footer>
        <div class="container footer-container">
            <div class="footer-section">
                <h3>Bort's Books</h3>
                <p>Your trusted source for manga collections since 2023.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="/borts-books/index.php">Home</a></li>
                    <li><a href="/borts-books/pages/shop.php">Shop</a></li>
                    <li><a href="/borts-books/pages/collections.php">Collections</a></li>
                    <li><a href="/borts-books/pages/sell.php">Sell Manga</a></li>
                    <li><a href="/borts-books/pages/about.php">About</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Help</h3>
                <ul>
                    <li><a href="/borts-books/pages/faq.php">FAQ</a></li>
                    <li><a href="/borts-books/pages/shipping.php">Shipping</a></li>
                    <li><a href="/borts-books/pages/returns.php">Returns</a></li>
                    <li><a href="/borts-books/pages/contact.php">Contact Us</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact</h3>
                <ul>
                    <li><i class="fas fa-envelope"></i> info@bortsbooks.com</li>
                    <li><i class="fas fa-phone"></i> (123) 456-7890</li>
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