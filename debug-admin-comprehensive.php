<?php
// COMPREHENSIVE ADMIN SYSTEM DEBUG
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>🔧 Comprehensive Admin System Debug</h1>";
echo "<p>This tool will test every aspect of the admin system to identify exactly what's causing white screens.</p>";

// Step 1: Basic PHP and File System Test
echo "<h2>Step 1: Basic Environment Test</h2>";
try {
    echo "✅ PHP Version: " . phpversion() . "<br>";
    echo "✅ Current directory: " . __DIR__ . "<br>";
    echo "✅ Script name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
    echo "✅ Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
    echo "✅ Server software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
} catch (Throwable $e) {
    echo "❌ Basic environment test failed: " . $e->getMessage() . "<br>";
    exit;
}

// Step 2: File Existence Check
echo "<h2>Step 2: Critical File Existence Check</h2>";
$critical_files = [
    'includes/config.php',
    'includes/security.php', 
    'includes/admin-auth.php',
    'includes/db.php',
    'pages/admin-login.php',
    'pages/admin-dashboard.php',
    'pages/admin.php',
    'pages/admin-inventory.php',
    '.env'
];

foreach ($critical_files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists (" . filesize($file) . " bytes)<br>";
    } else {
        echo "❌ $file MISSING<br>";
    }
}

// Step 3: Include Files Test (Same as admin pages)
echo "<h2>Step 3: Include Files Test</h2>";
try {
    echo "Including config.php...<br>";
    require_once __DIR__ . '/includes/config.php';
    echo "✅ Config.php included<br>";
    
    echo "Including security.php...<br>";
    require_once __DIR__ . '/includes/security.php';
    echo "✅ Security.php included<br>";
    
    echo "Including admin-auth.php...<br>";
    require_once __DIR__ . '/includes/admin-auth.php';
    echo "✅ Admin-auth.php included<br>";
    
    echo "Including db.php...<br>";
    require_once __DIR__ . '/includes/db.php';
    echo "✅ db.php included<br>";
    
} catch (Throwable $e) {
    echo "❌ Include failed: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

// Step 4: Session Test
echo "<h2>Step 4: Session System Test</h2>";
try {
    echo "Starting secure session...<br>";
    secure_session_start();
    echo "✅ Session started successfully<br>";
    echo "Session ID: " . session_id() . "<br>";
    echo "Session status: " . session_status() . "<br>";
    echo "Session save path: " . session_save_path() . "<br>";
    
    // Test session variables
    echo "Current session variables:<br>";
    foreach ($_SESSION as $key => $value) {
        if (is_array($value)) {
            echo "• $key: " . print_r($value, true) . "<br>";
        } else {
            echo "• $key: " . htmlspecialchars($value) . "<br>";
        }
    }
    
} catch (Throwable $e) {
    echo "❌ Session failed: " . $e->getMessage() . "<br>";
    exit;
}

// Step 5: Security Headers Test
echo "<h2>Step 5: Security Headers Test</h2>";
try {
    set_security_headers();
    echo "✅ Security headers set<br>";
    
    // Show headers that were set
    $headers = headers_list();
    if (!empty($headers)) {
        echo "Headers set:<br>";
        foreach ($headers as $header) {
            echo "• " . htmlspecialchars($header) . "<br>";
        }
    }
    
} catch (Throwable $e) {
    echo "❌ Security headers failed: " . $e->getMessage() . "<br>";
    exit;
}

// Step 6: Database Connection Test
echo "<h2>Step 6: Database Connection Test</h2>";
try {
    global $db, $pdo;
    
    echo "Checking global database variables...<br>";
    if (isset($db) && $db instanceof PDO) {
        echo "✅ Global \$db connection exists<br>";
        $conn = $db;
    } elseif (isset($pdo) && $pdo instanceof PDO) {
        echo "✅ Global \$pdo connection exists<br>";
        $conn = $pdo;
    } else {
        echo "❌ No global database connection found<br>";
        echo "Attempting manual connection...<br>";
        
        $envPath = __DIR__ . '/.env';
        if (file_exists($envPath)) {
            echo "✅ .env file found<br>";
            
            $envContent = file_get_contents($envPath);
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
            
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $dbname = $_ENV['DB_NAME'] ?? '';
            $user = $_ENV['DB_USER'] ?? '';
            $pass = $_ENV['DB_PASS'] ?? '';
            $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
            $port = $_ENV['DB_PORT'] ?? 3306;
            
            echo "DB Config: host=$host, dbname=$dbname, user=$user<br>";
            
            if (empty($dbname) || empty($user)) {
                echo "❌ Missing database credentials<br>";
                exit;
            }
            
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $conn = new PDO($dsn, $user, $pass, $options);
            echo "✅ Manual database connection successful<br>";
        } else {
            echo "❌ .env file not found<br>";
            exit;
        }
    }
    
    // Test database queries
    echo "Testing database queries...<br>";
    
    // Test products table
    $stmt = $conn->prepare('SELECT COUNT(*) FROM products');
    $stmt->execute();
    $productCount = $stmt->fetchColumn();
    echo "✅ Products table: $productCount records<br>";
    
    // Test other tables
    $tables = ['customer_requests', 'sell_submissions', 'newsletter_subscribers', 'orders'];
    foreach ($tables as $table) {
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM $table");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            echo "✅ $table table: $count records<br>";
        } catch (PDOException $e) {
            echo "⚠️ $table table: " . $e->getMessage() . "<br>";
        }
    }
    
} catch (Throwable $e) {
    echo "❌ Database test failed: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
    exit;
}

// Step 7: Admin Authentication Functions Test
echo "<h2>Step 7: Admin Authentication Functions Test</h2>";
try {
    echo "Testing admin authentication functions...<br>";
    
    // Test function existence
    $auth_functions = [
        'getAdminCredentials',
        'verify_admin_password', 
        'admin_login',
        'is_admin_logged_in',
        'check_admin_auth',
        'generate_csrf_token',
        'verify_csrf_token'
    ];
    
    foreach ($auth_functions as $func) {
        if (function_exists($func)) {
            echo "✅ Function $func exists<br>";
        } else {
            echo "❌ Function $func MISSING<br>";
        }
    }
    
    // Test admin credentials retrieval
    echo "Testing admin credentials retrieval...<br>";
    $adminCreds = getAdminCredentials();
    if ($adminCreds) {
        echo "✅ Admin credentials retrieved<br>";
        echo "Admin username: " . htmlspecialchars($adminCreds['username'] ?? 'Not set') . "<br>";
        echo "Password hash length: " . strlen($adminCreds['password_hash'] ?? '') . " characters<br>";
    } else {
        echo "❌ Failed to retrieve admin credentials<br>";
    }
    
    // Test current login status
    echo "Testing current login status...<br>";
    $isLoggedIn = is_admin_logged_in();
    echo "Currently logged in: " . ($isLoggedIn ? 'YES' : 'NO') . "<br>";
    
} catch (Throwable $e) {
    echo "❌ Admin auth test failed: " . $e->getMessage() . "<br>";
    exit;
}

// Step 8: CSRF Token Test
echo "<h2>Step 8: CSRF Token Test</h2>";
try {
    echo "Generating CSRF token...<br>";
    $csrf_token = generate_csrf_token();
    echo "✅ CSRF token generated: " . substr($csrf_token, 0, 20) . "...<br>";
    
    echo "Verifying CSRF token...<br>";
    $csrf_valid = verify_csrf_token($csrf_token);
    echo "CSRF token valid: " . ($csrf_valid ? 'YES' : 'NO') . "<br>";
    
} catch (Throwable $e) {
    echo "❌ CSRF test failed: " . $e->getMessage() . "<br>";
    exit;
}

// Step 9: Rate Limiting Test
echo "<h2>Step 9: Rate Limiting Test</h2>";
try {
    echo "Testing rate limiting...<br>";
    $rate_ok = check_rate_limit('admin_test', 10, 300);
    echo "Rate limit check: " . ($rate_ok ? 'PASSED' : 'BLOCKED') . "<br>";
    
} catch (Throwable $e) {
    echo "❌ Rate limiting test failed: " . $e->getMessage() . "<br>";
    exit;
}

// Step 10: Admin Login Simulation
echo "<h2>Step 10: Admin Login Simulation</h2>";
if (isset($_POST['test_login'])) {
    try {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        echo "Attempting login with username: " . htmlspecialchars($username) . "<br>";
        
        if (empty($username) || empty($password)) {
            echo "❌ Username or password empty<br>";
        } else {
            // Test password verification directly
            echo "Testing password verification...<br>";
            $passwordValid = verify_admin_password($password);
            echo "Password verification: " . ($passwordValid ? '✅ VALID' : '❌ INVALID') . "<br>";
            
            if ($passwordValid) {
                // Test full login process
                echo "Testing full login process...<br>";
                $loginResult = admin_login($username, $password);
                echo "Login result: " . ($loginResult ? '✅ SUCCESS' : '❌ FAILED') . "<br>";
                
                if ($loginResult) {
                    echo "✅ Login successful! Session variables set:<br>";
                    foreach ($_SESSION as $key => $value) {
                        if (strpos($key, 'admin') !== false || strpos($key, 'login') !== false) {
                            echo "• $key: " . htmlspecialchars($value) . "<br>";
                        }
                    }
                }
            }
        }
        
    } catch (Throwable $e) {
        echo "❌ Login simulation failed: " . $e->getMessage() . "<br>";
    }
}

// Step 11: Admin Page Loading Test
echo "<h2>Step 11: Admin Page Loading Test</h2>";
try {
    echo "Testing admin page includes...<br>";
    
    // Test if we can include admin pages without executing them
    $admin_pages = [
        'pages/admin-login.php',
        'pages/admin-dashboard.php', 
        'pages/admin.php',
        'pages/admin-inventory.php'
    ];
    
    foreach ($admin_pages as $page) {
        if (file_exists($page)) {
            echo "✅ $page exists (" . filesize($page) . " bytes)<br>";
            
            // Check if file is readable
            if (is_readable($page)) {
                echo "✅ $page is readable<br>";
                
                // Check for common syntax issues by trying to parse the file
                $content = file_get_contents($page);
                if ($content !== false) {
                    // Basic checks for unclosed PHP tags or obvious syntax issues
                    $php_open_count = substr_count($content, '<?php');
                    $php_close_count = substr_count($content, '?>');
                    
                    if ($php_open_count > 0) {
                        echo "✅ $page has valid PHP opening tags<br>";
                    }
                    
                    // Check for obvious syntax errors (very basic)
                    if (strpos($content, 'Parse error') === false && strpos($content, 'Fatal error') === false) {
                        echo "✅ $page basic syntax check passed<br>";
                    } else {
                        echo "⚠️ $page may have syntax issues<br>";
                    }
                } else {
                    echo "❌ $page could not be read<br>";
                }
            } else {
                echo "❌ $page is not readable<br>";
            }
        } else {
            echo "❌ $page missing<br>";
        }
    }
    
} catch (Throwable $e) {
    echo "❌ Admin page test failed: " . $e->getMessage() . "<br>";
}

// Step 12: Memory and Performance Test
echo "<h2>Step 12: Memory and Performance Test</h2>";
try {
    echo "Memory usage: " . memory_get_usage(true) . " bytes<br>";
    echo "Peak memory: " . memory_get_peak_usage(true) . " bytes<br>";
    echo "Memory limit: " . ini_get('memory_limit') . "<br>";
    echo "Max execution time: " . ini_get('max_execution_time') . " seconds<br>";
    echo "Upload max filesize: " . ini_get('upload_max_filesize') . "<br>";
    echo "Post max size: " . ini_get('post_max_size') . "<br>";
    
} catch (Throwable $e) {
    echo "❌ Performance test failed: " . $e->getMessage() . "<br>";
}

// Step 13: Error Log Check
echo "<h2>Step 13: Error Log Check</h2>";
try {
    $error_log_path = ini_get('error_log');
    echo "Error log path: " . ($error_log_path ?: 'Not set') . "<br>";
    
    $log_file = __DIR__ . '/php_error.log';
    if (file_exists($log_file)) {
        echo "✅ Local error log exists: $log_file<br>";
        $log_size = filesize($log_file);
        echo "Log file size: $log_size bytes<br>";
        
        if ($log_size > 0 && $log_size < 10000) {
            echo "Recent errors:<br>";
            echo "<pre>" . htmlspecialchars(file_get_contents($log_file)) . "</pre>";
        } elseif ($log_size > 10000) {
            echo "Log file too large, showing last 2000 characters:<br>";
            echo "<pre>" . htmlspecialchars(substr(file_get_contents($log_file), -2000)) . "</pre>";
        }
    } else {
        echo "⚠️ No local error log found<br>";
    }
    
} catch (Throwable $e) {
    echo "❌ Error log check failed: " . $e->getMessage() . "<br>";
}

// Summary and Recommendations
echo "<h2>🎯 Debug Summary and Recommendations</h2>";

echo "<h3>Test Login Form</h3>";
if (!isset($_POST['test_login'])) {
    echo '<form method="POST">';
    echo '<p>Username: <input type="text" name="username" value="admin" required></p>';
    echo '<p>Password: <input type="password" name="password" placeholder="Enter admin password" required></p>';
    echo '<p><button type="submit" name="test_login">🔐 Test Admin Login</button></p>';
    echo '</form>';
}

echo "<h3>Direct Page Tests</h3>";
echo "<p>Test these pages directly:</p>";
echo "<ul>";
echo "<li><a href='pages/admin-login.php' target='_blank'>🔗 Admin Login Page</a></li>";
echo "<li><a href='pages/admin-dashboard.php' target='_blank'>🔗 Admin Dashboard</a></li>";
echo "<li><a href='pages/admin.php' target='_blank'>🔗 Admin Panel</a></li>";
echo "<li><a href='pages/admin-inventory.php' target='_blank'>🔗 Admin Inventory</a></li>";
echo "</ul>";

echo "<h3>Common Issues to Check</h3>";
echo "<ul>";
echo "<li>If database connection fails: Check .env file credentials</li>";
echo "<li>If functions are missing: Check include order in admin pages</li>";
echo "<li>If login fails: Verify admin password hash in database</li>";
echo "<li>If white screens persist: Check PHP error logs</li>";
echo "<li>If session issues: Check session save path permissions</li>";
echo "</ul>";

echo "<h3>Next Steps</h3>";
echo "<p>Based on the results above:</p>";
echo "<ol>";
echo "<li>✅ <strong>Green checkmarks</strong>: Component is working correctly</li>";
echo "<li>❌ <strong>Red X marks</strong>: Component has issues that need fixing</li>";
echo "<li>⚠️ <strong>Yellow warnings</strong>: Component may have minor issues</li>";
echo "</ol>";

echo "<p><strong>If all tests pass but admin pages still show white screens:</strong></p>";
echo "<ul>";
echo "<li>Check server error logs (not just PHP error logs)</li>";
echo "<li>Enable WordPress debug mode temporarily</li>";
echo "<li>Check file permissions on admin pages</li>";
echo "<li>Test with a minimal admin page to isolate the issue</li>";
echo "</ul>";

?> 