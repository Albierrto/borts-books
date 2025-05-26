<?php
/**
 * eBay Reviews Import Script
 * Use this to manually import your eBay reviews
 */

session_start();

// Admin authentication
$admin_username = 'bort';
$admin_password = 'LolaSombra1!';

// Check if user is trying to login
if (isset($_POST['login'])) {
    if ($_POST['username'] === $admin_username && $_POST['password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = 'Invalid username or password';
    }
}

// Check if user is trying to logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Show login form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - Bort's Books</title>
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
            
            .login-container {
                background: white;
                border-radius: 12px;
                padding: 2rem;
                box-shadow: 0 8px 30px rgba(0,0,0,0.2);
                width: 100%;
                max-width: 400px;
            }
            
            .login-header {
                text-align: center;
                margin-bottom: 2rem;
            }
            
            .login-header h1 {
                color: #333;
                margin: 0 0 0.5rem 0;
            }
            
            .login-header p {
                color: #666;
                margin: 0;
            }
            
            .form-group {
                margin-bottom: 1.5rem;
            }
            
            .form-group label {
                display: block;
                margin-bottom: 0.5rem;
                color: #333;
                font-weight: 600;
            }
            
            .form-group input {
                width: 100%;
                padding: 12px;
                border: 2px solid #e1e5e9;
                border-radius: 8px;
                font-size: 1rem;
                transition: border-color 0.3s ease;
                box-sizing: border-box;
            }
            
            .form-group input:focus {
                outline: none;
                border-color: #667eea;
            }
            
            .btn-login {
                width: 100%;
                background: #667eea;
                color: white;
                border: none;
                padding: 12px;
                border-radius: 8px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            
            .btn-login:hover {
                background: #5a6fd8;
                transform: translateY(-1px);
            }
            
            .error {
                background: #f8d7da;
                color: #721c24;
                padding: 1rem;
                border-radius: 8px;
                margin-bottom: 1rem;
                border: 1px solid #f5c6cb;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="login-header">
                <h1><i class="fas fa-lock"></i> Admin Login</h1>
                <p>Bort's Books Administration</p>
            </div>
            
            <?php if (isset($login_error)): ?>
                <div class="error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $login_error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" name="login" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

require_once '../includes/db.php';
require_once '../includes/reviews-system.php';

// Initialize reviews system
$reviewsSystem = new ReviewsSystem($db);

// Sample eBay reviews - Replace these with your actual eBay reviews
$ebayReviews = [
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

// Check if form was submitted
if ($_POST['action'] === 'import' && $_POST['confirm'] === 'yes') {
    $imported = 0;
    $errors = 0;
    
    foreach ($ebayReviews as $review) {
        try {
            $result = $reviewsSystem->importEbayReview(
                $review['customer_name'],
                $review['rating'],
                $review['review_text'],
                $review['review_title'],
                $review['ebay_feedback_id']
            );
            
            if ($result) {
                $imported++;
            } else {
                $errors++;
            }
        } catch (Exception $e) {
            $errors++;
        }
    }
    
    $message = "Import completed! Imported: $imported reviews, Errors: $errors";
}

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
        
        <?php if (isset($message)): ?>
            <div class="message">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
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
        
        <h3>Reviews to Import (<?php echo count($ebayReviews); ?> total):</h3>
        
        <?php foreach ($ebayReviews as $index => $review): ?>
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
                <input type="hidden" name="action" value="import">
                <label>
                    <input type="checkbox" name="confirm" value="yes" required>
                    I confirm these are genuine eBay reviews and I want to import them
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
            <a href="admin-dashboard.php" class="btn">
                <i class="fas fa-tachometer-alt"></i> Admin Dashboard
            </a>
            <a href="?logout=1" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</body>
</html> 