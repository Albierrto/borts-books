<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Start session for cart functionality
session_start();

// Set page title
$pageTitle = "Welcome to Bort's Books";
$currentPage = "home";

// Fetch trending manga from database
require_once 'includes/db.php';
$trendingQuery = "SELECT p.*, pi.image_url 
                 FROM products p 
                 LEFT JOIN (
                     SELECT product_id, MIN(id) as min_image_id
                     FROM product_images
                     GROUP BY product_id
                 ) pim ON p.id = pim.product_id
                 LEFT JOIN product_images pi ON pim.min_image_id = pi.id
                 ORDER BY p.created_at DESC 
                 LIMIT 8";
$trendingManga = $db->query($trendingQuery)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Manga Store</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Permanent+Marker&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f7f7fa; font-family: 'Inter', sans-serif; }
        .hero {
            background: linear-gradient(120deg, #232946 60%, #eebbc3 100%);
            color: #fff;
            padding: 4rem 0 3rem 0;
            text-align: center;
            position: relative;
        }
        .hero h1 {
            font-family: 'Permanent Marker', cursive;
            font-size: 3rem;
            margin-bottom: 1rem;
            letter-spacing: 2px;
        }
        .hero p { font-size: 1.3rem; margin-bottom: 2rem; }
        .hero-ctas { display: flex; justify-content: center; gap: 1.5rem; }
        .hero-ctas a {
            padding: 1rem 2.5rem;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: 700;
            text-decoration: none;
            transition: background 0.2s;
        }
        .hero-ctas .btn-primary { background: #eebbc3; color: #232946; }
        .hero-ctas .btn-primary:hover { background: #fff; color: #232946; }
        .hero-ctas .btn-secondary { background: transparent; border: 2px solid #eebbc3; color: #fff; }
        .hero-ctas .btn-secondary:hover { background: #eebbc3; color: #232946; }
        .genre-section {
            background: #fff;
            padding: 2.5rem 0 1.5rem 0;
        }
        .genre-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1.5rem;
            max-width: 900px;
            margin: 0 auto;
        }
        .genre-card {
            background: #f7f7fa;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(35,41,70,0.07);
            text-align: center;
            padding: 1.2rem 0.5rem;
            transition: transform 0.15s;
            cursor: pointer;
        }
        .genre-card:hover { transform: translateY(-6px) scale(1.04); background: #eebbc3; color: #232946; }
        .genre-card img { width: 60px; height: 90px; object-fit: cover; border-radius: 8px; margin-bottom: 0.7rem; }
        .genre-card span { font-weight: 600; font-size: 1.1rem; }
        .section-title { text-align: center; font-size: 2rem; font-weight: 800; margin: 2.5rem 0 1rem 0; letter-spacing: 1px; }
        .trending-section { background: #232946; color: #fff; padding: 2.5rem 0; }
        .trending-carousel {
            display: flex;
            gap: 1.5rem;
            overflow-x: auto;
            padding: 1rem 0 1rem 1rem;
            scroll-snap-type: x mandatory;
        }
        .trending-card {
            background: #fff;
            color: #232946;
            border-radius: 14px;
            min-width: 220px;
            box-shadow: 0 2px 8px rgba(35,41,70,0.09);
            margin-bottom: 1rem;
            flex: 0 0 auto;
            scroll-snap-align: start;
            transition: transform 0.15s;
        }
        .trending-card:hover { transform: translateY(-6px) scale(1.04); }
        .trending-card img { width: 100%; height: 180px; object-fit: cover; border-radius: 14px 14px 0 0; }
        .trending-card .info { padding: 1rem; }
        .trending-card .title { font-weight: 700; font-size: 1.1rem; margin-bottom: 0.3rem; }
        .trending-card .price { color: #e63946; font-weight: 700; margin-bottom: 0.5rem; }
        .trending-card .add-cart { background: #eebbc3; color: #232946; border: none; border-radius: 20px; padding: 0.5rem 1.2rem; font-weight: 700; cursor: pointer; transition: background 0.2s; }
        .trending-card .add-cart:hover { background: #232946; color: #fff; }
        .value-props {
            display: flex;
            justify-content: center;
            gap: 2.5rem;
            margin: 2.5rem 0 1.5rem 0;
            flex-wrap: wrap;
        }
        .value-prop {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(35,41,70,0.07);
            padding: 1.5rem 2rem;
            text-align: center;
            flex: 1 1 180px;
            min-width: 180px;
        }
        .value-prop i { font-size: 2rem; color: #eebbc3; margin-bottom: 0.7rem; }
        .value-prop h4 { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.3rem; }
        .value-prop p { font-size: 0.98rem; color: #555; }
        .sell-callout {
            background: linear-gradient(90deg, #eebbc3 60%, #fff 100%);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 2.5rem 2rem;
            margin: 2.5rem auto 2rem auto;
            max-width: 900px;
            box-shadow: 0 2px 12px rgba(35,41,70,0.08);
            gap: 2rem;
        }
        .sell-callout img { width: 120px; border-radius: 12px; }
        .sell-callout-content { flex: 1; }
        .sell-callout h2 { font-size: 1.5rem; font-weight: 800; margin-bottom: 0.7rem; }
        .sell-callout p { font-size: 1.05rem; margin-bottom: 1rem; }
        .sell-callout a { background: #232946; color: #fff; padding: 0.8rem 2rem; border-radius: 30px; font-weight: 700; text-decoration: none; transition: background 0.2s; }
        .sell-callout a:hover { background: #e63946; }
        .reviews-section { background: #fff; padding: 2.5rem 0; }
        .reviews-grid { display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap; }
        .review-card {
            background: #f7f7fa;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(35,41,70,0.07);
            padding: 1.2rem 1.5rem;
            max-width: 320px;
            min-width: 220px;
            text-align: left;
        }
        .review-card .avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-bottom: 0.5rem; }
        .review-card .name { font-weight: 700; font-size: 1rem; margin-bottom: 0.2rem; }
        .review-card .stars { color: #eebbc3; margin-bottom: 0.3rem; }
        .newsletter-section { background: #232946; color: #fff; text-align: center; padding: 2.5rem 0; }
        .newsletter-section h3 { font-size: 1.4rem; font-weight: 800; margin-bottom: 0.7rem; }
        .newsletter-form { display: flex; justify-content: center; gap: 0.7rem; margin-top: 1rem; }
        .newsletter-form input[type="email"] {
            padding: 0.7rem 1.2rem;
            border-radius: 30px;
            border: none;
            font-size: 1rem;
            min-width: 220px;
        }
        .newsletter-form button {
            background: #eebbc3;
            color: #232946;
            border: none;
            border-radius: 30px;
            padding: 0.7rem 2rem;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .newsletter-form button:hover { background: #fff; color: #232946; }
        @media (max-width: 900px) {
            .sell-callout { flex-direction: column; text-align: center; }
            .sell-callout img { margin-bottom: 1rem; }
        }
        @media (max-width: 600px) {
            .hero h1 { font-size: 2.1rem; }
            .section-title { font-size: 1.3rem; }
            .sell-callout { padding: 1.2rem 0.5rem; }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="index.php" class="logo">Bort's <span>Books</span></a>
            <nav>
                <ul>
                    <li><a href="/index.php" <?php echo $currentPage === 'home' ? 'class="active"' : ''; ?>>Home</a></li>
                    <li><a href="/pages/shop.php" <?php echo $currentPage === 'shop' ? 'class="active"' : ''; ?>>Shop</a></li>
                    <li><a href="/pages/sell.php" <?php echo $currentPage === 'sell' ? 'class="active"' : ''; ?>>Sell Manga</a></li>
                    <li><a href="/pages/about.php" <?php echo $currentPage === 'about' ? 'class="active"' : ''; ?>>About</a></li>
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

    <section class="hero">
        <h1>Discover, Collect, and Sell Manga</h1>
        <p>Your ultimate destination for authentic manga, rare finds, and unbeatable deals. Shop, sell, and join a passionate manga community!</p>
        <div class="hero-ctas">
            <a href="/pages/shop.php" class="btn-primary">Shop Now</a>
            <a href="/pages/sell.php" class="btn-secondary">Sell Your Manga</a>
        </div>
    </section>

    <section class="sell-hero" style="background:linear-gradient(120deg,#eebbc3 60%,#232946 100%);padding:3.5rem 0 3rem 0;display:flex;align-items:center;justify-content:center;">
        <div style="display:flex;align-items:center;gap:3rem;max-width:1100px;width:100%;flex-wrap:wrap;justify-content:center;">
            <img src="https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=320&q=80" alt="Sell Manga" style="width:220px;height:220px;object-fit:cover;border-radius:18px;box-shadow:0 4px 24px rgba(35,41,70,0.13);border:6px solid #fff;">
            <div style="flex:1;min-width:260px;">
                <h2 style="font-size:2.3rem;font-weight:900;color:#232946;margin-bottom:1rem;letter-spacing:1px;">Turn Your Manga Into Cash!</h2>
                <p style="font-size:1.2rem;color:#232946;margin-bottom:1.5rem;max-width:500px;">Have a collection to sell? We offer top dollar and a smooth, friendly process. Start your selling journey now and join hundreds of happy manga fans who've cashed in with Bort's Books!</p>
                <a href="/pages/sell.php" style="background:#232946;color:#fff;padding:1.1rem 2.5rem;border-radius:30px;font-weight:800;font-size:1.2rem;text-decoration:none;box-shadow:0 2px 12px rgba(35,41,70,0.13);transition:background 0.2s;">Get an Offer</a>
            </div>
        </div>
    </section>

    <section class="trending-section">
        <div class="section-title">Trending Manga</div>
        <div style="position:relative;max-width:1200px;margin:0 auto;">
            <button id="carouselPrev" style="position:absolute;left:-30px;top:50%;transform:translateY(-50%);background:#eebbc3;color:#232946;border:none;border-radius:50%;width:48px;height:48px;font-size:2rem;box-shadow:0 2px 8px rgba(35,41,70,0.12);z-index:2;cursor:pointer;transition:background 0.2s;display:flex;align-items:center;justify-content:center;">&#8592;</button>
            <div id="home-carousel" style="overflow:hidden;width:100%;">
                <div id="carouselInner" style="display:flex;transition:transform 0.5s cubic-bezier(.4,2,.6,1);gap:2rem;padding:1rem 0;">
                    <?php foreach ($trendingManga as $manga): ?>
                    <div class="trending-card" style="min-width:240px;max-width:240px;background:#fff;color:#232946;border-radius:14px;box-shadow:0 2px 8px rgba(35,41,70,0.09);margin-bottom:1rem;flex:0 0 auto;display:flex;flex-direction:column;align-items:center;">
                        <?php
                        $imgSrc = '';
                        if (!empty($manga['image_url'])) {
                            if (preg_match('/^https?:\/\//', $manga['image_url'])) {
                                $imgSrc = htmlspecialchars($manga['image_url']);
                            } else {
                                $imgSrc = '/' . ltrim(preg_replace('/^\.\.\//', '', $manga['image_url']), '/');
                            }
                        } else {
                            $svg = base64_encode('<svg width="240" height="320" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="#f5f5fa"/><text x="50%" y="50%" font-family="Arial" font-size="24" fill="#232946" text-anchor="middle" dominant-baseline="middle">No Image</text></svg>');
                            $imgSrc = "data:image/svg+xml;base64,$svg";
                        }
                        ?>
                        <a href="/pages/product.php?id=<?php echo $manga['id']; ?>" style="display:block;width:200px;height:280px;">
                            <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($manga['title']); ?>" style="width:200px;height:280px;object-fit:cover;border-radius:12px 12px 0 0;box-shadow:0 2px 8px rgba(35,41,70,0.08);margin-top:1rem;">
                        </a>
                        <div class="info" style="padding:1rem;width:100%;display:flex;flex-direction:column;align-items:center;">
                            <div class="title" style="font-size:1.15rem;font-weight:700;margin-bottom:0.3rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;width:100%;" title="<?php echo htmlspecialchars($manga['title']); ?>"><?php echo htmlspecialchars($manga['title']); ?></div>
                            <div class="price" style="color:#e63946;font-weight:700;margin-bottom:0.5rem;">$<?php echo number_format($manga['price'], 2); ?></div>
                            <button class="add-cart" onclick="addToCart(<?php echo $manga['id']; ?>)" style="background:#eebbc3;color:#232946;border:none;border-radius:20px;padding:0.5rem 1.2rem;font-weight:700;cursor:pointer;transition:background 0.2s;">Add to Cart</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <button id="carouselNext" style="position:absolute;right:-30px;top:50%;transform:translateY(-50%);background:#eebbc3;color:#232946;border:none;border-radius:50%;width:48px;height:48px;font-size:2rem;box-shadow:0 2px 8px rgba(35,41,70,0.12);z-index:2;cursor:pointer;transition:background 0.2s;display:flex;align-items:center;justify-content:center;">&#8594;</button>
        </div>
        <script>
        // Carousel logic
        const carousel = document.getElementById('home-carousel');
        const carouselInner = document.getElementById('carouselInner');
        const prevBtn = document.getElementById('carouselPrev');
        const nextBtn = document.getElementById('carouselNext');
        const cardWidth = 240 + 32; // card width + gap
        let currentIndex = 0;
        const visibleCards = window.innerWidth < 700 ? 2 : 4;
        const totalCards = carouselInner.children.length;

        function updateCarousel() {
            carouselInner.style.transform = `translateX(-${currentIndex * cardWidth}px)`;
        }
        nextBtn.addEventListener('click', () => {
            if (currentIndex < totalCards - visibleCards) {
                currentIndex++;
                updateCarousel();
            } else {
                currentIndex = 0;
                updateCarousel();
            }
        });
        prevBtn.addEventListener('click', () => {
            if (currentIndex > 0) {
                currentIndex--;
                updateCarousel();
            } else {
                currentIndex = totalCards - visibleCards;
                if (currentIndex < 0) currentIndex = 0;
                updateCarousel();
            }
        });
        // Responsive: update visibleCards on resize
        window.addEventListener('resize', () => {
            // Optionally, recalculate visibleCards and clamp currentIndex
        });
        </script>
    </section>

    <div class="value-props">
        <div class="value-prop"><i class="fas fa-certificate"></i><h4>Authentic Manga</h4><p>100% official, licensed manga only.</p></div>
        <div class="value-prop"><i class="fas fa-shipping-fast"></i><h4>Fast Shipping</h4><p>Quick, secure delivery worldwide.</p></div>
        <div class="value-prop"><i class="fas fa-tags"></i><h4>Great Prices</h4><p>Competitive deals on all titles.</p></div>
        <div class="value-prop"><i class="fas fa-undo"></i><h4>Easy Returns</h4><p>Hassle-free returns & support.</p></div>
    </div>

    <section class="reviews-section">
        <div class="section-title">What Our Customers Say</div>
        <div class="reviews-grid">
            <div class="review-card"><img src="https://randomuser.me/api/portraits/men/32.jpg" class="avatar"><div class="name">Alex R.</div><div class="stars">★★★★★</div><div>"Super fast shipping and the manga was in perfect condition! Will buy again."</div></div>
            <div class="review-card"><img src="https://randomuser.me/api/portraits/women/44.jpg" class="avatar"><div class="name">Mina S.</div><div class="stars">★★★★★</div><div>"Love the selection and the prices. Found rare volumes I couldn't get anywhere else."</div></div>
            <div class="review-card"><img src="https://randomuser.me/api/portraits/men/65.jpg" class="avatar"><div class="name">Jordan K.</div><div class="stars">★★★★★</div><div>"Selling my collection was so easy. Great communication and quick payment!"</div></div>
        </div>
    </section>

    <section class="newsletter-section">
        <h3>Get Exclusive Deals & New Arrivals</h3>
        <form class="newsletter-form">
            <input type="email" placeholder="Your email address" required>
            <button type="submit">Subscribe</button>
        </form>
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
                    <li><a href="/index.php" <?php echo $currentPage === 'home' ? 'class="active"' : ''; ?>>Home</a></li>
                    <li><a href="/pages/shop.php" <?php echo $currentPage === 'shop' ? 'class="active"' : ''; ?>>Shop</a></li>
                    <li><a href="/pages/sell.php" <?php echo $currentPage === 'sell' ? 'class="active"' : ''; ?>>Sell Manga</a></li>
                    <li><a href="/pages/about.php" <?php echo $currentPage === 'about' ? 'class="active"' : ''; ?>>About</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Help</h3>
                <ul>
                    <li><a href="/pages/faq.php">FAQ</a></li>
                    <li><a href="/pages/shipping.php">Shipping</a></li>
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
</body>
</html>