<?php
require_once 'includes/security.php';

// Start secure session
secure_session_start();

// Set security headers
set_security_headers();

// Check rate limiting
if (!check_rate_limit('clear_cart', 10, 300)) {
    http_response_code(429);
    die('Too many cart clear requests. Please wait before trying again.');
}

// Verify this is a valid request (not a CSRF attempt)
// For GET requests, we'll add a simple token verification
$token = $_GET['token'] ?? '';
if (empty($token) || $token !== ($_SESSION['cart_clear_token'] ?? '')) {
    // Generate a new token for legitimate use
    $_SESSION['cart_clear_token'] = bin2hex(random_bytes(16));
    
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <link rel='icon' type='image/svg+xml' href='data:image/svg+xml,%3Csvg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\"%3E%3Cdefs%3E%3ClinearGradient id=\"grad\" x1=\"0%25\" y1=\"0%25\" x2=\"100%25\" y2=\"100%25\"%3E%3Cstop offset=\"0%25\" style=\"stop-color:%23667eea;stop-opacity:1\" /%3E%3Cstop offset=\"100%25\" style=\"stop-color:%23764ba2;stop-opacity:1\" /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=\"100\" height=\"100\" rx=\"15\" fill=\"url(%23grad)\"%3E%3Cpath d=\"M25 20h50c2.5 0 4.5 2 4.5 4.5v51c0 2.5-2 4.5-4.5 4.5H25c-2.5 0-4.5-2-4.5-4.5v-51c0-2.5 2-4.5 4.5-4.5z\" fill=\"white\"%3E%3Cpath d=\"M30 30h40v5H30z\" fill=\"%23667eea\"%3E%3Cpath d=\"M30 40h35v3H30z\" fill=\"%23999\"%3E%3Cpath d=\"M30 47h30v3H30z\" fill=\"%23999\"%3E%3Cpath d=\"M30 54h25v3H30z\" fill=\"%23999\"%3E%3C/svg%3E'>
        <title>Clear Cart - Bort's Books</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { max-width: 500px; margin: 50px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
            .btn { background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 10px; }
            .btn:hover { background: #c82333; }
            .btn-secondary { background: #6c757d; }
            .btn-secondary:hover { background: #5a6268; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Clear Shopping Cart</h2>
            <p>Are you sure you want to clear all items from your cart?</p>
            <p><strong>This action cannot be undone.</strong></p>
            <a href='clear-cart.php?token=" . htmlspecialchars($_SESSION['cart_clear_token']) . "' class='btn'>Yes, Clear Cart</a>
            <a href='cart.php' class='btn btn-secondary'>Cancel</a>
        </div>
    </body>
    </html>";
    exit;
}

// Clear the cart completely
$_SESSION['cart'] = [];

// Invalidate the token after use
unset($_SESSION['cart_clear_token']);

// Log the action for monitoring
log_security_event('cart_cleared', [], 'low');

// Provide feedback
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <link rel='icon' type='image/svg+xml' href='data:image/svg+xml,%3Csvg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\"%3E%3Cdefs%3E%3ClinearGradient id=\"grad\" x1=\"0%25\" y1=\"0%25\" x2=\"100%25\" y2=\"100%25\"%3E%3Cstop offset=\"0%25\" style=\"stop-color:%23667eea;stop-opacity:1\" /%3E%3Cstop offset=\"100%25\" style=\"stop-color:%23764ba2;stop-opacity:1\" /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=\"100\" height=\"100\" rx=\"15\" fill=\"url(%23grad)\"%3E%3Cpath d=\"M25 20h50c2.5 0 4.5 2 4.5 4.5v51c0 2.5-2 4.5-4.5 4.5H25c-2.5 0-4.5-2-4.5-4.5v-51c0-2.5 2-4.5 4.5-4.5z\" fill=\"white\"%3E%3Cpath d=\"M30 30h40v5H30z\" fill=\"%23667eea\"%3E%3Cpath d=\"M30 40h35v3H30z\" fill=\"%23999\"%3E%3Cpath d=\"M30 47h30v3H30z\" fill=\"%23999\"%3E%3Cpath d=\"M30 54h25v3H30z\" fill=\"%23999\"%3E%3C/svg%3E'>
    <title>Cart Cleared - Bort's Books</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 500px; margin: 50px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .btn { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 10px; }
        .btn:hover { background: #005a87; }
        .btn-secondary { background: #28a745; }
        .btn-secondary:hover { background: #218838; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='success'>
            <h3>✅ Cart Cleared Successfully</h3>
            <p>All items have been removed from your cart.</p>
        </div>
        <a href='cart.php' class='btn'>View Cart</a>
        <a href='index.php' class='btn btn-secondary'>Continue Shopping</a>
    </div>
</body>
</html>";
?> 