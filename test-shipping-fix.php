<?php
/**
 * Test shipping calculation fix
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/usps-shipping.php';

echo "<h1>Shipping Calculation Fix Test</h1>";

// Test the scenario you mentioned: 10lb, 8x10x12 package
$test_product = [
    'id' => 1,
    'title' => 'Test Heavy Manga Set',
    'price' => 90.00,
    'weight' => 10,  // 10 pounds
    'dimensions' => '8x10x12',
    'shipping_option' => 'calculated'
];

$usps = new USPSShipping();

echo "<h2>Test Product:</h2>";
echo "<ul>";
echo "<li>Weight: {$test_product['weight']} lbs</li>";
echo "<li>Dimensions: {$test_product['dimensions']} inches</li>";
echo "<li>Price: \${$test_product['price']}</li>";
echo "</ul>";

echo "<h2>Shipping Calculations:</h2>";

$test_zips = ['90210', '10001', '60601', '30301'];
$services = ['Media', 'Ground', 'Priority', 'Express'];

foreach ($test_zips as $zip) {
    echo "<h3>To ZIP: $zip</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
    echo "<tr><th>Service</th><th>Rate</th><th>Delivery</th><th>Debug Info</th></tr>";
    
    foreach ($services as $service) {
        try {
            $result = $usps->calculateShipping($test_product, $zip, $service);
            
            $debug_info = '';
            if (isset($result['debug'])) {
                $debug = $result['debug'];
                $debug_info = "Weight: {$debug['weight_pounds']}lb, Volume: {$debug['volume']}, Base: \${$debug['base']}, Weight Cost: \${$debug['weight_cost']}, Zone Cost: \${$debug['zone_cost']}, Size Cost: \${$debug['size_cost']}";
            }
            
            echo "<tr>";
            echo "<td>{$result['service']}</td>";
            echo "<td>\${$result['rate']}</td>";
            echo "<td>{$result['days']}</td>";
            echo "<td style='font-size: 0.8em;'>$debug_info</td>";
            echo "</tr>";
            
        } catch (Exception $e) {
            echo "<tr><td>$service</td><td colspan='3'>Error: {$e->getMessage()}</td></tr>";
        }
    }
    echo "</table>";
}

echo "<h2>Test Regular Manga Book:</h2>";

$normal_manga = [
    'id' => 2,
    'title' => 'Regular Manga Volume',
    'price' => 12.99,
    'weight' => 0.5,  // 0.5 pounds
    'dimensions' => '7.5x5x0.8',
    'shipping_option' => 'calculated'
];

echo "<p>Weight: {$normal_manga['weight']} lbs, Dimensions: {$normal_manga['dimensions']}</p>";

foreach (['Ground', 'Priority'] as $service) {
    $result = $usps->calculateShipping($normal_manga, '90210', $service);
    echo "<p><strong>$service:</strong> \${$result['rate']} - {$result['days']}</p>";
}

echo "<h2>Expected Results:</h2>";
echo "<ul>";
echo "<li>Heavy package (10lb) should be under \$30 for Ground/Priority</li>";
echo "<li>Regular manga should be under \$10 for most services</li>";
echo "<li>Express should be most expensive but under \$50</li>";
echo "<li>No more \$150+ shipping costs!</li>";
echo "</ul>";
?> 