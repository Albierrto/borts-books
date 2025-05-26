<?php
/**
 * Universal cart display helper
 * Include this file to get consistent cart count across all pages
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Calculate cart count (using count for unique items, not quantities)
$cart_count = count($_SESSION['cart']);

/**
 * Get cart count for display
 * Only declare if not already declared in cart.php
 */
if (!function_exists('getCartCount')) {
    function getCartCount() {
        return isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
    }
}

/**
 * Generate cart header HTML
 */
function renderCartHeader($base_path = '') {
    $cart_count = getCartCount();
    return '<a href="' . $base_path . 'cart.php" title="Shopping Cart" class="cart-link">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-count">' . $cart_count . '</span>
    </a>';
}

/**
 * Get cart count as JSON for AJAX
 */
function getCartCountJson() {
    header('Content-Type: application/json');
    echo json_encode(['cart_count' => getCartCount()]);
    exit;
}

/**
 * Clean up cart by removing deleted products
 * Made safe to prevent breaking cart functionality
 */
function cleanupCart() {
    // Early return if no cart items or database issues
    if (empty($_SESSION['cart'])) {
        return;
    }
    
    // Don't clean up if there are too many items (could be performance issue)
    if (count($_SESSION['cart']) > 50) {
        error_log('Skipping cart cleanup - too many items');
        return;
    }
    
    try {
        global $db;
        
        // Check if database connection exists
        if (!isset($db) || !$db) {
            return; // Silently skip if no database
        }
        
        // Get all product IDs from cart
        $cart_ids = array_keys($_SESSION['cart']);
        
        // Don't proceed if no IDs
        if (empty($cart_ids)) {
            return;
        }
        
        $placeholders = implode(',', array_fill(0, count($cart_ids), '?'));
        
        // Check which products still exist in database
        $stmt = $db->prepare("SELECT id FROM products WHERE id IN ($placeholders)");
        $stmt->execute($cart_ids);
        $existing_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Only clean up if we got a valid response
        if (is_array($existing_ids)) {
            // Remove non-existent products from cart
            $original_count = count($_SESSION['cart']);
            $_SESSION['cart'] = array_intersect_key($_SESSION['cart'], array_flip($existing_ids));
            
            $removed_count = $original_count - count($_SESSION['cart']);
            if ($removed_count > 0) {
                error_log("Cart cleanup: removed $removed_count deleted items");
            }
        }
        
    } catch (Exception $e) {
        // Log error but don't let it break the cart
        error_log('Cart cleanup error (non-fatal): ' . $e->getMessage());
    }
}
?>