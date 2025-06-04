<?php
require_once '../includes/security.php';
require_once '../includes/admin-auth.php';
require_once '../includes/db.php';

// Start secure session
secure_session_start();

// Set security headers
set_security_headers();

// Check if user is logged in as admin
if (!admin_is_logged_in()) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access denied']);
    } else {
        header('Location: admin-login.php');
    }
    exit;
}

// Verify CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid request token']);
    exit;
}

// Check rate limiting
if (!check_rate_limit('file_upload', 20, 3600)) {
    echo json_encode(['success' => false, 'message' => 'Too many upload attempts. Please wait before trying again.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = validate_int($_POST['product_id']);
    if (!$product_id || $product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }

    $upload_dir = '../assets/img/products/';
    
    // Create uploads directory with secure permissions
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
            exit;
        }
    }
    
    $success = true;
    $errors = [];
    $uploaded_count = 0;
    
    // Handle multiple file uploads
    if (isset($_FILES['images']) && is_array($_FILES['images']['tmp_name'])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $original_filename = basename($_FILES['images']['name'][$key]);
                $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
                
                // Enhanced file validation
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($file_extension, $allowed_extensions)) {
                    $errors[] = "Invalid file extension for $original_filename. Only JPG, PNG, GIF and WebP are allowed.";
                    continue;
                }
                
                // Validate MIME type
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $tmp_name);
                finfo_close($finfo);
                
                if (!in_array($mime_type, $allowed_types)) {
                    $errors[] = "Invalid file type for $original_filename. File appears to be: $mime_type";
                    continue;
                }
                
                // Validate file size (max 10MB)
                if ($_FILES['images']['size'][$key] > 10 * 1024 * 1024) {
                    $errors[] = "File $original_filename is too large. Maximum size is 10MB.";
                    continue;
                }
                
                // Generate secure filename
                $unique_filename = bin2hex(random_bytes(16)) . '_' . time() . '.' . $file_extension;
                $target = $upload_dir . $unique_filename;
                
                // Move uploaded file
                if (move_uploaded_file($tmp_name, $target)) {
                    // Set secure file permissions
                    chmod($target, 0644);
                    
                    try {
                        // Check if this is the first image for this product (make it main)
                        $stmt = $db->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ?");
                        $stmt->execute([$product_id]);
                        $existing_count = $stmt->fetchColumn();
                        $is_main = ($existing_count == 0) ? 1 : 0;
                        
                        // Insert into database with correct schema
                        $stmt = $db->prepare("INSERT INTO product_images (product_id, filename, is_main) VALUES (?, ?, ?)");
                        if ($stmt->execute([$product_id, $unique_filename, $is_main])) {
                            $uploaded_count++;
                            
                            // Log successful upload
                            log_security_event('file_upload_success', [
                                'product_id' => $product_id,
                                'filename' => $unique_filename,
                                'original_name' => $original_filename
                            ], 'low');
                        } else {
                            $errors[] = "Failed to save image $original_filename to database.";
                            unlink($target); // Delete the file if database insert fails
                        }
                    } catch (Exception $e) {
                        $errors[] = "Database error for $original_filename.";
                        unlink($target);
                        log_security_event('upload_database_error', [
                            'error' => $e->getMessage(),
                            'product_id' => $product_id
                        ], 'medium');
                    }
                } else {
                    $errors[] = "Failed to upload $original_filename.";
                    log_security_event('file_upload_failed', [
                        'original_name' => $original_filename,
                        'product_id' => $product_id
                    ], 'medium');
                }
            } else {
                $errors[] = "Error uploading file: " . $_FILES['images']['name'][$key];
            }
        }
    }
    
    // Return JSON response for AJAX requests
    if (empty($errors) && $uploaded_count > 0) {
        echo json_encode([
            'success' => true, 
            'message' => "$uploaded_count image(s) uploaded successfully!",
            'uploaded_count' => $uploaded_count
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => empty($errors) ? 'No images were uploaded.' : implode(", ", $errors),
            'errors' => $errors
        ]);
    }
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method or missing data']);
    exit;
} 