<?php
// Local test script for reviews system (XAMPP)
try {
    // Local XAMPP database connection
    $db = new PDO('mysql:host=localhost;dbname=borts_books', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Local database connected successfully\n";
    
    // Include reviews system
    require_once 'includes/reviews-system.php';
    
    echo "Testing Reviews System...\n\n";
    
    // Initialize reviews system
    $reviewsSystem = new ReviewsSystem($db);
    echo "✓ Reviews system initialized successfully\n";
    
    // Test getting reviews
    $reviews = $reviewsSystem->getReviews(5);
    echo "✓ Retrieved " . count($reviews) . " reviews\n";
    
    // Test average rating
    $avgData = $reviewsSystem->getAverageRating();
    if ($avgData['avg_rating']) {
        echo "✓ Average rating: " . number_format($avgData['avg_rating'], 1) . " (" . $avgData['total_reviews'] . " total reviews)\n";
    } else {
        echo "✓ No reviews yet (this is normal for a fresh install)\n";
    }
    
    // Test star rating generation
    $starHtml = $reviewsSystem->generateStarRating(4.5);
    echo "✓ Star rating HTML generated\n";
    
    // Test reviews widget
    $widget = $reviewsSystem->displayReviewsWidget(null, 3);
    echo "✓ Reviews widget generated (" . strlen($widget) . " characters)\n";
    
    echo "\n🎉 All tests passed! Reviews system is working correctly.\n";
    echo "\nNext steps:\n";
    echo "1. Visit: http://localhost/borts-books/admin/import-ebay-reviews.php\n";
    echo "2. Import your eBay reviews to see them on the homepage\n";
    echo "3. The reviews will appear in the customer reviews section\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Make sure XAMPP MySQL is running\n";
    echo "2. Create 'borts_books' database in phpMyAdmin\n";
    echo "3. Check database credentials in includes/db.php\n";
}
?> 