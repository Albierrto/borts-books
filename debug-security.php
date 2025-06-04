<?php
// Security Debug Page - Isolate the security.php issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Security Debug</title></head><body>";
echo "<h1>Security.php Debug</h1>";

// Step 1: Define required constants first
echo "<h2>1. Setup Constants</h2>";
if (!defined('INCLUDED_FROM_APP')) {
    define('INCLUDED_FROM_APP', true);
    echo "✅ INCLUDED_FROM_APP defined<br>";
}

// Step 2: Load environment variables manually
echo "<h2>2. Load Environment</h2>";
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
        echo "✅ Environment loaded<br>";
    }
}

// Step 3: Test config.php first
echo "<h2>3. Load Config.php</h2>";
try {
    require_once 'includes/config.php';
    echo "✅ Config loaded successfully<br>";
    echo "APP_ENV: " . (defined('APP_ENV') ? APP_ENV : 'not defined') . "<br>";
} catch (Throwable $e) {
    echo "❌ Config failed: " . $e->getMessage() . "<br>";
}

// Step 4: Test security-monitoring.php separately
echo "<h2>4. Test Security Monitoring</h2>";
try {
    // First check if file exists
    if (file_exists('includes/security-monitoring.php')) {
        echo "✅ Security monitoring file exists<br>";
        
        // Try to include it
        ob_start();
        require_once 'includes/security-monitoring.php';
        $output = ob_get_clean();
        
        echo "✅ Security monitoring loaded<br>";
        if (!empty($output)) {
            echo "Output: <pre>" . htmlspecialchars($output) . "</pre>";
        }
    } else {
        echo "❌ Security monitoring file not found<br>";
    }
} catch (Throwable $e) {
    echo "❌ Security monitoring failed: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

// Step 5: Test security.php WITHOUT the monitoring include
echo "<h2>5. Test Security.php (Modified)</h2>";
try {
    // Read the security.php content
    $securityContent = file_get_contents('includes/security.php');
    
    // Remove the security-monitoring.php include line
    $modifiedContent = str_replace(
        "require_once __DIR__ . '/security-monitoring.php';",
        "// require_once __DIR__ . '/security-monitoring.php'; // Temporarily disabled",
        $securityContent
    );
    
    // Write to a temporary file
    file_put_contents('temp-security.php', $modifiedContent);
    
    // Try to include the modified version
    ob_start();
    include 'temp-security.php';
    $output = ob_get_clean();
    
    echo "✅ Modified security.php loaded successfully<br>";
    if (!empty($output)) {
        echo "Output: <pre>" . htmlspecialchars($output) . "</pre>";
    }
    
    // Clean up
    unlink('temp-security.php');
    
} catch (Throwable $e) {
    echo "❌ Modified security.php failed: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    
    // Clean up on error
    if (file_exists('temp-security.php')) {
        unlink('temp-security.php');
    }
}

// Step 6: Test database connection for monitoring
echo "<h2>6. Test Database Connection for Monitoring</h2>";
try {
    require_once 'includes/db.php';
    echo "✅ Database connected<br>";
    
    // Test if we can create a SecurityMonitor instance
    if (class_exists('SecurityMonitor')) {
        echo "✅ SecurityMonitor class available<br>";
        
        // Try to create instance
        $monitor = new SecurityMonitor($db);
        echo "✅ SecurityMonitor instance created<br>";
    } else {
        echo "❌ SecurityMonitor class not found<br>";
    }
    
} catch (Throwable $e) {
    echo "❌ Database/Monitor failed: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

// Step 7: Check for access control files
echo "<h2>7. Check for Access Control Files</h2>";
$accessFiles = [
    '.htaccess',
    'includes/.htaccess',
    'security-check.php',
    'access-control.php'
];

foreach ($accessFiles as $file) {
    if (file_exists($file)) {
        echo "⚠️ Found: $file<br>";
        
        // Show content if it's small
        $content = file_get_contents($file);
        if (strlen($content) < 500) {
            echo "Content:<br><pre>" . htmlspecialchars($content) . "</pre>";
        } else {
            echo "File size: " . strlen($content) . " bytes<br>";
        }
    } else {
        echo "✅ Not found: $file<br>";
    }
}

echo "<p><a href='index.php'>Try Index Page</a> | <a href='debug-current.php'>Back to Current Debug</a></p>";

echo "</body></html>";
?> 