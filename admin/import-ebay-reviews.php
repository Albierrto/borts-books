<?php
/**
 * eBay Reviews Import Script
 * Use this to manually import your eBay reviews
 */

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
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="../index.php" class="btn">
                <i class="fas fa-home"></i> Back to Website
            </a>
        </div>
    </div>
</body>
</html> 