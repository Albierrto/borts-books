<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';

// Ensure global database access
global $db, $pdo;

// Ensure database connections are available
if (!isset($db) || !($db instanceof PDO)) {
    // Re-establish database connection if needed
    try {
        // Load environment variables
        $envPath = dirname(__DIR__) . '/.env';
        if (file_exists($envPath)) {
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
        }
        
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $dbname = $_ENV['DB_NAME'] ?? '';
        $user = $_ENV['DB_USER'] ?? '';
        $pass = $_ENV['DB_PASS'] ?? '';
        $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
        $port = $_ENV['DB_PORT'] ?? 3306;
        
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $db = new PDO($dsn, $user, $pass, $options);
        $pdo = $db; // Alias for compatibility
    } catch (Exception $e) {
        die('Database connection error. Please contact support.');
    }
}

// Start secure session
secure_session_start();

// Set security headers
set_security_headers();

// Check admin authentication
check_admin_auth();

// Generate CSRF token for forms
$csrf_token = generate_csrf_token();

// Get dashboard statistics
$stats = [
    'total_books' => 0,
    'total_collections' => 0,
    'pending_requests' => 0,
    'pending_submissions' => 0
];

try {
    // Get total products (was trying to query 'books' table that doesn't exist)
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $stats['total_books'] = $stmt->fetchColumn();
    
    // Collections table doesn't exist, so set to 0
    $stats['total_collections'] = 0;
    
    // Get pending customer requests
    $stmt = $pdo->query("SELECT COUNT(*) FROM customer_requests WHERE status = 'pending'");
    $stats['pending_requests'] = $stmt->fetchColumn();
    
    // Get pending sell submissions
    $stmt = $pdo->query("SELECT COUNT(*) FROM sell_submissions WHERE status = 'pending'");
    $stats['pending_submissions'] = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    log_security_event('database_error', ['error' => $e->getMessage()]);
    $error = "An error occurred while fetching dashboard statistics.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bort's Books</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .dashboard-title {
            font-size: 2rem;
            font-weight: 800;
            color: #232946;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-title {
            font-size: 0.9rem;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 0.5rem;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #232946;
        }
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .action-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .action-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #232946;
            margin-bottom: 1rem;
        }
        .action-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .action-item {
            margin-bottom: 0.75rem;
        }
        .action-link {
            display: block;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 8px;
            color: #232946;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .action-link:hover {
            background: #667eea;
            color: white;
            transform: translateX(5px);
        }
        .logout-btn {
            padding: 0.75rem 1.5rem;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Admin Dashboard</h1>
            <form method="POST" action="admin-login.php" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <button type="submit" name="logout" class="logout-btn">Logout</button>
            </form>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total Products</div>
                <div class="stat-value"><?php echo number_format($stats['total_books']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Collections</div>
                <div class="stat-value"><?php echo number_format($stats['total_collections']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Pending Requests</div>
                <div class="stat-value"><?php echo number_format($stats['pending_requests']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Pending Submissions</div>
                <div class="stat-value"><?php echo number_format($stats['pending_submissions']); ?></div>
            </div>
        </div>
        
        <div class="action-grid">
            <div class="action-card">
                <h2 class="action-title">Inventory Management</h2>
                <ul class="action-list">
                    <li class="action-item">
                        <a href="admin-inventory.php" class="action-link">Manage Books</a>
                    </li>
                    <li class="action-item">
                        <a href="ebay-single-import.php" class="action-link">📦 Import eBay Listing</a>
                    </li>
                    <li class="action-item">
                        <a href="admin-collections.php" class="action-link">Manage Collections</a>
                    </li>
                    <li class="action-item">
                        <a href="admin-mass-shipping.php" class="action-link">Mass Shipping Update</a>
                    </li>
                </ul>
            </div>
            
            <div class="action-card">
                <h2 class="action-title">Customer Interactions</h2>
                <ul class="action-list">
                    <li class="action-item">
                        <a href="admin-customer-requests.php" class="action-link">Customer Requests</a>
                    </li>
                    <li class="action-item">
                        <a href="admin-sell-submissions.php" class="action-link">Sell Submissions</a>
                    </li>
                    <li class="action-item">
                        <a href="admin-email.php" class="action-link">Email Management</a>
                    </li>
                </ul>
            </div>
            
            <div class="action-card">
                <h2 class="action-title">Quick Actions</h2>
                <ul class="action-list">
                    <li class="action-item">
                        <a href="admin-quick-edit.php" class="action-link">Quick Edit Books</a>
                    </li>
                    <li class="action-item">
                        <a href="admin-mass-delete.php" class="action-link">Mass Delete</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html> 