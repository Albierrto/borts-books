<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/inventory-manager.php';

// Ensure database connections are available
global $db, $pdo;
if (!isset($pdo) || !($pdo instanceof PDO)) {
    if (isset($db) && ($db instanceof PDO)) {
        $pdo = $db; // Use existing $db connection
    } else {
        // Re-establish database connection if needed
        try {
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
            $pdo = $db;
        } catch (Exception $e) {
            die('Database connection error. Please contact support.');
        }
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

// Initialize inventory manager
$inventoryManager = new InventoryManager($db);

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
        log_security_event('csrf_failure', ['page' => 'admin-inventory']);
    } else {
        // Check rate limiting
        if (!check_rate_limit('inventory_update', 20, 300)) {
            $error = 'Too many update attempts. Please try again later.';
            log_security_event('rate_limit_exceeded', ['page' => 'admin-inventory']);
        } else {
            $action = sanitize_input($_POST['action'] ?? '');
            
            if ($action === 'add_book') {
                $title = sanitize_input($_POST['title'] ?? '');
                $author = sanitize_input($_POST['author'] ?? '');
                $isbn = sanitize_input($_POST['isbn'] ?? '');
                $price = validate_float($_POST['price'] ?? '');
                $condition = sanitize_input($_POST['condition'] ?? '');
                $description = sanitize_input($_POST['description'] ?? '');
                
                if (empty($title) || empty($author) || !$price) {
                    $error = 'Title, author, and price are required';
                } else {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO products (title, author, isbn, price, condition, description) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$title, $author, $isbn, $price, $condition, $description]);
                        
                        $message = 'Product added successfully';
                        log_security_event('product_added', ['title' => $title, 'author' => $author]);
                    } catch (PDOException $e) {
                        $error = 'An error occurred while adding the product';
                        log_security_event('database_error', ['error' => $e->getMessage()]);
                    }
                }
            } elseif ($action === 'delete_book') {
                $book_id = validate_int($_POST['book_id'] ?? '');
                
                if (!$book_id) {
                    $error = 'Invalid product ID';
                } else {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                        $stmt->execute([$book_id]);
                        
                        $message = 'Product deleted successfully';
                        log_security_event('product_deleted', ['id' => $book_id]);
                    } catch (PDOException $e) {
                        $error = 'An error occurred while deleting the product';
                        log_security_event('database_error', ['error' => $e->getMessage()]);
                    }
                }
            }
        }
    }
}

// Get search parameters
$search = sanitize_input($_GET['search'] ?? '');
$condition_filter = sanitize_input($_GET['condition'] ?? '');

// Build query
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($condition_filter) {
    $where_conditions[] = "condition = ?";
    $params[] = $condition_filter;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get products
try {
    $stmt = $pdo->prepare("SELECT * FROM products $where_clause ORDER BY title");
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'An error occurred while fetching products';
    log_security_event('database_error', ['error' => $e->getMessage()]);
    $products = [];
}

// Get statistics
try {
    $total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $total_value = $pdo->query("SELECT SUM(price) FROM products")->fetchColumn() ?: 0;
    $conditions = $pdo->query("SELECT condition, COUNT(*) as count FROM products WHERE condition IS NOT NULL GROUP BY condition")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $total_products = 0;
    $total_value = 0;
    $conditions = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Bort's Books</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .title {
            font-size: 2rem;
            font-weight: 800;
            color: #232946;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: #667eea;
            display: block;
        }
        .stat-label {
            color: #666;
            margin-top: 0.5rem;
        }
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .filter-row {
            display: grid;
            grid-template-columns: 1fr 200px auto;
            gap: 1rem;
            align-items: end;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .product-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #232946;
            margin-bottom: 0.5rem;
        }
        .product-author {
            color: #667eea;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .product-details {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
        }
        .product-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: #28a745;
            margin-bottom: 1rem;
        }
        .submit-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102,126,234,0.3);
        }
        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .delete-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        .message {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        .success {
            background: #d4edda;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .condition-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .condition-excellent {
            background: #d4edda;
            color: #155724;
        }
        .condition-good {
            background: #cce5ff;
            color: #004085;
        }
        .condition-fair {
            background: #fff3cd;
            color: #856404;
        }
        .condition-poor {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin-dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="header">
            <h1 class="title">Inventory Management</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="message success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-value"><?php echo $total_products; ?></span>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card">
                <span class="stat-value">$<?php echo number_format($total_value, 2); ?></span>
                <div class="stat-label">Total Value</div>
            </div>
            <?php foreach ($conditions as $condition): ?>
                <div class="stat-card">
                    <span class="stat-value"><?php echo $condition['count']; ?></span>
                    <div class="stat-label"><?php echo ucfirst($condition['condition']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Add Product Form -->
        <div class="form-container">
            <h2>Add New Product</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="add_book">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" name="title" id="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="author">Author</label>
                        <input type="text" name="author" id="author" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="isbn">ISBN</label>
                        <input type="text" name="isbn" id="isbn">
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price ($)</label>
                        <input type="number" name="price" id="price" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="condition">Condition</label>
                        <select name="condition" id="condition">
                            <option value="">Select condition</option>
                            <option value="excellent">Excellent</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" rows="3"></textarea>
                </div>
                
                <button type="submit" class="submit-btn">Add Product</button>
            </form>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <form method="GET">
                <div class="filter-row">
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Title, author, or ISBN...">
                    </div>
                    <div class="form-group">
                        <label for="condition_filter">Condition</label>
                        <select name="condition" id="condition_filter">
                            <option value="">All Conditions</option>
                            <option value="excellent" <?php echo $condition_filter === 'excellent' ? 'selected' : ''; ?>>Excellent</option>
                            <option value="good" <?php echo $condition_filter === 'good' ? 'selected' : ''; ?>>Good</option>
                            <option value="fair" <?php echo $condition_filter === 'fair' ? 'selected' : ''; ?>>Fair</option>
                            <option value="poor" <?php echo $condition_filter === 'poor' ? 'selected' : ''; ?>>Poor</option>
                        </select>
                    </div>
                    <button type="submit" class="submit-btn">Filter</button>
                </div>
            </form>
        </div>
        
        <!-- Products Grid -->
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div class="product-title">
                        <?php echo htmlspecialchars($product['title']); ?>
                    </div>
                    <div class="product-author">
                        by <?php echo htmlspecialchars($product['author']); ?>
                    </div>
                    <div class="product-details">
                        <?php if ($product['isbn']): ?>
                            ISBN: <?php echo htmlspecialchars($product['isbn']); ?><br>
                        <?php endif; ?>
                        <?php if ($product['condition']): ?>
                            <span class="condition-badge condition-<?php echo $product['condition']; ?>">
                                <?php echo ucfirst($product['condition']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="product-price">
                        $<?php echo number_format($product['price'], 2); ?>
                    </div>
                    <?php if ($product['description']): ?>
                        <div class="product-details">
                            <?php echo htmlspecialchars($product['description']); ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" style="margin-top: 1rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="delete_book">
                        <input type="hidden" name="book_id" value="<?php echo $product['id']; ?>">
                        <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete this product?')">Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html> 