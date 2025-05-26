<?php
session_start();
require_once '../includes/cart-display.php';

$pageTitle = "Returns & Refunds";
$currentPage = "returns";
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
        .returns-container {
            max-width: 1000px;
            margin: 3rem auto;
            padding: 0 1rem;
        }
        .returns-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .returns-card h2 {
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
        .returns-content {
            padding: 2rem;
        }
        .policy-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        .policy-item {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .policy-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.1);
            border-color: #eebbc3;
        }
        .policy-item .icon {
            font-size: 2.5rem;
            color: #eebbc3;
            margin-bottom: 1rem;
        }
        .policy-item h3 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #232946;
        }
        .policy-item .highlight {
            font-size: 1.3rem;
            font-weight: 800;
            color: #e63946;
            margin-bottom: 0.5rem;
        }
        .policy-item p {
            color: #555;
            line-height: 1.5;
            font-size: 0.95rem;
        }
        .return-process {
            background: linear-gradient(135deg, #232946 0%, #395aa0 100%);
            color: #fff;
            border-radius: 16px;
            padding: 2rem;
            margin: 2rem 0;
        }
        .return-process h3 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            text-align: center;
        }
        .process-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .process-step {
            text-align: center;
        }
        .step-number {
            background: linear-gradient(45deg, #eebbc3, #f7c7d0);
            color: #232946;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.2rem;
            margin: 0 auto 1rem auto;
        }
        .process-step h4 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        .process-step p {
            font-size: 0.9rem;
            opacity: 0.9;
            line-height: 1.4;
        }
        .alert-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-left: 4px solid #ffc107;
            border-radius: 8px;
            padding: 1rem 1.5rem;
            margin: 1.5rem 0;
        }
        .alert-box.success {
            background: #d4edda;
            border-color: #c3e6cb;
            border-left-color: #28a745;
        }
        .alert-box.danger {
            background: #f8d7da;
            border-color: #f5c6cb;
            border-left-color: #dc3545;
        }
        .alert-box h4 {
            margin: 0 0 0.5rem 0;
            color: #856404;
        }
        .alert-box.success h4 {
            color: #155724;
        }
        .alert-box.danger h4 {
            color: #721c24;
        }
        .alert-box p {
            margin: 0;
            color: #856404;
        }
        .alert-box.success p {
            color: #155724;
        }
        .alert-box.danger p {
            color: #721c24;
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
            margin: 0 0.5rem;
        }
        .contact-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
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
        @media (max-width: 768px) {
            .policy-grid {
                grid-template-columns: 1fr;
            }
            .process-steps {
                grid-template-columns: 1fr;
            }
            .contact-cta {
                padding: 2rem 1rem;
            }
            .contact-btn {
                margin: 0.5rem 0;
                width: 100%;
                max-width: 250px;
                justify-content: center;
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
        <h1>Returns & Refunds</h1>
        <p>Easy returns and hassle-free refunds for your peace of mind</p>
    </section>

    <main class="returns-container">
        <div class="returns-card">
            <h2><i class="fas fa-undo"></i> Return Policy Overview</h2>
            <div class="returns-content">
                <div class="alert-box success">
                    <h4><i class="fas fa-check-circle"></i> Customer Satisfaction Guarantee</h4>
                    <p>We stand behind every item we sell. If you're not completely satisfied with your purchase, we'll make it right with our hassle-free return policy.</p>
                </div>

                <div class="policy-grid">
                    <div class="policy-item">
                        <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                        <h3>Return Window</h3>
                        <div class="highlight">30 Days</div>
                        <p>You have 30 days from the delivery date to initiate a return. Items must be in original condition.</p>
                    </div>
                    
                    <div class="policy-item">
                        <div class="icon"><i class="fas fa-box"></i></div>
                        <h3>Original Condition</h3>
                        <div class="highlight">Required</div>
                        <p>Items must be returned in the same condition you received them, with all original packaging and materials.</p>
                    </div>
                    
                    <div class="policy-item">
                        <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                        <h3>Full Refunds</h3>
                        <div class="highlight">Available</div>
                        <p>Receive a full refund to your original payment method once we process your return (3-5 business days).</p>
                    </div>
                    
                    <div class="policy-item">
                        <div class="icon"><i class="fas fa-exchange-alt"></i></div>
                        <h3>Exchanges</h3>
                        <div class="highlight">Supported</div>
                        <p>Need a different item? We offer exchanges subject to availability. Price differences may apply.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="return-process">
            <h3><i class="fas fa-route"></i> How to Return an Item</h3>
            <p style="text-align: center; opacity: 0.9; margin-bottom: 2rem;">Follow these simple steps to return your item</p>
            
            <div class="process-steps">
                <div class="process-step">
                    <div class="step-number">1</div>
                    <h4>Contact Us</h4>
                    <p>Email us at info@bortsbooks.com with your order number and reason for return</p>
                </div>
                
                <div class="process-step">
                    <div class="step-number">2</div>
                    <h4>Get Authorization</h4>
                    <p>We'll provide return instructions and a Return Authorization (RA) number within 24 hours</p>
                </div>
                
                <div class="process-step">
                    <div class="step-number">3</div>
                    <h4>Package Item</h4>
                    <p>Securely package the item with all original materials and include the RA number</p>
                </div>
                
                <div class="process-step">
                    <div class="step-number">4</div>
                    <h4>Ship It Back</h4>
                    <p>Send the package to our returns center using the address we provide</p>
                </div>
                
                <div class="process-step">
                    <div class="step-number">5</div>
                    <h4>Get Refunded</h4>
                    <p>Receive your refund within 3-5 business days after we process your return</p>
                </div>
            </div>
        </div>

        <div class="returns-card">
            <h2><i class="fas fa-shield-alt"></i> Return Conditions</h2>
            <div class="returns-content">
                <div class="alert-box">
                    <h4><i class="fas fa-info-circle"></i> Important Information</h4>
                    <p>Please review these conditions before initiating a return to ensure your return is processed quickly.</p>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <i class="fas fa-check-circle icon"></i>
                        <div>
                            <h4>Acceptable Returns</h4>
                            <p>Items in original condition, unopened collectibles, undamaged books with no markings or writing, items with original packaging intact.</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-times-circle icon" style="color: #e63946;"></i>
                        <div>
                            <h4>Non-Returnable Items</h4>
                            <p>Items damaged by customer, books with writing or highlighting, items without original packaging, special orders or custom items.</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-truck icon"></i>
                        <div>
                            <h4>Return Shipping</h4>
                            <p>Customer pays return shipping unless item was damaged in transit or we sent wrong item. We'll provide a prepaid label if it's our error.</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-clock icon"></i>
                        <div>
                            <h4>Processing Time</h4>
                            <p>Returns are processed within 2-3 business days of receipt. Refunds appear on your account within 3-5 business days after processing.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="returns-card">
            <h2><i class="fas fa-exclamation-triangle"></i> Damaged or Wrong Items</h2>
            <div class="returns-content">
                <div class="alert-box danger">
                    <h4><i class="fas fa-box-open"></i> Received Damaged or Wrong Item?</h4>
                    <p>If your item arrived damaged or if we sent the wrong item, we'll make it right immediately with expedited processing and prepaid return shipping.</p>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <i class="fas fa-camera icon"></i>
                        <div>
                            <h4>Document the Issue</h4>
                            <p>Take photos of the damaged item and packaging. This helps us improve our packaging and process your return faster.</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-bolt icon"></i>
                        <div>
                            <h4>Priority Processing</h4>
                            <p>Damaged or wrong items receive priority processing. We'll send a replacement immediately or process your refund within 24 hours.</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-shipping-fast icon"></i>
                        <div>
                            <h4>Free Return Shipping</h4>
                            <p>We'll email you a prepaid return label. No cost to you when the issue is on our end.</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-gift icon"></i>
                        <div>
                            <h4>Compensation</h4>
                            <p>For significant inconvenience, we may offer store credit or expedited shipping on your next order as an apology.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="returns-card">
            <h2><i class="fas fa-question-circle"></i> Return FAQ</h2>
            <div class="returns-content">
                <div class="info-grid">
                    <div class="info-item">
                        <i class="fas fa-credit-card icon"></i>
                        <div>
                            <h4>How will I be refunded?</h4>
                            <p>Refunds are issued to your original payment method. Credit card refunds may take 3-5 business days to appear on your statement.</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-envelope icon"></i>
                        <div>
                            <h4>Will I get confirmation?</h4>
                            <p>Yes! You'll receive email confirmations when we receive your return and when your refund is processed.</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-exchange-alt icon"></i>
                        <div>
                            <h4>Can I exchange for a different item?</h4>
                            <p>Yes, exchanges are available subject to stock availability. Contact us to arrange an exchange instead of a return.</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-gift icon"></i>
                        <div>
                            <h4>What about gift purchases?</h4>
                            <p>Gift recipients can return items for store credit or exchange. Original purchaser can request refund to original payment method.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="contact-cta">
            <h2>Need Help with a Return?</h2>
            <p>Our customer service team is here to make your return process as smooth as possible. Contact us and we'll take care of everything!</p>
            <div>
                <a href="contact.php" class="contact-btn">
                    <i class="fas fa-envelope"></i>
                    Email Support
                </a>
                <a href="tel:+11234567890" class="contact-btn">
                    <i class="fas fa-phone"></i>
                    Call Us
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
                    <li><a href="returns.php" class="active">Returns</a></li>
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
</body>
</html> 