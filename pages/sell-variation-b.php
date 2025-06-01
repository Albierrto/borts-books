<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = "Sell Your Manga Collection - Fair Prices, Fast Service";
$currentPage = "sell";
require_once '../includes/db.php';

// Same form processing logic as original
$successMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim(strip_tags($_POST['full_name'] ?? ''));
    $email = trim(strip_tags($_POST['email'] ?? ''));
    $phone = trim(strip_tags($_POST['phone'] ?? ''));
    $zip_code = trim(strip_tags($_POST['zip_code'] ?? ''));
    $num_items = intval($_POST['num_items'] ?? 0);
    $overall_condition = trim(strip_tags($_POST['overall_condition'] ?? ''));
    $collection_description = trim(strip_tags($_POST['collection_description'] ?? ''));
    $preferred_payment = trim(strip_tags($_POST['preferred_payment'] ?? ''));
    
    $photo_paths = [];
    if (!empty($_FILES['collection_photos']['name'][0])) {
        $upload_dir = __DIR__ . '/../uploads/sell-photos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        foreach ($_FILES['collection_photos']['tmp_name'] as $idx => $tmp_name) {
            if ($_FILES['collection_photos']['error'][$idx] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['collection_photos']['name'][$idx], PATHINFO_EXTENSION);
                $filename = uniqid('sell_', true) . '.' . $ext;
                $dest = $upload_dir . $filename;
                if (move_uploaded_file($tmp_name, $dest)) {
                    $photo_paths[] = 'uploads/sell-photos/' . $filename;
                }
            }
        }
    }
    $photo_paths_json = json_encode($photo_paths);
    
    if (count($photo_paths) > 0) {
        $stmt = $db->prepare('INSERT INTO sell_submissions (full_name, email, phone, zip_code, num_items, overall_condition, collection_description, preferred_payment, photo_paths) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$full_name, $email, $phone, $zip_code, $num_items, $overall_condition, $collection_description, $preferred_payment, $photo_paths_json]);
        $successMsg = 'Thank you! Your submission has been received. We\'ll review your collection and send you a detailed quote within 24 hours.';
    } else {
        $successMsg = '<span style="color:#d32f2f;">Please upload photos of your collection so we can provide an accurate quote.</span>';
    }
}

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
    <title><?php echo $pageTitle; ?> - Bort's Books</title>
    <meta name="description" content="Sell your manga collection to passionate collectors. Professional evaluation, fair market prices, and transparent process. Join thousands of satisfied sellers.">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='grad' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%23667eea;stop-opacity:1' /%3E%3Cstop offset='100%25' style='stop-color:%23764ba2;stop-opacity:1' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='15' fill='url(%23grad)'/%3E%3Cpath d='M25 20h50c2.5 0 4.5 2 4.5 4.5v51c0 2.5-2 4.5-4.5 4.5H25c-2.5 0-4.5-2-4.5-4.5v-51c0-2.5 2-4.5 4.5-4.5z' fill='white'/%3E%3Cpath d='M30 30h40v5H30z' fill='%23667eea'/%3E%3Cpath d='M30 40h35v3H30z' fill='%23999'/%3E%3Cpath d='M30 47h30v3H30z' fill='%23999'/%3E%3Cpath d='M30 54h25v3H30z' fill='%23999'/%3E%3Cpath d='M30 61h20v3H30z' fill='%23999'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #10b981;
            --accent: #f59e0b;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --border: #e5e7eb;
            --success: #059669;
            --warning: #d97706;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background: var(--bg-light);
        }

        /* TRUST BANNER */
        .trust-banner {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 12px 0;
            text-align: center;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .trust-banner .highlight {
            font-weight: 700;
            color: #fbbf24;
        }

        /* HERO SECTION */
        .hero-section {
            background: linear-gradient(135deg, var(--bg-white) 0%, #f1f5f9 100%);
            padding: 80px 20px;
            border-bottom: 1px solid var(--border);
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .hero-content h1 {
            font-size: clamp(2.5rem, 5vw, 3.5rem);
            font-weight: 800;
            margin-bottom: 24px;
            color: var(--text-dark);
            line-height: 1.1;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--text-light);
            margin-bottom: 32px;
            font-weight: 400;
        }

        .hero-features {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: var(--bg-white);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid var(--border);
        }

        .feature-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--secondary), #34d399);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
        }

        .feature-text {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        .hero-cta {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 16px 32px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.25);
            transition: all 0.3s ease;
        }

        .hero-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(37, 99, 235, 0.35);
        }

        .hero-visual {
            background: var(--bg-white);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.08);
            border: 1px solid var(--border);
        }

        /* SOCIAL PROOF SECTION */
        .social-proof {
            background: var(--bg-white);
            padding: 60px 20px;
            border-bottom: 1px solid var(--border);
        }

        .social-proof-container {
            max-width: 1000px;
            margin: 0 auto;
            text-align: center;
        }

        .social-proof h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 48px;
            color: var(--text-dark);
        }

        .testimonials {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 32px;
            margin-bottom: 48px;
        }

        .testimonial {
            background: var(--bg-light);
            padding: 32px;
            border-radius: 16px;
            border: 1px solid var(--border);
            text-align: left;
            position: relative;
        }

        .testimonial::before {
            content: '"';
            position: absolute;
            top: -10px;
            left: 20px;
            font-size: 4rem;
            color: var(--primary);
            opacity: 0.3;
            font-family: serif;
        }

        .testimonial-text {
            font-size: 1.1rem;
            color: var(--text-dark);
            margin-bottom: 20px;
            font-style: italic;
            line-height: 1.7;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .author-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .author-info {
            text-align: left;
        }

        .author-name {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 2px;
        }

        .author-details {
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
            max-width: 600px;
            margin: 0 auto;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 0.95rem;
            color: var(--text-light);
            font-weight: 500;
        }

        /* PRICING TRANSPARENCY SECTION */
        .pricing-section {
            background: linear-gradient(135deg, #fef7ff 0%, #f3f4f6 100%);
            padding: 80px 20px;
        }

        .pricing-container {
            max-width: 1000px;
            margin: 0 auto;
            text-align: center;
        }

        .pricing-section h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text-dark);
        }

        .pricing-subtitle {
            font-size: 1.2rem;
            color: var(--text-light);
            margin-bottom: 48px;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 48px;
        }

        .pricing-card {
            background: var(--bg-white);
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            border: 1px solid var(--border);
            text-align: center;
            transition: all 0.3s ease;
        }

        .pricing-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.12);
        }

        .pricing-card h3 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--text-dark);
        }

        .price-range {
            font-size: 2rem;
            font-weight: 800;
            color: var(--secondary);
            margin-bottom: 12px;
        }

        .price-details {
            color: var(--text-light);
            font-size: 0.95rem;
            margin-bottom: 20px;
        }

        .condition-badge {
            display: inline-block;
            padding: 6px 12px;
            background: linear-gradient(135deg, var(--accent), #fbbf24);
            color: white;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .transparency-note {
            background: var(--bg-white);
            padding: 24px;
            border-radius: 12px;
            border-left: 4px solid var(--primary);
            text-align: left;
            max-width: 700px;
            margin: 0 auto;
        }

        .transparency-note h4 {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .transparency-note p {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        /* FORM SECTION */
        .form-section {
            background: var(--bg-white);
            padding: 80px 20px;
        }

        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .form-header {
            text-align: center;
            margin-bottom: 48px;
        }

        .form-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--text-dark);
        }

        .form-subtitle {
            font-size: 1.2rem;
            color: var(--text-light);
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-size: 1rem;
        }

        .form-control {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
            background: var(--bg-white);
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        .form-control::placeholder {
            color: var(--text-light);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* PHOTO UPLOAD */
        .upload-area {
            display: block;
            width: 100%;
            padding: 48px;
            border: 2px dashed var(--border);
            border-radius: 16px;
            text-align: center;
            cursor: pointer;
            background: var(--bg-light);
            transition: all 0.3s ease;
        }

        .upload-area:hover {
            border-color: var(--primary);
            background: #eff6ff;
        }

        .upload-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 16px;
        }

        .upload-text {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .upload-subtext {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        /* SUBMIT BUTTON */
        .submit-section {
            text-align: center;
            padding: 40px 0;
        }

        .submit-btn {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, var(--secondary), #10b981);
            color: white;
            padding: 18px 40px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.25);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(16, 185, 129, 0.35);
        }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* SUCCESS MESSAGE */
        .success-message {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
            padding: 24px;
            border-radius: 12px;
            text-align: center;
            margin: 24px auto;
            font-size: 1.1rem;
            font-weight: 500;
            max-width: 600px;
            box-shadow: 0 8px 25px rgba(5, 150, 105, 0.25);
            display: none;
        }

        .success-message.show {
            display: block;
            animation: success-slide 0.5s ease-out;
        }

        @keyframes success-slide {
            0% { transform: translateY(-20px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }

        /* MOBILE RESPONSIVE */
        @media (max-width: 768px) {
            .hero-container {
                grid-template-columns: 1fr;
                gap: 40px;
                text-align: center;
            }
            
            .hero-features {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .pricing-grid,
            .testimonials,
            .stats-row {
                grid-template-columns: 1fr;
            }
            
            .hero-section,
            .social-proof,
            .pricing-section,
            .form-section {
                padding: 60px 15px;
            }
        }

        /* Loading states */
        .loading {
            display: none;
        }

        .submit-btn.loading .loading {
            display: inline;
        }

        .submit-btn.loading .btn-text {
            display: none;
        }
    </style>
</head>
<body>
    <!-- TRUST BANNER -->
    <div class="trust-banner">
        🏆 <span class="highlight">Trusted by 10,000+ manga collectors</span> • Professional evaluation since 2019 • A+ BBB Rating
    </div>

    <!-- Success Message -->
    <?php if ($successMsg): ?>
    <div class="success-message show">
        <?php echo $successMsg; ?>
    </div>
    <?php endif; ?>

    <!-- HERO SECTION -->
    <section class="hero-section">
        <div class="hero-container">
            <div class="hero-content">
                <h1>Get Professional Value for Your Manga Collection</h1>
                <p class="hero-subtitle">
                    Fair market pricing, transparent process, and payment within 24 hours. 
                    Join thousands of satisfied collectors who trust us with their collections.
                </p>
                
                <div class="hero-features">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <div class="feature-text">Professional Appraisal</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <div class="feature-text">Fair Market Prices</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="feature-text">24-Hour Quotes</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="feature-text">Secure Process</div>
                    </div>
                </div>
                
                <a href="#quote-form" class="hero-cta">
                    <i class="fas fa-calculator"></i>
                    Get Your Free Quote
                </a>
            </div>
            
            <div class="hero-visual">
                <div style="text-align: center; color: #6b7280;">
                    <i class="fas fa-books" style="font-size: 4rem; margin-bottom: 24px; color: #2563eb;"></i>
                    <h3 style="margin-bottom: 16px; color: #1f2937;">Professional Collection Assessment</h3>
                    <p>Our certified appraisers have over 20 years of experience in manga valuation, ensuring you get the best possible price for your collection.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- SOCIAL PROOF SECTION -->
    <section class="social-proof">
        <div class="social-proof-container">
            <h2>What Collectors Say About Us</h2>
            
            <div class="testimonials">
                <div class="testimonial">
                    <p class="testimonial-text">
                        I was skeptical about selling online, but Bort's Books exceeded my expectations. 
                        They gave me a fair quote and the whole process was transparent and professional.
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">MK</div>
                        <div class="author-info">
                            <div class="author-name">Mike K.</div>
                            <div class="author-details">California • 847 volumes sold</div>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial">
                    <p class="testimonial-text">
                        The appraisal was detailed and accurate. They recognized the value of my rare editions 
                        and paid exactly what they quoted. Will definitely use them again.
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">SL</div>
                        <div class="author-info">
                            <div class="author-name">Sarah L.</div>
                            <div class="author-details">Texas • 412 volumes sold</div>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial">
                    <p class="testimonial-text">
                        Fast, honest, and professional. They handled my collection with care and 
                        provided documentation for everything. Highly recommended!
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">DW</div>
                        <div class="author-info">
                            <div class="author-name">David W.</div>
                            <div class="author-details">Florida • 623 volumes sold</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-number">10K+</div>
                    <div class="stat-label">Happy Sellers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">4.9★</div>
                    <div class="stat-label">Average Rating</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">5 years</div>
                    <div class="stat-label">In Business</div>
                </div>
            </div>
        </div>
    </section>

    <!-- PRICING TRANSPARENCY SECTION -->
    <section class="pricing-section">
        <div class="pricing-container">
            <h2>Transparent Pricing Guidelines</h2>
            <p class="pricing-subtitle">
                Our pricing is based on current market conditions, condition, and rarity. Here are recent examples:
            </p>
            
            <div class="pricing-grid">
                <div class="pricing-card">
                    <h3>Popular Series</h3>
                    <div class="price-range">$6-15</div>
                    <div class="price-details">per volume</div>
                    <div class="condition-badge">Very Good Condition</div>
                    <p style="margin-top: 16px; color: #6b7280; font-size: 0.9rem;">
                        Naruto, One Piece, Attack on Titan, etc.
                    </p>
                </div>
                
                <div class="pricing-card">
                    <h3>Limited Editions</h3>
                    <div class="price-range">$25-85</div>
                    <div class="price-details">per volume</div>
                    <div class="condition-badge">Near Mint</div>
                    <p style="margin-top: 16px; color: #6b7280; font-size: 0.9rem;">
                        First prints, special editions, box sets
                    </p>
                </div>
                
                <div class="pricing-card">
                    <h3>Rare Collections</h3>
                    <div class="price-range">$100+</div>
                    <div class="price-details">per volume</div>
                    <div class="condition-badge">Collector Grade</div>
                    <p style="margin-top: 16px; color: #6b7280; font-size: 0.9rem;">
                        Out-of-print, vintage, signed editions
                    </p>
                </div>
            </div>
            
            <div class="transparency-note">
                <h4><i class="fas fa-info-circle" style="color: #2563eb; margin-right: 8px;"></i>How We Determine Value</h4>
                <p>
                    Our certified appraisers use current market data from eBay, auction houses, and collector databases. 
                    We consider condition, rarity, demand, and authenticity. You'll receive a detailed breakdown with your quote.
                </p>
            </div>
        </div>
    </section>

    <!-- FORM SECTION -->
    <section class="form-section" id="quote-form">
        <div class="form-container">
            <div class="form-header">
                <h2 class="form-title">Get Your Professional Quote</h2>
                <p class="form-subtitle">
                    Fill out the form below and upload photos. We'll review your collection and 
                    send you a detailed, no-obligation quote within 24 hours.
                </p>
            </div>

            <form method="POST" enctype="multipart/form-data" id="quoteForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required placeholder="Your full name">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" required placeholder="your.email@example.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number (Optional)</label>
                        <input type="tel" id="phone" name="phone" class="form-control" placeholder="(555) 123-4567">
                    </div>
                    <div class="form-group">
                        <label for="zip_code">ZIP Code</label>
                        <input type="text" id="zip_code" name="zip_code" class="form-control" required placeholder="12345">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="num_items">Approximate Number of Volumes</label>
                        <select id="num_items" name="num_items" class="form-control" required>
                            <option value="">Select range</option>
                            <option value="1-25">1-25 volumes</option>
                            <option value="26-50">26-50 volumes</option>
                            <option value="51-100">51-100 volumes</option>
                            <option value="101-200">101-200 volumes</option>
                            <option value="200+">200+ volumes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="overall_condition">Overall Condition</label>
                        <select id="overall_condition" name="overall_condition" class="form-control" required>
                            <option value="">Select condition</option>
                            <option value="like_new">Like New</option>
                            <option value="very_good">Very Good</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="collection_description">Collection Details</label>
                    <textarea id="collection_description" name="collection_description" class="form-control" rows="4" required placeholder="Please list the titles, volume numbers, and any special editions. Example: Naruto volumes 1-72, One Piece volumes 1-95, Attack on Titan complete set, etc. The more specific you are, the more accurate our quote will be."></textarea>
                </div>

                <div class="form-group">
                    <label for="preferred_payment">Preferred Payment Method</label>
                    <select id="preferred_payment" name="preferred_payment" class="form-control" required>
                        <option value="">Select payment method</option>
                        <option value="paypal">PayPal</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="check">Check</option>
                        <option value="zelle">Zelle</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="collection_photos">Upload Photos of Your Collection</label>
                    <label for="collection_photos" class="upload-area">
                        <div class="upload-icon">
                            <i class="fas fa-camera"></i>
                        </div>
                        <div class="upload-text">Click to upload photos</div>
                        <div class="upload-subtext">
                            Clear photos help us provide more accurate quotes. 
                            Include spine shots and any damage or special editions.
                        </div>
                    </label>
                    <input type="file" id="collection_photos" name="collection_photos[]" multiple accept="image/*" style="display: none;" required>
                    <div id="photo-preview" style="margin-top: 16px;"></div>
                </div>

                <div class="submit-section">
                    <button type="submit" class="submit-btn" id="submitBtn">
                        <i class="fas fa-paper-plane"></i>
                        <span class="btn-text">Submit for Quote</span>
                        <span class="loading">
                            <i class="fas fa-spinner fa-spin"></i>
                            Processing...
                        </span>
                    </button>
                    <p style="margin-top: 16px; color: #6b7280; font-size: 0.9rem;">
                        Free quote • No obligation • Response within 24 hours
                    </p>
                </div>
            </form>
        </div>
    </section>

    <script>
        // Photo preview with better styling
        document.getElementById('collection_photos').addEventListener('change', function(e) {
            const preview = document.getElementById('photo-preview');
            preview.innerHTML = '';
            
            if (e.target.files.length > 0) {
                const container = document.createElement('div');
                container.style.cssText = 'display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 12px; margin-top: 16px;';
                
                for (let i = 0; i < Math.min(e.target.files.length, 6); i++) {
                    const file = e.target.files[i];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const imgContainer = document.createElement('div');
                        imgContainer.style.cssText = 'position: relative; border-radius: 8px; overflow: hidden; border: 2px solid #e5e7eb;';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.cssText = 'width: 100%; height: 100px; object-fit: cover;';
                        
                        imgContainer.appendChild(img);
                        container.appendChild(imgContainer);
                    };
                    
                    reader.readAsDataURL(file);
                }
                
                const status = document.createElement('p');
                status.style.cssText = 'color: #059669; font-weight: 600; margin-top: 12px; font-size: 0.95rem;';
                status.textContent = `✅ ${e.target.files.length} photo(s) uploaded successfully`;
                
                preview.appendChild(status);
                preview.appendChild(container);
            }
        });

        // Form submission
        document.getElementById('quoteForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        });

        // Smooth scroll
        document.querySelector('.hero-cta').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('quote-form').scrollIntoView({
                behavior: 'smooth'
            });
        });

        // Auto-hide success message
        setTimeout(function() {
            const successMsg = document.querySelector('.success-message.show');
            if (successMsg) {
                successMsg.style.opacity = '0';
                setTimeout(() => successMsg.remove(), 500);
            }
        }, 10000);
    </script>
</body>
</html> 