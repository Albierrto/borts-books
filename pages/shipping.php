<?php
session_start();
require_once '../includes/cart-display.php';

$pageTitle = "Shipping Information";
$currentPage = "shipping";
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
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f7f7fa; font-family: 'Inter', sans-serif; }
        .page-header {
            background: linear-gradient(135deg, #232946 0%, #395aa0 100%);
            color: #fff;
            padding: 3rem 1rem 2rem 1rem;
            text-align: center;
        }
        .page-header h1 {
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 800;
            margin-bottom: 1rem;
        }
        .page-header p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }
        .shipping-container {
            max-width: 1000px;
            margin: 3rem auto;
            padding: 0 1rem;
        }
        .shipping-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .shipping-card h2 {
            background: linear-gradient(45deg, #eebbc3, #f7c7d0);
            color: #232946;
            margin: 0;
            padding: 1.5rem 2rem;
            font-size: 1.4rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .shipping-content {
            padding: 2rem;
        }
        .shipping-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        .shipping-option {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .shipping-option:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.1);
            border-color: #eebbc3;
        }
        .shipping-option .icon {
            font-size: 2.5rem;
            color: #eebbc3;
            margin-bottom: 1rem;
        }
        .shipping-option h3 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #232946;
        }
        .shipping-option .price {
            font-size: 1.5rem;
            font-weight: 800;
            color: #e63946;
            margin-bottom: 0.5rem;
        }
        .shipping-option .delivery-time {
            color: #666;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .shipping-option .description {
            color: #555;
            line-height: 1.5;
            font-size: 0.95rem;
        }
        .tracking-card {
            background: linear-gradient(135deg, #232946 0%, #395aa0 100%);
            color: #fff;
            padding: 2rem;
            border-radius: 16px;
            text-align: center;
            margin: 2rem 0;
        }
        .tracking-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .tracking-card p {
            opacity: 0.9;
            margin-bottom: 1.5rem;
        }
        .tracking-form {
            display: flex;
            gap: 1rem;
            max-width: 400px;
            margin: 0 auto;
            flex-wrap: wrap;
            justify-content: center;
        }
        .tracking-form input {
            flex: 1;
            padding: 0.8rem 1.2rem;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            min-width: 200px;
        }
        .tracking-form button {
            background: linear-gradient(45deg, #eebbc3, #f7c7d0);
            color: #232946;
            border: none;
            border-radius: 25px;
            padding: 0.8rem 1.5rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .tracking-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }
        .info-item .icon {
            color: #eebbc3;
            font-size: 1.5rem;
            margin-top: 0.2rem;
        }
        .info-item h4 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #232946;
        }
        .info-item p {
            color: #555;
            line-height: 1.5;
            margin: 0;
        }
        .processing-timeline {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 2rem 0;
            position: relative;
        }
        .processing-timeline::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #eebbc3;
            z-index: 1;
        }
        .timeline-step {
            background: #fff;
            border: 3px solid #eebbc3;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 2;
            color: #eebbc3;
            font-size: 1.5rem;
        }
        .timeline-step.active {
            background: #eebbc3;
            color: #fff;
        }
        .timeline-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
        }
        .timeline-label {
            text-align: center;
            font-size: 0.9rem;
            font-weight: 600;
            color: #666;
            flex: 1;
        }
        .alert-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-left: 4px solid #17a2b8;
            border-radius: 8px;
            padding: 1rem 1.5rem;
            margin: 1.5rem 0;
        }
        .alert-box h4 {
            margin: 0 0 0.5rem 0;
            color: #0c5460;
        }
        .alert-box p {
            margin: 0;
            color: #0c5460;
        }
        @media (max-width: 768px) {
            .shipping-options {
                grid-template-columns: 1fr;
            }
            .processing-timeline {
                flex-direction: column;
                gap: 1rem;
            }
            .processing-timeline::before {
                display: none;
            }
            .timeline-labels {
                flex-direction: column;
                gap: 1rem;
                margin-top: 0;
            }
            .tracking-form {
                flex-direction: column;
            }
            .tracking-form input {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="../index.php" class="logo">Bort's <span>Books</span></a>
            <nav>
                <ul>
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="shop.php">Shop</a></li>
                    <li><a href="sell.php">Sell Manga</a></li>
                    <li><a href="about.php">About</a></li>
                </ul>
            </nav>
            <div class="search-cart">
                <a href="../search.php" title="Search"><i class="fas fa-search"></i></a>
                <a href="../cart.php" title="Shopping Cart" class="cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </a>
            </div>
        </div>
    </header>

    <section class="page-header">
        <h1>Shipping Information</h1>
        <p>Fast, secure delivery options for your manga collection</p>
    </section>

    <main class="shipping-container">
        <div class="shipping-card">
            <h2><i class="fas fa-truck"></i> Shipping Options</h2>
            <div class="shipping-content">
                <p>We offer multiple shipping options to get your manga to you safely and on time. All shipments include tracking and are fully insured.</p>
                
                <div class="shipping-options">
                    <div class="shipping-option">
                        <div class="icon"><i class="fas fa-book"></i></div>
                        <h3>Media Mail</h3>
                        <div class="price">$3.00</div>
                        <div class="delivery-time">2-8 Business Days</div>
                        <div class="description">
                            Cost-effective option for books and educational materials. Perfect for manga collections. USPS Media Mail service.
                        </div>
                    </div>
                    
                    <div class="shipping-option">
                        <div class="icon"><i class="fas fa-shipping-fast"></i></div>
                        <h3>Ground Advantage</h3>
                        <div class="price">$4.50</div>
                        <div class="delivery-time">3-5 Business Days</div>
                        <div class="description">
                            Our standard shipping option. Reliable delivery with tracking included. USPS Ground Advantage service.
                        </div>
                    </div>
                    
                    <div class="shipping-option">
                        <div class="icon"><i class="fas fa-rocket"></i></div>
                        <h3>Priority Mail</h3>
                        <div class="price">$7.50</div>
                        <div class="delivery-time">1-3 Business Days</div>
                        <div class="description">
                            Fastest option available. Express delivery with priority handling and enhanced tracking.
                        </div>
                    </div>
                </div>

                <div class="alert-box">
                    <h4><i class="fas fa-info-circle"></i> Shipping Calculator</h4>
                    <p>Actual shipping costs are calculated at checkout based on your specific order weight, dimensions, and delivery address. The prices shown above are our standard rates.</p>
                </div>
            </div>
        </div>

        <div class="shipping-card">
            <h2><i class="fas fa-clock"></i> Processing Timeline</h2>
            <div class="shipping-content">
                <p>Here's what happens after you place your order:</p>
                
                <div class="processing-timeline">
                    <div class="timeline-step active"><i class="fas fa-check"></i></div>
                    <div class="timeline-step active"><i class="fas fa-box"></i></div>
                    <div class="timeline-step active"><i class="fas fa-truck"></i></div>
                    <div class="timeline-step"><i class="fas fa-home"></i></div>
                </div>
                
                <div class="timeline-labels">
                    <div class="timeline-label">Order Received</div>
                    <div class="timeline-label">Processing (1-2 days)</div>
                    <div class="timeline-label">Shipped</div>
                    <div class="timeline-label">Delivered</div>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <i class="fas fa-check-circle icon"></i>
                        <div>
                            <h4>Order Confirmation</h4>
                            <p>You'll receive an email confirmation immediately after placing your order with all the details.</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-cogs icon"></i>
                        <div>
                            <h4>Processing Time</h4>
                            <p>Orders are processed within 1-2 business days. During peak times, processing may take up to 3 days.</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-shipping-fast icon"></i>
                        <div>
                            <h4>Shipment Notification</h4>
                            <p>Once shipped, you'll receive an email with tracking information and delivery estimates.</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-shield-alt icon"></i>
                        <div>
                            <h4>Safe Packaging</h4>
                            <p>All items are carefully packaged with protective materials to ensure they arrive in perfect condition.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tracking-card">
            <h3><i class="fas fa-search-location"></i> Track Your Order</h3>
            <p>Enter your tracking number below to get real-time updates on your shipment</p>
            <div class="tracking-form">
                <input type="text" id="trackingNumber" placeholder="Enter tracking number">
                <button onclick="trackPackage()">
                    <i class="fas fa-search"></i>
                    Track
                </button>
            </div>
        </div>

        <div class="shipping-card">
            <h2><i class="fas fa-globe-americas"></i> Shipping Coverage</h2>
            <div class="shipping-content">
                <div class="info-grid">
                    <div class="info-item">
                        <i class="fas fa-map-marked-alt icon"></i>
                        <div>
                            <h4>Domestic Shipping</h4>
                            <p>We ship to all 50 United States, including Alaska and Hawaii. We also ship to US territories and military bases (APO/FPO addresses).</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-clock icon"></i>
                        <div>
                            <h4>Business Days</h4>
                            <p>Shipping times are calculated in business days (Monday-Friday, excluding federal holidays). Weekend deliveries may be available for Priority Mail.</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-exclamation-triangle icon"></i>
                        <div>
                            <h4>Weather Delays</h4>
                            <p>Severe weather conditions may cause shipping delays. We'll notify you of any significant delays and provide updated delivery estimates.</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-envelope icon"></i>
                        <div>
                            <h4>PO Box Delivery</h4>
                            <p>We can ship to PO Boxes using USPS services. Media Mail and Ground Advantage are available for PO Box addresses.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="shipping-card">
            <h2><i class="fas fa-question-circle"></i> Shipping FAQ</h2>
            <div class="shipping-content">
                <div class="info-grid">
                    <div class="info-item">
                        <i class="fas fa-dollar-sign icon"></i>
                        <div>
                            <h4>When am I charged for shipping?</h4>
                            <p>Shipping charges are calculated and charged at the time of purchase, along with your order total.</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-undo icon"></i>
                        <div>
                            <h4>Can I change my shipping address?</h4>
                            <p>Address changes can only be made within 2 hours of order placement. Contact us immediately if you need to make changes.</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-box-open icon"></i>
                        <div>
                            <h4>What if my package is damaged?</h4>
                            <p>All shipments are insured. If your package arrives damaged, contact us within 48 hours with photos for a replacement or refund.</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-calendar-times icon"></i>
                        <div>
                            <h4>What if nobody is home for delivery?</h4>
                            <p>USPS will attempt delivery and leave a notice. You can arrange redelivery or pick up at your local post office.</p>
                        </div>
                    </div>
                </div>
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
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="shop.php">Shop</a></li>
                    <li><a href="sell.php">Sell Manga</a></li>
                    <li><a href="about.php">About</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Help</h3>
                <ul>
                    <li><a href="faq.php">FAQ</a></li>
                    <li><a href="shipping.php" class="active">Shipping</a></li>
                    <li><a href="returns.php">Returns</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
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
        function trackPackage() {
            const trackingNumber = document.getElementById('trackingNumber').value.trim();
            
            if (!trackingNumber) {
                alert('Please enter a tracking number');
                return;
            }
            
            // Validate tracking number format (basic validation)
            if (trackingNumber.length < 10) {
                alert('Please enter a valid tracking number');
                return;
            }
            
            // Redirect to USPS tracking
            const uspsUrl = `https://tools.usps.com/go/TrackConfirmAction?tLabels=${trackingNumber}`;
            window.open(uspsUrl, '_blank');
        }

        // Allow Enter key to trigger tracking
        document.getElementById('trackingNumber').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                trackPackage();
            }
        });
    </script>
</body>
</html> 