<?php
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';

// Start secure session
secure_session_start();

// Set security headers
set_security_headers();

// Check admin authentication
check_admin_auth();

// Generate CSRF token for forms
$csrf_token = generate_csrf_token();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
        log_security_event('csrf_failure', ['page' => 'admin-mass-shipping']);
    } else {
        // Check rate limiting
        if (!check_rate_limit('mass_shipping_update', 10, 300)) {
            $error = 'Too many update attempts. Please try again later.';
            log_security_event('rate_limit_exceeded', ['page' => 'admin-mass-shipping']);
        } else {
            $shipping_status = sanitize_input($_POST['shipping_status'] ?? '');
            $book_ids = array_map('intval', $_POST['book_ids'] ?? []);
            
            if (empty($shipping_status) || empty($book_ids)) {
                $error = 'Please select both shipping status and books';
            } else {
                try {
                    $placeholders = str_repeat('?,', count($book_ids) - 1) . '?';
                    $stmt = $pdo->prepare("UPDATE books SET shipping_status = ? WHERE id IN ($placeholders)");
                    $params = array_merge([$shipping_status], $book_ids);
                    $stmt->execute($params);
                    
                    $message = 'Shipping status updated successfully';
                    log_security_event('mass_shipping_update', [
                        'status' => $shipping_status,
                        'book_count' => count($book_ids)
                    ]);
                } catch (PDOException $e) {
                    $error = 'An error occurred while updating shipping status';
                    log_security_event('database_error', ['error' => $e->getMessage()]);
                }
            }
        }
    }
}

// Get all books
try {
    $stmt = $pdo->query("SELECT id, title, shipping_status FROM books ORDER BY title");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'An error occurred while fetching books';
    log_security_event('database_error', ['error' => $e->getMessage()]);
    $books = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mass Shipping Update - Bort's Books</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .container {
            max-width: 1200px;
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
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }
        .form-group select,
        .form-group input[type="text"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        .form-group select:focus,
        .form-group input[type="text"]:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .book-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .book-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
        }
        .book-title {
            flex: 1;
            font-weight: 500;
        }
        .book-status {
            font-size: 0.9rem;
            color: #666;
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
    </style>
</head>
<body>
    <div class="container">
        <a href="admin-dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="header">
            <h1 class="title">Mass Shipping Update</h1>
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
        
        <div class="form-container">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="shipping_status">New Shipping Status</label>
                    <select name="shipping_status" id="shipping_status" required>
                        <option value="">Select Status</option>
                        <option value="in_stock">In Stock</option>
                        <option value="out_of_stock">Out of Stock</option>
                        <option value="on_order">On Order</option>
                        <option value="discontinued">Discontinued</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Select Books to Update</label>
                    <div class="books-grid">
                        <?php foreach ($books as $book): ?>
                            <div class="book-item">
                                <input type="checkbox" name="book_ids[]" value="<?php echo $book['id']; ?>" id="book_<?php echo $book['id']; ?>">
                                <label for="book_<?php echo $book['id']; ?>" class="book-title">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </label>
                                <span class="book-status">
                                    <?php echo htmlspecialchars($book['shipping_status']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">Update Shipping Status</button>
            </form>
        </div>
    </div>
</body>
</html> 