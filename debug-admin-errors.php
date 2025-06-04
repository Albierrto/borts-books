<?php
// Enable error reporting to catch any PHP errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>Admin Error Debug</h1>";

// Start session
session_start();
echo "<h2>1. Session Check</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Session status: " . session_status() . "<br>";
echo "Session variables:<br>";
foreach ($_SESSION as $key => $value) {
    if (is_array($value)) {
        echo "• $key: " . print_r($value, true) . "<br>";
    } else {
        echo "• $key: " . htmlspecialchars($value) . "<br>";
    }
}

echo "<h2>2. File Inclusion Test</h2>";
try {
    echo "Testing includes...<br>";
    require_once 'includes/security.php';
    echo "✅ Security loaded<br>";
    
    require_once 'includes/admin-auth.php';
    echo "✅ Admin auth loaded<br>";
    
    echo "✅ All includes successful<br>";
} catch (Exception $e) {
    echo "❌ Include error: " . $e->getMessage() . "<br>";
}

echo "<h2>3. Authentication Test</h2>";
try {
    if (function_exists('is_admin_logged_in')) {
        $isLoggedIn = is_admin_logged_in();
        echo "Is admin logged in: " . ($isLoggedIn ? 'YES' : 'NO') . "<br>";
        
        if (!$isLoggedIn) {
            echo "❌ Not logged in - this would cause redirect<br>";
        } else {
            echo "✅ Logged in successfully<br>";
        }
    } else {
        echo "❌ is_admin_logged_in function not found<br>";
    }
} catch (Exception $e) {
    echo "❌ Auth check error: " . $e->getMessage() . "<br>";
}

echo "<h2>4. Database Connection Test</h2>";
try {
    // Load environment variables
    $envPath = '.env';
    if (file_exists($envPath)) {
        echo "✅ .env file found<br>";
        $envContent = file_get_contents($envPath);
        $lines = explode("\n", $envContent);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = array_map('trim', explode('=', $line, 2));
                $value = trim($value, '"\'');
                $_ENV[$name] = $value;
            }
        }
        echo "✅ Environment variables loaded<br>";
    } else {
        echo "❌ .env file not found<br>";
    }
    
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? '';
    $user = $_ENV['DB_USER'] ?? '';
    $pass = $_ENV['DB_PASS'] ?? '';
    $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
    $port = $_ENV['DB_PORT'] ?? 3306;
    
    echo "Database config: $user@$host:$port/$dbname<br>";
    
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "✅ Database connection successful<br>";
    
    // Test the query that dashboard uses
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $count = $stmt->fetchColumn();
    echo "✅ Products table query successful: $count records<br>";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<h2>5. Admin Dashboard Test</h2>";
echo "<p>Now let's try to load the admin dashboard and catch any errors:</p>";

// Capture output and errors from dashboard
ob_start();
try {
    include 'pages/admin-dashboard.php';
    $output = ob_get_contents();
    ob_end_clean();
    
    if (empty(trim($output))) {
        echo "❌ Dashboard produced no output (blank page)<br>";
    } else {
        echo "✅ Dashboard loaded successfully (" . strlen($output) . " characters)<br>";
        echo "<h3>Dashboard Preview (first 500 chars):</h3>";
        echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "...</pre>";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Dashboard error: " . $e->getMessage() . "<br>";
} catch (Error $e) {
    ob_end_clean();
    echo "❌ Dashboard fatal error: " . $e->getMessage() . "<br>";
}

echo "<h2>6. Direct Dashboard Link</h2>";
echo '<a href="pages/admin-dashboard.php" target="_blank">Test Admin Dashboard</a><br>';
echo '<a href="pages/admin-inventory.php" target="_blank">Test Admin Inventory</a><br>';

?> 