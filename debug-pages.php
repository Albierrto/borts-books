<?php
// Page-Specific Debug - Test admin and shop pages
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Page Debug</title></head><body>";
echo "<h1>Admin & Shop Page Debug</h1>";

// Step 1: Test shop page
echo "<h2>1. Shop Page Test</h2>";
try {
    echo "Testing pages/shop.php...<br>";
    
    ob_start();
    include 'pages/shop.php';
    $shopOutput = ob_get_clean();
    
    echo "✅ Shop page executed<br>";
    echo "Output length: " . strlen($shopOutput) . " bytes<br>";
    
    if (empty($shopOutput)) {
        echo "❌ Shop page produced no output (blank page)<br>";
    } else {
        echo "✅ Shop page produced output<br>";
        echo "First 200 chars: <pre>" . htmlspecialchars(substr($shopOutput, 0, 200)) . "</pre>";
    }
    
} catch (Throwable $e) {
    echo "❌ Shop page failed: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

// Step 2: Test admin login page
echo "<h2>2. Admin Login Page Test</h2>";
try {
    echo "Testing pages/admin-login.php...<br>";
    
    ob_start();
    include 'pages/admin-login.php';
    $adminOutput = ob_get_clean();
    
    echo "✅ Admin login executed<br>";
    echo "Output length: " . strlen($adminOutput) . " bytes<br>";
    
    if (empty($adminOutput)) {
        echo "❌ Admin login produced no output (blank page)<br>";
    } else {
        echo "✅ Admin login produced output<br>";
        echo "First 200 chars: <pre>" . htmlspecialchars(substr($adminOutput, 0, 200)) . "</pre>";
    }
    
} catch (Throwable $e) {
    echo "❌ Admin login failed: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

// Step 3: Check admin files
echo "<h2>3. Admin Directory Check</h2>";
if (is_dir('admin')) {
    $adminFiles = scandir('admin');
    echo "Admin directory contents:<br>";
    foreach ($adminFiles as $file) {
        if ($file != '.' && $file != '..') {
            echo "- $file<br>";
        }
    }
    
    // Test a simple admin file
    if (file_exists('admin/dashboard.php')) {
        echo "<h3>Testing admin/dashboard.php</h3>";
        try {
            ob_start();
            include 'admin/dashboard.php';
            $dashOutput = ob_get_clean();
            
            echo "✅ Dashboard executed<br>";
            echo "Output length: " . strlen($dashOutput) . " bytes<br>";
            
            if (empty($dashOutput)) {
                echo "❌ Dashboard produced no output<br>";
            } else {
                echo "✅ Dashboard produced output<br>";
            }
            
        } catch (Throwable $e) {
            echo "❌ Dashboard failed: " . $e->getMessage() . "<br>";
            echo "File: " . $e->getFile() . "<br>";
            echo "Line: " . $e->getLine() . "<br>";
        }
    }
} else {
    echo "❌ Admin directory not found<br>";
}

// Step 4: Check for admin authentication issues
echo "<h2>4. Admin Authentication Test</h2>";
try {
    if (file_exists('includes/admin-auth.php')) {
        echo "Testing admin-auth.php...<br>";
        
        ob_start();
        include 'includes/admin-auth.php';
        $authOutput = ob_get_clean();
        
        echo "✅ Admin auth loaded<br>";
        if (!empty($authOutput)) {
            echo "Auth output: <pre>" . htmlspecialchars($authOutput) . "</pre>";
        }
    }
} catch (Throwable $e) {
    echo "❌ Admin auth failed: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

// Step 5: Check for session issues
echo "<h2>5. Session & Security Check</h2>";
echo "Session status: " . session_status() . "<br>";
echo "Session ID: " . (session_id() ?: 'None') . "<br>";

// Check if security headers are causing issues
if (headers_sent($file, $line)) {
    echo "⚠️ Headers already sent by $file:$line<br>";
} else {
    echo "✅ Headers not yet sent<br>";
}

// Step 6: Check specific files
echo "<h2>6. File Existence Check</h2>";
$criticalFiles = [
    'pages/shop.php',
    'pages/admin-login.php',
    'admin/dashboard.php',
    'includes/admin-auth.php',
    'includes/cart-display.php',
    'includes/reviews-system.php'
];

foreach ($criticalFiles as $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "✅ $file ($size bytes)<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}

echo "<p><a href='pages/shop.php'>Try Shop Page</a> | <a href='pages/admin-login.php'>Try Admin Login</a> | <a href='index.php'>Back to Home</a></p>";

echo "</body></html>";
?> 