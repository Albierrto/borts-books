<?php
// Debug Configuration Page
// This page will help identify configuration issues

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Configuration Debug Page</h1>";
echo "<p>This page will help identify configuration issues.</p>";

// Step 1: Check if .env file exists
$envPath = __DIR__ . '/.env';
echo "<h2>1. Environment File Check</h2>";
if (file_exists($envPath)) {
    echo "✅ .env file found at: " . $envPath . "<br>";
    
    // Read .env file safely
    $envContent = file_get_contents($envPath);
    if ($envContent !== false) {
        echo "✅ .env file readable<br>";
        
        // Parse environment variables (but don't show sensitive values)
        $lines = explode("\n", $envContent);
        $envVars = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            
            if (strpos($line, '=') !== false) {
                list($name, $value) = array_map('trim', explode('=', $line, 2));
                $value = trim($value, '"\'');
                
                // Hide sensitive values
                if (strpos($name, 'PASS') !== false || strpos($name, 'SECRET') !== false || strpos($name, 'KEY') !== false) {
                    $envVars[$name] = strlen($value) > 0 ? '[SET - ' . strlen($value) . ' chars]' : '[EMPTY]';
                } else {
                    $envVars[$name] = $value;
                }
            }
        }
        
        echo "<h3>Environment Variables Found:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Variable</th><th>Value</th></tr>";
        foreach ($envVars as $name => $value) {
            echo "<tr><td>$name</td><td>$value</td></tr>";
        }
        echo "</table>";
        
    } else {
        echo "❌ .env file not readable<br>";
    }
} else {
    echo "❌ .env file not found at: " . $envPath . "<br>";
}

// Step 2: Try to load config
echo "<h2>2. Configuration Loading Test</h2>";
try {
    // Manually set INCLUDED_FROM_APP to bypass the direct access check
    define('INCLUDED_FROM_APP', true);
    
    // Load environment variables manually
    if (file_exists($envPath)) {
        $envContent = file_get_contents($envPath);
        if ($envContent !== false) {
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
        }
    }
    
    // Test APP_ENV
    $app_env = $_ENV['APP_ENV'] ?? 'development';
    echo "APP_ENV: " . $app_env . "<br>";
    
    // Test required security keys
    $security_key = $_ENV['SECURITY_KEY'] ?? '';
    $encryption_key = $_ENV['ENCRYPTION_KEY'] ?? '';
    $admin_password_hash = $_ENV['ADMIN_PASSWORD_HASH'] ?? '';
    
    echo "<h3>Security Configuration Status:</h3>";
    echo "SECURITY_KEY: " . (strlen($security_key) > 0 ? '✅ SET (' . strlen($security_key) . ' chars)' : '❌ MISSING') . "<br>";
    echo "ENCRYPTION_KEY: " . (strlen($encryption_key) > 0 ? '✅ SET (' . strlen($encryption_key) . ' chars)' : '❌ MISSING') . "<br>";
    echo "ADMIN_PASSWORD_HASH: " . (strlen($admin_password_hash) > 0 ? '✅ SET (' . strlen($admin_password_hash) . ' chars)' : '❌ MISSING') . "<br>";
    
    // Test if we're in production mode and missing required keys
    if ($app_env === 'production') {
        echo "<h3>Production Mode Requirements:</h3>";
        $errors = [];
        if (empty($security_key)) $errors[] = 'SECURITY_KEY is required in production';
        if (empty($encryption_key)) $errors[] = 'ENCRYPTION_KEY is required in production';
        if (empty($admin_password_hash)) $errors[] = 'ADMIN_PASSWORD_HASH is required in production';
        
        if (!empty($errors)) {
            echo "❌ <strong>ERRORS FOUND:</strong><br>";
            foreach ($errors as $error) {
                echo "• " . $error . "<br>";
            }
        } else {
            echo "✅ All production requirements met<br>";
        }
    }
    
    echo "✅ Basic configuration parsing successful<br>";
    
} catch (Exception $e) {
    echo "❌ Configuration loading failed: " . $e->getMessage() . "<br>";
}

// Step 3: Test database connection
echo "<h2>3. Database Connection Test</h2>";
try {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? '';
    $user = $_ENV['DB_USER'] ?? '';
    $pass = $_ENV['DB_PASS'] ?? '';
    
    echo "Database Host: " . $host . "<br>";
    echo "Database Name: " . $dbname . "<br>";
    echo "Database User: " . $user . "<br>";
    echo "Database Password: " . (strlen($pass) > 0 ? '[SET - ' . strlen($pass) . ' chars]' : '[EMPTY]') . "<br>";
    
    if (empty($dbname) || empty($user)) {
        echo "❌ Database credentials incomplete<br>";
    } else {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        
        echo "Attempting connection...<br>";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        echo "✅ Database connection successful!<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Step 4: Suggested fixes
echo "<h2>4. Suggested Fixes</h2>";

if ($app_env === 'production' && (empty($security_key) || empty($encryption_key) || empty($admin_password_hash))) {
    echo "<h3>Option 1: Set to Development Mode (Quick Fix)</h3>";
    echo "Add this to your .env file:<br>";
    echo "<code>APP_ENV=development</code><br><br>";
    
    echo "<h3>Option 2: Add Required Production Keys</h3>";
    echo "Add these to your .env file:<br>";
    echo "<code>";
    if (empty($security_key)) {
        echo "SECURITY_KEY=" . bin2hex(random_bytes(32)) . "<br>";
    }
    if (empty($encryption_key)) {
        echo "ENCRYPTION_KEY=" . bin2hex(random_bytes(32)) . "<br>";
    }
    if (empty($admin_password_hash)) {
        echo "ADMIN_PASSWORD_HASH=" . password_hash('admin123', PASSWORD_ARGON2ID) . "<br>";
    }
    echo "</code>";
}

echo "<p><strong>After making changes to your .env file, refresh this page to test again.</strong></p>";
echo "<p><a href='index.php'>Test Home Page</a> | <a href='pages/admin-login.php'>Test Admin Login</a></p>";
?> 