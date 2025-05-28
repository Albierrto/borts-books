<?php
session_start();

$pageTitle = "Mobile Navigation Test";
$currentPage = "test";

// Initialize cart for testing
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cart_count = count($_SESSION['cart']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Bort's Books</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Permanent+Marker&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/mobile-nav.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { 
            background: #f7f7fa; 
            font-family: 'Inter', sans-serif; 
            margin: 0;
            padding: 0;
        }
        
        .test-container {
            max-width: 800px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 2rem;
        }
        
        .test-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: #232946;
            text-align: center;
        }
        
        .test-content {
            line-height: 1.6;
            color: #555;
        }
        
        .test-instructions {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 1rem;
            margin: 1.5rem 0;
            border-radius: 0 8px 8px 0;
        }
        
        .test-checklist {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }
        
        .test-checklist h3 {
            margin-top: 0;
            color: #232946;
        }
        
        .test-checklist ul {
            margin: 0;
            padding-left: 1.5rem;
        }
        
        .test-checklist li {
            margin-bottom: 0.5rem;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }
        
        .status-good { background: #28a745; }
        .status-warning { background: #ffc107; }
        .status-error { background: #dc3545; }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="index.php" class="logo">Bort's <span>Books</span></a>
            <nav class="main-nav">
                <a href="index.php" <?php echo $currentPage === 'home' ? 'class="active"' : ''; ?>>Home</a>
                <a href="pages/shop.php" <?php echo $currentPage === 'shop' ? 'class="active"' : ''; ?>>Shop</a>
                <a href="pages/track-order.php" <?php echo $currentPage === 'track' ? 'class="active"' : ''; ?>>Track Order</a>
                <a href="pages/sell.php" <?php echo $currentPage === 'sell' ? 'class="active"' : ''; ?>>Sell Manga</a>
                <a href="pages/about.php" <?php echo $currentPage === 'about' ? 'class="active"' : ''; ?>>About</a>
            </nav>
            <div class="search-cart">
                <a href="pages/cart.php" class="cart-icon" title="Shopping Cart">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </a>
            </div>
        </div>
    </header>

    <main>
        <div class="test-container">
            <h1 class="test-title">Mobile Navigation Test</h1>
            
            <div class="test-instructions">
                <h3><i class="fas fa-info-circle"></i> Testing Instructions</h3>
                <p>This page is designed to test the new mobile navigation system. Please test on different screen sizes and devices.</p>
            </div>
            
            <div class="test-content">
                <h2>What to Test:</h2>
                
                <div class="test-checklist">
                    <h3>Desktop (768px+)</h3>
                    <ul>
                        <li><span class="status-indicator status-good"></span>Navigation should be visible in header</li>
                        <li><span class="status-indicator status-good"></span>No hamburger menu should be visible</li>
                        <li><span class="status-indicator status-good"></span>All navigation links should work</li>
                        <li><span class="status-indicator status-good"></span>Cart icon should be visible</li>
                    </ul>
                </div>
                
                <div class="test-checklist">
                    <h3>Mobile (768px and below)</h3>
                    <ul>
                        <li><span class="status-indicator status-good"></span>Hamburger menu should be visible in top-right</li>
                        <li><span class="status-indicator status-good"></span>Desktop navigation should be hidden</li>
                        <li><span class="status-indicator status-good"></span>Clicking hamburger should open slide-out menu</li>
                        <li><span class="status-indicator status-good"></span>Menu should slide in from the right</li>
                        <li><span class="status-indicator status-good"></span>Overlay should appear behind menu</li>
                        <li><span class="status-indicator status-good"></span>Clicking overlay should close menu</li>
                        <li><span class="status-indicator status-good"></span>Clicking menu links should close menu</li>
                        <li><span class="status-indicator status-good"></span>ESC key should close menu</li>
                        <li><span class="status-indicator status-good"></span>Body scroll should be prevented when menu is open</li>
                        <li><span class="status-indicator status-good"></span>Cart link should be in mobile menu footer</li>
                    </ul>
                </div>
                
                <div class="test-checklist">
                    <h3>Functionality</h3>
                    <ul>
                        <li><span class="status-indicator status-good"></span>No flickering when opening/closing menu</li>
                        <li><span class="status-indicator status-good"></span>Smooth animations</li>
                        <li><span class="status-indicator status-good"></span>Proper focus management</li>
                        <li><span class="status-indicator status-good"></span>Active page highlighting</li>
                        <li><span class="status-indicator status-good"></span>Cart count updates properly</li>
                    </ul>
                </div>
            </div>
            
            <div class="test-instructions">
                <h3><i class="fas fa-mobile-alt"></i> How to Test Mobile</h3>
                <p>
                    1. Open browser developer tools (F12)<br>
                    2. Click the device toggle icon or press Ctrl+Shift+M<br>
                    3. Select a mobile device or set width to 375px<br>
                    4. Test all the mobile functionality listed above
                </p>
            </div>
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="index.php" style="background: linear-gradient(45deg, #eebbc3, #f7c7d0); color: #232946; padding: 1rem 2rem; border-radius: 50px; text-decoration: none; font-weight: 700;">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        </div>
    </main>

    <footer>
        <div class="container footer-container">
            <div class="footer-section">
                <h3>Bort's Books</h3>
                <p>Your trusted source for manga collections since 2023.</p>
            </div>

            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="pages/shop.php">Shop</a></li>
                    <li><a href="pages/track-order.php">Track Order</a></li>
                    <li><a href="pages/sell.php">Sell Manga</a></li>
                    <li><a href="pages/about.php">About</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Help</h3>
                <ul>
                    <li><a href="pages/faq.php">FAQ</a></li>
                    <li><a href="pages/returns.php">Returns</a></li>
                    <li><a href="pages/contact.php">Contact Us</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Contact</h3>
                <ul>
                    <li><i class="fas fa-envelope"></i> bort@bortsbooks.com</li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom container">
            <p>&copy; <?php echo date('Y'); ?> Bort's Books. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/mobile-nav.js"></script>
</body>
</html> 