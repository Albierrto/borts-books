<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Get Stripe keys from environment variables
$stripe_publishable_key = $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '';
$stripe_secret_key = $_ENV['STRIPE_SECRET_KEY'] ?? '';

if (empty($stripe_secret_key)) {
    throw new Exception('Stripe secret key not found in environment variables. Please add STRIPE_SECRET_KEY to your .env file.');
}

// Set Stripe API key
\Stripe\Stripe::setApiKey($stripe_secret_key);

/**
 * Create a Stripe Checkout Session
 */
function createStripeCheckoutSession($cart, $customerInfo) {
    global $db, $stripe_publishable_key;
    
    try {
        // Calculate totals
        $subtotal = 0;
        $line_items = [];
        
        if (!empty($cart)) {
            $ids = implode(',', array_map('intval', array_keys($cart)));
            $stmt = $db->query("SELECT * FROM products WHERE id IN ($ids)");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($products as $product) {
                $quantity = $cart[$product['id']];
                $line_total = $product['price'] * $quantity;
                $subtotal += $line_total;
                
                $line_items[] = [
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $product['title'],
                            'description' => $product['description'] ?: 'Manga book',
                        ],
                        'unit_amount' => round($product['price'] * 100), // Convert to cents
                    ],
                    'quantity' => $quantity,
                ];
            }
        }
        
        // Add shipping
        if ($subtotal > 0) {
            $line_items[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Shipping',
                        'description' => 'Standard shipping',
                    ],
                    'unit_amount' => round(SHIPPING_RATE * 100), // Convert to cents
                ],
                'quantity' => 1,
            ];
        }
        
        // Create Checkout Session
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $line_items,
            'mode' => 'payment',
            'success_url' => SITE_URL . '/success.php?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => SITE_URL . '/cart.php',
            'customer_email' => $customerInfo['email'],
            'metadata' => [
                'customer_name' => $customerInfo['name'],
                'customer_phone' => $customerInfo['phone'],
            ],
        ]);
        
        return $session;
        
    } catch (Exception $e) {
        error_log('Stripe Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Verify a successful payment
 */
function verifyStripePayment($session_id) {
    try {
        $session = \Stripe\Checkout\Session::retrieve($session_id);
        return $session;
    } catch (Exception $e) {
        error_log('Stripe Verification Error: ' . $e->getMessage());
        return false;
    }
}
?> 