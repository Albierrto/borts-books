<?php
session_start();
require_once '../includes/cart-display.php';

$pageTitle = "Frequently Asked Questions";
$currentPage = "faq";
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
        .faq-container {
            max-width: 900px;
            margin: 3rem auto;
            padding: 0 1rem;
        }
        .faq-search {
            background: #fff;
            border-radius: 50px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 1rem 2rem;
            margin-bottom: 3rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .faq-search input {
            flex: 1;
            border: none;
            font-size: 1.1rem;
            outline: none;
        }
        .faq-search i {
            color: #888;
            font-size: 1.2rem;
        }
        .faq-category {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .faq-category h2 {
            background: linear-gradient(45deg, #eebbc3, #f7c7d0);
            color: #232946;
            margin: 0;
            padding: 1.5rem 2rem;
            font-size: 1.4rem;
            font-weight: 700;
        }
        .faq-item {
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }
        .faq-item:last-child {
            border-bottom: none;
        }
        .faq-question {
            padding: 1.5rem 2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            transition: all 0.2s;
        }
        .faq-question:hover {
            background: #f8f9fa;
        }
        .faq-question h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: #232946;
            flex: 1;
        }
        .faq-icon {
            color: #eebbc3;
            font-size: 1.2rem;
            transition: transform 0.3s;
        }
        .faq-answer {
            padding: 0 2rem 1.5rem 2rem;
            display: none;
            color: #555;
            line-height: 1.6;
        }
        .faq-answer.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        .faq-item.active .faq-icon {
            transform: rotate(45deg);
        }
        .contact-cta {
            background: linear-gradient(135deg, #232946 0%, #395aa0 100%);
            color: #fff;
            text-align: center;
            padding: 3rem 2rem;
            border-radius: 16px;
            margin-top: 3rem;
        }
        .contact-cta h2 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }
        .contact-cta p {
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        .contact-btn {
            background: linear-gradient(45deg, #eebbc3, #f7c7d0);
            color: #232946;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        .contact-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 768px) {
            .faq-search {
                padding: 0.8rem 1.5rem;
            }
            .faq-category h2 {
                padding: 1.2rem 1.5rem;
                font-size: 1.2rem;
            }
            .faq-question {
                padding: 1.2rem 1.5rem;
            }
            .faq-answer {
                padding: 0 1.5rem 1.2rem 1.5rem;
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
                    <li><a href="track-order.php">Track Order</a></li>
                    <li><a href="sell.php">Sell Manga</a></li>
                    <li><a href="about.php">About</a></li>
                </ul>
            </nav>
            <div class="search-cart">
                <a href="../cart.php" title="Shopping Cart" class="cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </a>
            </div>
        </div>
    </header>

    <section class="page-header">
        <h1>Frequently Asked Questions</h1>
        <p>Find answers to common questions about shopping, selling, and our services</p>
    </section>

    <main class="faq-container">
        <div class="faq-search">
            <i class="fas fa-search"></i>
            <input type="text" id="faqSearch" placeholder="Search for answers...">
        </div>

        <div class="faq-category">
            <h2><i class="fas fa-shopping-bag"></i> Shopping & Orders</h2>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <h3>How do I place an order?</h3>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p>Browse our collection, add items to your cart, and proceed to checkout. You'll need to provide shipping information and payment details. We accept all major credit cards through our secure Stripe payment system.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <h3>Can I modify or cancel my order?</h3>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p>Orders can be modified or cancelled within 2 hours of placement. After this time, the order enters our fulfillment process. Contact us immediately at info@bortsbooks.com if you need to make changes.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <h3>Do you offer international shipping?</h3>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p>Currently, we ship within the United States only. We're working on expanding to international shipping soon. Sign up for our newsletter to be notified when international shipping becomes available.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <h3>What payment methods do you accept?</h3>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p>We accept all major credit cards (Visa, MasterCard, American Express, Discover), debit cards, and digital wallets through our secure Stripe payment processor. We do not accept cash, checks, or cryptocurrency at this time.</p>
                </div>
            </div>
        </div>

        <div class="faq-category">
            <h2><i class="fas fa-shipping-fast"></i> Shipping & Delivery</h2>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <h3>How much does shipping cost?</h3>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p>Shipping costs are calculated by USPS based on the weight and dimensions of your order and your delivery location. The exact cost will be calculated and displayed at checkout before you complete your purchase.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <h3>How long does processing take?</h3>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p>Orders are typically processed within 1-2 business days. During peak times (holidays, sales), processing may take up to 3 business days. You'll receive a tracking number once your order ships.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <h3>Do you offer free shipping?</h3>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p>We occasionally offer free shipping promotions on orders over a certain amount. Sign up for our newsletter to be notified of these special offers and promotional events.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <h3>Can I track my order?</h3>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p>Yes! Once your order ships, you'll receive an email with tracking information. You can track your package directly through USPS or contact us for assistance.</p>
                </div>
            </div>
        </div>

        <div class="faq-category">
            <h2><i class="fas fa-dollar-sign"></i> Selling Manga</h2>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <h3>How do I sell my manga collection?</h3>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p>Visit our "Sell Manga" page and fill out the form with details about your collection. Include photos and descriptions of the condition. We'll review your submission and provide a quote within 24-48 hours.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <h3>What condition should my manga be in?</h3>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p>We accept manga in good to excellent condition. Books should have minimal wear, no missing pages, and be free from water damage or excessive markings. Slight shelf wear is acceptable.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <h3>How do you determine the price?</h3>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p>Prices are based on current market value, rarity, condition, and demand. We research recent sales data and market trends to offer fair, competitive prices for your collection.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <h3>How do I get paid?</h3>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p>We offer payment via PayPal, Venmo, or check. Payment is processed within 2-3 business days after we receive and verify your items. You'll receive confirmation once payment is sent.</p>
                </div>
            </div>
        </div>

        <div class="faq-category">
            <h2><i class="fas fa-undo"></i> Returns & Exchanges</h2>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <h3>What is your return policy?</h3>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p>We offer a 30-day return policy for items in their original condition. Items must be returned in the same condition they were received, with all original packaging.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <h3>Who pays for return shipping?</h3>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p>Return shipping is paid by the customer unless the item was damaged in transit or we sent the wrong item. In those cases, we'll provide a prepaid return label.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <h3>How do I initiate a return?</h3>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p>Contact us at info@bortsbooks.com with your order number and reason for return. We'll provide return instructions and, if applicable, a return authorization number.</p>
                </div>
            </div>
        </div>

        <div class="contact-cta">
            <h2>Still Have Questions?</h2>
            <p>Our friendly customer service team is here to help you with any questions not covered here.</p>
            <a href="contact.php" class="contact-btn">
                <i class="fas fa-envelope"></i>
                Contact Us
            </a>
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
                    <li><a href="track-order.php">Track Order</a></li>
                    <li><a href="sell.php">Sell Manga</a></li>
                    <li><a href="about.php">About</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Help</h3>
                <ul>
                    <li><a href="faq.php" class="active">FAQ</a></li>
                    <li><a href="returns.php">Returns</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Contact</h3>
                <ul>
                    <li><i class="fas fa-envelope"></i> info@bortsbooks.com</li>
                    <li><i class="fas fa-phone"></i> (123) 456-7890</li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom container">
            <p>&copy; <?php echo date('Y'); ?> Bort's Books. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function toggleFAQ(element) {
            const item = element.parentElement;
            const answer = item.querySelector('.faq-answer');
            const icon = element.querySelector('.faq-icon');
            
            // Close all other FAQ items
            document.querySelectorAll('.faq-item').forEach(faqItem => {
                if (faqItem !== item) {
                    faqItem.classList.remove('active');
                    faqItem.querySelector('.faq-answer').classList.remove('show');
                }
            });
            
            // Toggle current item
            item.classList.toggle('active');
            answer.classList.toggle('show');
        }

        // FAQ search functionality
        document.getElementById('faqSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const faqItems = document.querySelectorAll('.faq-item');
            
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question h3').textContent.toLowerCase();
                const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
                
                if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html> 