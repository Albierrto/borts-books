<?php
$pageTitle = "Checkout";
$currentPage = "checkout";
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
            <h1>Checkout</h1>
            <p>Complete your purchase</p>
        </div>
    </section>

    <section class="container checkout-container">
        <div class="checkout-form">
            <form id="checkout-form">
                <div class="form-section">
                    <h3>Contact Information</h3>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                <div class="form-section">
                    <h3>Shipping Address</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first-name">First Name</label>
                            <input type="text" id="first-name" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label for="last-name">Last Name</label>
                            <input type="text" id="last-name" name="last_name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" required>
                    </div>
                    <div class="form-group">
                        <label for="address2">Apartment, suite, etc. (optional)</label>
                        <input type="text" id="address2" name="address2">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" required>
                        </div>
                        <div class="form-group">
                            <label for="state">State</label>
                            <select id="state" name="state" required>
                                <option value="">Select a state</option>
                                <!-- States will be loaded dynamically -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="zip">ZIP Code</label>
                            <input type="text" id="zip" name="zip" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                </div>
                <div class="form-section">
                    <h3>Payment Method</h3>
                    <div class="payment-methods">
                        <div class="payment-method">
                            <input type="radio" id="credit-card" name="payment_method" value="credit_card" checked>
                            <label for="credit-card">Credit Card</label>
                        </div>
                        <div class="payment-method">
                            <input type="radio" id="paypal" name="payment_method" value="paypal">
                            <label for="paypal">PayPal</label>
                        </div>
                    </div>
                    <div id="credit-card-form">
                        <div class="form-group">
                            <label for="card-number">Card Number</label>
                            <input type="text" id="card-number" name="card_number" placeholder="1234 5678 9012 3456">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="expiry">Expiry Date</label>
                                <input type="text" id="expiry" name="expiry" placeholder="MM/YY">
                            </div>
                            <div class="form-group">
                                <label for="cvv">CVV</label>
                                <input type="text" id="cvv" name="cvv" placeholder="123">
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="order-summary">
            <div class="summary-header">
                <h3>Order Summary</h3>
            </div>
            <div class="summary-items" id="checkout-items">
                <!-- Order items will be loaded dynamically here -->
            </div>
            <div class="summary-details">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span class="subtotal" id="checkout-subtotal">$0.00</span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span class="shipping" id="checkout-shipping">$0.00</span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span class="total-amount" id="checkout-total">$0.00</span>
                </div>
            </div>
            <button class="btn" id="place-order">Place Order</button>
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