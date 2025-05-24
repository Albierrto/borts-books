<?php
// Simple database connection test for live server
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Connection Test</h1>";
echo "<p>Testing database connection on live server...</p>";

// Try to load environment variables
$envPath = __DIR__ . '/.env';
$env_loaded = false;
$env_vars = [];

if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = array_map('trim', explode('=', $line, 2));
        $env_vars[$name] = $value;
        $_ENV[$name] = $value;
    }
    $env_loaded = true;
    echo "<p style='color: green;'>✅ .env file found and loaded</p>";
} else {
    echo "<p style='color: red;'>❌ .env file not found at: " . htmlspecialchars($envPath) . "</p>";
}

echo "<h2>Environment Variables Status:</h2>";
echo "<ul>";

$required_vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
foreach ($required_vars as $var) {
    $value = $_ENV[$var] ?? 'NOT SET';
    $display_value = $var === 'DB_PASS' ? (empty($value) || $value === 'NOT SET' ? 'NOT SET' : '***hidden***') : $value;
    $status = ($value !== 'NOT SET' && !empty($value)) ? '✅' : '❌';
    echo "<li>$var: $status $display_value</li>";
}
echo "</ul>";

// Test database connection
if ($env_loaded) {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $db   = $_ENV['DB_NAME'] ?? '';
    $user = $_ENV['DB_USER'] ?? '';
    $pass = $_ENV['DB_PASS'] ?? '';
    $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

    echo "<h2>Connection Test:</h2>";
    echo "<p>Attempting to connect with:</p>";
    echo "<ul>";
    echo "<li>Host: " . htmlspecialchars($host) . "</li>";
    echo "<li>Database: " . htmlspecialchars($db) . "</li>";
    echo "<li>User: " . htmlspecialchars($user) . "</li>";
    echo "<li>Password: " . (empty($pass) ? '(empty)' : '***provided***') . "</li>";
    echo "</ul>";

    try {
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, $user, $pass, $options);
        echo "<p style='color: green; font-weight: bold;'>✅ DATABASE CONNECTION SUCCESSFUL!</p>";
        
        // Test a simple query
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
        $result = $stmt->fetch();
        echo "<p style='color: green;'>✅ Query test successful - Found " . $result['count'] . " products in database</p>";
        
    } catch (PDOException $e) {
        echo "<p style='color: red; font-weight: bold;'>❌ DATABASE CONNECTION FAILED!</p>";
        echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        
        // Common solutions
        echo "<h3>Common Solutions:</h3>";
        echo "<ul>";
        echo "<li>Check that the database name is correct (usually starts with your cPanel username)</li>";
        echo "<li>Verify the database user has the correct permissions</li>";
        echo "<li>Reset the database password in cPanel</li>";
        echo "<li>Make sure the database exists in cPanel MySQL Databases</li>";
        echo "</ul>";
    }
} else {
    echo "<p style='color: red;'>❌ Cannot test connection - .env file missing or invalid</p>";
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Log into your cPanel</li>";
echo "<li>Go to 'MySQL Databases'</li>";
echo "<li>Check/update your database credentials in the .env file</li>";
echo "<li>Make sure the database user has ALL PRIVILEGES on the database</li>";
echo "<li>If needed, reset the database password</li>";
echo "</ol>";

echo "<p><a href='cart.php'>← Test Cart Page</a> | <a href='checkout.php'>Test Checkout Page →</a></p>";
?> 