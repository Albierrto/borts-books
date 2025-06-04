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
        log_security_event('csrf_failure', ['page' => 'admin-quick-edit']);
    } else {
        // Check rate limiting
        if (!check_rate_limit('quick_edit', 20, 300)) {
            $error = 'Too many edit attempts. Please try again later.';
            log_security_event('rate_limit_exceeded', ['page' => 'admin-quick-edit']);
        } else {
            $book_id = validate_int($_POST['book_id'] ?? '');
            $field = sanitize_input($_POST['field'] ?? '');
            $value = sanitize_input($_POST['value'] ?? '');
            
            if (!$book_id || empty($field) || empty($value)) {
                $error = 'Please provide all required fields';
            } else {
                try {
                    // Validate field name for security
                    $allowed_fields = ['title', 'author', 'isbn', 'price', 'condition', 'description'];
                    if (!in_array($field, $allowed_fields)) {
                        throw new Exception('Invalid field name');
                    }
                    
                    $stmt = $pdo->prepare("UPDATE books SET $field = ? WHERE id = ?");
                    $stmt->execute([$value, $book_id]);
                    
                    $message = 'Book updated successfully';
                    log_security_event('book_updated', [
                        'book_id' => $book_id,
                        'field' => $field
                    ]);
                } catch (Exception $e) {
                    $error = 'An error occurred while updating the book';
                    log_security_event('database_error', ['error' => $e->getMessage()]);
                }
            }
        }
    }
}

// Get all books
try {
    $stmt = $pdo->query("SELECT id, title, author, isbn, price, condition, description FROM books ORDER BY title");
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
    <title>Quick Edit Books - Bort's Books</title>
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
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .book-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .book-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #232946;
            margin-bottom: 1rem;
        }
        .book-field {
            margin-bottom: 1rem;
        }
        .field-label {
            display: block;
            font-weight: 600;
            color: #666;
            margin-bottom: 0.5rem;
        }
        .field-value {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .field-input {
            flex: 1;
            padding: 0.5rem;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        .field-input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        .save-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .save-btn:hover {
            background: #5a67d8;
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
            <h1 class="title">Quick Edit Books</h1>
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
        
        <div class="books-grid">
            <?php foreach ($books as $book): ?>
                <div class="book-card">
                    <h2 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h2>
                    
                    <form method="POST" class="book-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        
                        <div class="book-field">
                            <label class="field-label">Title</label>
                            <div class="field-value">
                                <input type="text" name="value" class="field-input" value="<?php echo htmlspecialchars($book['title']); ?>">
                                <input type="hidden" name="field" value="title">
                                <button type="submit" class="save-btn">Save</button>
                            </div>
                        </div>
                    </form>
                    
                    <form method="POST" class="book-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        
                        <div class="book-field">
                            <label class="field-label">Author</label>
                            <div class="field-value">
                                <input type="text" name="value" class="field-input" value="<?php echo htmlspecialchars($book['author']); ?>">
                                <input type="hidden" name="field" value="author">
                                <button type="submit" class="save-btn">Save</button>
                            </div>
                        </div>
                    </form>
                    
                    <form method="POST" class="book-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        
                        <div class="book-field">
                            <label class="field-label">ISBN</label>
                            <div class="field-value">
                                <input type="text" name="value" class="field-input" value="<?php echo htmlspecialchars($book['isbn']); ?>">
                                <input type="hidden" name="field" value="isbn">
                                <button type="submit" class="save-btn">Save</button>
                            </div>
                        </div>
                    </form>
                    
                    <form method="POST" class="book-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        
                        <div class="book-field">
                            <label class="field-label">Price</label>
                            <div class="field-value">
                                <input type="number" name="value" class="field-input" step="0.01" value="<?php echo htmlspecialchars($book['price']); ?>">
                                <input type="hidden" name="field" value="price">
                                <button type="submit" class="save-btn">Save</button>
                            </div>
                        </div>
                    </form>
                    
                    <form method="POST" class="book-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        
                        <div class="book-field">
                            <label class="field-label">Condition</label>
                            <div class="field-value">
                                <input type="text" name="value" class="field-input" value="<?php echo htmlspecialchars($book['condition']); ?>">
                                <input type="hidden" name="field" value="condition">
                                <button type="submit" class="save-btn">Save</button>
                            </div>
                        </div>
                    </form>
                    
                    <form method="POST" class="book-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        
                        <div class="book-field">
                            <label class="field-label">Description</label>
                            <div class="field-value">
                                <textarea name="value" class="field-input" rows="3"><?php echo htmlspecialchars($book['description']); ?></textarea>
                                <input type="hidden" name="field" value="description">
                                <button type="submit" class="save-btn">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html> 