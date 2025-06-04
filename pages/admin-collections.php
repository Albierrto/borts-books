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
        log_security_event('csrf_failure', ['page' => 'admin-collections']);
    } else {
        // Check rate limiting
        if (!check_rate_limit('collection_update', 10, 300)) {
            $error = 'Too many update attempts. Please try again later.';
            log_security_event('rate_limit_exceeded', ['page' => 'admin-collections']);
        } else {
            $action = sanitize_input($_POST['action'] ?? '');
            
            if ($action === 'create') {
                $name = sanitize_input($_POST['name'] ?? '');
                $description = sanitize_input($_POST['description'] ?? '');
                
                if (empty($name)) {
                    $error = 'Collection name is required';
                } else {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO collections (name, description) VALUES (?, ?)");
                        $stmt->execute([$name, $description]);
                        
                        $message = 'Collection created successfully';
                        log_security_event('collection_created', ['name' => $name]);
                    } catch (PDOException $e) {
                        $error = 'An error occurred while creating the collection';
                        log_security_event('database_error', ['error' => $e->getMessage()]);
                    }
                }
            } elseif ($action === 'delete') {
                $collection_id = validate_int($_POST['collection_id'] ?? '');
                
                if (!$collection_id) {
                    $error = 'Invalid collection ID';
                } else {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM collections WHERE id = ?");
                        $stmt->execute([$collection_id]);
                        
                        $message = 'Collection deleted successfully';
                        log_security_event('collection_deleted', ['id' => $collection_id]);
                    } catch (PDOException $e) {
                        $error = 'An error occurred while deleting the collection';
                        log_security_event('database_error', ['error' => $e->getMessage()]);
                    }
                }
            }
        }
    }
}

// Get all collections
try {
    $stmt = $pdo->query("SELECT * FROM collections ORDER BY name");
    $collections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'An error occurred while fetching collections';
    log_security_event('database_error', ['error' => $e->getMessage()]);
    $collections = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Collections - Bort's Books</title>
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
            margin-bottom: 2rem;
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
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        .collections-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .collection-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .collection-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #232946;
            margin-bottom: 0.5rem;
        }
        .collection-description {
            color: #666;
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
    </style>
</head>
<body>
    <div class="container">
        <a href="admin-dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="header">
            <h1 class="title">Manage Collections</h1>
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
            <h2>Create New Collection</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label for="name">Collection Name</label>
                    <input type="text" name="name" id="name" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" rows="3"></textarea>
                </div>
                
                <button type="submit" class="submit-btn">Create Collection</button>
            </form>
        </div>
        
        <div class="collections-grid">
            <?php foreach ($collections as $collection): ?>
                <div class="collection-card">
                    <div class="collection-name">
                        <?php echo htmlspecialchars($collection['name']); ?>
                    </div>
                    <div class="collection-description">
                        <?php echo htmlspecialchars($collection['description']); ?>
                    </div>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="collection_id" value="<?php echo $collection['id']; ?>">
                        <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete this collection?')">Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html> 