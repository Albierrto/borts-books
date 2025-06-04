<?php
/**
 * eBay Reviews Import Script
 * Use this to manually import your eBay reviews
 */

require_once '../includes/security.php';
require_once '../includes/admin-auth.php';
require_once '../includes/password-security.php';

// Start secure session
secure_session_start();

// Set security headers
set_security_headers();

// Check honeypot and security monitoring
check_honeypot_access();

$error_message = '';
$success_message = '';

// Check if admin is already logged in through main system
if (!is_admin_logged_in()) {
    // If not logged in, show login form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Access Required - Bort's Books</title>
        <link rel="stylesheet" href="../assets/css/styles.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            body {
                font-family: 'Inter', sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0;
                padding: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .access-container {
                background: white;
                border-radius: 12px;
                padding: 3rem;
                box-shadow: 0 8px 30px rgba(0,0,0,0.2);
                width: 100%;
                max-width: 500px;
                text-align: center;
            }
            
            .access-header {
                margin-bottom: 2rem;
            }
            
            .access-header h1 {
                color: #333;
                margin: 0 0 0.5rem 0;
                font-size: 2rem;
            }
            
            .access-header p {
                color: #666;
                margin: 0;
                font-size: 1.1rem;
            }
            
            .security-notice {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 8px;
                padding: 1.5rem;
                margin: 2rem 0;
                color: #856404;
            }
            
            .btn-login {
                background: #667eea;
                color: white;
                border: none;
                padding: 1rem 2rem;
                border-radius: 8px;
                font-size: 1.1rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            .btn-login:hover {
                background: #5a6fd8;
                transform: translateY(-1px);
            }
        </style>
    </head>
    <body>
        <div class="access-container">
            <div class="access-header">
                <h1><i class="fas fa-shield-alt"></i> Admin Access Required</h1>
                <p>eBay Reviews Import Tool</p>
            </div>
            
            <div class="security-notice">
                <i class="fas fa-info-circle"></i>
                <strong>Security Notice:</strong> This tool requires admin authentication through the main admin system for security purposes.
            </div>
            
            <p style="color: #666; margin: 2rem 0;">
                Please log in through the main admin dashboard to access this import tool.
            </p>
            
            <a href="../pages/admin-login.php" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Go to Admin Login
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Admin is logged in, show import interface
require_once '../includes/db.php';
require_once '../includes/reviews-system.php';

// Check CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid request. Please try again.';
        log_security_event('csrf_failure', ['page' => 'import-ebay-reviews']);
    } else {
        // Check rate limiting
        if (!check_rate_limit('import_reviews', 10, 3600)) {
            $error_message = 'Too many import attempts. Please try again later.';
            log_security_event('rate_limit_exceeded', ['page' => 'import-ebay-reviews']);
        }
    }
}

// Initialize reviews system
$reviewsSystem = new ReviewsSystem($db);

// Handle review import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error_message) && isset($_POST['import_reviews'])) {
    try {
        $imported_count = 0;
        $skipped_count = 0;
        
        // Process each review from form data
        if (!empty($_POST['reviews']) && is_array($_POST['reviews'])) {
            foreach ($_POST['reviews'] as $review_data) {
                // Validate and sanitize review data
                $customer_name = sanitize_input($review_data['customer_name'] ?? '');
                $rating = validate_int($review_data['rating'] ?? 0);
                $review_title = sanitize_input($review_data['review_title'] ?? '');
                $review_text = sanitize_input($review_data['review_text'] ?? '');
                $ebay_feedback_id = sanitize_input($review_data['ebay_feedback_id'] ?? '');
                
                // Validate required fields
                if (empty($customer_name) || $rating < 1 || $rating > 5 || empty($review_text)) {
                    $skipped_count++;
                    continue;
                }
                
                // Check if already imported
                if ($reviewsSystem->reviewExists($ebay_feedback_id)) {
                    $skipped_count++;
                    continue;
                }
                
                // Add review
                $result = $reviewsSystem->addReview([
                    'customer_name' => $customer_name,
                    'rating' => $rating,
                    'title' => $review_title,
                    'content' => $review_text,
                    'source' => 'ebay',
                    'source_id' => $ebay_feedback_id,
                    'verified' => true
                ]);
                
                if ($result) {
                    $imported_count++;
                } else {
                    $skipped_count++;
                }
            }
        }
        
        $success_message = "Import completed: $imported_count reviews imported, $skipped_count skipped (duplicates or invalid data)";
        log_security_event('reviews_imported', ['imported' => $imported_count, 'skipped' => $skipped_count]);
        
    } catch (Exception $e) {
        $error_message = 'Import failed: ' . htmlspecialchars($e->getMessage());
        log_security_event('import_error', ['error' => $e->getMessage()]);
    }
}

// Generate CSRF token
$csrf_token = generate_csrf_token();

// Sample eBay reviews template (sanitized)
$sampleReviews = [
    [
        'customer_name' => 'manga_collector_2023',
        'rating' => 5,
        'review_title' => 'Perfect condition and fast shipping!',
        'review_text' => 'Ordered a complete Naruto set and it arrived exactly as described. Books were in perfect condition and shipping was incredibly fast. Will definitely order again!',
        'ebay_feedback_id' => 'fb_001'
    ],
    [
        'customer_name' => 'anime_fan_87',
        'rating' => 5,
        'review_title' => 'Great prices and excellent service',
        'review_text' => 'Found the best prices for Attack on Titan volumes here. Customer service was responsive and helpful. Highly recommend!',
        'ebay_feedback_id' => 'fb_002'
    ],
    [
        'customer_name' => 'bookworm_jane',
        'rating' => 5,
        'review_title' => 'Authentic manga, great condition',
        'review_text' => 'Was worried about buying manga online but these were 100% authentic and in excellent condition. Packaging was secure too.',
        'ebay_feedback_id' => 'fb_003'
    ],
    [
        'customer_name' => 'otaku_mike',
        'rating' => 4,
        'review_title' => 'Good selection and fair prices',
        'review_text' => 'Nice variety of manga titles. Prices are very competitive compared to other sellers. One book had minor shelf wear but overall satisfied.',
        'ebay_feedback_id' => 'fb_004'
    ],
    [
        'customer_name' => 'manga_master',
        'rating' => 5,
        'review_title' => 'Best manga seller on eBay!',
        'review_text' => 'I\'ve bought from many manga sellers and this is by far the best. Accurate descriptions, fair prices, and lightning-fast shipping. A++',
        'ebay_feedback_id' => 'fb_005'
    ],
    [
        'customer_name' => 'collector_sarah',
        'rating' => 5,
        'review_title' => 'Rare finds at great prices',
        'review_text' => 'Found some rare manga volumes I couldn\'t find anywhere else. Prices were much better than Amazon or other retailers. Very happy!',
        'ebay_feedback_id' => 'fb_006'
    ]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import eBay Reviews - Bort's Books Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .header h1 {
            color: #333;
            margin: 0 0 0.5rem 0;
        }
        
        .header p {
            color: #666;
            margin: 0;
        }
        
        .review-preview {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            border-left: 4px solid #667eea;
        }
        
        .review-preview h4 {
            margin: 0 0 0.5rem 0;
            color: #333;
        }
        
        .review-preview .stars {
            color: #ffc107;
            margin-bottom: 0.5rem;
        }
        
        .review-preview p {
            margin: 0;
            color: #555;
            font-size: 0.9rem;
        }
        
        .import-form {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 2rem 0;
        }
        
        .import-form h3 {
            margin: 0 0 1rem 0;
            color: #1976d2;
        }
        
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #5a6fd8;
            transform: translateY(-1px);
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-upload"></i> Import eBay Reviews</h1>
            <p>Import your existing eBay feedback to build credibility on your website</p>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="message">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="warning">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="warning">
            <strong><i class="fas fa-exclamation-triangle"></i> Instructions:</strong>
            <ol>
                <li>Replace the sample reviews below with your actual eBay feedback</li>
                <li>Make sure to anonymize customer names if needed</li>
                <li>Only import genuine reviews to maintain credibility</li>
                <li>This script should only be run once to avoid duplicates</li>
            </ol>
        </div>
        
        <h3>Reviews to Import (<?php echo count($sampleReviews); ?> total):</h3>
        
        <?php foreach ($sampleReviews as $index => $review): ?>
            <div class="review-preview">
                <h4><?php echo htmlspecialchars($review['review_title']); ?></h4>
                <div class="stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : ' far'; ?>"></i>
                    <?php endfor; ?>
                    (<?php echo $review['rating']; ?>/5)
                </div>
                <p><strong>By:</strong> <?php echo htmlspecialchars($review['customer_name']); ?></p>
                <p><?php echo htmlspecialchars($review['review_text']); ?></p>
            </div>
        <?php endforeach; ?>
        
        <div class="import-form">
            <h3><i class="fas fa-database"></i> Import These Reviews</h3>
            <p>This will add all the reviews above to your website's review system. They will be marked as "eBay" reviews and show verified purchase badges.</p>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="import_reviews" value="1">
                <label>
                    <input type="checkbox" name="reviews[]" value="<?php echo htmlspecialchars(json_encode($sampleReviews[0])); ?>" required>
                    <?php echo htmlspecialchars($sampleReviews[0]['review_title']); ?>
                </label>
                <br><br>
                <label>
                    <input type="checkbox" name="reviews[]" value="<?php echo htmlspecialchars(json_encode($sampleReviews[1])); ?>" required>
                    <?php echo htmlspecialchars($sampleReviews[1]['review_title']); ?>
                </label>
                <br><br>
                <label>
                    <input type="checkbox" name="reviews[]" value="<?php echo htmlspecialchars(json_encode($sampleReviews[2])); ?>" required>
                    <?php echo htmlspecialchars($sampleReviews[2]['review_title']); ?>
                </label>
                <br><br>
                <label>
                    <input type="checkbox" name="reviews[]" value="<?php echo htmlspecialchars(json_encode($sampleReviews[3])); ?>" required>
                    <?php echo htmlspecialchars($sampleReviews[3]['review_title']); ?>
                </label>
                <br><br>
                <label>
                    <input type="checkbox" name="reviews[]" value="<?php echo htmlspecialchars(json_encode($sampleReviews[4])); ?>" required>
                    <?php echo htmlspecialchars($sampleReviews[4]['review_title']); ?>
                </label>
                <br><br>
                <label>
                    <input type="checkbox" name="reviews[]" value="<?php echo htmlspecialchars(json_encode($sampleReviews[5])); ?>" required>
                    <?php echo htmlspecialchars($sampleReviews[5]['review_title']); ?>
                </label>
                <br><br>
                <button type="submit" class="btn">
                    <i class="fas fa-upload"></i> Import Reviews
                </button>
            </form>
        </div>
        
        <div style="text-align: center; margin-top: 2rem; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="../index.php" class="btn">
                <i class="fas fa-home"></i> Back to Website
            </a>
            <a href="?logout=1" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</body>
</html> 