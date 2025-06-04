<?php
// Enable error reporting to catch any PHP errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>Simple Debug - No Auth Required</h1>";

echo "<h2>1. Basic PHP Test</h2>";
echo "✅ PHP is working<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Current directory: " . __DIR__ . "<br>";

echo "<h2>2. File Existence Check</h2>";
$files = [
    'includes/security.php',
    'includes/admin-auth.php',
    'includes/config.php',
    'includes/db.php',
    'pages/admin-login.php',
    'pages/admin-dashboard.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}

echo "<h2>3. Security.php Include Test</h2>";
try {
    require_once 'includes/security.php';
    echo "✅ Security.php loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ Security.php error: " . $e->getMessage() . "<br>";
} catch (Error $e) {
    echo "❌ Security.php fatal error: " . $e->getMessage() . "<br>";
}

echo "<h2>4. Admin-auth.php Include Test</h2>";
try {
    require_once 'includes/admin-auth.php';
    echo "✅ Admin-auth.php loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ Admin-auth.php error: " . $e->getMessage() . "<br>";
} catch (Error $e) {
    echo "❌ Admin-auth.php fatal error: " . $e->getMessage() . "<br>";
}

echo "<h2>5. Admin Login Page Test</h2>";
echo "<p>Testing admin login page without executing it:</p>";

// Check if we can read the admin-login.php file
try {
    $loginContent = file_get_contents('pages/admin-login.php');
    if ($loginContent) {
        echo "✅ Admin-login.php file readable (" . strlen($loginContent) . " characters)<br>";
        
        // Check for common PHP syntax issues
        if (strpos($loginContent, '<?php') === false) {
            echo "❌ No opening PHP tag found<br>";
        } else {
            echo "✅ Opening PHP tag found<br>";
        }
        
        // Check for unmatched braces/brackets
        $openBraces = substr_count($loginContent, '{');
        $closeBraces = substr_count($loginContent, '}');
        echo "Braces: $openBraces open, $closeBraces close<br>";
        if ($openBraces != $closeBraces) {
            echo "❌ Unmatched braces detected<br>";
        } else {
            echo "✅ Braces matched<br>";
        }
        
    } else {
        echo "❌ Cannot read admin-login.php file<br>";
    }
} catch (Exception $e) {
    echo "❌ Error reading admin-login.php: " . $e->getMessage() . "<br>";
}

echo "<h2>6. Session Test</h2>";
try {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
        echo "✅ Session started<br>";
    } else {
        echo "✅ Session already active<br>";
    }
    echo "Session ID: " . session_id() . "<br>";
} catch (Exception $e) {
    echo "❌ Session error: " . $e->getMessage() . "<br>";
}

echo "<h2>7. Function Availability Test</h2>";
$functions = [
    'secure_session_start',
    'set_security_headers', 
    'verify_csrf_token',
    'generate_csrf_token',
    'admin_login',
    'is_admin_logged_in'
];

foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "✅ $func() available<br>";
    } else {
        echo "❌ $func() not found<br>";
    }
}

echo "<h2>8. Direct Admin Login Test</h2>";
echo "<p>Try loading admin login directly (will show any PHP errors):</p>";

// Capture any errors from the admin login page
ob_start();
try {
    // Don't actually include it, just check if it would load
    $syntax_check = php_check_syntax('pages/admin-login.php');
    if (function_exists('php_check_syntax')) {
        if ($syntax_check) {
            echo "✅ Admin-login.php syntax is valid<br>";
        } else {
            echo "❌ Admin-login.php has syntax errors<br>";
        }
    } else {
        echo "ℹ️ php_check_syntax not available (normal in many PHP versions)<br>";
    }
} catch (Exception $e) {
    echo "❌ Syntax check error: " . $e->getMessage() . "<br>";
}
ob_end_clean();

echo "<h2>9. Manual Links</h2>";
echo '<a href="pages/admin-login.php" target="_blank">Direct Admin Login Link</a><br>';
echo '<p style="color: red;">If the above link shows a white page, check your server error logs for PHP errors.</p>';

?> 