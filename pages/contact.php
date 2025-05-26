<?php
session_start();
require_once '../includes/cart-display.php';

$pageTitle = "Contact Us";
$currentPage = "contact";

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $inquiry_type = $_POST['inquiry_type'] ?? '';
    
    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // In a real application, you would send an email here
        // For now, we'll just show a success message
        $success_message = 'Thank you for contacting us! We\'ll get back to you within 24 hours.';
        
        // Log the contact form submission (in real app, save to database or send email)
        error_log("Contact form submitted: Name: $name, Email: $email, Subject: $subject, Type: $inquiry_type");
    }
}
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
        .contact-container {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
        }
        .contact-form-section {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 2rem;
        }
        .contact-form-section h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #232946;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .contact-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #232946;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #eebbc3;
            box-shadow: 0 0 0 3px rgba(238, 187, 195, 0.1);
        }
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .submit-btn {
            background: linear-gradient(45deg, #eebbc3, #f7c7d0);
            color: #232946;
            border: none;
            border-radius: 50px;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .submit-btn:hover {
            background: linear-gradient(45deg, #232946, #395aa0);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        .contact-info-section {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        .contact-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        .contact-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        .contact-card h3 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #232946;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .contact-method {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 0.8rem;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .contact-method:hover {
            background: #f8f9fa;
        }
        .contact-method .icon {
            background: linear-gradient(45deg, #eebbc3, #f7c7d0);
            color: #232946;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        .contact-method .details h4 {
            margin: 0 0 0.2rem 0;
            font-weight: 600;
            color: #232946;
        }
        .contact-method .details p {
            margin: 0;
            color: #666;
            font-size: 0.95rem;
        }
        .contact-method .details a {
            color: #232946;
            text-decoration: none;
            font-weight: 600;
        }
        .contact-method .details a:hover {
            color: #e63946;
        }
        .hours-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }
        .hour-row {
            display: flex;
            justify-content: space-between;
            padding: 0.3rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .hour-row:last-child {
            border-bottom: none;
        }
        .hour-row .day {
            font-weight: 600;
            color: #232946;
        }
        .hour-row .time {
            color: #666;
        }
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .faq-quick {
            background: linear-gradient(135deg, #232946 0%, #395aa0 100%);
            color: #fff;
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            margin-top: 2rem;
        }
        .faq-quick h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .faq-quick p {
            opacity: 0.9;
            margin-bottom: 1.5rem;
        }
        .faq-btn {
            background: linear-gradient(45deg, #eebbc3, #f7c7d0);
            color: #232946;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        .faq-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }
        @media (max-width: 768px) {
            .contact-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
            .hours-grid {
                grid-template-columns: 1fr;
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
        <h1>Contact Us</h1>
        <p>Get in touch with our friendly team - we're here to help with all your manga needs!</p>
    </section>

    <main class="contact-container">
        <div class="contact-form-section">
            <h2><i class="fas fa-envelope"></i> Send us a Message</h2>
            
            <?php if ($success_message): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form class="contact-form" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="inquiry_type">Inquiry Type</label>
                        <select id="inquiry_type" name="inquiry_type">
                            <option value="">Select a topic</option>
                            <option value="order" <?php echo ($_POST['inquiry_type'] ?? '') === 'order' ? 'selected' : ''; ?>>Order Support</option>
                            <option value="shipping" <?php echo ($_POST['inquiry_type'] ?? '') === 'shipping' ? 'selected' : ''; ?>>Shipping Question</option>
                            <option value="return" <?php echo ($_POST['inquiry_type'] ?? '') === 'return' ? 'selected' : ''; ?>>Returns & Refunds</option>
                            <option value="selling" <?php echo ($_POST['inquiry_type'] ?? '') === 'selling' ? 'selected' : ''; ?>>Selling Manga</option>
                            <option value="technical" <?php echo ($_POST['inquiry_type'] ?? '') === 'technical' ? 'selected' : ''; ?>>Website Issue</option>
                            <option value="other" <?php echo ($_POST['inquiry_type'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" id="subject" name="subject" required value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" required placeholder="Please provide as much detail as possible. For order-related inquiries, include your order number."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i>
                    Send Message
                </button>
            </form>
        </div>

        <div class="contact-info-section">
            <div class="contact-card">
                <h3><i class="fas fa-phone"></i> Get in Touch</h3>
                
                <div class="contact-method">
                    <div class="icon"><i class="fas fa-envelope"></i></div>
                    <div class="details">
                        <h4>Email Support</h4>
                        <p><a href="mailto:info@bortsbooks.com">info@bortsbooks.com</a></p>
                        <p>We respond within 24 hours</p>
                    </div>
                </div>
                
                <div class="contact-method">
                    <div class="icon"><i class="fas fa-phone"></i></div>
                    <div class="details">
                        <h4>Phone Support</h4>
                        <p><a href="tel:+11234567890">(123) 456-7890</a></p>
                        <p>Mon-Fri: 9 AM - 6 PM PST</p>
                    </div>
                </div>
                
                <div class="contact-method">
                    <div class="icon"><i class="fas fa-comments"></i></div>
                    <div class="details">
                        <h4>Live Chat</h4>
                        <p>Available on our website</p>
                        <p>Mon-Fri: 10 AM - 5 PM PST</p>
                    </div>
                </div>
            </div>

            <div class="contact-card">
                <h3><i class="fas fa-clock"></i> Business Hours</h3>
                <div class="hours-grid">
                    <div>
                        <div class="hour-row">
                            <span class="day">Monday</span>
                            <span class="time">9 AM - 6 PM</span>
                        </div>
                        <div class="hour-row">
                            <span class="day">Tuesday</span>
                            <span class="time">9 AM - 6 PM</span>
                        </div>
                        <div class="hour-row">
                            <span class="day">Wednesday</span>
                            <span class="time">9 AM - 6 PM</span>
                        </div>
                        <div class="hour-row">
                            <span class="day">Thursday</span>
                            <span class="time">9 AM - 6 PM</span>
                        </div>
                    </div>
                    <div>
                        <div class="hour-row">
                            <span class="day">Friday</span>
                            <span class="time">9 AM - 6 PM</span>
                        </div>
                        <div class="hour-row">
                            <span class="day">Saturday</span>
                            <span class="time">10 AM - 4 PM</span>
                        </div>
                        <div class="hour-row">
                            <span class="day">Sunday</span>
                            <span class="time">Closed</span>
                        </div>
                        <div class="hour-row">
                            <span class="day">Holidays</span>
                            <span class="time">Closed</span>
                        </div>
                    </div>
                </div>
                <p style="margin-top: 1rem; color: #666; font-size: 0.9rem;">
                    <i class="fas fa-info-circle"></i>
                    All times Pacific Standard Time (PST)
                </p>
            </div>

            <div class="contact-card">
                <h3><i class="fas fa-map-marker-alt"></i> Visit Our Store</h3>
                
                <div class="contact-method">
                    <div class="icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="details">
                        <h4>Physical Location</h4>
                        <p>123 Manga Street<br>
                        Anime City, AC 12345<br>
                        United States</p>
                    </div>
                </div>
                
                <div class="contact-method">
                    <div class="icon"><i class="fas fa-car"></i></div>
                    <div class="details">
                        <h4>Parking</h4>
                        <p>Free parking available</p>
                        <p>Street parking and lot</p>
                    </div>
                </div>
                
                <div class="contact-method">
                    <div class="icon"><i class="fas fa-subway"></i></div>
                    <div class="details">
                        <h4>Public Transit</h4>
                        <p>Bus routes 12, 34, 56</p>
                        <p>Manga Station (2 blocks)</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="faq-quick">
        <h3><i class="fas fa-question-circle"></i> Need Quick Answers?</h3>
        <p>Check out our FAQ section for instant answers to common questions about orders, shipping, and returns.</p>
        <a href="faq.php" class="faq-btn">
            <i class="fas fa-search"></i>
            Browse FAQ
        </a>
    </div>

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
                    <li><a href="shipping.php">Shipping</a></li>
                    <li><a href="returns.php">Returns</a></li>
                    <li><a href="contact.php" class="active">Contact Us</a></li>
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