<?php
session_start();
require_once '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

$messages = [];
$errors = [];

try {
    // First, let's check what columns exist in product_images table
    $stmt = $db->query("DESCRIBE product_images");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $existing_columns = array_column($columns, 'Field');
    $messages[] = "Current table columns: " . implode(", ", $existing_columns);
    
    // Check if we need to add missing columns
    $required_columns = ['id', 'product_id', 'filename', 'is_main'];
    $missing_columns = array_diff($required_columns, $existing_columns);
    
    if (!empty($missing_columns)) {
        $messages[] = "Missing columns detected: " . implode(", ", $missing_columns);
        
        // Add missing columns
        if (in_array('filename', $missing_columns)) {
            $db->exec("ALTER TABLE product_images ADD COLUMN filename VARCHAR(255) NULL");
            $messages[] = "Added 'filename' column";
        }
        
        if (in_array('is_main', $missing_columns)) {
            $db->exec("ALTER TABLE product_images ADD COLUMN is_main TINYINT(1) DEFAULT 0");
            $messages[] = "Added 'is_main' column";
        }
    }
    
    // If we have image_url but no filename, migrate the data
    if (in_array('image_url', $existing_columns) && in_array('filename', $existing_columns)) {
        $stmt = $db->query("SELECT id, image_url FROM product_images WHERE filename IS NULL AND image_url IS NOT NULL");
        $images_to_migrate = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($images_to_migrate as $image) {
            // Extract filename from image_url path
            $filename = basename($image['image_url']);
            $update_stmt = $db->prepare("UPDATE product_images SET filename = ? WHERE id = ?");
            $update_stmt->execute([$filename, $image['id']]);
        }
        
        if (count($images_to_migrate) > 0) {
            $messages[] = "Migrated " . count($images_to_migrate) . " image records to new schema";
        }
    }
    
    // Set first image as main for products that don't have a main image
    $stmt = $db->exec("
        UPDATE product_images p1 
        SET is_main = 1 
        WHERE p1.id IN (
            SELECT * FROM (
                SELECT MIN(id) 
                FROM product_images p2 
                WHERE p2.product_id = p1.product_id 
                AND p1.product_id NOT IN (
                    SELECT DISTINCT product_id 
                    FROM product_images 
                    WHERE is_main = 1
                )
            ) AS subquery
        )
    ");
    
    if ($stmt > 0) {
        $messages[] = "Set main images for $stmt products";
    }
    
} catch (Exception $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// Create directories with proper permissions
$directories = [
    '../assets/img/products',
    '../assets/img/products/thumbs'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            $messages[] = "Created directory: $dir";
        } else {
            $errors[] = "Failed to create directory: $dir";
        }
    } else {
        // Check if directory is writable
        if (is_writable($dir)) {
            $messages[] = "Directory exists and is writable: $dir";
        } else {
            $errors[] = "Directory exists but is not writable: $dir";
        }
    }
}

// Test database connection and table structure
try {
    $test_stmt = $db->query("SELECT id, product_id, filename, is_main FROM product_images LIMIT 1");
    $messages[] = "Database table structure test: PASSED";
} catch (Exception $e) {
    $errors[] = "Database table structure test: FAILED - " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Image System - Admin</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .fix-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .fix-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .results {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .message {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 4px;
            border-left: 4px solid #28a745;
            background: #d4edda;
            color: #155724;
        }
        
        .error {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 4px;
            border-left: 4px solid #dc3545;
            background: #f8d7da;
            color: #721c24;
        }
        
        .actions {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 0.5rem;
            font-weight: 600;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .btn.secondary {
            background: #6c757d;
        }
        
        .btn.secondary:hover {
            background: #545b62;
        }
    </style>
</head>
<body>
    <div class="fix-container">
        <div style="margin-bottom: 1rem;">
            <a href="admin.php" style="display: inline-flex; align-items: center; gap: 0.5rem; color: #666; text-decoration: none; font-weight: 600;">
                ← Back to Admin Dashboard
            </a>
        </div>
        
        <div class="fix-header">
            <h1>🔧 Image System Fix</h1>
            <p>Repair database schema and directory structure for image uploads</p>
        </div>
        
        <div class="results">
            <h3>Fix Results</h3>
            
            <?php if (!empty($messages)): ?>
                <h4>✅ Success Messages:</h4>
                <?php foreach ($messages as $message): ?>
                    <div class="message"><?php echo htmlspecialchars($message); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <h4>❌ Errors:</h4>
                <?php foreach ($errors as $error): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (empty($messages) && empty($errors)): ?>
                <div class="message">No fixes were needed - everything looks good!</div>
            <?php endif; ?>
        </div>
        
        <div class="actions">
            <h3>Next Steps</h3>
            <p>If there were no errors, you can now try uploading images again.</p>
            
            <a href="admin.php" class="btn">Back to Admin</a>
            <a href="upload-images.php?product_id=815" class="btn secondary">Test Upload Images</a>
            
            <?php if (!empty($errors)): ?>
                <br><br>
                <p style="color: #dc3545; font-weight: bold;">
                    ⚠️ There were errors. You may need to contact your hosting provider to fix directory permissions.
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 