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
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #059669;
            --warning: #d97706;
            --danger: #dc2626;
            --info: #0284c7;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --spacing-1: 0.25rem;
            --spacing-2: 0.5rem;
            --spacing-3: 0.75rem;
            --spacing-4: 1rem;
            --spacing-6: 1.5rem;
            --spacing-8: 2rem;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }
        body {
            background: var(--gray-50);
            color: var(--gray-900);
        }
        .dashboard-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: var(--spacing-4);
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-8);
            padding: var(--spacing-6);
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
        }
        .dashboard-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--gray-900);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: var(--spacing-6);
            margin-bottom: var(--spacing-8);
        }
        .stat-card {
            background: white;
            padding: var(--spacing-6);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            transition: transform 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .stat-title {
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--gray-500);
            font-weight: 600;
            margin-bottom: var(--spacing-2);
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
        }
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: var(--spacing-6);
        }
        .action-card {
            background: white;
            padding: var(--spacing-6);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
        }
        .action-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--spacing-4);
        }
        .action-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .action-item {
            margin-bottom: var(--spacing-3);
        }
        .action-link {
            display: block;
            padding: var(--spacing-3);
            background: var(--gray-100);
            border-radius: 8px;
            color: var(--gray-900);
            text-decoration: none;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        .action-link:hover {
            background: var(--primary);
            color: white;
            transform: translateX(5px);
        }
        .logout-btn {
            padding: var(--spacing-3) var(--spacing-6);
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .logout-btn:hover {
            background: #b91c1c;
            transform: translateY(-2px);
        }
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: var(--spacing-4);
            border-radius: 8px;
            margin-bottom: var(--spacing-4);
        }
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--spacing-4);
            }
            .stats-grid, .action-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --gray-50: #111827;
                --gray-100: #1f2937;
                --gray-200: #374151;
                --gray-300: #4b5563;
                --gray-400: #6b7280;
                --gray-500: #9ca3af;
                --gray-600: #d1d5db;
                --gray-700: #e5e7eb;
                --gray-800: #f3f4f6;
                --gray-900: #f9fafb;
            }
            body {
                background: var(--gray-50);
                color: var(--gray-900);
            }
            .dashboard-header,
            .stat-card,
            .action-card {
                background: var(--gray-100);
            }
            .action-link {
                background: var(--gray-200);
                color: var(--gray-700);
            }
            .action-link:hover {
                background: var(--primary);
                color: white;
            }
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
                    <li class="action-item">
                        <a href="ebay-single-import.php" class="action-link">📦 Import eBay Listing</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html> 