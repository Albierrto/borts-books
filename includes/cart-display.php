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
 */
function getCartCount() {
    return isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
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
?> 