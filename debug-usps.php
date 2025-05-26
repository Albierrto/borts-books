<?php
/**
 * USPS API Debug - Check if credentials are loading properly
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>USPS API Debug</h1>";

// Check if .env file exists and load it manually
$envPath = __DIR__ . '/.env';
echo "<h2>Environment File Check</h2>";
echo "<p>.env file exists: " . (file_exists($envPath) ? "✅ Yes" : "❌ No") . "</p>";

if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    echo "<p>.env file size: " . strlen($envContent) . " bytes</p>";
    
    // Load .env file manually
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        if (strpos($line, '=') === false) continue; // Skip invalid lines
        list($name, $value) = array_map('trim', explode('=', $line, 2));
        $_ENV[$name] = $value;
    }
    error_log("USPS - Environment variables loaded from .env file");
} else {
    error_log("USPS - No .env file found at: $envPath");
} 