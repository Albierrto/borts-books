<?php
/**
 * Test USPS Shipping Rates - Compare with PirateShip
 * This file tests the new accurate shipping rate calculations
 */

require_once 'includes/usps-shipping.php';

// Test scenarios - typical manga shipping
$testCases = [
    [
        'name' => 'Single Manga Book',
        'weight' => 6, // 6 ounces
        'dimensions' => '7.5x5.0x0.8', // inches
        'origin' => '90210', // Los Angeles
        'destinations' => [
            '10001' => 'New York, NY',
            '60601' => 'Chicago, IL', 
            '77001' => 'Houston, TX',
            '33101' => 'Miami, FL',
            '98101' => 'Seattle, WA',
            '30301' => 'Atlanta, GA',
            '80201' => 'Denver, CO'
        ]
    ],
    [
        'name' => 'Heavy Manga Set (3 books)',
        'weight' => 18, // 18 ounces (1.125 lbs)
        'dimensions' => '7.5x5.0x2.4', // inches
        'origin' => '90210',
        'destinations' => [
            '10001' => 'New York, NY',
            '60601' => 'Chicago, IL',
            '77001' => 'Houston, TX'
        ]
    ]
];

echo "<!DOCTYPE html>
<html>
<head>
    <title>USPS Shipping Rate Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-case { margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; }
        .rate-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .rate-table th, .rate-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .rate-table th { background-color: #f5f5f5; }
        .service-media { background-color: #e8f5e8; }
        .service-ground { background-color: #e8f0ff; }
        .service-priority { background-color: #fff0e8; }
        .debug-info { font-size: 0.8em; color: #666; margin-top: 5px; }
        .comparison { background-color: #fffacd; padding: 10px; margin-top: 10px; }
    </style>
</head>
<body>";

echo "<h1>USPS Shipping Rate Test - Updated 2024 Rates</h1>";
echo "<p><strong>Compare these rates with PirateShip for accuracy verification</strong></p>";

foreach ($testCases as $testCase) {
    echo "<div class='test-case'>";
    echo "<h2>{$testCase['name']}</h2>";
    echo "<p><strong>Weight:</strong> {$testCase['weight']} oz | <strong>Dimensions:</strong> {$testCase['dimensions']} inches</p>";
    
    // Create a mock product
    $product = [
        'weight' => $testCase['weight'],
        'dimensions' => $testCase['dimensions'],
        'shipping_option' => 'calculated'
    ];
    
    echo "<table class='rate-table'>";
    echo "<tr><th>Destination</th><th>Media Mail</th><th>Ground Advantage</th><th>Priority Mail</th></tr>";
    
    foreach ($testCase['destinations'] as $zip => $city) {
        echo "<tr>";
        echo "<td><strong>$city</strong><br>$zip</td>";
        
        $usps = new USPSShipping($testCase['origin']);
        
        // Test each service
        $services = ['Media', 'Ground', 'Priority'];
        foreach ($services as $service) {
            $result = $usps->calculateShipping($product, $zip, $service);
            $cssClass = 'service-' . strtolower($service);
            
            echo "<td class='$cssClass'>";
            echo "<strong>$" . number_format($result['rate'], 2) . "</strong><br>";
            echo "<small>{$result['days']}</small>";
            
            if (isset($result['debug'])) {
                echo "<div class='debug-info'>";
                echo "Zone: {$result['debug']['zone']} | ";
                echo "Weight: {$result['debug']['weight_lbs']} lbs | ";
                echo "Distance: {$result['debug']['distance_miles']} mi<br>";
                echo "Base: $" . number_format($result['debug']['base_rate'], 2);
                if ($result['debug']['size_surcharge'] > 0) {
                    echo " + Size: $" . number_format($result['debug']['size_surcharge'], 2);
                }
                echo "</div>";
            }
            echo "</td>";
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<div class='comparison'>";
    echo "<h4>🔍 Verification Instructions:</h4>";
    echo "<ol>";
    echo "<li>Go to <a href='https://ship.pirateship.com' target='_blank'>PirateShip.com</a></li>";
    echo "<li>Enter shipping from ZIP {$testCase['origin']} to any destination above</li>";
    echo "<li>Use weight: {$testCase['weight']} oz and dimensions: {$testCase['dimensions']} inches</li>";
    echo "<li>Compare the rates - they should be very close (within $0.50)</li>";
    echo "</ol>";
    echo "<p><strong>Note:</strong> Small differences may occur due to PirateShip's commercial pricing vs. retail rates.</p>";
    echo "</div>";
    
    echo "</div>";
}

echo "<div style='margin-top: 30px; padding: 15px; background-color: #f0f8ff; border: 1px solid #0066cc;'>";
echo "<h3>🚀 Next Steps:</h3>";
echo "<ul>";
echo "<li><strong>If rates look accurate:</strong> Delete this test file for security</li>";
echo "<li><strong>If rates are still off:</strong> Check the specific zones and weights that are incorrect</li>";
echo "<li><strong>For real USPS API rates:</strong> Set up your USPS API credentials in the .env file</li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
?> 