<?php
// Current Debug Page - Check what's happening now
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Current Debug</title></head><body>";
echo "<h1>Current Configuration Debug</h1>";
echo "<p>Let's see what's happening now that security keys have been added.</p>";

// Step 1: Test basic config loading
echo "<h2>1. Basic Configuration Test</h2>";
try {
    define('INCLUDED_FROM_APP', true);
    
    // Load .env manually first
    $envPath = __DIR__ . '/.env';
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
    
    echo "Environment loaded manually<br>";
    echo "APP_ENV: " . ($_ENV['APP_ENV'] ?? 'not set') . "<br>";
    
    // Now try to include config.php
    ob_start();
    include 'includes/config.php';
    $configOutput = ob_get_clean();
    
    echo "✅ config.php loaded successfully<br>";
    if (!empty($configOutput)) {
        echo "Config output: <pre>" . htmlspecialchars($configOutput) . "</pre>";
    }
    
} catch (Throwable $e) {
    echo "❌ Config failed: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

// Step 2: Test security.php
echo "<h2>2. Security.php Test</h2>";
try {
    ob_start();
    include 'includes/security.php';
    $securityOutput = ob_get_clean();
    
    echo "✅ security.php loaded successfully<br>";
    if (!empty($securityOutput)) {
        echo "Security output: <pre>" . htmlspecialchars($securityOutput) . "</pre>";
    }
    
} catch (Throwable $e) {
    echo "❌ Security failed: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

// Step 3: Test db.php
echo "<h2>3. Database Connection Test</h2>";
try {
    ob_start();
    include 'includes/db.php';
    $dbOutput = ob_get_clean();
    
    echo "✅ db.php loaded successfully<br>";
    if (!empty($dbOutput)) {
        echo "DB output: <pre>" . htmlspecialchars($dbOutput) . "</pre>";
    }
    
} catch (Throwable $e) {
    echo "❌ Database failed: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

// Step 4: Test a simple page include
echo "<h2>4. Index.php Loading Test</h2>";
try {
    ob_start();
    
    // Try to capture what happens when loading index.php
    $indexContent = file_get_contents(__DIR__ . '/index.php');
    echo "Index.php file size: " . strlen($indexContent) . " bytes<br>";
    
    // Try to execute it safely
    ob_start();
    include 'index.php';
    $indexOutput = ob_get_clean();
    
    echo "✅ Index.php executed<br>";
    echo "Output length: " . strlen($indexOutput) . " bytes<br>";
    
    if (empty($indexOutput)) {
        echo "❌ Index.php produced no output (blank page)<br>";
    } else {
        echo "✅ Index.php produced output<br>";
        // Show first 500 chars of output
        echo "First 500 chars: <pre>" . htmlspecialchars(substr($indexOutput, 0, 500)) . "</pre>";
    }
    
} catch (Throwable $e) {
    echo "❌ Index.php failed: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

// Step 5: Check if constants are defined
echo "<h2>5. Constants Check</h2>";
$constants = ['APP_NAME', 'APP_ENV', 'SECURITY_KEY', 'ENCRYPTION_KEY', 'ADMIN_PASSWORD_HASH'];
foreach ($constants as $const) {
    if (defined($const)) {
        $value = constant($const);
        if (in_array($const, ['SECURITY_KEY', 'ENCRYPTION_KEY', 'ADMIN_PASSWORD_HASH'])) {
            echo "$const: ✅ SET (" . strlen($value) . " chars)<br>";
        } else {
            echo "$const: $value<br>";
        }
    } else {
        echo "❌ $const not defined<br>";
    }
}

echo "<h2>6. PHP Error Log Check</h2>";
echo "Check your server's PHP error log for any fatal errors.<br>";
echo "Common locations: /var/log/php_errors.log or in your hosting control panel.<br>";

echo "<p><a href='index.php'>Try Index Page</a> | <a href='simple-debug.php'>Back to Simple Debug</a></p>";

echo "</body></html>";
?> 