<?php
require_once '../includes/security.php';
require_once '../includes/admin-auth.php';

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
        log_security_event('csrf_failure', ['page' => 'admin-mass-delete']);
    } else {
        // Check rate limiting
        if (!check_rate_limit('mass_delete', 5, 300)) {
            $error = 'Too many delete attempts. Please try again later.';
            log_security_event('rate_limit_exceeded', ['page' => 'admin-mass-delete']);
        } else {
            $book_ids = array_map('intval', $_POST['book_ids'] ?? []);
            
            if (empty($book_ids)) {
                $error = 'Please select at least one book to delete';
            } else {
                try {
                    $placeholders = str_repeat('?,', count($book_ids) - 1) . '?';
                    $stmt = $pdo->prepare("DELETE FROM books WHERE id IN ($placeholders)");
                    $stmt->execute($book_ids);
                    
                    $message = 'Selected books have been deleted successfully';
                    log_security_event('books_deleted', [
                        'book_count' => count($book_ids),
                        'book_ids' => $book_ids
                    ]);
                } catch (PDOException $e) {
                    $error = 'An error occurred while deleting the books';
                    log_security_event('database_error', ['error' => $e->getMessage()]);
                }
            }
        }
    }
}

// Get all books
try {
    $stmt = $pdo->query("SELECT id, title, author, isbn FROM books ORDER BY title");
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
    <title>Mass Delete Books - Bort's Books</title>
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
        .warning-box {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .book-item {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .book-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
        }
        .book-info {
            flex: 1;
        }
        .book-title {
            font-weight: 600;
            color: #232946;
            margin-bottom: 0.25rem;
        }
        .book-details {
            font-size: 0.9rem;
            color: #666;
        }
        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
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
    </style>
</head>
<body>
    <div class="container">
        <a href="admin-dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="header">
            <h1 class="title">Mass Delete Books</h1>
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
        
        <div class="warning-box">
            ⚠️ Warning: This action cannot be undone. Please be certain before deleting books.
        </div>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="books-grid">
                <?php foreach ($books as $book): ?>
                    <div class="book-item">
                        <input type="checkbox" name="book_ids[]" value="<?php echo $book['id']; ?>" id="book_<?php echo $book['id']; ?>">
                        <div class="book-info">
                            <div class="book-title">
                                <?php echo htmlspecialchars($book['title']); ?>
                            </div>
                            <div class="book-details">
                                <?php echo htmlspecialchars($book['author']); ?> | 
                                ISBN: <?php echo htmlspecialchars($book['isbn']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button type="submit" class="delete-btn">Delete Selected Books</button>
        </form>
    </div>
</body>
</html> 