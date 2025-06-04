<?php
// Admin Debug Tool
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Admin Debug</title></head><body>";
echo "<h1>Admin System Debug</h1>";

// Step 1: Test config loading
echo "<h2>1. Configuration Test</h2>";
try {
    require_once 'includes/config.php';
    echo "✅ Config loaded<br>";
} catch (Throwable $e) {
    echo "❌ Config failed: " . $e->getMessage() . "<br>";
}

// Step 2: Test database connection
echo "<h2>2. Database Connection Test</h2>";
try {
    global $db, $pdo;
    require_once 'includes/db.php';
    
    if (isset($db) && $db instanceof PDO) {
        echo "✅ \$db connection available<br>";
    } else {
        echo "❌ \$db connection missing<br>";
    }
    
    if (isset($pdo) && $pdo instanceof PDO) {
        echo "✅ \$pdo connection available<br>";
    } else {
        echo "❌ \$pdo connection missing<br>";
    }
    
    // Test a query
    if (isset($pdo)) {
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        echo "✅ Database query successful: " . $result['test'] . "<br>";
    }
    
} catch (Throwable $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Step 3: Test admin authentication system
echo "<h2>3. Admin Auth System Test</h2>";
try {
    require_once 'includes/admin-auth.php';
    echo "✅ Admin auth loaded<br>";
    
    // Check admin password hash
    if (defined('ADMIN_PASSWORD_HASH') && !empty(ADMIN_PASSWORD_HASH)) {
        echo "✅ Admin password hash configured<br>";
    } else {
        echo "❌ Admin password hash not configured<br>";
    }
    
} catch (Throwable $e) {
    echo "❌ Admin auth error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

// Step 4: Test admin login page directly
echo "<h2>4. Admin Login Page Test</h2>";
try {
    // Test if admin login page can be loaded
    if (file_exists('pages/admin-login.php')) {
        echo "✅ Admin login file exists<br>";
        
        // Test includes without executing the full page
        $loginContent = file_get_contents('pages/admin-login.php');
        if (strpos($loginContent, 'dirname(__DIR__)') !== false) {
            echo "✅ Admin login uses absolute paths<br>";
        } else {
            echo "❌ Admin login may have path issues<br>";
        }
    } else {
        echo "❌ Admin login file missing<br>";
    }
} catch (Throwable $e) {
    echo "❌ Admin login test failed: " . $e->getMessage() . "<br>";
}

// Step 5: Test database tables
echo "<h2>5. Database Tables Test</h2>";
try {
    if (isset($pdo)) {
        $tables = ['products', 'customer_requests', 'sell_submissions', 'newsletter_subscribers', 'orders'];
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->fetch()) {
                    $countStmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                    $count = $countStmt->fetch()['count'];
                    echo "✅ Table '$table': $count records<br>";
                } else {
                    echo "❌ Table '$table' not found<br>";
                }
            } catch (Exception $e) {
                echo "❌ Table '$table' error: " . $e->getMessage() . "<br>";
            }
        }
    }
} catch (Throwable $e) {
    echo "❌ Table check failed: " . $e->getMessage() . "<br>";
}

echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li><a href='pages/admin-login.php'>Test Admin Login</a></li>";
echo "<li><a href='index.php'>Back to Home</a></li>";
echo "<li><a href='debug-pages.php'>General Debug</a></li>";
echo "</ul>";

echo "</body></html>";
?> 