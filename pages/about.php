<?php
$pageTitle = "About Us";
$currentPage = "about";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Bort's Books</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/mobile-nav.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .about-container {
            max-width: 800px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 2rem;
            text-align: center;
        }
        .about-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #2a9d8f;
        }
        .about-content {
            font-size: 1.1rem;
            color: #333;
            line-height: 1.6;
        }


    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="../index.php" class="logo">Bort's <span>Books</span></a>
            <nav class="main-nav">
                <a href="../index.php" <?php echo $currentPage === 'home' ? 'class="active"' : ''; ?>>Home</a>
                <a href="shop.php" <?php echo $currentPage === 'shop' ? 'class="active"' : ''; ?>>Shop</a>
                <a href="track-order.php" <?php echo $currentPage === 'track' ? 'class="active"' : ''; ?>>Track Order</a>
                <a href="sell.php" <?php echo $currentPage === 'sell' ? 'class="active"' : ''; ?>>Sell Manga</a>
                <a href="about.php" <?php echo $currentPage === 'about' ? 'class="active"' : ''; ?>>About</a>
            </nav>
            <div class="search-cart">
                <a href="../cart.php" title="Shopping Cart" class="cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count">0</span>
                </a>
            </div>
        </div>
    </header>
    <main>
        <div class="about-container">
            <h1 class="about-title">About Us</h1>
            <p class="about-content">
                <b>Bort's Books</b> is your trusted source for manga collections since 2023. We're passionate about connecting manga fans with the stories and series they love—whether you're a seasoned collector or just starting your journey.
            </p>
            
            <!-- Pricing Promise Section -->
            <div style="background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 2rem; border-radius: 12px; margin: 2rem 0; text-align: center; box-shadow: 0 4px 20px rgba(40, 167, 69, 0.3);">
                <h2 style="margin: 0 0 1rem 0; font-size: 1.8rem; font-weight: 800;">
                    <i class="fas fa-shield-alt"></i> Our Pricing Promise
                </h2>
                <p style="margin: 0 0 1rem 0; font-size: 1.2rem; font-weight: 600;">
                    ✅ NO FAKE SALES - Our prices are always genuine<br>
                    ✅ LOWEST PRICES GUARANTEED - We beat Amazon, eBay, Crunchyroll & all competitors<br>
                    ✅ TRANSPARENT PRICING - What you see is what you pay, no hidden fees
                </p>
                <p style="margin: 0; font-size: 1rem; opacity: 0.95; font-style: italic;">
                    "We don't play pricing games. Our everyday prices are lower than others' 'sale' prices!"
                </p>
            </div>

            <p class="about-content">
                Our mission is to make manga accessible and affordable for everyone. We offer a curated selection of both popular and rare titles, <strong>genuinely competitive prices</strong>, and a friendly, knowledgeable team ready to help you find your next favorite read.
            </p>
            
            <p class="about-content">
                <strong>Why choose Bort's Books?</strong> Unlike other retailers who use inflated "MSRP" prices to create fake discounts, we believe in honest, transparent pricing. Our prices are consistently lower than major competitors like Amazon, eBay, and Crunchyroll - not just during "sales" but every single day.
            </p>
            
            <p class="about-content">
                We believe in community, transparency, and a love for all things manga. Thank you for supporting our small business and being part of the Bort's Books family!
            </p>
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

    <script src="../assets/js/mobile-nav.js"></script>
</body>
</html> 