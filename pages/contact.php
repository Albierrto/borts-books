<?php
require_once '../includes/security.php';
require_once '../includes/secure-email.php';
require_once '../includes/database-encryption.php';
require_once '../includes/cart-display.php';
require_once '../includes/db.php';

// Start secure session
secure_session_start();

// Set security headers
set_security_headers();

$pageTitle = "Contact Us";
$currentPage = "contact";

// Initialize security components
$encryption = new DatabaseEncryption();
$emailSystem = new SecureEmailSystem();

// Ensure customer_requests table exists with required columns
try {
    $res = $db->query("SHOW TABLES LIKE 'customer_requests'");
    if ($res->rowCount() == 0) {
        $db->exec("CREATE TABLE customer_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARBINARY(512) NOT NULL,
            email VARBINARY(512) NOT NULL,
            email_hash CHAR(64) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message VARBINARY(4096) NOT NULL,
            inquiry_type VARCHAR(100) DEFAULT 'General',
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email_hash (email_hash),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    } else {
        // Ensure all required columns exist (legacy deployments)
        $cols = $db->query("SHOW COLUMNS FROM customer_requests")->fetchAll(PDO::FETCH_COLUMN);
        $required = [
            'email_hash' => "CHAR(64)",
            'ip_address' => "VARCHAR(45)",
            'user_agent' => "TEXT",
            'created_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ];
        foreach ($required as $col => $definition) {
            if (!in_array($col, $cols)) {
                $db->exec("ALTER TABLE customer_requests ADD COLUMN $col $definition");
            }
        }
    }
} catch (PDOException $e) {
    error_log('customer_requests table check error: ' . $e->getMessage());
}

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid request. Please try again.';
        log_security_event('csrf_failure', ['page' => 'contact']);
    } else {
        // Check rate limiting
        if (!check_rate_limit('contact_form', 5, 3600)) {
            $error_message = 'Too many contact requests. Please try again later.';
            log_security_event('rate_limit_exceeded', ['page' => 'contact']);
        } else {
            // Sanitize and validate inputs
            $name = sanitize_input($_POST['name'] ?? '');
            $email = validate_email($_POST['email'] ?? '');
            $subject = sanitize_input($_POST['subject'] ?? '');
            $message = sanitize_input($_POST['message'] ?? '');
            $inquiry_type = sanitize_input($_POST['inquiry_type'] ?? '');
            
            // Enhanced validation
            $validation_errors = [];
            
            if (empty($name) || strlen($name) < 2) {
                $validation_errors[] = 'Please enter a valid name (at least 2 characters).';
            } elseif (strlen($name) > 100) {
                $validation_errors[] = 'Name is too long (maximum 100 characters).';
            }
            
            if (!$email) {
                $validation_errors[] = 'Please enter a valid email address.';
            }
            
            if (empty($subject) || strlen($subject) < 5) {
                $validation_errors[] = 'Please enter a subject (at least 5 characters).';
            } elseif (strlen($subject) > 200) {
                $validation_errors[] = 'Subject is too long (maximum 200 characters).';
            }
            
            if (empty($message) || strlen($message) < 10) {
                $validation_errors[] = 'Please enter a message (at least 10 characters).';
            } elseif (strlen($message) > 2000) {
                $validation_errors[] = 'Message is too long (maximum 2000 characters).';
            }
            
            // Check for spam patterns
            $spam_patterns = [
                '/\b(viagra|cialis|casino|lottery|winner|congratulations)\b/i',
                '/\b(click here|free money|guaranteed|urgent|limited time)\b/i',
                '/http[s]?:\/\/[^\s]{1,50}/i' // URLs (suspicious in contact forms)
            ];
            
            foreach ($spam_patterns as $pattern) {
                if (preg_match($pattern, $message) || preg_match($pattern, $subject)) {
                    $validation_errors[] = 'Your message appears to contain spam content.';
                    log_security_event('spam_detected', [
                        'pattern' => $pattern,
                        'email' => $email,
                        'subject' => substr($subject, 0, 50)
                    ], 'medium');
                    break;
                }
            }
            
            if (!empty($validation_errors)) {
                $error_message = implode(' ', $validation_errors);
            } else {
                try {
                    // Encrypt sensitive data before storing
                    $encrypted_data = $encryption->encryptFields([
                        'name' => $name,
                        'email' => $email,
                        'message' => $message
                    ], ['name', 'email', 'message']);
                    
                    // Create searchable hash for email (for duplicate detection)
                    $email_hash = $encryption->createSearchHash($email, 'email');
                    
                    // Check for recent duplicate submissions
                    $stmt = $db->prepare("
                        SELECT COUNT(*) FROM customer_requests 
                        WHERE email_hash = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                    ");
                    $stmt->execute([$email_hash]);
                    $recent_count = $stmt->fetchColumn();
                    
                    if ($recent_count >= 3) {
                        $error_message = 'Too many recent submissions from this email. Please wait before submitting again.';
                        log_security_event('duplicate_contact_submission', ['email_hash' => $email_hash]);
                    } else {
                        // Save encrypted customer request to database
                        $stmt = $db->prepare("
                            INSERT INTO customer_requests (
                                name, email, email_hash, subject, message, 
                                inquiry_type, ip_address, user_agent, created_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                        ");
                        
                        $result = $stmt->execute([
                            $encrypted_data['name'],
                            $encrypted_data['email'],
                            $email_hash,
                            $subject,
                            $encrypted_data['message'],
                            $inquiry_type,
                            get_client_ip(),
                            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                        ]);
                        
                        if ($result) {
                            // Send confirmation email to customer
                            try {
                                $emailSystem->sendEmail(
                                    $email,
                                    'Thank you for contacting Bort\'s Books',
                                    "
                                    <h2>Thank you for contacting us!</h2>
                                    <p>Dear $name,</p>
                                    <p>We have received your message regarding: <strong>$subject</strong></p>
                                    <p>Our team will review your inquiry and respond within 24 hours during business days.</p>
                                    <p>If you have an urgent matter, please call us directly.</p>
                                    <p>Best regards,<br>The Bort's Books Team</p>
                                    ",
                                    ['template' => 'transactional']
                                );
                            } catch (Exception $e) {
                                // Email failed but form submission succeeded
                                error_log("Contact confirmation email failed: " . $e->getMessage());
                            }
                            
                            // Send notification to admin (without sensitive data in logs)
                            try {
                                $adminEmail = 'admin@bortsbooks.com'; // Should come from config
                                $emailSystem->sendEmail(
                                    $adminEmail,
                                    'New Contact Form Submission - ' . $subject,
                                    "
                                    <h2>New Contact Form Submission</h2>
                                    <p><strong>From:</strong> $name &lt;$email&gt;</p>
                                    <p><strong>Subject:</strong> $subject</p>
                                    <p><strong>Inquiry Type:</strong> $inquiry_type</p>
                                    <p><strong>Message:</strong></p>
                                    <div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>
                                        " . nl2br(htmlspecialchars($message)) . "
                                    </div>
                                    <p><strong>Submitted:</strong> " . date('Y-m-d H:i:s') . "</p>
                                    <p><strong>IP Address:</strong> " . get_client_ip() . "</p>
                                    ",
                                    ['template' => 'notification']
                                );
                            } catch (Exception $e) {
                                error_log("Admin notification email failed: " . $e->getMessage());
                            }
                            
                            $success_message = 'Thank you for contacting us! We\'ll get back to you within 24 hours.';
                            
                            // Log successful contact form submission
                            log_security_event('contact_form_success', [
                                'email_hash' => $email_hash,
                                'inquiry_type' => $inquiry_type,
                                'subject_length' => strlen($subject),
                                'message_length' => strlen($message)
                            ]);
                            
                            // Clear form data on success
                            $_POST = [];
                        } else {
                            $error_message = 'There was an error submitting your request. Please try again.';
                            log_security_event('contact_form_db_error', ['email_hash' => $email_hash]);
                        }
                    }
                    
                } catch (Exception $e) {
                    error_log("Contact form error: " . $e->getMessage());
                    $error_message = 'There was an error submitting your request. Please try again.';
                    log_security_event('contact_form_error', [
                        'error' => $e->getMessage(),
                        'email_hash' => $email_hash ?? 'unknown'
                    ]);
                }
            }
        }
    }
}

// Generate CSRF token
$csrf_token = generate_csrf_token();

// Initialize cart for header
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
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='grad' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%23667eea;stop-opacity:1' /%3E%3Cstop offset='100%25' style='stop-color:%23764ba2;stop-opacity:1' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='15' fill='url(%23grad)'/%3E%3Cpath d='M25 20h50c2.5 0 4.5 2 4.5 4.5v51c0 2.5-2 4.5-4.5 4.5H25c-2.5 0-4.5-2-4.5-4.5v-51c0-2.5 2-4.5 4.5-4.5z' fill='white'/%3E%3Cpath d='M30 30h40v5H30z' fill='%23667eea'/%3E%3Cpath d='M30 40h35v3H30z' fill='%23999'/%3E%3Cpath d='M30 47h30v3H30z' fill='%23999'/%3E%3Cpath d='M30 54h25v3H30z' fill='%23999'/%3E%3Cpath d='M30 61h20v3H30z' fill='%23999'/%3E%3C/svg%3E">
    <title><?php echo $pageTitle; ?> - Bort's Books</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Permanent+Marker&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/mobile-nav.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f7f7fa; font-family: 'Inter', sans-serif; }
        
        .security-notice {
            background: #e8f5e8;
            border: 1px solid #4caf50;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #2e7d32;
        }
        
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
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
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
            <nav class="main-nav">
                <a href="../index.php" <?php echo $currentPage === 'home' ? 'class="active"' : ''; ?>>Home</a>
                <a href="shop.php" <?php echo $currentPage === 'shop' ? 'class="active"' : ''; ?>>Shop</a>
                <a href="track-order.php" <?php echo $currentPage === 'track' ? 'class="active"' : ''; ?>>Track Order</a>
                <a href="sell.php" <?php echo $currentPage === 'sell' ? 'class="active"' : ''; ?>>Sell Manga</a>
                <a href="about.php" <?php echo $currentPage === 'about' ? 'class="active"' : ''; ?>>About</a>
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
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
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
                <h3><i class="fas fa-envelope"></i> Get in Touch</h3>
                
                <div class="contact-method">
                    <div class="icon"><i class="fas fa-envelope"></i></div>
                    <div class="details">
                        <h4>Email Support</h4>
                        <p><a href="mailto:bort@bortsbooks.com">bort@bortsbooks.com</a></p>
                        <p>We respond within 24 hours</p>
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
                    <li><a href="track-order.php">Track Order</a></li>
                    <li><a href="sell.php">Sell Manga</a></li>
                    <li><a href="about.php">About</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Help</h3>
                <ul>
                    <li><a href="faq.php">FAQ</a></li>
                    <li><a href="returns.php">Returns</a></li>
                    <li><a href="contact.php" class="active">Contact Us</a></li>
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

    <script src="../assets/js/mobile-nav.js"></script>
</body>
</html> 