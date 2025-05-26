<?php
$pageTitle = "How It Works - Selling Your Manga";
$currentPage = "sell";
require_once '../includes/db.php';

// Initialize cart for header
session_start();
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
    <title>How It Works - Selling Your Manga - Bort's Books</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Mobile-First Responsive Design */
        .how-it-works-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header and Navigation Styles */
        header {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            max-width: 1200px;
            margin: 0 auto;
            padding-left: 20px;
            padding-right: 20px;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logo span {
            color: #667eea;
        }

        .logo:hover {
            transform: translateY(-1px);
        }

        nav ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 2rem;
        }

        nav a {
            color: #333;
            text-decoration: none;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            position: relative;
        }

        nav a:hover,
        nav a.active {
            color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }

        .search-cart {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .cart-link {
            position: relative;
            color: #333;
            font-size: 1.2rem;
            text-decoration: none;
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .cart-link:hover {
            color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Page Header */
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
        }

        .page-header h1 {
            margin: 0 0 15px 0;
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 800;
        }

        .page-header p {
            margin: 0;
            font-size: clamp(1.1rem, 3vw, 1.3rem);
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Steps Container */
        .steps-container {
            display: grid;
            gap: 30px;
            margin-bottom: 40px;
        }

        .step {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-left: 5px solid #667eea;
            transition: all 0.3s ease;
        }

        .step:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }

        .step-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .step-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 800;
            flex-shrink: 0;
        }

        .step h3 {
            margin: 0;
            color: #333;
            font-size: clamp(1.3rem, 3vw, 1.6rem);
            font-weight: 700;
        }

        .step p {
            margin: 0 0 15px 0;
            color: #666;
            line-height: 1.6;
            font-size: clamp(1rem, 2.5vw, 1.1rem);
        }

        .step ul {
            margin: 15px 0;
            padding-left: 20px;
            color: #666;
        }

        .step ul li {
            margin-bottom: 8px;
            line-height: 1.5;
        }

        /* Payment Methods Section */
        .payment-methods {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
        }

        .payment-methods h3 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: clamp(1.4rem, 3.5vw, 1.8rem);
        }

        .payment-icons {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            margin: 20px 0;
        }

        .payment-method {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            min-width: 120px;
        }

        .payment-method:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .payment-method i {
            font-size: 2.5rem;
            color: #667eea;
        }

        .payment-method span {
            font-weight: 600;
            color: #333;
            font-size: 1.1rem;
        }

        /* Quote Guarantee */
        .quote-guarantee {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            margin: 30px 0;
        }

        .quote-guarantee h3 {
            margin: 0 0 15px 0;
            font-size: clamp(1.4rem, 3.5vw, 1.8rem);
            font-weight: 700;
        }

        .quote-guarantee p {
            margin: 0;
            font-size: clamp(1rem, 2.5vw, 1.2rem);
            opacity: 0.95;
        }

        .quote-guarantee i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }

        /* CTA Section */
        .cta-section {
            text-align: center;
            padding: 40px 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin: 40px 0;
        }

        .cta-section h3 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: clamp(1.5rem, 4vw, 2rem);
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* FAQ Section */
        .faq-section {
            margin: 40px 0;
        }

        .faq-section h3 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-size: clamp(1.5rem, 4vw, 2rem);
        }

        .faq-item {
            background: white;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .faq-question {
            padding: 20px;
            background: #f8f9fa;
            border: none;
            width: 100%;
            text-align: left;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .faq-question:hover {
            background: #e9ecef;
        }

        .faq-answer {
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .faq-answer.active {
            padding: 20px;
            max-height: 200px;
        }

        /* Footer Styles */
        footer {
            background: #2c3e50;
            color: #fff;
            margin-top: 3rem;
        }

        footer .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 20px 1rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .footer-section h3 {
            margin-bottom: 1rem;
            color: #fff;
            font-size: 1.2rem;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-section ul li {
            margin-bottom: 0.5rem;
        }

        .footer-section ul li a {
            color: #bdc3c7;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section ul li a:hover {
            color: #fff;
        }

        .footer-bottom {
            background: #1a252f;
            padding: 1rem 0;
            text-align: center;
            border-top: 1px solid #34495e;
        }

        .footer-bottom .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-bottom p {
            margin: 0;
            color: #bdc3c7;
            font-size: 0.9rem;
        }

        /* Mobile Navigation */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 1rem;
            }

            nav ul {
                flex-direction: column;
                gap: 0.5rem;
                width: 100%;
                text-align: center;
            }

            .search-cart {
                width: 100%;
                justify-content: center;
            }

            footer .container {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .payment-icons {
                gap: 15px;
            }

            .payment-method {
                min-width: 100px;
                padding: 15px;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="/index.php" class="logo">Bort's <span>Books</span></a>
            
            <nav>
                <ul>
                    <li><a href="/index.php">Home</a></li>
                    <li><a href="/pages/shop.php">Shop</a></li>
                    <li><a href="/pages/about.php">About</a></li>
                    <li><a href="/pages/sell.php" class="active">Sell</a></li>
                    <li><a href="/pages/contact.php">Contact</a></li>
                </ul>
            </nav>
            
            <div class="search-cart">
                <a href="/pages/cart.php" class="cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-count"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </header>

    <main>
        <div class="how-it-works-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-handshake"></i> How Selling to Bort's Books Works</h1>
                <p>Turn your manga collection into cash with our simple, transparent selling process. We make it easy to get top dollar for your manga sets!</p>
            </div>

            <!-- Quote Guarantee -->
            <div class="quote-guarantee">
                <i class="fas fa-clock"></i>
                <h3>24-Hour Quote Guarantee</h3>
                <p>Submit your collection today and receive a detailed quote within 24 hours. We pride ourselves on fast, fair evaluations!</p>
            </div>

            <!-- Steps -->
            <div class="steps-container">
                <!-- Step 1 -->
                <div class="step">
                    <div class="step-header">
                        <div class="step-number">1</div>
                        <h3>Submit Your Collection</h3>
                    </div>
                    <p>Fill out our simple online form with details about your manga sets. Include:</p>
                    <ul>
                        <li><strong>Series titles</strong> - Tell us which manga series you have</li>
                        <li><strong>Volume ranges</strong> - Which volumes you own (e.g., 1-20, 5-15)</li>
                        <li><strong>Condition details</strong> - Honest assessment of wear and tear</li>
                        <li><strong>Clear photos</strong> - Multiple angles showing spines and condition</li>
                        <li><strong>Your asking price</strong> - Optional, but helps us understand your expectations</li>
                    </ul>
                    <p><strong>Pro tip:</strong> The more detailed and accurate your submission, the faster we can process your quote!</p>
                </div>

                <!-- Step 2 -->
                <div class="step">
                    <div class="step-header">
                        <div class="step-number">2</div>
                        <h3>Get Your Quote</h3>
                    </div>
                    <p>Within 24 hours, our manga experts will review your submission and send you a detailed quote including:</p>
                    <ul>
                        <li><strong>Individual set valuations</strong> - Breakdown by series</li>
                        <li><strong>Total offer amount</strong> - What we'll pay for everything</li>
                        <li><strong>Shipping instructions</strong> - How to safely send your collection</li>
                        <li><strong>Payment method options</strong> - Choose how you want to be paid</li>
                    </ul>
                    <p>No obligation! You can accept, negotiate, or decline our offer with no pressure.</p>
                </div>

                <!-- Step 3 -->
                <div class="step">
                    <div class="step-header">
                        <div class="step-number">3</div>
                        <h3>Ship Your Collection</h3>
                    </div>
                    <p>If you accept our offer, we'll provide detailed shipping instructions:</p>
                    <ul>
                        <li><strong>Secure packaging</strong> - We'll guide you on proper protection</li>
                        <li><strong>Shipping label</strong> - We can provide prepaid labels for large collections</li>
                        <li><strong>Tracking number</strong> - Always use tracked shipping for security</li>
                        <li><strong>Insurance</strong> - We recommend insuring valuable shipments</li>
                    </ul>
                    <p><strong>Important:</strong> Include your quote reference number in the package!</p>
                </div>

                <!-- Step 4 -->
                <div class="step">
                    <div class="step-header">
                        <div class="step-number">4</div>
                        <h3>Get Paid Fast</h3>
                    </div>
                    <p>Once we receive and verify your collection, payment is sent within 1-2 business days via your preferred method:</p>
                    
                    <div class="payment-methods">
                        <h3><i class="fas fa-credit-card"></i> Payment Options</h3>
                        <div class="payment-icons">
                            <div class="payment-method">
                                <i class="fab fa-paypal"></i>
                                <span>PayPal</span>
                            </div>
                            <div class="payment-method">
                                <i class="fas fa-mobile-alt"></i>
                                <span>Zelle</span>
                            </div>
                            <div class="payment-method">
                                <i class="fas fa-dollar-sign"></i>
                                <span>CashApp</span>
                            </div>
                        </div>
                        <p>All payments are sent in USD. Choose the method that works best for you!</p>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="faq-section">
                <h3><i class="fas fa-question-circle"></i> Frequently Asked Questions</h3>
                
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFAQ(this)">
                        What types of manga do you buy?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>We buy all types of manga including popular series, rare editions, complete sets, and individual volumes. We're especially interested in complete or near-complete series, first editions, and hard-to-find titles.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFAQ(this)">
                        How do you determine the value of my collection?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>We consider current market values, condition, completeness of sets, rarity, and demand. Our experienced team uses multiple pricing sources to ensure fair, competitive offers.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFAQ(this)">
                        What if I don't like your offer?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>No problem! Our quotes are completely free with no obligation. You can decline, negotiate, or ask questions about our valuation. We want you to be completely satisfied.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFAQ(this)">
                        Do you cover shipping costs?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>For collections valued over $200, we provide prepaid shipping labels. For smaller collections, shipping costs are typically deducted from the final payment amount.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFAQ(this)">
                        How long does the entire process take?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>From submission to payment: Quote within 24 hours, shipping time varies by location (1-5 days), verification and payment within 1-2 business days of receipt. Total time is typically 3-8 days.</p>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="cta-section">
                <h3>Ready to Turn Your Manga Into Cash?</h3>
                <p>Join hundreds of satisfied sellers who have trusted Bort's Books with their collections. Get your free quote today!</p>
                
                <div class="cta-buttons">
                    <a href="/pages/sell.php" class="btn btn-primary">
                        <i class="fas fa-upload"></i>
                        Start Selling Now
                    </a>
                    <a href="/pages/contact.php" class="btn btn-secondary">
                        <i class="fas fa-comments"></i>
                        Have Questions?
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
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
                    <li><a href="/pages/how-it-works-sell.php">How Selling Works</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Connect</h3>
                <ul>
                    <li><a href="#"><i class="fab fa-facebook"></i> Facebook</a></li>
                    <li><a href="#"><i class="fab fa-twitter"></i> Twitter</a></li>
                    <li><a href="#"><i class="fab fa-instagram"></i> Instagram</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="container">
                <p>&copy; 2024 Bort's Books. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleFAQ(button) {
            const answer = button.nextElementSibling;
            const icon = button.querySelector('i');
            
            // Close all other FAQs
            document.querySelectorAll('.faq-answer').forEach(item => {
                if (item !== answer) {
                    item.classList.remove('active');
                    item.previousElementSibling.querySelector('i').style.transform = 'rotate(0deg)';
                }
            });
            
            // Toggle current FAQ
            answer.classList.toggle('active');
            
            if (answer.classList.contains('active')) {
                icon.style.transform = 'rotate(180deg)';
            } else {
                icon.style.transform = 'rotate(0deg)';
            }
        }

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html> 