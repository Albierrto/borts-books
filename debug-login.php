<?php
// Login Process Debug Tool
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Login Debug</title></head><body>";
echo "<h1>Admin Login Process Debug</h1>";

// Step 1: Test session functionality
echo "<h2>1. Session Test</h2>";
session_start();
echo "✅ Session started<br>";
echo "Session ID: " . session_id() . "<br>";
echo "Session status: " . session_status() . "<br>";

// Step 2: Test includes
echo "<h2>2. Include Files Test</h2>";
try {
    require_once 'includes/config.php';
    echo "✅ Config loaded<br>";
    
    require_once 'includes/security.php';
    echo "✅ Security loaded<br>";
    
    require_once 'includes/admin-auth.php';
    echo "✅ Admin auth loaded<br>";
    
} catch (Throwable $e) {
    echo "❌ Include error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

// Step 3: Test admin password verification
echo "<h2>3. Password Verification Test</h2>";
if (defined('ADMIN_PASSWORD_HASH')) {
    echo "✅ Admin password hash: " . substr(ADMIN_PASSWORD_HASH, 0, 20) . "...<br>";
    
    // Test with a dummy password to see if verification works
    $testPassword = "test123";
    if (function_exists('verify_admin_password')) {
        $result = verify_admin_password($testPassword);
        echo "❌ Test password verification (should fail): " . ($result ? 'PASSED' : 'FAILED') . "<br>";
    } else {
        echo "❌ verify_admin_password function not found<br>";
    }
} else {
    echo "❌ ADMIN_PASSWORD_HASH not defined<br>";
}

// Step 4: Test authentication functions
echo "<h2>4. Authentication Functions Test</h2>";
if (function_exists('check_admin_auth')) {
    echo "✅ check_admin_auth function exists<br>";
} else {
    echo "❌ check_admin_auth function missing<br>";
}

if (function_exists('secure_session_start')) {
    echo "✅ secure_session_start function exists<br>";
} else {
    echo "❌ secure_session_start function missing<br>";
}

// Step 5: Test what happens when we try to access admin dashboard
echo "<h2>5. Dashboard Access Test</h2>";
echo "Current session variables:<br>";
foreach ($_SESSION as $key => $value) {
    echo "• $key: " . (is_string($value) ? htmlspecialchars($value) : print_r($value, true)) . "<br>";
}

// Step 6: Test manual login simulation
echo "<h2>6. Manual Login Simulation</h2>";
echo "<form method='POST' style='margin:20px 0; padding:20px; border:1px solid #ccc;'>";
echo "<h3>Test Login Form</h3>";
echo "<label>Username: <input type='text' name='username' value='admin'></label><br><br>";
echo "<label>Password: <input type='password' name='password' placeholder='Enter admin password'></label><br><br>";
echo "<button type='submit' name='test_login'>Test Login</button>";
echo "</form>";

// Process test login
if (isset($_POST['test_login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<h3>Login Test Results:</h3>";
    echo "Username entered: " . htmlspecialchars($username) . "<br>";
    echo "Password length: " . strlen($password) . " characters<br>";
    
    if (function_exists('verify_admin_password')) {
        $passwordValid = verify_admin_password($password);
        echo "Password verification: " . ($passwordValid ? '✅ VALID' : '❌ INVALID') . "<br>";
        
        if ($passwordValid && $username === 'admin') {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['login_time'] = time();
            
            echo "✅ Session variables set:<br>";
            echo "• admin_logged_in: " . ($_SESSION['admin_logged_in'] ? 'true' : 'false') . "<br>";
            echo "• admin_username: " . htmlspecialchars($_SESSION['admin_username']) . "<br>";
            echo "• login_time: " . $_SESSION['login_time'] . "<br>";
            
            echo "<p><strong>✅ Login successful! Try accessing dashboard:</strong></p>";
            echo "<a href='pages/admin-dashboard.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Go to Dashboard</a>";
        }
    } else {
        echo "❌ verify_admin_password function not available<br>";
    }
}

echo "<hr>";
echo "<p><strong>Navigation:</strong></p>";
echo "<ul>";
echo "<li><a href='pages/admin-login.php'>Official Admin Login</a></li>";
echo "<li><a href='debug-admin.php'>Admin System Debug</a></li>";
echo "<li><a href='index.php'>Back to Home</a></li>";
echo "</ul>";

echo "</body></html>";
?> 