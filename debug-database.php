<?php
// Database Connection Debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Database Debug</title></head><body>";
echo "<h1>Database Connection Debug</h1>";

// Step 1: Check if includes work
echo "<h2>1. Include Test</h2>";
try {
    require_once 'includes/config.php';
    echo "✅ Config loaded<br>";
} catch (Throwable $e) {
    echo "❌ Config failed: " . $e->getMessage() . "<br>";
}

// Step 2: Check environment
echo "<h2>2. Environment Check</h2>";
$envFile = '.env';
if (file_exists($envFile)) {
    echo "✅ .env file exists<br>";
    $envSize = filesize($envFile);
    echo "Size: $envSize bytes<br>";
    
    // Try to read first few lines safely
    $handle = fopen($envFile, 'r');
    $lineCount = 0;
    while (($line = fgets($handle)) !== false && $lineCount < 5) {
        $line = trim($line);
        if (!empty($line) && strpos($line, '#') !== 0) {
            $parts = explode('=', $line, 2);
            if (count($parts) >= 2) {
                $key = $parts[0];
                $hasValue = !empty(trim($parts[1], '"\''));
                echo "• $key: " . ($hasValue ? "✅ Set" : "❌ Empty") . "<br>";
            }
        }
        $lineCount++;
    }
    fclose($handle);
} else {
    echo "❌ .env file not found<br>";
}

// Step 3: Test database connection manually
echo "<h2>3. Database Connection Test</h2>";
try {
    // Load env manually
    $envContent = file_get_contents('.env');
    $lines = explode("\n", $envContent);
    $env = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = array_map('trim', explode('=', $line, 2));
            $value = trim($value, '"\'');
            $env[$name] = $value;
        }
    }
    
    echo "DB Config:<br>";
    echo "• Host: " . ($env['DB_HOST'] ?? 'localhost') . "<br>";
    echo "• Database: " . ($env['DB_NAME'] ?? 'Not set') . "<br>";
    echo "• User: " . ($env['DB_USER'] ?? 'Not set') . "<br>";
    echo "• Password: " . (isset($env['DB_PASS']) && !empty($env['DB_PASS']) ? 'Set' : 'Not set') . "<br>";
    
    $host = $env['DB_HOST'] ?? 'localhost';
    $dbname = $env['DB_NAME'] ?? '';
    $user = $env['DB_USER'] ?? '';
    $pass = $env['DB_PASS'] ?? '';
    $charset = $env['DB_CHARSET'] ?? 'utf8mb4';
    $port = $env['DB_PORT'] ?? 3306;
    
    if (empty($dbname) || empty($user)) {
        echo "❌ Database credentials missing<br>";
    } else {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
        echo "DSN: $dsn<br>";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $testDb = new PDO($dsn, $user, $pass, $options);
        echo "✅ Database connection successful!<br>";
        
        // Test a simple query
        $stmt = $testDb->query("SELECT 1 as test");
        $result = $stmt->fetch();
        echo "✅ Query test successful: " . $result['test'] . "<br>";
        
        // Check products table
        $stmt = $testDb->query("SHOW TABLES LIKE 'products'");
        if ($stmt->fetch()) {
            echo "✅ Products table exists<br>";
            
            $stmt = $testDb->query("SELECT COUNT(*) as count FROM products");
            $result = $stmt->fetch();
            echo "Products count: " . $result['count'] . "<br>";
        } else {
            echo "❌ Products table not found<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
} catch (Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Step 4: Test includes/db.php
echo "<h2>4. DB Include Test</h2>";
try {
    global $db;
    require_once 'includes/db.php';
    
    if (isset($db) && $db instanceof PDO) {
        echo "✅ includes/db.php loaded successfully<br>";
        echo "✅ \$db variable is a PDO instance<br>";
        
        // Test with the global $db
        $stmt = $db->query("SELECT 1 as test");
        $result = $stmt->fetch();
        echo "✅ Query with \$db successful: " . $result['test'] . "<br>";
    } else {
        echo "❌ \$db variable is not set or not a PDO instance<br>";
        echo "Type: " . gettype($db ?? null) . "<br>";
    }
} catch (Throwable $e) {
    echo "❌ includes/db.php failed: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

echo "</body></html>";
?> 