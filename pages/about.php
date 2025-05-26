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

        /* Responsive Mobile Navigation */
        .topnav {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .topnav a {
            color: #333;
            text-decoration: none;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            position: relative;
        }

        .topnav a:hover,
        .topnav a.active {
            color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }

        /* Hide the hamburger icon by default */
        .topnav .icon {
            display: none;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .topnav .icon:hover {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        /* Mobile Navigation Styles */
        @media screen and (max-width: 768px) {
            .header-container {
                flex-wrap: wrap;
                justify-content: space-between;
                align-items: center;
                position: relative;
                padding: 1rem 20px;
            }

            .topnav {
                order: 3;
                width: 100%;
                flex-direction: column;
                gap: 0;
                background: #fff;
                border-top: 1px solid #e1e5e9;
                margin-top: 1rem;
                padding-top: 1rem;
                display: none;
            }

            .topnav.responsive {
                display: flex;
            }

            .topnav a:not(.icon) {
                display: block;
                width: 100%;
                text-align: left;
                padding: 1rem;
                border-bottom: 1px solid #f0f0f0;
                margin: 0;
                border-radius: 0;
            }

            .topnav a:not(.icon):last-of-type {
                border-bottom: none;
            }

            .topnav .icon {
                display: block;
                position: absolute;
                right: 20px;
                top: 1rem;
                order: 4;
            }

            .logo {
                order: 1;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="/index.php" class="logo">Bort's <span>Books</span></a>
            <nav class="topnav" id="myTopnav">
                <a href="/index.php" <?php echo $currentPage === 'home' ? 'class="active"' : ''; ?>>Home</a>
                <a href="/pages/shop.php" <?php echo $currentPage === 'shop' ? 'class="active"' : ''; ?>>Shop</a>
                <a href="/pages/track-order.php" <?php echo $currentPage === 'track' ? 'class="active"' : ''; ?>>Track Order</a>
                <a href="/pages/sell.php" <?php echo $currentPage === 'sell' ? 'class="active"' : ''; ?>>Sell Manga</a>
                <a href="/pages/about.php" <?php echo $currentPage === 'about' ? 'class="active"' : ''; ?>>About</a>
                <a href="javascript:void(0);" class="icon" onclick="toggleMobileNav()">
                    <i class="fa fa-bars"></i>
                </a>
            </nav>
        </div>
    </header>
    <main>
        <div class="about-container">
            <h1 class="about-title">About Us</h1>
            <p class="about-content">
                <b>Bort's Books</b> is your trusted source for manga collections since 2023. We're passionate about connecting manga fans with the stories and series they love—whether you're a seasoned collector or just starting your journey.
            </p>
            <p class="about-content">
                Our mission is to make manga accessible and affordable for everyone. We offer a curated selection of both popular and rare titles, competitive prices, and a friendly, knowledgeable team ready to help you find your next favorite read.
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

    <script>
        // Mobile Navigation Toggle Function
        function toggleMobileNav() {
            var x = document.getElementById("myTopnav");
            if (x.className === "topnav") {
                x.className += " responsive";
            } else {
                x.className = "topnav";
            }
        }

        // Close mobile nav when clicking outside
        document.addEventListener('click', function(e) {
            const nav = document.getElementById("myTopnav");
            const hamburger = nav.querySelector('.icon');
            
            if (!nav.contains(e.target) && nav.classList.contains('responsive')) {
                nav.className = "topnav";
            }
        });

        // Close mobile nav when clicking on a link
        document.querySelectorAll('.topnav a:not(.icon)').forEach(link => {
            link.addEventListener('click', function() {
                const nav = document.getElementById("myTopnav");
                nav.className = "topnav";
            });
        });
    </script>
</body>
</html> 