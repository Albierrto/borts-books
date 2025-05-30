<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/tracking-pixels.php';

// Handle form submission
$message = '';
$messageType = '';
$form_submitted = false;

if ($_POST && isset($_POST['submit_collection'])) {
    try {
        // Sanitize input
        $name = htmlspecialchars(trim($_POST['name'] ?? ''));
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
        $location = htmlspecialchars(trim($_POST['location'] ?? ''));
        $collection_type = htmlspecialchars(trim($_POST['collection_type'] ?? ''));
        $estimated_items = (int)($_POST['estimated_items'] ?? 0);
        $condition = htmlspecialchars(trim($_POST['condition'] ?? ''));
        $description = htmlspecialchars(trim($_POST['description'] ?? ''));
        $preferred_contact = htmlspecialchars(trim($_POST['preferred_contact'] ?? ''));
        $timeline = htmlspecialchars(trim($_POST['timeline'] ?? ''));
        
        // Validate required fields
        if (empty($name) || empty($email) || empty($phone)) {
            throw new Exception('Please fill in all required fields.');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }
        
        // Save to database
        $stmt = $db->prepare("
            INSERT INTO collection_submissions (
                name, email, phone, location, collection_type, 
                estimated_items, condition_description, description, 
                preferred_contact, timeline, submitted_at, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')
        ");
        
        $stmt->execute([
            $name, $email, $phone, $location, $collection_type,
            $estimated_items, $condition, $description, 
            $preferred_contact, $timeline
        ]);
        
        // Send notification email to admin
        $subject = "New Collection Submission - $name";
        $admin_message = "
            <h2>New Collection Submission</h2>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Phone:</strong> $phone</p>
            <p><strong>Location:</strong> $location</p>
            <p><strong>Collection Type:</strong> $collection_type</p>
            <p><strong>Estimated Items:</strong> $estimated_items</p>
            <p><strong>Condition:</strong> $condition</p>
            <p><strong>Description:</strong> $description</p>
            <p><strong>Preferred Contact:</strong> $preferred_contact</p>
            <p><strong>Timeline:</strong> $timeline</p>
            <p><strong>Submitted:</strong> " . date('F j, Y g:i A') . "</p>
        ";
        
        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: Bort's Books <noreply@bortsbooks.com>\r\n";
        mail('admin@bortsbooks.com', $subject, $admin_message, $headers);
        
        // Send confirmation email to customer
        $customer_subject = "We Received Your Collection Submission!";
        $customer_message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                    .content { background: #f9f9f9; padding: 20px; }
                    .footer { background: #333; color: white; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>📚 Collection Submission Received!</h1>
                        <p>Thank you for considering Bort's Books, $name!</p>
                    </div>
                    
                    <div class='content'>
                        <h2>What Happens Next?</h2>
                        <p>✅ <strong>Received</strong> - We've got your collection details</p>
                        <p>📞 <strong>Review</strong> - Our team will review your submission within 24 hours</p>
                        <p>💰 <strong>Quote</strong> - We'll contact you with a fair offer</p>
                        <p>🚚 <strong>Pickup/Shipping</strong> - We arrange convenient collection</p>
                        <p>💸 <strong>Payment</strong> - Fast payment via your preferred method</p>
                        
                        <h3>Your Submission Details:</h3>
                        <p><strong>Collection Type:</strong> $collection_type</p>
                        <p><strong>Estimated Items:</strong> $estimated_items</p>
                        <p><strong>Preferred Contact:</strong> $preferred_contact</p>
                        
                        <h3>Questions?</h3>
                        <p>📧 Email: collections@bortsbooks.com</p>
                        <p>📱 Text/Call: (555) 123-4567</p>
                        <p>⏰ Response Time: Within 24 hours</p>
                    </div>
                    
                    <div class='footer'>
                        <p><strong>Bort's Books - Collection Buyers</strong></p>
                        <p>🌟 Fair Prices | 📦 Free Pickup | 💰 Fast Payment</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        mail($email, $customer_subject, $customer_message, $headers);
        
        $message = "Thank you! We've received your collection submission and will contact you within 24 hours with a quote.";
        $messageType = "success";
        $form_submitted = true;
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = "error";
    }
}

// Create table if it doesn't exist
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS collection_submissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            location VARCHAR(100),
            collection_type VARCHAR(50),
            estimated_items INT,
            condition_description TEXT,
            description TEXT,
            preferred_contact VARCHAR(20),
            timeline VARCHAR(50),
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('pending', 'reviewed', 'quoted', 'accepted', 'completed') DEFAULT 'pending',
            admin_notes TEXT,
            quote_amount DECIMAL(10,2),
            INDEX idx_email (email),
            INDEX idx_status (status),
            INDEX idx_submitted (submitted_at)
        )
    ");
} catch (Exception $e) {
    error_log("Collection submissions table creation failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell Your Manga Collection - Get Cash Fast | Bort's Books</title>
    <meta name="description" content="Sell your manga collection for top dollar! Free quotes, fair prices, fast payment. We buy manga collections nationwide. Get your quote today!">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <?php 
    // Output tracking pixels
    outputTrackingCodes([
        'google_analytics_id' => 'G-XXXXXXXXXX', // Replace with your actual GA4 ID
        'facebook_pixel_id' => '1234567890123456', // Replace with your actual Facebook Pixel ID
        'google_ads_id' => 'AW-XXXXXXXXX', // Replace with your actual Google Ads ID
        'google_ads_conversion_label' => 'XXXXXXXXX' // Replace with your actual conversion label
    ]);
    ?>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 1rem;
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
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="books" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse"><rect width="20" height="20" fill="none"/><rect x="2" y="5" width="3" height="12" fill="rgba(255,255,255,0.1)"/><rect x="6" y="3" width="3" height="14" fill="rgba(255,255,255,0.1)"/><rect x="10" y="6" width="3" height="11" fill="rgba(255,255,255,0.1)"/><rect x="14" y="4" width="3" height="13" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23books)"/></svg>') repeat;
            opacity: 0.1;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero-subtitle {
            font-size: clamp(1.2rem, 3vw, 1.8rem);
            margin-bottom: 2rem;
            opacity: 0.95;
        }

        .hero-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .hero-feature {
            background: rgba(255,255,255,0.1);
            padding: 1.5rem;
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        .hero-feature i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #ffd700;
        }

        /* Trust Signals */
        .trust-bar {
            background: #f8f9fa;
            padding: 1rem;
            text-align: center;
            border-bottom: 1px solid #e9ecef;
        }

        .trust-items {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
            max-width: 1200px;
            margin: 0 auto;
        }

        .trust-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #28a745;
            font-weight: 600;
        }

        /* Main Content */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 3rem;
            padding: 3rem 0;
        }

        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }

        /* Form Styling */
        .quote-form {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            position: sticky;
            top: 2rem;
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-header h2 {
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .required {
            color: #dc3545;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 480px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .submit-btn {
            width: 100%;
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        }

        /* Content Sections */
        .content-section {
            margin-bottom: 3rem;
        }

        .content-section h2 {
            color: #667eea;
            margin-bottom: 1rem;
            font-size: 2rem;
        }

        .process-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }

        .step {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            position: relative;
        }

        .step-number {
            background: #667eea;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 0 auto 1rem auto;
        }

        .testimonials {
            background: #f8f9fa;
            padding: 3rem 1rem;
            margin: 3rem 0;
        }

        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .testimonial {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .testimonial-text {
            font-style: italic;
            margin-bottom: 1rem;
            color: #666;
        }

        .testimonial-author {
            font-weight: 600;
            color: #333;
        }

        .stars {
            color: #ffc107;
            margin-bottom: 1rem;
        }

        /* Value Estimator */
        .value-estimator {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin: 2rem 0;
            text-align: center;
        }

        .estimate-result {
            font-size: 2rem;
            font-weight: 800;
            margin: 1rem 0;
        }

        /* Message Styling */
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* FAQ Section */
        .faq-item {
            background: white;
            margin-bottom: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .faq-question {
            padding: 1.5rem;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .faq-answer {
            padding: 0 1.5rem 1.5rem 1.5rem;
            display: none;
            color: #666;
        }

        .faq-answer.active {
            display: block;
        }

        /* Mobile Optimizations */
        @media (max-width: 768px) {
            .hero {
                padding: 2rem 1rem;
            }
            
            .hero-features {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .trust-items {
                gap: 1rem;
            }
            
            .quote-form {
                position: static;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>💰 Sell Your Manga Collection</h1>
            <p class="hero-subtitle">Get Top Dollar for Your Books - Free Quote in 24 Hours!</p>
            
            <div class="hero-features">
                <div class="hero-feature">
                    <i class="fas fa-dollar-sign"></i>
                    <h3>Fair Prices</h3>
                    <p>We pay 40-60% of retail value</p>
                </div>
                <div class="hero-feature">
                    <i class="fas fa-shipping-fast"></i>
                    <h3>Free Pickup</h3>
                    <p>We arrange convenient collection</p>
                </div>
                <div class="hero-feature">
                    <i class="fas fa-clock"></i>
                    <h3>Fast Payment</h3>
                    <p>Get paid within 48 hours</p>
                </div>
                <div class="hero-feature">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Trusted Buyer</h3>
                    <p>5+ years, 1000+ collections</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust Bar -->
    <div class="trust-bar">
        <div class="trust-items">
            <div class="trust-item">
                <i class="fas fa-check-circle"></i>
                <span>BBB A+ Rating</span>
            </div>
            <div class="trust-item">
                <i class="fas fa-users"></i>
                <span>1000+ Happy Sellers</span>
            </div>
            <div class="trust-item">
                <i class="fas fa-lock"></i>
                <span>Secure & Insured</span>
            </div>
            <div class="trust-item">
                <i class="fas fa-phone"></i>
                <span>Real People, Real Service</span>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="main-content">
            <!-- Left Content -->
            <div class="content">
                <!-- Value Estimator -->
                <div class="value-estimator">
                    <h2>📊 Quick Value Estimator</h2>
                    <p>Get an instant estimate of your collection's value!</p>
                    <div class="estimate-result" id="estimateResult">$0 - $0</div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem;">Number of Volumes:</label>
                            <input type="number" id="volumeCount" min="1" max="10000" value="50" style="padding: 0.5rem; border-radius: 4px; border: none;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem;">Average Condition:</label>
                            <select id="conditionSelect" style="padding: 0.5rem; border-radius: 4px; border: none;">
                                <option value="excellent">Excellent (Like New)</option>
                                <option value="good" selected>Good (Minor Wear)</option>
                                <option value="fair">Fair (Noticeable Wear)</option>
                                <option value="poor">Poor (Heavy Wear)</option>
                            </select>
                        </div>
                    </div>
                    <button onclick="calculateEstimate()" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; margin-top: 1rem; cursor: pointer;">Calculate Value</button>
                </div>

                <!-- How It Works -->
                <div class="content-section">
                    <h2>🔄 How It Works</h2>
                    <div class="process-steps">
                        <div class="step">
                            <div class="step-number">1</div>
                            <h3>Submit Details</h3>
                            <p>Tell us about your collection using our simple form</p>
                        </div>
                        <div class="step">
                            <div class="step-number">2</div>
                            <h3>Get Quote</h3>
                            <p>We'll review and send you a fair offer within 24 hours</p>
                        </div>
                        <div class="step">
                            <div class="step-number">3</div>
                            <h3>Ship or Pickup</h3>
                            <p>We arrange free pickup or provide prepaid shipping</p>
                        </div>
                        <div class="step">
                            <div class="step-number">4</div>
                            <h3>Get Paid</h3>
                            <p>Receive payment via PayPal, Zelle, or check</p>
                        </div>
                    </div>
                </div>

                <!-- What We Buy -->
                <div class="content-section">
                    <h2>📚 What We Buy</h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; text-align: center;">
                            <i class="fas fa-book" style="font-size: 2rem; color: #667eea; margin-bottom: 1rem;"></i>
                            <h4>Manga Series</h4>
                            <p>Complete or partial sets</p>
                        </div>
                        <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; text-align: center;">
                            <i class="fas fa-star" style="font-size: 2rem; color: #667eea; margin-bottom: 1rem;"></i>
                            <h4>Rare Editions</h4>
                            <p>First prints, limited editions</p>
                        </div>
                        <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; text-align: center;">
                            <i class="fas fa-layer-group" style="font-size: 2rem; color: #667eea; margin-bottom: 1rem;"></i>
                            <h4>Box Sets</h4>
                            <p>Complete series collections</p>
                        </div>
                        <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; text-align: center;">
                            <i class="fas fa-globe" style="font-size: 2rem; color: #667eea; margin-bottom: 1rem;"></i>
                            <h4>Any Language</h4>
                            <p>English, Japanese, other</p>
                        </div>
                    </div>
                </div>

                <!-- FAQ -->
                <div class="content-section">
                    <h2>❓ Frequently Asked Questions</h2>
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            How do you determine the value of my collection?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            We consider current market prices, condition, rarity, and demand. Our experienced team evaluates each collection individually to provide fair, competitive offers.
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            Do you buy incomplete series?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            Yes! We buy both complete and partial collections. Even individual volumes of popular series have value.
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            How long does the process take?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            We provide quotes within 24 hours. Once accepted, we arrange pickup/shipping within 2-3 days and payment within 48 hours of receiving your collection.
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            What if I don't accept your offer?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            No problem! There's no obligation. If you decline our offer, we'll return your collection at no cost to you.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar - Quote Form -->
            <div class="quote-form">
                <div class="form-header">
                    <h2>💰 Get Your Free Quote</h2>
                    <p>Fill out the form below and we'll contact you within 24 hours!</p>
                </div>

                <?php if ($message): ?>
                    <div class="message <?php echo $messageType; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="collectionForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number <span class="required">*</span></label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="location">Location (City, State)</label>
                        <input type="text" id="location" name="location" placeholder="e.g., Los Angeles, CA">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="collection_type">Collection Type</label>
                            <select id="collection_type" name="collection_type">
                                <option value="">Select type...</option>
                                <option value="manga">Manga</option>
                                <option value="light_novels">Light Novels</option>
                                <option value="graphic_novels">Graphic Novels</option>
                                <option value="mixed">Mixed Collection</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="estimated_items">Estimated # of Items</label>
                            <input type="number" id="estimated_items" name="estimated_items" min="1" placeholder="e.g., 50">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="condition">Overall Condition</label>
                        <select id="condition" name="condition">
                            <option value="">Select condition...</option>
                            <option value="excellent">Excellent (Like New)</option>
                            <option value="good">Good (Minor Wear)</option>
                            <option value="fair">Fair (Noticeable Wear)</option>
                            <option value="poor">Poor (Heavy Wear)</option>
                            <option value="mixed">Mixed Conditions</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Collection Description</label>
                        <textarea id="description" name="description" rows="4" placeholder="Tell us about your collection... series names, special editions, etc."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="preferred_contact">Preferred Contact</label>
                            <select id="preferred_contact" name="preferred_contact">
                                <option value="email">Email</option>
                                <option value="phone">Phone Call</option>
                                <option value="text">Text Message</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="timeline">Timeline</label>
                            <select id="timeline" name="timeline">
                                <option value="asap">ASAP</option>
                                <option value="week">Within a week</option>
                                <option value="month">Within a month</option>
                                <option value="flexible">Flexible</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="submit_collection" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Get My Free Quote
                    </button>

                    <p style="font-size: 0.9rem; color: #666; text-align: center; margin-top: 1rem;">
                        📞 Prefer to call? <strong>(555) 123-4567</strong><br>
                        📧 Email: <strong>collections@bortsbooks.com</strong>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <!-- Testimonials -->
    <section class="testimonials">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 3rem; color: #667eea;">💬 What Our Sellers Say</h2>
            <div class="testimonial-grid">
                <div class="testimonial">
                    <div class="stars">⭐⭐⭐⭐⭐</div>
                    <div class="testimonial-text">"Sold my entire Naruto collection to Bort's Books. Fair price, easy process, and they picked it up from my house. Couldn't be happier!"</div>
                    <div class="testimonial-author">- Sarah M., Portland, OR</div>
                </div>
                <div class="testimonial">
                    <div class="stars">⭐⭐⭐⭐⭐</div>
                    <div class="testimonial-text">"I was moving and needed to sell 200+ manga volumes quickly. They gave me a great quote and handled everything professionally."</div>
                    <div class="testimonial-author">- Mike T., Austin, TX</div>
                </div>
                <div class="testimonial">
                    <div class="stars">⭐⭐⭐⭐⭐</div>
                    <div class="testimonial-text">"Best prices I found anywhere! They paid way more than other buyers and the whole process was super smooth."</div>
                    <div class="testimonial-author">- Jessica L., Miami, FL</div>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Value Estimator
        function calculateEstimate() {
            const volumes = parseInt(document.getElementById('volumeCount').value) || 0;
            const condition = document.getElementById('conditionSelect').value;
            
            let baseValue = 3; // Base value per volume
            let multiplier = 1;
            
            switch(condition) {
                case 'excellent': multiplier = 1.5; break;
                case 'good': multiplier = 1.2; break;
                case 'fair': multiplier = 0.8; break;
                case 'poor': multiplier = 0.5; break;
            }
            
            const lowEstimate = Math.round(volumes * baseValue * multiplier * 0.8);
            const highEstimate = Math.round(volumes * baseValue * multiplier * 1.2);
            
            document.getElementById('estimateResult').textContent = `$${lowEstimate} - $${highEstimate}`;
        }
        
        // Auto-calculate on input change
        document.getElementById('volumeCount').addEventListener('input', calculateEstimate);
        document.getElementById('conditionSelect').addEventListener('change', calculateEstimate);
        
        // Initial calculation
        calculateEstimate();
        
        // FAQ Toggle
        function toggleFAQ(element) {
            const answer = element.nextElementSibling;
            const icon = element.querySelector('i');
            
            if (answer.classList.contains('active')) {
                answer.classList.remove('active');
                icon.style.transform = 'rotate(0deg)';
            } else {
                // Close all other FAQs
                document.querySelectorAll('.faq-answer.active').forEach(item => {
                    item.classList.remove('active');
                });
                document.querySelectorAll('.faq-question i').forEach(icon => {
                    icon.style.transform = 'rotate(0deg)';
                });
                
                // Open this FAQ
                answer.classList.add('active');
                icon.style.transform = 'rotate(180deg)';
            }
        }
        
        // Form Enhancement
        document.getElementById('collectionForm').addEventListener('submit', function(e) {
            const submitBtn = document.querySelector('.submit-btn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            submitBtn.disabled = true;
        });
        
        // Auto-fill estimator values into form
        document.getElementById('volumeCount').addEventListener('change', function() {
            document.getElementById('estimated_items').value = this.value;
        });
        
        document.getElementById('conditionSelect').addEventListener('change', function() {
            const conditionMap = {
                'excellent': 'excellent',
                'good': 'good', 
                'fair': 'fair',
                'poor': 'poor'
            };
            document.getElementById('condition').value = conditionMap[this.value] || '';
        });
    </script>

    <!-- Tracking Pixels (Add your actual pixels here) -->
    <!-- Google Analytics -->
    <!-- Facebook Pixel -->
    <!-- Google Ads Conversion -->
</body>
</html> 