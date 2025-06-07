<?php
// SSL Status Checker
echo "<h1>SSL Certificate Status Check</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .warning { color: orange; }</style>";

echo "<h2>Current Connection Status:</h2>";

// Check if currently on HTTPS
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    echo "<p class='success'>✅ Currently connected via HTTPS</p>";
} else {
    echo "<p class='error'>❌ Currently connected via HTTP (not secure)</p>";
}

// Show current URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$current_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
echo "<p><strong>Current URL:</strong> $current_url</p>";

// Test HTTPS version
$https_url = 'https://' . $_SERVER['HTTP_HOST'] . '/ssl-check.php';
echo "<p><strong>Test HTTPS URL:</strong> <a href='$https_url' target='_blank'>$https_url</a></p>";

echo "<h2>SSL Certificate Information:</h2>";

// Get SSL certificate info
$hostname = $_SERVER['HTTP_HOST'];
$context = stream_context_create([
    "ssl" => [
        "capture_peer_cert" => true,
        "verify_peer" => false,
        "verify_peer_name" => false,
    ]
]);

$socket = @stream_socket_client(
    "ssl://$hostname:443",
    $errno,
    $errstr,
    30,
    STREAM_CLIENT_CONNECT,
    $context
);

if ($socket) {
    echo "<p class='success'>✅ SSL connection successful</p>";
    
    $cert = stream_context_get_params($socket);
    if (isset($cert['options']['ssl']['peer_certificate'])) {
        $cert_info = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
        
        echo "<h3>Certificate Details:</h3>";
        echo "<ul>";
        echo "<li><strong>Subject:</strong> " . ($cert_info['subject']['CN'] ?? 'N/A') . "</li>";
        echo "<li><strong>Issuer:</strong> " . ($cert_info['issuer']['CN'] ?? 'N/A') . "</li>";
        echo "<li><strong>Valid From:</strong> " . date('Y-m-d H:i:s', $cert_info['validFrom_time_t']) . "</li>";
        echo "<li><strong>Valid Until:</strong> " . date('Y-m-d H:i:s', $cert_info['validTo_time_t']) . "</li>";
        
        // Check if certificate is still valid
        $now = time();
        if ($now < $cert_info['validFrom_time_t']) {
            echo "<li class='error'>❌ Certificate is not yet valid</li>";
        } elseif ($now > $cert_info['validTo_time_t']) {
            echo "<li class='error'>❌ Certificate has expired</li>";
        } else {
            $days_until_expiry = floor(($cert_info['validTo_time_t'] - $now) / (60 * 60 * 24));
            if ($days_until_expiry < 30) {
                echo "<li class='warning'>⚠️ Certificate expires in $days_until_expiry days</li>";
            } else {
                echo "<li class='success'>✅ Certificate is valid ($days_until_expiry days remaining)</li>";
            }
        }
        echo "</ul>";
    }
    fclose($socket);
} else {
    echo "<p class='error'>❌ Could not establish SSL connection</p>";
    echo "<p>Error: $errstr ($errno)</p>";
}

echo "<h2>Next Steps:</h2>";

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    echo "<p class='success'>✅ SSL is working! You should add HTTPS redirects to your .htaccess file.</p>";
    echo "<p><a href='setup-https-redirects.php'>Click here to set up automatic HTTPS redirects</a></p>";
} else {
    echo "<p class='warning'>⚠️ You need to:</p>";
    echo "<ul>";
    echo "<li>1. Enable SSL certificate in cPanel (if not already done)</li>";
    echo "<li>2. Test the HTTPS URL above</li>";
    echo "<li>3. Add HTTPS redirects to .htaccess</li>";
    echo "</ul>";
    
    echo "<h3>cPanel SSL Setup Instructions:</h3>";
    echo "<ol>";
    echo "<li>Log into your cPanel</li>";
    echo "<li>Look for 'SSL/TLS' or 'Let's Encrypt' in the Security section</li>";
    echo "<li>Enable 'Force HTTPS Redirect' if available</li>";
    echo "<li>Or generate a Let's Encrypt certificate (usually free)</li>";
    echo "</ol>";
}

echo "<hr>";
echo "<p><a href='index.php'>Back to Home</a> | <a href='" . $https_url . "'>Test HTTPS Version</a></p>";
?> 