<?php
// Simple Debug Page - Completely Isolated
// This bypasses all existing configuration to identify the issue

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Simple Debug</title></head><body>";
echo "<h1>Simple Configuration Debug</h1>";
echo "<p>This page is completely isolated from the existing configuration system.</p>";

// Step 1: Check .env file
echo "<h2>1. Environment File Check</h2>";
$envPath = __DIR__ . '/.env';
echo "Looking for .env at: " . $envPath . "<br>";

if (file_exists($envPath)) {
    echo "✅ .env file found<br>";
    
    $envContent = file_get_contents($envPath);
    if ($envContent !== false) {
        echo "✅ .env file readable<br>";
        echo "File size: " . strlen($envContent) . " bytes<br>";
        
        // Parse and show environment variables
        $lines = explode("\n", $envContent);
        echo "<h3>Environment Variables:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; font-family: monospace;'>";
        echo "<tr><th>Line</th><th>Variable</th><th>Status</th></tr>";
        
        $lineNum = 0;
        foreach ($lines as $line) {
            $lineNum++;
            $line = trim($line);
            
            if (empty($line)) {
                echo "<tr><td>$lineNum</td><td>[EMPTY LINE]</td><td>Skipped</td></tr>";
                continue;
            }
            
            if (strpos($line, '#') === 0) {
                echo "<tr><td>$lineNum</td><td>$line</td><td>Comment</td></tr>";
                continue;
            }
            
            if (strpos($line, '=') !== false) {
                list($name, $value) = array_map('trim', explode('=', $line, 2));
                $value = trim($value, '"\'');
                
                // Hide sensitive values but show if they're set
                if (strpos($name, 'PASS') !== false || strpos($name, 'SECRET') !== false || strpos($name, 'KEY') !== false) {
                    $displayValue = strlen($value) > 0 ? '[SET - ' . strlen($value) . ' chars]' : '[EMPTY]';
                } else {
                    $displayValue = strlen($value) > 30 ? substr($value, 0, 30) . '...' : $value;
                }
                
                $status = strlen($value) > 0 ? "✅ Set" : "❌ Empty";
                echo "<tr><td>$lineNum</td><td>$name = $displayValue</td><td>$status</td></tr>";
            } else {
                echo "<tr><td>$lineNum</td><td>$line</td><td>❌ Invalid format</td></tr>";
            }
        }
        echo "</table>";
        
    } else {
        echo "❌ .env file not readable<br>";
    }
} else {
    echo "❌ .env file not found<br>";
}

// Step 2: Check what files exist
echo "<h2>2. Critical Files Check</h2>";
$criticalFiles = [
    'includes/config.php',
    'includes/security.php',
    'includes/db.php',
    'includes/admin-auth.php'
];

foreach ($criticalFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}

// Step 3: Try to manually test config.php
echo "<h2>3. Manual Config.php Test</h2>";
if (file_exists('includes/config.php')) {
    echo "Testing config.php directly...<br>";
    
    // Capture any output or errors
    ob_start();
    $error = null;
    try {
        // First, manually define INCLUDED_FROM_APP
        if (!defined('INCLUDED_FROM_APP')) {
            define('INCLUDED_FROM_APP', true);
        }
        
        include 'includes/config.php';
        echo "✅ config.php loaded successfully<br>";
        
        // Test if constants are defined
        $constants = ['APP_NAME', 'APP_ENV', 'SECURITY_KEY', 'ENCRYPTION_KEY'];
        foreach ($constants as $const) {
            if (defined($const)) {
                $value = constant($const);
                if (in_array($const, ['SECURITY_KEY', 'ENCRYPTION_KEY'])) {
                    echo "$const: " . (strlen($value) > 0 ? '✅ SET (' . strlen($value) . ' chars)' : '❌ EMPTY') . "<br>";
                } else {
                    echo "$const: $value<br>";
                }
            } else {
                echo "❌ $const not defined<br>";
            }
        }
        
    } catch (Throwable $e) {
        $error = $e;
        echo "❌ config.php failed to load<br>";
        echo "Error: " . $e->getMessage() . "<br>";
        echo "File: " . $e->getFile() . "<br>";
        echo "Line: " . $e->getLine() . "<br>";
    }
    
    $output = ob_get_clean();
    echo $output;
    
    if ($error) {
        echo "<h3>Full Error Details:</h3>";
        echo "<pre style='background: #ffebee; padding: 10px; border: 1px solid #red;'>";
        echo "Message: " . $error->getMessage() . "\n";
        echo "File: " . $error->getFile() . "\n";
        echo "Line: " . $error->getLine() . "\n";
        echo "Stack Trace:\n" . $error->getTraceAsString();
        echo "</pre>";
    }
} else {
    echo "❌ config.php file not found<br>";
}

// Step 4: Show PHP info
echo "<h2>4. PHP Environment</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "<br>";
echo "Current Directory: " . __DIR__ . "<br>";

echo "<h2>5. Next Steps</h2>";
echo "<p>Based on the results above, we can identify what's causing the configuration error.</p>";
echo "<p><a href='index.php'>Try Home Page</a> | <a href='pages/admin-login.php'>Try Admin Login</a></p>";

echo "</body></html>";
?> 