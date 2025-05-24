<?php
/**
 * USPS API Test Page
 * Use this to verify your USPS API configuration
 */

// Load environment variables
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = array_map('trim', explode('=', $line, 2));
        $_ENV[$name] = $value;
    }
}

require_once 'includes/usps-shipping.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>USPS API Test - Bort's Books</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:2rem auto;padding:1rem;background:#f5f5f5;}";
echo ".success{color:#28a745;background:#d4edda;padding:1rem;border-radius:6px;margin:1rem 0;}";
echo ".error{color:#dc3545;background:#f8d7da;padding:1rem;border-radius:6px;margin:1rem 0;}";
echo ".info{color:#0c5460;background:#d1ecf1;padding:1rem;border-radius:6px;margin:1rem 0;}";
echo ".test-section{background:#fff;padding:1.5rem;border-radius:8px;margin:1rem 0;box-shadow:0 2px 4px rgba(0,0,0,0.1);}";
echo "table{width:100%;border-collapse:collapse;margin:1rem 0;}";
echo "th,td{padding:0.5rem;border:1px solid #ddd;text-align:left;}";
echo "th{background:#f8f9fa;}";
echo ".rate-test{margin-top:2rem;padding:1rem;background:#f8f9fa;border-radius:6px;}";
echo "</style></head><body>";

echo "<h1>USPS API Configuration Test</h1>";
echo "<p>This page tests your USPS API integration. <strong>Delete this file after testing!</strong></p>";

// Check environment variables
echo "<div class='test-section'>";
echo "<h2>Environment Variables</h2>";

$uspsVars = [
    'USPS_CONSUMER_KEY' => $_ENV['USPS_CONSUMER_KEY'] ?? 'NOT SET',
    'USPS_CONSUMER_SECRET' => $_ENV['USPS_CONSUMER_SECRET'] ?? 'NOT SET',
    'USPS_ORIGIN_ZIP' => $_ENV['USPS_ORIGIN_ZIP'] ?? 'NOT SET'
];

echo "<table>";
echo "<tr><th>Variable</th><th>Status</th><th>Value</th></tr>";
foreach ($uspsVars as $var => $value) {
    $status = ($value !== 'NOT SET' && !empty($value)) ? '✅ Set' : '❌ Missing';
    $displayValue = $var === 'USPS_CONSUMER_SECRET' ? 
        ($value !== 'NOT SET' ? '***hidden***' : 'NOT SET') : 
        $value;
    echo "<tr><td>$var</td><td>$status</td><td>$displayValue</td></tr>";
}
echo "</table>";

if ($uspsVars['USPS_CONSUMER_KEY'] === 'NOT SET' || $uspsVars['USPS_CONSUMER_SECRET'] === 'NOT SET') {
    echo "<div class='error'>";
    echo "<strong>Configuration Required:</strong><br>";
    echo "1. Add your USPS API credentials to your .env file:<br>";
    echo "<code>USPS_CONSUMER_KEY=your_consumer_key_here<br>";
    echo "USPS_CONSUMER_SECRET=your_consumer_secret_here<br>";
    echo "USPS_ORIGIN_ZIP=your_business_zip_code</code><br><br>";
    echo "2. Get your credentials from: <a href='https://developer.usps.com/' target='_blank'>USPS Developer Portal</a>";
    echo "</div>";
}
echo "</div>";

// Test USPS connection
echo "<div class='test-section'>";
echo "<h2>USPS API Connection Test</h2>";

try {
    $usps = new USPSShipping();
    $connectionTest = $usps->testConnection();
    
    if ($connectionTest['success']) {
        echo "<div class='success'>";
        echo "<strong>✅ " . $connectionTest['message'] . "</strong><br>";
        if (isset($connectionTest['access_token'])) {
            echo "Access Token: " . $connectionTest['access_token'];
        }
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<strong>❌ " . $connectionTest['message'] . "</strong>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>❌ Connection Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "</div>";

// Test shipping rate calculation
if (isset($connectionTest) && $connectionTest['success']) {
    echo "<div class='test-section'>";
    echo "<h2>Shipping Rate Test</h2>";
    
    // Create a sample product (typical manga)
    $testProduct = [
        'id' => 1,
        'title' => 'Test Manga',
        'weight' => 6.0, // 6 ounces
        'dimensions' => '7.5x5.0x0.8', // inches
        'shipping_option' => 'calculated'
    ];
    
    $testZip = '10001'; // New York
    
    echo "<div class='info'>";
    echo "<strong>Test Product:</strong><br>";
    echo "• Weight: {$testProduct['weight']} oz<br>";
    echo "• Dimensions: {$testProduct['dimensions']} inches<br>";
    echo "• Destination: ZIP $testZip<br>";
    echo "</div>";
    
    echo "<h3>Shipping Options:</h3>";
    
    try {
        $options = $usps->getShippingOptions($testProduct, $testZip);
        
        echo "<table>";
        echo "<tr><th>Service</th><th>Rate</th><th>Delivery Time</th><th>Source</th></tr>";
        
        foreach ($options as $option) {
            $rate = $option['rate'] == 0 ? 'FREE' : '$' . number_format($option['rate'], 2);
            $source = $option['api_source'] ?? 'Estimated';
            echo "<tr>";
            echo "<td>{$option['service']}</td>";
            echo "<td>$rate</td>";
            echo "<td>{$option['days']}</td>";
            echo "<td>$source</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<strong>Rate Calculation Error:</strong> " . $e->getMessage();
        echo "</div>";
    }
    
    echo "</div>";
    
    // Interactive rate calculator
    echo "<div class='test-section'>";
    echo "<h2>Interactive Rate Calculator</h2>";
    echo "<div class='rate-test'>";
    echo "<label for='test-zip'>Enter ZIP Code to Test:</label><br>";
    echo "<input type='text' id='test-zip' placeholder='e.g. 90210' style='padding:0.5rem;margin:0.5rem 0;'>";
    echo "<button onclick='testShipping()' style='padding:0.5rem 1rem;background:#007bff;color:white;border:none;border-radius:4px;cursor:pointer;'>Calculate Rates</button>";
    echo "<div id='rate-results' style='margin-top:1rem;'></div>";
    echo "</div>";
    echo "</div>";
    
    echo "<script>";
    echo "function testShipping() {";
    echo "  const zip = document.getElementById('test-zip').value;";
    echo "  const results = document.getElementById('rate-results');";
    echo "  if (!zip) { alert('Please enter a ZIP code'); return; }";
    echo "  results.innerHTML = 'Calculating...';";
    echo "  fetch('/includes/usps-shipping.php', {";
    echo "    method: 'POST',";
    echo "    headers: {'Content-Type': 'application/x-www-form-urlencoded'},";
    echo "    body: 'action=get_shipping_quote&product_id=1&zip=' + zip";
    echo "  })";
    echo "  .then(response => response.json())";
    echo "  .then(data => {";
    echo "    if (data.success && data.options) {";
    echo "      let html = '<h4>Rates to ' + zip + ':</h4><table><tr><th>Service</th><th>Rate</th><th>Delivery</th></tr>';";
    echo "      data.options.forEach(option => {";
    echo "        const rate = option.rate === 0 ? 'FREE' : '$' + option.rate.toFixed(2);";
    echo "        html += `<tr><td>\${option.service}</td><td>\${rate}</td><td>\${option.days}</td></tr>`;";
    echo "      });";
    echo "      html += '</table>';";
    echo "      results.innerHTML = html;";
    echo "    } else {";
    echo "      results.innerHTML = '<div class=\"error\">Error: ' + (data.error || 'Unknown error') + '</div>';";
    echo "    }";
    echo "  })";
    echo "  .catch(error => {";
    echo "    results.innerHTML = '<div class=\"error\">Request failed: ' + error + '</div>';";
    echo "  });";
    echo "}";
    echo "</script>";
}

echo "<div class='test-section'>";
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>If the connection test failed, verify your USPS API credentials in the .env file</li>";
echo "<li>If successful, the shipping calculator will now use real USPS rates when available</li>";
echo "<li>Test the shipping calculator on your product pages</li>";
echo "<li><strong>Delete this test file</strong> when you're done testing</li>";
echo "</ol>";

echo "<h3>Getting USPS API Keys:</h3>";
echo "<ol>";
echo "<li>Visit <a href='https://developer.usps.com/' target='_blank'>USPS Developer Portal</a></li>";
echo "<li>Create a developer account</li>";
echo "<li>Create a new application to get your Consumer Key and Secret</li>";
echo "<li>Add them to your .env file</li>";
echo "</ol>";
echo "</div>";

echo "<p><a href='index.php'>← Back to Home</a> | <a href='pages/shop.php'>Test on Shop Page →</a></p>";

echo "</body></html>";
?> 