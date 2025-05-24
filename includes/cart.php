<?php
/**
 * Cart utility functions
 */

/**
 * Get cart count
 */
function getCartCount() {
    return isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
}

/**
 * Get cart total
 */
function getCartTotal() {
    if (empty($_SESSION['cart'])) {
        return 0;
    }
    
    global $db;
    $cart = $_SESSION['cart'];
    $total = 0;
    
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $stmt = $db->query("SELECT * FROM products WHERE id IN ($ids)");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        $quantity = $cart[$product['id']];
        $total += $product['price'] * $quantity;
    }
    
    return $total;
}

/**
 * Add item to cart
 */
function addToCart($product_id, $quantity = 1) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $product_id = (int)$product_id;
    $quantity = max(1, (int)$quantity);
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
    
    return true;
}

/**
 * Remove item from cart
 */
function removeFromCart($product_id) {
    $product_id = (int)$product_id;
    
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        return true;
    }
    
    return false;
}

/**
 * Update cart item quantity
 */
function updateCartQuantity($product_id, $quantity) {
    $product_id = (int)$product_id;
    $quantity = max(1, (int)$quantity);
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] = $quantity;
        return true;
    }
    
    return false;
}

/**
 * Clear entire cart
 */
function clearCart() {
    $_SESSION['cart'] = [];
    return true;
}
?> 