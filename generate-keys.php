<?php
// Security Key Generator
// Run this script to generate all required security keys for your .env file

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Security Key Generator</title></head><body>";
echo "<h1>Security Key Generator</h1>";
echo "<p>This script generates all the security keys needed for your .env file.</p>";

// Generate secure random keys
echo "<h2>Generated Security Keys:</h2>";

echo "<h3>1. SECURITY_KEY</h3>";
$securityKey = bin2hex(random_bytes(32));
echo "<code>SECURITY_KEY=" . $securityKey . "</code><br>";

echo "<h3>2. ENCRYPTION_KEY</h3>";
$encryptionKey = bin2hex(random_bytes(32));
echo "<code>ENCRYPTION_KEY=" . $encryptionKey . "</code><br>";

echo "<h3>3. JWT_SECRET</h3>";
$jwtSecret = bin2hex(random_bytes(32));
echo "<code>JWT_SECRET=" . $jwtSecret . "</code><br>";

echo "<h3>4. ADMIN_PASSWORD_HASH</h3>";
echo "<p>Choose your admin password:</p>";

// If password is submitted, generate the hash
if (isset($_POST['admin_password']) && !empty($_POST['admin_password'])) {
    $adminPassword = $_POST['admin_password'];
    $adminHash = password_hash($adminPassword, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);
    
    echo "<p><strong>✅ Password hash generated for: '$adminPassword'</strong></p>";
    echo "<code>ADMIN_PASSWORD_HASH=" . $adminHash . "</code><br>";
    
    echo "<h2>Complete .env Configuration</h2>";
    echo "<p>Copy and paste these lines into your .env file:</p>";
    echo "<textarea style='width: 100%; height: 150px; font-family: monospace;' readonly>";
    echo "# Security Configuration\n";
    echo "SECURITY_KEY=" . $securityKey . "\n";
    echo "ENCRYPTION_KEY=" . $encryptionKey . "\n";
    echo "JWT_SECRET=" . $jwtSecret . "\n";
    echo "ADMIN_PASSWORD_HASH=" . $adminHash . "\n";
    echo "ADMIN_USERNAME=admin\n";
    echo "ADMIN_EMAIL=admin@bortsbooks.com\n";
    echo "</textarea>";
    
    echo "<h3>📋 Instructions:</h3>";
    echo "<ol>";
    echo "<li>Copy the text from the box above</li>";
    echo "<li>Add it to your .env file</li>";
    echo "<li>Save the .env file</li>";
    echo "<li>Test your site at <a href='index.php'>index.php</a></li>";
    echo "<li><strong>Delete this generate-keys.php file for security!</strong></li>";
    echo "</ol>";
    
} else {
    // Show password form
    echo "<form method='POST'>";
    echo "<p>Enter your desired admin password:</p>";
    echo "<input type='text' name='admin_password' placeholder='Enter admin password' required style='padding: 8px; font-size: 14px;'>";
    echo "<button type='submit' style='padding: 8px 16px; font-size: 14px; margin-left: 10px;'>Generate Password Hash</button>";
    echo "</form>";
    
    echo "<p><em>Note: You can use any password you want. Common choices: admin123, password, or your own secure password.</em></p>";
}

echo "<h2>Alternative: Quick Development Setup</h2>";
echo "<p>If you want to quickly get your site working, you can instead just change your .env file to:</p>";
echo "<code>APP_ENV=development</code>";
echo "<p>This will use default development keys and get your site working immediately.</p>";

echo "<hr>";
echo "<p><strong>Security Note:</strong> Make sure to delete this generate-keys.php file after you're done using it!</p>";

echo "</body></html>";
?> 