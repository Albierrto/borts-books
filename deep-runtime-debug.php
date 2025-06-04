<?php
// DEEP RUNTIME DEBUG - Execute the exact same code path as admin-login.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);

echo "<h1>Deep Runtime Debug - Exact Login Path</h1>";
echo "<p>This will execute the EXACT same code path as admin-login.php</p>";

// Step 1: Include files exactly as admin-login.php does
echo "<h2>Step 1: File Includes (Same as admin-login.php)</h2>";
try {
    echo "Including security.php...<br>";
    require_once dirname(__DIR__) . '/includes/security.php';
    echo "✅ Security.php included<br>";
    
    echo "Including admin-auth.php...<br>";
    require_once dirname(__DIR__) . '/includes/admin-auth.php';
    echo "✅ Admin-auth.php included<br>";
    
} catch (Throwable $e) {
    echo "❌ Include failed: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

// Step 2: Start session exactly as admin-login.php does
echo "<h2>Step 2: Session Start</h2>";
try {
    echo "Calling secure_session_start()...<br>";
    secure_session_start();
    echo "✅ Session started successfully<br>";
    echo "Session ID: " . session_id() . "<br>";
} catch (Throwable $e) {
    echo "❌ Session start failed: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

// Step 3: Set security headers exactly as admin-login.php does
echo "<h2>Step 3: Security Headers</h2>";
try {
    echo "Calling set_security_headers()...<br>";
    set_security_headers();
    echo "✅ Security headers set<br>";
} catch (Throwable $e) {
    echo "❌ Security headers failed: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

// Step 4: Check if already logged in exactly as admin-login.php does
echo "<h2>Step 4: Login Check</h2>";
try {
    echo "Checking if admin is already logged in...<br>";
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        echo "ℹ️ Would redirect to dashboard (user is logged in)<br>";
    } else {
        echo "✅ User not logged in, continuing to login form<br>";
    }
} catch (Throwable $e) {
    echo "❌ Login check failed: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

// Step 5: Generate CSRF token exactly as admin-login.php does
echo "<h2>Step 5: CSRF Token Generation</h2>";
try {
    echo "Calling generate_csrf_token()...<br>";
    $csrf_token = generate_csrf_token();
    echo "✅ CSRF token generated: " . substr($csrf_token, 0, 10) . "...<br>";
} catch (Throwable $e) {
    echo "❌ CSRF token generation failed: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

// Step 6: Test POST request handling (simulate login attempt)
echo "<h2>Step 6: POST Request Simulation</h2>";
echo "<p>Testing what happens when processing a login form submission...</p>";

// Simulate POST data
$_POST['csrf_token'] = $csrf_token;
$_POST['username'] = 'admin';
$_POST['password'] = 'testpass';
$_SERVER['REQUEST_METHOD'] = 'POST';

try {
    echo "Simulating POST request with CSRF token...<br>";
    
    // Test CSRF verification
    echo "Testing verify_csrf_token()...<br>";
    $csrf_valid = verify_csrf_token($_POST['csrf_token'] ?? '');
    echo "CSRF verification result: " . ($csrf_valid ? 'VALID' : 'INVALID') . "<br>";
    
    if (!$csrf_valid) {
        echo "ℹ️ Would show CSRF error<br>";
    } else {
        echo "✅ CSRF token valid, continuing...<br>";
        
        // Test rate limiting
        echo "Testing check_rate_limit()...<br>";
        $rate_ok = check_rate_limit('admin_login', 5, 300);
        echo "Rate limit check: " . ($rate_ok ? 'OK' : 'BLOCKED') . "<br>";
        
        if (!$rate_ok) {
            echo "ℹ️ Would show rate limit error<br>";
        } else {
            echo "✅ Rate limit OK, continuing...<br>";
            
            // Test input sanitization
            echo "Testing sanitize_input()...<br>";
            $username = sanitize_input($_POST['username'] ?? '');
            echo "Sanitized username: '" . $username . "'<br>";
            
            // Test admin_login function
            echo "Testing admin_login() function...<br>";
            $login_result = admin_login($username, $_POST['password']);
            echo "Login result: " . ($login_result ? 'SUCCESS' : 'FAILED') . "<br>";
        }
    }
    
} catch (Throwable $e) {
    echo "❌ POST processing failed: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

// Step 7: Test HTML output generation
echo "<h2>Step 7: HTML Output Test</h2>";
try {
    echo "Testing HTML generation...<br>";
    
    // Test basic HTML structure
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - Bort's Books</title>
        <link rel="stylesheet" href="../assets/css/styles.css">
    </head>
    <body>
        <h1>Test HTML Output</h1>
        <p>If you see this, HTML generation works fine.</p>
    </body>
    </html>
    <?php
    $html_output = ob_get_clean();
    
    echo "✅ HTML generated successfully (" . strlen($html_output) . " characters)<br>";
    
} catch (Throwable $e) {
    echo "❌ HTML generation failed: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

// Step 8: Memory usage check
echo "<h2>Step 8: Memory Usage</h2>";
echo "Current memory usage: " . memory_get_usage(true) . " bytes<br>";
echo "Peak memory usage: " . memory_get_peak_usage(true) . " bytes<br>";
echo "Memory limit: " . ini_get('memory_limit') . "<br>";

// Step 9: Check for specific errors in the dependency chain
echo "<h2>Step 9: Dependency Chain Test</h2>";
try {
    echo "Testing log_security_event()...<br>";
    if (function_exists('log_security_event')) {
        log_security_event('debug_test', ['message' => 'Testing from debug script']);
        echo "✅ log_security_event() works<br>";
    } else {
        echo "❌ log_security_event() function not found<br>";
    }
    
    echo "Testing get_client_ip()...<br>";
    if (function_exists('get_client_ip')) {
        $ip = get_client_ip();
        echo "✅ get_client_ip() works: $ip<br>";
    } else {
        echo "❌ get_client_ip() function not found<br>";
    }
    
} catch (Throwable $e) {
    echo "❌ Dependency test failed: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Step 10: Final Test - Actual Admin Login Page</h2>";
echo "<p>Now let's try to actually load the admin-login.php page content:</p>";

try {
    echo "Attempting to execute admin-login.php in isolated environment...<br>";
    
    // Reset POST data to avoid conflicts
    unset($_POST);
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    // Capture the actual admin-login.php output
    ob_start();
    include 'pages/admin-login.php';
    $page_output = ob_get_clean();
    
    if (strlen($page_output) > 0) {
        echo "✅ Admin-login.php executed successfully!<br>";
        echo "Output length: " . strlen($page_output) . " characters<br>";
        echo "<h3>First 500 characters of output:</h3>";
        echo "<pre style='background: #f0f0f0; padding: 10px; max-height: 200px; overflow: auto;'>";
        echo htmlspecialchars(substr($page_output, 0, 500));
        echo "</pre>";
    } else {
        echo "❌ Admin-login.php produced no output (this is the problem!)<br>";
    }
    
} catch (Throwable $e) {
    echo "❌ Admin-login.php execution failed: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Conclusion</h2>";
echo "<p>All steps completed. If any step failed above, that's likely the cause of your white screen.</p>";
echo "<p><strong>If all steps passed but admin-login.php still shows blank, the issue is likely:</strong></p>";
echo "<ul>";
echo "<li>A redirect happening before output (check for header() calls)</li>";
echo "<li>Output buffering being cleared</li>";
echo "<li>A fatal error in the HTML generation section</li>";
echo "<li>CSS/JavaScript issues preventing display</li>";
echo "</ul>";

?> 