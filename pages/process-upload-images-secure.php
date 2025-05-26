<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/file-security.php';

// CSRF Protection
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('CSRF token validation failed');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $upload_dir = '../uploads/products/' . $product_id . '/';
    
    // Create uploads directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $success = true;
    $errors = [];
    $uploaded_files = [];
    
    // Handle multiple file uploads with enhanced security
    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
            
            // Prepare file data for validation
            $file = [
                'name' => $_FILES['images']['name'][$key],
                'tmp_name' => $tmp_name,
                'size' => $_FILES['images']['size'][$key],
                'type' => $_FILES['images']['type'][$key]
            ];
            
            // Security validation
            $validation = FileSecurityValidator::validateImageUpload($file);
            
            if (!$validation['valid']) {
                $errors = array_merge($errors, $validation['errors']);
                
                // Quarantine suspicious files
                FileSecurityValidator::quarantineFile($tmp_name, 'Failed validation: ' . implode(', ', $validation['errors']));
                continue;
            }
            
            // Generate secure filename
            $secureFilename = FileSecurityValidator::generateSecureFileName($file['name']);
            $target = $upload_dir . $secureFilename;
            
            // Move uploaded file
            if (move_uploaded_file($tmp_name, $target)) {
                // Insert into database with prepared statement
                $stmt = $db->prepare("INSERT INTO product_images (product_id, image_url, original_name, file_size, mime_type) VALUES (?, ?, ?, ?, ?)");
                
                if ($stmt->execute([
                    $product_id, 
                    $target, 
                    FileSecurityValidator::sanitizeFileName($file['name']),
                    $file['size'],
                    $validation['mime_type']
                ])) {
                    $uploaded_files[] = $secureFilename;
                } else {
                    $errors[] = "Failed to save image " . $file['name'] . " to database.";
                    unlink($target); // Delete the file if database insert fails
                }
            } else {
                $errors[] = "Failed to upload " . $file['name'];
            }
        } else {
            $errors[] = "Error uploading file: " . $_FILES['images']['name'][$key];
        }
    }
    
    // Set session message
    if (empty($errors)) {
        $_SESSION['message'] = count($uploaded_files) . " image(s) uploaded successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Upload completed with errors: " . implode(", ", $errors);
        $_SESSION['message_type'] = "error";
    }
    
    // Redirect back to product edit page
    header("Location: edit-product.php?id=$product_id");
    exit;
} else {
    header("Location: admin.php");
    exit;
}
?> 