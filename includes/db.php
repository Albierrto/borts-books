<?php
// Load environment variables from .env
$envPath = dirname(__DIR__) . '/.env';
if (!file_exists($envPath)) {
    die('ERROR: .env file not found at ' . htmlspecialchars($envPath));
}
$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    list($name, $value) = array_map('trim', explode('=', $line, 2));
    $_ENV[$name] = $value;
}

// Database configuration
$host = $_ENV['DB_HOST'] ?? 'localhost';
$db   = $_ENV['DB_NAME'] ?? '';
$user = $_ENV['DB_USER'] ?? '';
$pass = $_ENV['DB_PASS'] ?? '';
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $db = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

// Add ebay_item_id column if it doesn't exist
try {
    $db->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS ebay_item_id VARCHAR(255)");
} catch (PDOException $e) {
    // Column might already exist, continue
}
