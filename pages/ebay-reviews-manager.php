<?php
/**
 * eBay Reviews Manager - Simple and Effective
 * Easy way to add your real eBay reviews
 */

session_start();

require_once '../includes/config.php';

// Admin credentials
$admin_username = 'admin';

// Check if user is trying to login
if (isset($_POST['login'])) {
    if (isset($_POST['username'], $_POST['password'])) {
        if ($_POST['username'] === $admin_username && password_verify($_POST['password'], ADMIN_PASSWORD_HASH)) {
            $_SESSION['admin_authenticated'] = true;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $login_error = 'Invalid username or password';
        }
    }
}

// Check if user is trying to logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    // Show login form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>eBay Reviews Manager - Bort's Books</title>
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
                <h1><i class="fas fa-star"></i> eBay Reviews Manager</h1>
                <p>Import Your Real eBay Reviews</p>
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

// Handle adding new review
if (isset($_POST['add_review'])) {
    $customer_name = trim($_POST['customer_name']);
    $rating = (int)$_POST['rating'];
    $review_title = trim($_POST['review_title']);
    $review_text = trim($_POST['review_text']);
    $ebay_feedback_id = 'ebay_' . time() . '_' . rand(100, 999);
    
    try {
        $result = $reviewsSystem->importEbayReview(
            $customer_name,
            $rating,
            $review_text,
            $review_title,
            $ebay_feedback_id
        );
        
        if ($result) {
            $success_message = "✅ Review added successfully! It will now appear on your website.";
        } else {
            $error_message = "❌ Failed to add review. Please try again.";
        }
    } catch (Exception $e) {
        $error_message = "❌ Error: " . $e->getMessage();
    }
}

// Handle deleting review
if (isset($_POST['delete_review'])) {
    $review_id = (int)$_POST['review_id'];
    try {
        $stmt = $db->prepare("DELETE FROM customer_reviews WHERE id = ? AND review_source = 'ebay'");
        $result = $stmt->execute([$review_id]);
        
        if ($result) {
            $success_message = "✅ Review deleted successfully!";
        } else {
            $error_message = "❌ Failed to delete review.";
        }
    } catch (Exception $e) {
        $error_message = "❌ Error: " . $e->getMessage();
    }
}

// Get existing eBay reviews
try {
    $stmt = $db->prepare("SELECT * FROM customer_reviews WHERE review_source = 'ebay' ORDER BY created_at DESC");
    $stmt->execute();
    $existing_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $existing_reviews = [];
    $error_message = "❌ Could not load existing reviews: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eBay Reviews Manager - Bort's Books</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1000px;
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
            border-bottom: 3px solid #667eea;
        }
        
        .header h1 {
            color: #333;
            margin: 0 0 0.5rem 0;
            font-size: 2.5rem;
        }
        
        .header p {
            color: #666;
            margin: 0;
            font-size: 1.2rem;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .add-review-section {
            background: #f8f9fa;
            border: 3px solid #28a745;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .add-review-section h2 {
            color: #28a745;
            margin: 0 0 1.5rem 0;
            font-size: 1.8rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #28a745;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .btn:hover {
            background: #218838;
            transform: translateY(-1px);
        }
        
        .btn-danger {
            background: #dc3545;
            padding: 6px 12px;
            font-size: 0.9rem;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .reviews-list {
            margin-top: 2rem;
        }
        
        .reviews-list h2 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }
        
        .review-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            position: relative;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .review-info h4 {
            margin: 0 0 0.5rem 0;
            color: #333;
            font-size: 1.2rem;
        }
        
        .stars {
            color: #ffc107;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        
        .review-meta {
            color: #666;
            font-size: 0.9rem;
        }
        
        .review-text {
            color: #555;
            margin: 1rem 0;
            line-height: 1.6;
        }
        
        .review-badges {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .badge {
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-ebay {
            background: #0064d2;
            color: white;
        }
        
        .badge-verified {
            background: #28a745;
            color: white;
        }
        
        .no-reviews {
            text-align: center;
            padding: 3rem;
            color: #666;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .instructions {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .instructions h3 {
            color: #1976d2;
            margin: 0 0 1rem 0;
        }
        
        .instructions ol {
            margin: 0;
            padding-left: 1.5rem;
        }
        
        .instructions li {
            margin-bottom: 0.5rem;
        }
        
        .navigation {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #f0f0f0;
        }
        
        .nav-btn {
            background: #667eea;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            margin: 0 0.5rem;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .nav-btn:hover {
            background: #5a6fd8;
            transform: translateY(-1px);
            text-decoration: none;
            color: white;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .stats {
                grid-template-columns: 1fr;
            }
            
            .review-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-star"></i> eBay Reviews Manager</h1>
            <p>Import and manage your real eBay reviews</p>
        </div>
        
        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($existing_reviews); ?></div>
                <div class="stat-label">eBay Reviews Imported</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    if (!empty($existing_reviews)) {
                        $avg = array_sum(array_column($existing_reviews, 'rating')) / count($existing_reviews);
                        echo number_format($avg, 1);
                    } else {
                        echo "0.0";
                    }
                    ?>
                </div>
                <div class="stat-label">Average Rating</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php echo count(array_filter($existing_reviews, function($r) { return $r['rating'] == 5; })); ?>
                </div>
                <div class="stat-label">5-Star Reviews</div>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if (isset($success_message)): ?>
            <div class="message success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="message error">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Instructions -->
        <div class="instructions">
            <h3><i class="fas fa-info-circle"></i> How to Import Your eBay Reviews</h3>
            <ol>
                <li><strong>Go to your eBay account</strong> and find the feedback you've received</li>
                <li><strong>Copy the customer's username</strong> (you can anonymize it like "manga_fan_123")</li>
                <li><strong>Copy the exact review text</strong> from eBay</li>
                <li><strong>Note the star rating</strong> they gave you</li>
                <li><strong>Fill out the form below</strong> and click "Add Review"</li>
                <li><strong>Repeat for each review</strong> you want to import</li>
                <li><strong>Check your website</strong> - reviews appear immediately on your home page!</li>
            </ol>
        </div>
        
        <!-- Add Review Form -->
        <div class="add-review-section">
            <h2><i class="fas fa-plus-circle"></i> Add New eBay Review</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="customer_name">Customer Name (from eBay):</label>
                        <input type="text" id="customer_name" name="customer_name" required 
                               placeholder="e.g., manga_collector_2023 or anonymize it">
                    </div>
                    <div class="form-group">
                        <label for="rating">Star Rating:</label>
                        <select id="rating" name="rating" required>
                            <option value="5">⭐⭐⭐⭐⭐ (5 Stars)</option>
                            <option value="4">⭐⭐⭐⭐ (4 Stars)</option>
                            <option value="3">⭐⭐⭐ (3 Stars)</option>
                            <option value="2">⭐⭐ (2 Stars)</option>
                            <option value="1">⭐ (1 Star)</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="review_title">Review Title (optional):</label>
                    <input type="text" id="review_title" name="review_title" 
                           placeholder="Brief summary or leave blank">
                </div>
                <div class="form-group">
                    <label for="review_text">Review Text (copy from eBay):</label>
                    <textarea id="review_text" name="review_text" required rows="4" 
                              placeholder="Paste the exact review text from your eBay feedback here..."></textarea>
                </div>
                <button type="submit" name="add_review" class="btn">
                    <i class="fas fa-plus"></i> Add Review to Website
                </button>
            </form>
        </div>
        
        <!-- Existing Reviews -->
        <div class="reviews-list">
            <h2><i class="fas fa-list"></i> Your Imported eBay Reviews</h2>
            
            <?php if (empty($existing_reviews)): ?>
                <div class="no-reviews">
                    <i class="fas fa-star" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                    <h3>No eBay reviews imported yet</h3>
                    <p>Use the form above to add your first eBay review!</p>
                </div>
            <?php else: ?>
                <?php foreach ($existing_reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="review-info">
                                <h4><?php echo htmlspecialchars($review['review_title'] ?: 'eBay Review'); ?></h4>
                                <div class="stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : ' far'; ?>"></i>
                                    <?php endfor; ?>
                                    (<?php echo $review['rating']; ?>/5)
                                </div>
                                <div class="review-meta">
                                    By: <strong><?php echo htmlspecialchars($review['customer_name']); ?></strong> • 
                                    Added: <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                </div>
                            </div>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                <button type="submit" name="delete_review" class="btn btn-danger" 
                                        onclick="return confirm('Are you sure you want to delete this review?')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                        <div class="review-text">
                            "<?php echo htmlspecialchars($review['review_text']); ?>"
                        </div>
                        <div class="review-badges">
                            <span class="badge badge-ebay"><i class="fab fa-ebay"></i> eBay</span>
                            <span class="badge badge-verified"><i class="fas fa-check-circle"></i> Verified</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Navigation -->
        <div class="navigation">
            <a href="../index.php" class="nav-btn">
                <i class="fas fa-home"></i> View Website
            </a>
            <a href="admin-dashboard.php" class="nav-btn">
                <i class="fas fa-tachometer-alt"></i> Admin Dashboard
            </a>
            <a href="?logout=1" class="nav-btn" style="background: #dc3545;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</body>
</html> 