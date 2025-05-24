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

// Application configuration
define('SITE_NAME', 'Bort\'s Books');
define('SITE_URL', 'http://localhost:8000');
define('CURRENCY', 'USD');
define('TAX_RATE', 0.0875); // 8.75% tax
define('SHIPPING_RATE', 5.00); // $5.00 shipping
?> 