<?php
// BYPASS VERSION - Admin Login without redirects
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>Admin Login Bypass - No Redirects</h1>";
echo "<p>This version shows the login form regardless of session state</p>";

// Include required files but catch any errors
try {
    require_once __DIR__ . '/includes/security.php';
    require_once __DIR__ . '/includes/admin-auth.php';
    echo "✅ Files included successfully<br>";
} catch (Exception $e) {
    die("Include error: " . $e->getMessage());
}

// Start session but don't redirect
try {
    secure_session_start();
    echo "✅ Session started<br>";
} catch (Exception $e) {
    die("Session error: " . $e->getMessage());
}

// Set security headers
try {
    set_security_headers();
    echo "✅ Security headers set<br>";
} catch (Exception $e) {
    echo "⚠️ Security headers failed: " . $e->getMessage() . "<br>";
}

// Show current session state
echo "<h2>Current Session State</h2>";
echo "Session ID: " . session_id() . "<br>";
if (isset($_SESSION['admin_logged_in'])) {
    echo "Admin logged in: " . ($_SESSION['admin_logged_in'] ? 'YES' : 'NO') . "<br>";
} else {
    echo "No admin_logged_in session variable<br>";
}

$error = '';
$username = '';

// Handle login attempt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Processing Login</h2>";
    
    // Generate CSRF token for comparison
    $expected_token = generate_csrf_token();
    
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request (CSRF)';
        echo "❌ CSRF failed<br>";
    } else {
        echo "✅ CSRF valid<br>";
        
        // Check rate limiting
        if (!check_rate_limit('admin_login', 5, 300)) {
            $error = 'Too many login attempts. Please try again later.';
            echo "❌ Rate limited<br>";
        } else {
            echo "✅ Rate limit OK<br>";
            
            $username = sanitize_input($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                $error = 'Please enter both username and password';
                echo "❌ Empty credentials<br>";
            } else {
                echo "Testing login for username: '$username'<br>";
                
                if (admin_login($username, $password)) {
                    echo "✅ Login successful!<br>";
                    echo "<p><strong>Normally would redirect to dashboard now</strong></p>";
                    echo "<a href='admin-dashboard.php'>Go to Dashboard</a><br>";
                } else {
                    $error = 'Invalid username or password';
                    echo "❌ Login failed<br>";
                }
            }
        }
    }
}

// Generate CSRF token for the form
try {
    $csrf_token = generate_csrf_token();
    echo "✅ CSRF token generated<br>";
} catch (Exception $e) {
    die("CSRF token error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login Bypass - Bort's Books</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .login-container { max-width: 400px; margin: 2rem auto; padding: 2rem; background: #f9f9f9; border-radius: 8px; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        .form-group input { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; }
        .login-btn { background: #007cba; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 4px; cursor: pointer; }
        .login-btn:hover { background: #005a87; }
        .error-message { background: #f8d7da; color: #721c24; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem; }
        .debug-info { background: #e2e3e5; padding: 1rem; margin: 1rem 0; border-radius: 4px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="debug-info">
        <h3>Debug Information</h3>
        <p>This is a bypass version that shows the login form regardless of session state.</p>
        <p>If this page loads but the regular admin-login.php shows white screen, the issue is likely a redirect problem.</p>
    </div>
    
    <div class="login-container">
        <div class="login-header">
            <h1>Admin Login (Bypass)</h1>
            <p>Please sign in to access the admin dashboard</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="login-btn">Sign In</button>
        </form>
        
        <div style="margin-top: 2rem;">
            <p><a href="../">← Back to Home</a></p>
            <p><a href="admin-dashboard.php">Direct Dashboard Link</a></p>
        </div>
    </div>
</body>
</html> 