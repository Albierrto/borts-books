<?php
/**
 * Reviews System for Bort's Books
 * Handles display of imported eBay reviews and new customer reviews
 */

class ReviewsSystem {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
        $this->createReviewsTable();
    }
    
    private function createReviewsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS customer_reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_name VARCHAR(100) NOT NULL,
            customer_email VARCHAR(255),
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            review_title VARCHAR(200),
            review_text TEXT NOT NULL,
            product_id INT,
            order_id VARCHAR(50),
            review_source ENUM('website', 'ebay', 'imported') DEFAULT 'website',
            ebay_feedback_id VARCHAR(100),
            verified_purchase BOOLEAN DEFAULT FALSE,
            helpful_votes INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
            INDEX idx_product_id (product_id),
            INDEX idx_rating (rating),
            INDEX idx_source (review_source),
            INDEX idx_status (status)
        )";
        
        $this->db->exec($sql);
    }
    
    /**
     * Import eBay reviews (you can manually add these)
     */
    public function importEbayReview($customerName, $rating, $reviewText, $reviewTitle = '', $ebayFeedbackId = '') {
        $stmt = $this->db->prepare("
            INSERT INTO customer_reviews 
            (customer_name, rating, review_title, review_text, review_source, ebay_feedback_id, verified_purchase) 
            VALUES (?, ?, ?, ?, 'ebay', ?, TRUE)
        ");
        
        return $stmt->execute([
            $customerName,
            $rating,
            $reviewTitle,
            $reviewText,
            $ebayFeedbackId
        ]);
    }
    
    /**
     * Add a new website review
     */
    public function addReview($customerName, $email, $rating, $reviewText, $reviewTitle = '', $productId = null, $orderId = null) {
        $stmt = $this->db->prepare("
            INSERT INTO customer_reviews 
            (customer_name, customer_email, rating, review_title, review_text, product_id, order_id, verified_purchase) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        // Check if this is a verified purchase
        $verifiedPurchase = false;
        if ($orderId) {
            // You can add logic here to verify the order exists
            $verifiedPurchase = true;
        }
        
        return $stmt->execute([
            $customerName,
            $email,
            $rating,
            $reviewTitle,
            $reviewText,
            $productId,
            $orderId,
            $verifiedPurchase
        ]);
    }
    
    /**
     * Get reviews for display
     */
    public function getReviews($limit = 10, $productId = null, $source = null) {
        $sql = "SELECT * FROM customer_reviews WHERE status = 'approved'";
        $params = [];
        
        if ($productId) {
            $sql .= " AND (product_id = ? OR product_id IS NULL)";
            $params[] = $productId;
        }
        
        if ($source) {
            $sql .= " AND review_source = ?";
            $params[] = $source;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get average rating
     */
    public function getAverageRating($productId = null) {
        $sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                FROM customer_reviews 
                WHERE status = 'approved'";
        $params = [];
        
        if ($productId) {
            $sql .= " AND (product_id = ? OR product_id IS NULL)";
            $params[] = $productId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Generate star rating HTML
     */
    public function generateStarRating($rating, $showNumber = true) {
        $html = '<div class="star-rating">';
        
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                $html .= '<i class="fas fa-star"></i>';
            } elseif ($i - 0.5 <= $rating) {
                $html .= '<i class="fas fa-star-half-alt"></i>';
            } else {
                $html .= '<i class="far fa-star"></i>';
            }
        }
        
        if ($showNumber) {
            $html .= ' <span class="rating-number">(' . number_format($rating, 1) . ')</span>';
        }
        
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Display reviews widget
     */
    public function displayReviewsWidget($productId = null, $limit = 6) {
        $reviews = $this->getReviews($limit, $productId);
        $avgData = $this->getAverageRating($productId);
        
        if (empty($reviews)) {
            return '';
        }
        
        $html = '
        <div class="reviews-widget">
            <div class="reviews-header">
                <h3><i class="fas fa-star"></i> Customer Reviews</h3>
                <div class="reviews-summary">
                    ' . $this->generateStarRating($avgData['avg_rating']) . '
                    <span class="reviews-count">Based on ' . $avgData['total_reviews'] . ' reviews</span>
                </div>
            </div>
            
            <div class="reviews-grid">';
        
        foreach ($reviews as $review) {
            $verifiedBadge = $review['verified_purchase'] ? '<span class="verified-badge"><i class="fas fa-check-circle"></i> Verified Purchase</span>' : '';
            $sourceBadge = $review['review_source'] === 'ebay' ? '<span class="source-badge ebay-badge"><i class="fab fa-ebay"></i> eBay</span>' : '';
            
            $html .= '
            <div class="review-card">
                <div class="review-header">
                    <div class="reviewer-info">
                        <strong class="reviewer-name">' . htmlspecialchars($review['customer_name']) . '</strong>
                        ' . $this->generateStarRating($review['rating'], false) . '
                    </div>
                    <div class="review-badges">
                        ' . $verifiedBadge . '
                        ' . $sourceBadge . '
                    </div>
                </div>';
            
            if ($review['review_title']) {
                $html .= '<h4 class="review-title">' . htmlspecialchars($review['review_title']) . '</h4>';
            }
            
            $html .= '
                <p class="review-text">' . htmlspecialchars($review['review_text']) . '</p>
                <div class="review-footer">
                    <span class="review-date">' . date('M j, Y', strtotime($review['created_at'])) . '</span>
                </div>
            </div>';
        }
        
        $html .= '
            </div>
        </div>';
        
        return $html;
    }
    
    /**
     * Get CSS for reviews styling
     */
    public function getReviewsCSS() {
        return '
        <style>
        .reviews-widget {
            background: #fff;
            border-radius: 12px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .reviews-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .reviews-header h3 {
            margin: 0 0 1rem 0;
            color: #333;
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .reviews-summary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .star-rating {
            display: flex;
            align-items: center;
            gap: 0.2rem;
            color: #ffc107;
            font-size: 1.2rem;
        }
        
        .star-rating .rating-number {
            color: #666;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
        .reviews-count {
            color: #666;
            font-size: 1rem;
        }
        
        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .review-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }
        
        .review-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .reviewer-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .reviewer-name {
            color: #333;
            font-size: 1.1rem;
        }
        
        .review-badges {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            align-items: flex-end;
        }
        
        .verified-badge {
            background: #28a745;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .source-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .ebay-badge {
            background: #0064d2;
            color: white;
        }
        
        .review-title {
            margin: 0 0 0.8rem 0;
            color: #333;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .review-text {
            margin: 0 0 1rem 0;
            color: #555;
            line-height: 1.6;
            font-size: 1rem;
        }
        
        .review-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #888;
            font-size: 0.9rem;
        }
        
        .review-date {
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .reviews-grid {
                grid-template-columns: 1fr;
            }
            
            .reviews-widget {
                padding: 1.5rem;
            }
            
            .review-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .review-badges {
                flex-direction: row;
                align-items: flex-start;
            }
        }
        </style>';
    }
}

// Initialize the reviews system
if (isset($db)) {
    $reviewsSystem = new ReviewsSystem($db);
}
?> 