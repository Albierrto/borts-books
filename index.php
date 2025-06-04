<?php
// Security: Disable error display in production
// Only enable detailed errors in development environment
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Start session for cart functionality
session_start();

// Include configuration first
require_once 'includes/config.php';

// Set page title
$pageTitle = "Welcome to Bort's Books";
$currentPage = "home";

// Fetch trending manga from database
require_once 'includes/db.php';
require_once 'includes/cart-display.php';
require_once 'includes/reviews-system.php';

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
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='grad' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%23667eea;stop-opacity:1' /%3E%3Cstop offset='100%25' style='stop-color:%23764ba2;stop-opacity:1' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='15' fill='url(%23grad)'/%3E%3Cpath d='M25 20h50c2.5 0 4.5 2 4.5 4.5v51c0 2.5-2 4.5-4.5 4.5H25c-2.5 0-4.5-2-4.5-4.5v-51c0-2.5 2-4.5 4.5-4.5z' fill='white'/%3E%3Cpath d='M30 30h40v5H30z' fill='%23667eea'/%3E%3Cpath d='M30 40h35v3H30z' fill='%23999'/%3E%3Cpath d='M30 47h30v3H30z' fill='%23999'/%3E%3Cpath d='M30 54h25v3H30z' fill='%23999'/%3E%3Cpath d='M30 61h20v3H30z' fill='%23999'/%3E%3C/svg%3E">
    <title><?php echo $pageTitle; ?> - Manga Store</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Permanent+Marker&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/mobile-nav.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { 
            background: #f7f7fa; 
            font-family: 'Inter', sans-serif; 
            margin: 0;
            padding: 0;
        }
        
        /* Enhanced Hero Section */
        .hero {
            background: linear-gradient(135deg, #232946 0%, #395aa0 50%, #eebbc3 100%);
            color: #fff;
            padding: 4rem 1rem 3rem 1rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="%23fff" opacity="0.1"/><circle cx="20" cy="20" r="1" fill="%23fff" opacity="0.05"/><circle cx="80" cy="30" r="1.5" fill="%23fff" opacity="0.08"/></svg>');
            animation: float 20s infinite linear;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 1200px;
            margin: 0 auto;
        }
        .hero h1 {
            font-family: 'Permanent Marker', cursive;
            font-size: clamp(2rem, 5vw, 3.5rem);
            margin-bottom: 1rem;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        .hero p { 
            font-size: clamp(1.1rem, 2.5vw, 1.4rem); 
            margin-bottom: 1.5rem; 
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }

        /* Price Guarantee Banner */
        .price-guarantee {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            margin: 1.5rem auto 2.5rem auto;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            animation: pulse 2s infinite;
        }

        .price-guarantee h3 {
            margin: 0 0 0.5rem 0;
            font-size: clamp(1.1rem, 2.5vw, 1.3rem);
            font-weight: 800;
        }

        .price-guarantee p {
            margin: 0;
            font-size: clamp(0.9rem, 2vw, 1rem);
            opacity: 0.95;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        /* Enhanced CTA Buttons */
        .hero-ctas { 
            display: flex; 
            justify-content: center; 
            gap: 1.5rem; 
            flex-wrap: wrap;
        }
        .hero-ctas a {
            padding: 1.2rem 2.8rem;
            border-radius: 50px;
            font-size: clamp(1rem, 2vw, 1.2rem);
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }
        .hero-ctas a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        .hero-ctas a:hover::before {
            left: 100%;
        }
        .hero-ctas .btn-primary { 
            background: linear-gradient(45deg, #eebbc3, #f7c7d0); 
            color: #232946; 
        }
        .hero-ctas .btn-primary:hover { 
            background: linear-gradient(45deg, #fff, #eebbc3); 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        .hero-ctas .btn-secondary { 
            background: transparent; 
            border: 3px solid #eebbc3; 
            color: #fff; 
        }
        .hero-ctas .btn-secondary:hover { 
            background: #eebbc3; 
            color: #232946;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(238, 187, 195, 0.4);
        }
        
        /* Sell Hero Section Enhancement */
        .sell-hero {
            background: linear-gradient(135deg, #eebbc3 0%, #f7c7d0 50%, #232946 100%);
            padding: 4rem 1rem 3rem 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .sell-hero-content {
            display: flex;
            align-items: center;
            gap: 3rem;
            max-width: 1100px;
            width: 100%;
            flex-wrap: wrap;
            justify-content: center;
        }
        .sell-hero img {
            width: 220px;
            height: 220px;
            object-fit: cover;
            border-radius: 18px;
            box-shadow: 0 8px 30px rgba(35,41,70,0.2);
            border: 6px solid #fff;
            transition: transform 0.3s ease;
        }
        .sell-hero img:hover {
            transform: scale(1.05) rotate(2deg);
        }
        .sell-hero-text {
            flex: 1;
            min-width: 300px;
        }
        .sell-hero h2 {
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            font-weight: 900;
            color: #232946;
            margin-bottom: 1rem;
            letter-spacing: 1px;
        }
        .sell-hero p {
            font-size: clamp(1.1rem, 2vw, 1.3rem);
            color: #232946;
            margin-bottom: 1.5rem;
            max-width: 500px;
            line-height: 1.6;
        }
        .sell-hero a {
            background: linear-gradient(45deg, #232946, #395aa0);
            color: #fff;
            padding: 1.2rem 2.8rem;
            border-radius: 50px;
            font-weight: 800;
            font-size: clamp(1rem, 2vw, 1.2rem);
            text-decoration: none;
            box-shadow: 0 4px 20px rgba(35,41,70,0.3);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .sell-hero a:hover {
            background: linear-gradient(45deg, #395aa0, #232946);
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(35,41,70,0.4);
        }
        
        /* Enhanced Trending Section */
        .trending-section { 
            background: linear-gradient(135deg, #232946 0%, #1a1f3a 100%);
            color: #fff; 
            padding: 3rem 1rem; 
        }
        .section-title { 
            text-align: center; 
            font-size: clamp(1.8rem, 4vw, 2.5rem); 
            font-weight: 800; 
            margin: 0 0 2rem 0; 
            letter-spacing: 1px;
        }
        
        /* Enhanced Carousel */
        .carousel-container {
            position: relative;
            max-width: 1200px;
            margin: 0 auto;
        }
        .carousel-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(45deg, #eebbc3, #f7c7d0);
            color: #232946;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 1.2rem;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 3;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .carousel-btn:hover {
            background: linear-gradient(45deg, #fff, #eebbc3);
            transform: translateY(-50%) scale(1.1);
        }
        .carousel-prev { left: -25px; }
        .carousel-next { right: -25px; }
        
        /* Enhanced Product Cards */
        .trending-card {
            background: #fff;
            color: #232946;
            border-radius: 16px;
            min-width: 240px;
            max-width: 240px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            flex: 0 0 auto;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        .trending-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        .trending-card img {
            width: 100%;
            height: 280px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .trending-card:hover img {
            transform: scale(1.05);
        }
        .trending-card .info {
            padding: 1.2rem;
            text-align: center;
        }
        .trending-card .title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .trending-card .price {
            color: #e63946;
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        .trending-card .add-cart {
            background: linear-gradient(45deg, #eebbc3, #f7c7d0);
            color: #232946;
            border: none;
            border-radius: 25px;
            padding: 0.7rem 1.5rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        .trending-card .add-cart:hover {
            background: linear-gradient(45deg, #232946, #395aa0);
            color: #fff;
            transform: translateY(-1px);
        }
        
        /* Enhanced Value Props */
        .value-props {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin: 3rem 1rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        .value-prop {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        .value-prop:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        .value-prop i { 
            font-size: 2.5rem; 
            color: #eebbc3; 
            margin-bottom: 1rem;
            display: block;
        }
        .value-prop h4 { 
            font-size: 1.2rem; 
            font-weight: 700; 
            margin-bottom: 0.5rem; 
        }
        .value-prop p { 
            font-size: 1rem; 
            color: #666; 
            line-height: 1.5;
        }
        
        /* Mobile Optimizations */
        @media (max-width: 768px) {
            .hero {
                padding: 3rem 1rem 2rem 1rem;
            }
            .hero-ctas {
                gap: 1rem;
            }
            .hero-ctas a {
                padding: 1rem 2rem;
                width: 100%;
                max-width: 280px;
                justify-content: center;
            }
            
            .sell-hero-content {
                flex-direction: column;
            gap: 2rem;
                text-align: center;
        }
            .sell-hero img {
                width: 180px;
                height: 180px;
            }
            
            .carousel-btn {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            .carousel-prev { left: -20px; }
            .carousel-next { right: -20px; }
            
            .value-props {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                margin: 2rem 1rem;
            }
            
            .trending-carousel {
                padding: 1rem 0.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .carousel-prev { left: -15px; }
            .carousel-next { right: -15px; }
            
            .trending-card {
                min-width: 200px;
                max-width: 200px;
            }
            .trending-card img {
                height: 240px;
            }
        }
        
        /* Animations */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .hero::before {
            animation: float 6s ease-in-out infinite;
        }
        
        /* Enhanced Reviews Section */
        .reviews-section { 
            background: #fff; 
            padding: 3rem 1rem; 
        }
        .reviews-grid { 
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem; 
            max-width: 1200px;
            margin: 0 auto;
        }
        .review-card {
            background: #f7f7fa;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        .review-card .avatar { 
            width: 60px; 
            height: 60px; 
            border-radius: 50%; 
            object-fit: cover; 
            margin: 0 auto 1rem auto;
            display: block;
            border: 3px solid #eebbc3;
        }
        .review-card .name { 
            font-weight: 700; 
            font-size: 1.1rem; 
            margin-bottom: 0.5rem; 
        }
        .review-card .stars { 
            color: #ffc107; 
            margin-bottom: 1rem; 
            font-size: 1.2rem;
        }
        .review-card p {
            font-style: italic;
            line-height: 1.5;
            color: #555;
        }
        
        /* Enhanced Newsletter Section */
        .newsletter-section { 
            background: linear-gradient(135deg, #232946 0%, #1a1f3a 100%);
            color: #fff; 
            text-align: center; 
            padding: 3rem 1rem; 
        }
        .newsletter-section h3 { 
            font-size: clamp(1.5rem, 3vw, 1.8rem); 
            font-weight: 800; 
            margin-bottom: 1rem; 
        }
        .newsletter-section p {
            margin-bottom: 2rem;
            font-size: 1.1rem;
            opacity: 0.9;
        }
        .newsletter-form { 
            max-width: 600px;
            margin: 0 auto;
        }
        .newsletter-inputs {
            display: flex; 
            gap: 1rem; 
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
        }
        .newsletter-name,
        .newsletter-email {
            padding: 1rem 1.5rem;
            border-radius: 50px;
            border: none;
            font-size: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            outline: none;
            transition: all 0.3s ease;
        }
        .newsletter-name {
            flex: 0 1 180px;
            min-width: 150px;
        }
        .newsletter-email {
            flex: 1 1 250px;
            min-width: 250px;
        }
        .newsletter-name:focus,
        .newsletter-email:focus {
            box-shadow: 0 4px 20px rgba(238, 187, 195, 0.3);
            transform: translateY(-1px);
        }
        .newsletter-form button {
            background: linear-gradient(45deg, #eebbc3, #f7c7d0);
            color: #232946;
            border: none;
            border-radius: 50px;
            padding: 1rem 2rem;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            min-width: 150px;
            position: relative;
        }
        .newsletter-form button:hover:not(:disabled) {
            background: linear-gradient(45deg, #fff, #eebbc3);
            transform: translateY(-2px);
        }
        .newsletter-form button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .newsletter-status {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 10px;
            font-weight: 600;
        }
        .newsletter-status.success {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }
        .newsletter-status.error {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        @media (max-width: 768px) {
            .newsletter-inputs {
                flex-direction: column;
                align-items: stretch;
            }
            .newsletter-name,
            .newsletter-email {
                width: 100%;
                max-width: 350px;
                margin: 0 auto;
            }
            .newsletter-form button {
                width: 100%;
                max-width: 200px;
                margin: 0 auto;
            }
        }

    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="index.php" class="logo">Bort's <span>Books</span></a>
            <nav class="main-nav">
                <a href="/index.php" <?php echo $currentPage === 'home' ? 'class="active"' : ''; ?>>Home</a>
                <a href="/pages/shop.php" <?php echo $currentPage === 'shop' ? 'class="active"' : ''; ?>>Shop</a>
                <a href="/pages/track-order.php" <?php echo $currentPage === 'track' ? 'class="active"' : ''; ?>>Track Order</a>
                <a href="/pages/sell.php" <?php echo $currentPage === 'sell' ? 'class="active"' : ''; ?>>Sell Manga</a>
                <a href="/pages/about.php" <?php echo $currentPage === 'about' ? 'class="active"' : ''; ?>>About</a>
            </nav>
            <div class="search-cart">
                <!-- REMOVED SEARCH BUTTON -->
                <a href="/pages/cart.php" class="cart-icon" title="Shopping Cart">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </a>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="hero-content">
        <h1>Discover, Collect, and Sell Manga</h1>
        <p>Your ultimate destination for authentic manga, rare finds, and unbeatable deals. Shop, sell, and join a passionate manga community!</p>
        
        <div class="price-guarantee">
            <h3><i class="fas fa-trophy"></i> LOWEST PRICES GUARANTEED</h3>
            <p>We beat Amazon, eBay, Crunchyroll & all competitors!</p>
        </div>
        
        <div class="hero-ctas">
                <a href="/pages/shop.php" class="btn-primary">
                    <i class="fas fa-shopping-bag"></i>
                    Shop Now
                </a>
                <a href="/pages/sell.php" class="btn-secondary">
                    <i class="fas fa-dollar-sign"></i>
                    Sell Your Manga
                </a>
            </div>
        </div>
    </section>

    <section class="sell-hero">
        <div class="sell-hero-content">
            <img src="https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=320&q=80" alt="Sell Manga">
            <div class="sell-hero-text">
                <h2>Turn Your Manga Into Cash!</h2>
                <p>Have a collection to sell? We offer top dollar and a smooth, friendly process. Start your selling journey now and join hundreds of happy manga fans who've cashed in with Bort's Books!</p>
                <a href="/pages/sell.php">
                    <i class="fas fa-money-bill-wave"></i>
                    Get an Offer
                </a>
            </div>
        </div>
    </section>

    <section class="trending-section">
        <div class="section-title">Trending Manga</div>
        <div class="carousel-container">
            <button id="carouselPrev" class="carousel-btn carousel-prev">&#8592;</button>
            <div id="home-carousel" style="overflow:hidden;width:100%;">
                <div id="carouselInner" style="display:flex;transition:transform 0.5s cubic-bezier(.4,2,.6,1);gap:2rem;padding:1rem 0;">
                    <?php foreach ($trendingManga as $manga): ?>
                    <div class="trending-card">
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
                        <a href="/pages/product.php?id=<?php echo $manga['id']; ?>" style="display:block;">
                            <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($manga['title']); ?>">
                        </a>
                        <div class="info">
                            <div class="title" title="<?php echo htmlspecialchars($manga['title']); ?>"><?php echo htmlspecialchars($manga['title']); ?></div>
                            <div class="price">$<?php echo number_format($manga['price'], 2); ?></div>
                            <button class="add-cart" onclick="addToCart(<?php echo $manga['id']; ?>)">
                                <i class="fas fa-cart-plus"></i>
                                Add to Cart
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <button id="carouselNext" class="carousel-btn carousel-next">&#8594;</button>
        </div>
    </section>

    <div class="value-props">
        <div class="value-prop">
            <i class="fas fa-certificate"></i>
            <h4>Authentic Manga</h4>
            <p>100% official, licensed manga only. No bootlegs or counterfeits.</p>
        </div>
        <div class="value-prop">
            <i class="fas fa-shipping-fast"></i>
            <h4>Fast Shipping</h4>
            <p>Quick, secure delivery worldwide with tracking included.</p>
        </div>
        <div class="value-prop">
            <i class="fas fa-tags"></i>
            <h4>Great Prices</h4>
            <p>Competitive deals on all titles with frequent sales and discounts.</p>
        </div>
        <div class="value-prop">
            <i class="fas fa-undo"></i>
            <h4>Easy Returns</h4>
            <p>Hassle-free returns and dedicated customer support.</p>
        </div>
    </div>

    <!-- Customer Reviews Section -->
    <?php 
    // Display reviews widget
    if (isset($reviewsSystem)) {
        echo $reviewsSystem->getReviewsCSS();
        echo $reviewsSystem->displayReviewsWidget(null, 6);
    }
    ?>

    <section class="newsletter-section">
        <h3>Get Exclusive Deals & New Arrivals</h3>
        <p>Join thousands of manga fans and be the first to know about new releases, special offers, and rare finds!</p>
        <form class="newsletter-form" id="newsletter-form">
            <div class="newsletter-inputs">
                <input type="text" name="name" placeholder="Your name (optional)" class="newsletter-name">
                <input type="email" name="email" placeholder="Your email address" required class="newsletter-email">
                <button type="submit">
                    <i class="fas fa-envelope"></i>
                    <span class="btn-text">Subscribe</span>
                    <span class="btn-loading" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i>
                        Subscribing...
                    </span>
                </button>
            </div>
            <div class="newsletter-status" style="display: none;"></div>
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
                    <li><a href="/pages/track-order.php" <?php echo $currentPage === 'track' ? 'class="active"' : ''; ?>>Track Order</a></li>
                    <li><a href="/pages/sell.php" <?php echo $currentPage === 'sell' ? 'class="active"' : ''; ?>>Sell Manga</a></li>
                    <li><a href="/pages/about.php" <?php echo $currentPage === 'about' ? 'class="active"' : ''; ?>>About</a></li>
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
                </ul>
            </div>
        </div>

        <div class="footer-bottom container">
            <p>&copy; <?php echo date('Y'); ?> Bort's Books. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Enhanced Carousel logic
        const carousel = document.getElementById('home-carousel');
        const carouselInner = document.getElementById('carouselInner');
        const prevBtn = document.getElementById('carouselPrev');
        const nextBtn = document.getElementById('carouselNext');
        const cardWidth = window.innerWidth <= 480 ? 232 : 272; // card width + gap
        let currentIndex = 0;
        const visibleCards = window.innerWidth <= 480 ? 1 : window.innerWidth <= 768 ? 2 : window.innerWidth <= 1024 ? 3 : 4;
        const totalCards = carouselInner.children.length;

        function updateCarousel() {
            if (totalCards <= visibleCards) {
                currentIndex = 0;
                prevBtn.style.display = 'none';
                nextBtn.style.display = 'none';
            } else {
                prevBtn.style.display = 'flex';
                nextBtn.style.display = 'flex';
            }
            carouselInner.style.transform = `translateX(-${currentIndex * cardWidth}px)`;
        }

        nextBtn.addEventListener('click', () => {
            if (totalCards <= visibleCards) return;
            if (currentIndex < totalCards - visibleCards) {
                currentIndex++;
            } else {
                currentIndex = 0;
            }
            updateCarousel();
        });

        prevBtn.addEventListener('click', () => {
            if (totalCards <= visibleCards) return;
            if (currentIndex > 0) {
                currentIndex--;
            } else {
                currentIndex = totalCards - visibleCards;
                if (currentIndex < 0) currentIndex = 0;
            }
            updateCarousel();
        });

        // Initialize carousel
        updateCarousel();

        // Handle window resize
        window.addEventListener('resize', () => {
            // Use debounced resize instead of immediate reload to prevent flicker
            clearTimeout(window.carouselResizeTimeout);
            window.carouselResizeTimeout = setTimeout(() => {
                const newVisibleCards = window.innerWidth <= 480 ? 1 : window.innerWidth <= 768 ? 2 : window.innerWidth <= 1024 ? 3 : 4;
                const newCardWidth = window.innerWidth <= 480 ? 232 : 272;
                
                // Only update if values actually changed
                if (newVisibleCards !== visibleCards || newCardWidth !== cardWidth) {
                    // Smoothly update without page reload
                    currentIndex = 0;
                    updateCarousel();
                }
            }, 250);
        });

        // Add to cart functionality
        function addToCart(productId) {
            fetch('/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'product_id=' + productId + '&redirect=false'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count in header
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    }
                    
                    // Show notification
                    showNotification(data.message, data.already_in_cart ? 'warning' : 'success');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error adding to cart. Please try again.', 'error');
            });
        }

        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = 'notification notification-' + type;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#d4edda' : type === 'warning' ? '#fff3cd' : '#f8d7da'};
                color: ${type === 'success' ? '#155724' : type === 'warning' ? '#856404' : '#721c24'};
                border: 1px solid ${type === 'success' ? '#c3e6cb' : type === 'warning' ? '#ffeaa7' : '#f5c6cb'};
                padding: 1rem 1.5rem;
                border-radius: 12px;
                z-index: 9999;
                font-weight: 600;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                animation: slideIn 0.3s ease-out;
                max-width: 300px;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        // Enhanced Newsletter form handling with AJAX
        document.getElementById('newsletter-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const email = form.querySelector('.newsletter-email').value.trim();
            const name = form.querySelector('.newsletter-name').value.trim();
            const button = form.querySelector('button[type="submit"]');
            const btnText = button.querySelector('.btn-text');
            const btnLoading = button.querySelector('.btn-loading');
            const statusDiv = form.querySelector('.newsletter-status');
            
            if (!email) {
                showNewsletterStatus('Please enter a valid email address', 'error');
                return;
            }
            
            // Show loading state
            button.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-flex';
            hideNewsletterStatus();
            
            // Send AJAX request
            const formData = new FormData();
            formData.append('action', 'newsletter_signup');
            formData.append('email', email);
            formData.append('name', name);
            formData.append('source', 'homepage');
            
            fetch('/includes/email-system.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNewsletterStatus(data.message, 'success');
                    form.reset();
                    
                    // Also show the floating notification
                    showNotification(data.message, 'success');
                } else {
                    showNewsletterStatus(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Newsletter signup error:', error);
                showNewsletterStatus('Sorry, there was an error. Please try again later.', 'error');
            })
            .finally(() => {
                // Reset button state
                button.disabled = false;
                btnText.style.display = 'inline';
                btnLoading.style.display = 'none';
            });
        });
        
        function showNewsletterStatus(message, type) {
            const statusDiv = document.querySelector('.newsletter-status');
            statusDiv.textContent = message;
            statusDiv.className = `newsletter-status ${type}`;
            statusDiv.style.display = 'block';
        }
        
        function hideNewsletterStatus() {
            const statusDiv = document.querySelector('.newsletter-status');
            statusDiv.style.display = 'none';
        }

    </script>
    <script src="assets/js/mobile-nav.js"></script>
</body>
</html>