<?php
// Load Stripe autoload
$autoload_path = __DIR__ . '/vendor/autoload.php';

if (!file_exists($autoload_path)) {
    throw new Exception('Stripe vendor autoload.php not found. Please upload the vendor directory to your server at: includes/vendor/');
}

require_once $autoload_path;

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
function createStripeCheckoutSession($cart, $customerInfo, $shipping_cost = null) {
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
                $quantity = 1; // Always 1 since we only allow one of each item
                $line_total = $product['price'];
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
                    'quantity' => 1,
                ];
            }
        }
        
        // Add shipping - use calculated shipping cost or fallback to default
        $shipping_amount = $shipping_cost !== null ? $shipping_cost : (defined('SHIPPING_RATE') ? SHIPPING_RATE : 5.00);
        
        if ($subtotal > 0 && $shipping_amount > 0) {
            $line_items[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Shipping',
                        'description' => 'USPS Shipping',
                    ],
                    'unit_amount' => round($shipping_amount * 100), // Convert to cents
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
    
                'shipping_address' => ($customerInfo['address'] ?? '') . ', ' . ($customerInfo['city'] ?? '') . ', ' . ($customerInfo['state'] ?? '') . ' ' . ($customerInfo['zip'] ?? ''),
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