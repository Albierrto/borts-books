<?php
/**
 * Mobile Optimization System for Bort's Books
 * Comprehensive mobile-first user experience enhancements
 */

class MobileOptimizer {
    
    private static $mobile_breakpoints = [
        'mobile' => 768,
        'tablet' => 1024,
        'desktop' => 1200
    ];
    
    public static function init() {
        // Detect device type
        self::detectDevice();
        
        // Set mobile-specific headers
        self::setMobileHeaders();
        
        // Initialize mobile features
        self::initMobileFeatures();
    }
    
    private static function detectDevice() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $mobile_agents = [
            'Mobile', 'Android', 'iPhone', 'iPad', 'iPod', 'BlackBerry', 
            'Windows Phone', 'Opera Mini', 'IEMobile', 'Mobile Safari'
        ];
        
        $is_mobile = false;
        foreach ($mobile_agents as $agent) {
            if (stripos($user_agent, $agent) !== false) {
                $is_mobile = true;
                break;
            }
        }
        
        $_SESSION['is_mobile'] = $is_mobile;
        $_SESSION['user_agent'] = $user_agent;
    }
    
    private static function setMobileHeaders() {
        if (!headers_sent()) {
            // Viewport meta tag for responsive design
            echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">' . "\n";
            
            // Mobile-specific meta tags
            echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
            echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
            echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
            echo '<meta name="theme-color" content="#232946">' . "\n";
            
            // Preconnect to important domains for mobile
            echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
            echo '<link rel="preconnect" href="https://js.stripe.com">' . "\n";
        }
    }
    
    private static function initMobileFeatures() {
        // Add mobile-specific CSS
        echo '<style>' . self::generateMobileCSS() . '</style>' . "\n";
        
        // Add mobile-specific JavaScript
        echo '<script>' . self::generateMobileJS() . '</script>' . "\n";
    }
    
    private static function generateMobileCSS() {
        return '
        /* Mobile-First Responsive Design */
        @media (max-width: 768px) {
            body {
                font-size: 16px;
                line-height: 1.5;
                -webkit-text-size-adjust: 100%;
            }
            
            /* Touch-friendly buttons */
            .btn, button, input[type="submit"] {
                min-height: 44px;
                min-width: 44px;
                padding: 12px 20px;
                font-size: 16px;
                border-radius: 8px;
                touch-action: manipulation;
            }
            
            /* Mobile navigation */
            .mobile-menu {
                position: fixed;
                top: 0;
                left: -100%;
                width: 80%;
                height: 100vh;
                background: #fff;
                z-index: 9999;
                transition: left 0.3s ease;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            }
            
            .mobile-menu.active {
                left: 0;
            }
            
            .mobile-menu-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100vh;
                background: rgba(0,0,0,0.5);
                z-index: 9998;
                display: none;
            }
            
            .mobile-menu-overlay.active {
                display: block;
            }
            
            /* Product grid optimization */
            .product-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 10px;
                padding: 10px;
            }
            
            .product-card {
                padding: 10px;
                border-radius: 8px;
            }
            
            .product-image {
                aspect-ratio: 3/4;
                object-fit: cover;
                border-radius: 4px;
            }
            
            /* Mobile-optimized forms */
            .form-group {
                margin-bottom: 20px;
            }
            
            input, select, textarea {
                width: 100%;
                padding: 12px;
                font-size: 16px;
                border: 2px solid #ddd;
                border-radius: 8px;
                box-sizing: border-box;
            }
            
            input:focus, select:focus, textarea:focus {
                border-color: #232946;
                outline: none;
                box-shadow: 0 0 0 3px rgba(35, 41, 70, 0.1);
            }
            
            /* Mobile checkout optimization */
            .checkout-form {
                padding: 20px;
            }
            
            .checkout-step {
                margin-bottom: 30px;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
            }
            
            /* Sticky elements for mobile */
            .mobile-sticky-cart {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: #232946;
                color: white;
                padding: 15px;
                z-index: 1000;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            }
            
            /* Mobile search optimization */
            .mobile-search {
                position: relative;
                margin: 10px;
            }
            
            .mobile-search input {
                padding-right: 50px;
            }
            
            .mobile-search-btn {
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
                background: none;
                border: none;
                color: #666;
            }
            
            /* Swipe gestures for product images */
            .product-images-mobile {
                overflow-x: auto;
                scroll-snap-type: x mandatory;
                display: flex;
                gap: 10px;
                padding: 10px 0;
            }
            
            .product-images-mobile img {
                scroll-snap-align: start;
                flex-shrink: 0;
                width: 80%;
                height: 300px;
                object-fit: cover;
                border-radius: 8px;
            }
            
            /* Mobile-friendly tables */
            .mobile-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            
            /* Hide desktop elements on mobile */
            .desktop-only {
                display: none !important;
            }
        }
        
        /* Tablet optimizations */
        @media (min-width: 769px) and (max-width: 1024px) {
            .product-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
            }
            
            .mobile-only {
                display: none !important;
            }
        }
        
        /* Desktop optimizations */
        @media (min-width: 1025px) {
            .mobile-only {
                display: none !important;
            }
            
            .mobile-menu {
                display: none !important;
            }
        }
        
        /* Touch-specific optimizations */
        @media (hover: none) and (pointer: coarse) {
            .hover-effect:hover {
                transform: none;
            }
            
            .touch-feedback:active {
                transform: scale(0.98);
                opacity: 0.8;
            }
        }
        ';
    }
    
    private static function generateMobileJS() {
        return '
        // Mobile optimization JavaScript
        document.addEventListener("DOMContentLoaded", function() {
            // Mobile menu functionality
            const mobileMenuBtn = document.querySelector(".mobile-menu-btn");
            const mobileMenu = document.querySelector(".mobile-menu");
            const mobileMenuOverlay = document.querySelector(".mobile-menu-overlay");
            
            if (mobileMenuBtn && mobileMenu) {
                mobileMenuBtn.addEventListener("click", function() {
                    mobileMenu.classList.toggle("active");
                    mobileMenuOverlay.classList.toggle("active");
                    document.body.style.overflow = mobileMenu.classList.contains("active") ? "hidden" : "";
                });
                
                mobileMenuOverlay.addEventListener("click", function() {
                    mobileMenu.classList.remove("active");
                    mobileMenuOverlay.classList.remove("active");
                    document.body.style.overflow = "";
                });
            }
            
            // Touch-friendly image gallery
            const productImages = document.querySelectorAll(".product-images-mobile");
            productImages.forEach(function(gallery) {
                let startX = 0;
                let scrollLeft = 0;
                
                gallery.addEventListener("touchstart", function(e) {
                    startX = e.touches[0].pageX - gallery.offsetLeft;
                    scrollLeft = gallery.scrollLeft;
                });
                
                gallery.addEventListener("touchmove", function(e) {
                    e.preventDefault();
                    const x = e.touches[0].pageX - gallery.offsetLeft;
                    const walk = (x - startX) * 2;
                    gallery.scrollLeft = scrollLeft - walk;
                });
            });
            
            // Lazy loading for mobile
            if ("IntersectionObserver" in window) {
                const imageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove("lazy");
                            imageObserver.unobserve(img);
                        }
                    });
                });
                
                document.querySelectorAll("img[data-src]").forEach(function(img) {
                    imageObserver.observe(img);
                });
            }
            
            // Mobile form enhancements
            const inputs = document.querySelectorAll("input, select, textarea");
            inputs.forEach(function(input) {
                // Auto-focus prevention on mobile
                if (window.innerWidth <= 768) {
                    input.removeAttribute("autofocus");
                }
                
                // Input type optimization
                if (input.type === "email") {
                    input.setAttribute("inputmode", "email");
                }
                if (input.type === "tel") {
                    input.setAttribute("inputmode", "tel");
                }
                if (input.type === "number") {
                    input.setAttribute("inputmode", "numeric");
                }
            });
            
            // Prevent zoom on input focus (iOS)
            if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
                const viewport = document.querySelector("meta[name=viewport]");
                if (viewport) {
                    inputs.forEach(function(input) {
                        input.addEventListener("focus", function() {
                            viewport.setAttribute("content", "width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no");
                        });
                        
                        input.addEventListener("blur", function() {
                            viewport.setAttribute("content", "width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes");
                        });
                    });
                }
            }
            
            // Mobile-specific analytics
            if (window.innerWidth <= 768) {
                // Track mobile-specific events
                document.addEventListener("touchstart", function() {
                    // Track touch interactions
                });
                
                // Track orientation changes
                window.addEventListener("orientationchange", function() {
                    setTimeout(function() {
                        // Adjust layout after orientation change
                        window.scrollTo(0, 0);
                    }, 100);
                });
            }
            
            // Progressive Web App features
            if ("serviceWorker" in navigator) {
                navigator.serviceWorker.register("/sw.js").catch(function(error) {
                    console.log("Service Worker registration failed:", error);
                });
            }
            
            // Add to home screen prompt
            let deferredPrompt;
            window.addEventListener("beforeinstallprompt", function(e) {
                e.preventDefault();
                deferredPrompt = e;
                
                // Show install button
                const installBtn = document.querySelector(".install-app-btn");
                if (installBtn) {
                    installBtn.style.display = "block";
                    installBtn.addEventListener("click", function() {
                        deferredPrompt.prompt();
                        deferredPrompt.userChoice.then(function(choiceResult) {
                            if (choiceResult.outcome === "accepted") {
                                console.log("User accepted the install prompt");
                            }
                            deferredPrompt = null;
                        });
                    });
                }
            });
        });
        ';
    }
    
    public static function generateMobileNavigation() {
        return '
        <div class="mobile-only">
            <button class="mobile-menu-btn" aria-label="Open menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <nav class="mobile-menu">
                <div class="mobile-menu-header">
                    <h3>Menu</h3>
                    <button class="mobile-menu-close" aria-label="Close menu">&times;</button>
                </div>
                
                <ul class="mobile-menu-items">
                    <li><a href="/">Home</a></li>
                    <li><a href="/pages/shop.php">Shop</a></li>
                    <li><a href="/pages/categories.php">Categories</a></li>
                    <li><a href="/pages/about.php">About</a></li>
                    <li><a href="/pages/contact.php">Contact</a></li>
                    <li><a href="/pages/account.php">My Account</a></li>
                    <li><a href="/pages/cart.php">Cart</a></li>
                </ul>
                
                <div class="mobile-menu-footer">
                    <div class="mobile-search">
                        <input type="search" placeholder="Search manga...">
                        <button class="mobile-search-btn" aria-label="Search">🔍</button>
                    </div>
                </div>
            </nav>
            
            <div class="mobile-menu-overlay"></div>
        </div>
        ';
    }
    
    public static function generateMobileProductCard($product) {
        $image_url = $product['image_url'] ?? '/assets/img/placeholder.jpg';
        $alt_text = KeywordOptimizer::generateAltText($product['title']);
        
        return '
        <div class="product-card mobile-optimized touch-feedback">
            <div class="product-image-container">
                <img src="' . htmlspecialchars($image_url) . '" 
                     alt="' . htmlspecialchars($alt_text) . '" 
                     class="product-image lazy"
                     loading="lazy">
                <div class="product-quick-actions mobile-only">
                    <button class="quick-view-btn" data-product-id="' . $product['id'] . '">👁️</button>
                    <button class="add-to-wishlist-btn" data-product-id="' . $product['id'] . '">❤️</button>
                </div>
            </div>
            
            <div class="product-info">
                <h3 class="product-title">' . htmlspecialchars($product['title']) . '</h3>
                <p class="product-price">$' . number_format($product['price'], 2) . '</p>
                <p class="product-condition">' . ucfirst($product['condition']) . '</p>
                
                <div class="mobile-product-actions">
                    <button class="btn btn-primary add-to-cart-mobile" 
                            data-product-id="' . $product['id'] . '">
                        Add to Cart
                    </button>
                </div>
            </div>
        </div>
        ';
    }
    
    public static function generateMobileCheckout() {
        return '
        <div class="mobile-checkout">
            <div class="checkout-progress mobile-only">
                <div class="progress-step active">Cart</div>
                <div class="progress-step">Shipping</div>
                <div class="progress-step">Payment</div>
                <div class="progress-step">Confirm</div>
            </div>
            
            <div class="mobile-sticky-cart">
                <div class="cart-summary">
                    <span class="cart-total">Total: $<span id="mobile-cart-total">0.00</span></span>
                    <button class="btn btn-primary checkout-btn">Proceed to Checkout</button>
                </div>
            </div>
        </div>
        ';
    }
    
    public static function isMobile() {
        return isset($_SESSION['is_mobile']) ? $_SESSION['is_mobile'] : false;
    }
    
    public static function getDeviceType() {
        $width = $_SESSION['screen_width'] ?? 0;
        
        if ($width <= self::$mobile_breakpoints['mobile']) {
            return 'mobile';
        } elseif ($width <= self::$mobile_breakpoints['tablet']) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }
    
    public static function optimizeImagesForMobile($images) {
        $optimized = [];
        
        foreach ($images as $image) {
            $optimized[] = [
                'src' => $image['src'],
                'srcset' => self::generateSrcSet($image['src']),
                'sizes' => '(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 33vw',
                'alt' => $image['alt'] ?? '',
                'loading' => 'lazy'
            ];
        }
        
        return $optimized;
    }
    
    private static function generateSrcSet($image_path) {
        $base_path = pathinfo($image_path, PATHINFO_DIRNAME);
        $filename = pathinfo($image_path, PATHINFO_FILENAME);
        $extension = pathinfo($image_path, PATHINFO_EXTENSION);
        
        return $image_path . ' 1x, ' . 
               $base_path . '/' . $filename . '@2x.' . $extension . ' 2x';
    }
    
    public static function generatePWAManifest() {
        $manifest = [
            'name' => 'Bort\'s Books - Premium Manga Collection',
            'short_name' => 'Bort\'s Books',
            'description' => 'Discover authentic manga books and collectibles',
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#232946',
            'theme_color' => '#232946',
            'orientation' => 'portrait-primary',
            'icons' => [
                [
                    'src' => '/assets/img/icon-192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png'
                ],
                [
                    'src' => '/assets/img/icon-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png'
                ]
            ]
        ];
        
        header('Content-Type: application/json');
        echo json_encode($manifest);
    }
}

// Initialize mobile optimization
MobileOptimizer::init();
?> 