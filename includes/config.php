<?php
// Load environment variables from .env
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = array_map('trim', explode('=', $line, 2));
        $_ENV[$name] = $value;
        putenv("$name=$value");
    }
}

// Automatically detect site URL based on current request
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$auto_site_url = $protocol . $host;

// Use auto-detected URL if not localhost, otherwise fall back to environment variable
if (strpos($host, 'localhost') === false && strpos($host, '127.0.0.1') === false) {
    $site_url = $auto_site_url;
} else {
    $site_url = $_ENV['SITE_URL'] ?? $auto_site_url;
}

// Application configuration
define('SITE_NAME', 'Bort\'s Books');
define('SITE_URL', $site_url);
define('CURRENCY', 'USD');
define('TAX_RATE', 0.0875); // 8.75% tax
define('SHIPPING_RATE', 5.00); // $5.00 shipping
?> 