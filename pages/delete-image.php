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
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    } else {
        header('Location: admin-dashboard.php');
    }
    exit;
}

// Verify CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    } else {
        $_SESSION['message'] = 'Invalid security token';
        $_SESSION['message_type'] = 'error';
        header('Location: admin-dashboard.php');
    }
    exit;
}

// Check rate limiting
if (!check_rate_limit('delete_image', 20, 300)) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        echo json_encode(['success' => false, 'message' => 'Too many delete requests. Please wait before trying again.']);
    } else {
        $_SESSION['message'] = 'Too many delete requests. Please wait before trying again.';
        $_SESSION['message_type'] = 'error';
        header('Location: admin-dashboard.php');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image_id'])) {
    // Validate and sanitize input
    $image_id = validate_int($_POST['image_id']);
    
    if (!$image_id || $image_id <= 0) {
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => 'Invalid image ID']);
        } else {
            $_SESSION['message'] = 'Invalid image ID';
            $_SESSION['message_type'] = 'error';
            header('Location: admin-dashboard.php');
        }
        exit;
    }
    
    // Get image info before deleting with security verification
    $stmt = $db->prepare("SELECT product_id, filename FROM product_images WHERE id = ?");
    $stmt->execute([$image_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    
    if ($image) {
        try {
            // Additional security: verify the filename doesn't contain path traversal
            $filename = basename($image['filename']); // Remove any path components
            
            // Delete the file securely
            $file_path = '../assets/img/products/' . $filename;
            if (file_exists($file_path) && is_file($file_path)) {
                if (!unlink($file_path)) {
                    throw new Exception("Failed to delete image file.");
                }
            }
            
            // Delete from database
            $stmt = $db->prepare("DELETE FROM product_images WHERE id = ?");
            if ($stmt->execute([$image_id])) {
                log_security_event('image_deleted', [
                    'image_id' => $image_id,
                    'product_id' => $image['product_id'],
                    'filename' => $filename
                ], 'low');
                
                if ($isAjax) {
                    echo json_encode(['success' => true, 'message' => 'Image deleted successfully!']);
                } else {
                    $_SESSION['message'] = "Image deleted successfully!";
                    $_SESSION['message_type'] = "success";
                    header("Location: edit-product.php?id=" . $image['product_id']);
                }
                exit;
            } else {
                throw new Exception("Failed to delete image from database.");
            }
        } catch (Exception $e) {
            log_security_event('image_delete_error', [
                'error' => $e->getMessage(),
                'image_id' => $image_id
            ], 'medium');
            
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => 'Delete operation failed']);
            } else {
                $_SESSION['message'] = 'Delete operation failed';
                $_SESSION['message_type'] = "error";
                header("Location: edit-product.php?id=" . $image['product_id']);
            }
            exit;
        }
    } else {
        log_security_event('image_delete_not_found', ['image_id' => $image_id], 'medium');
        
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => 'Image not found']);
        } else {
            $_SESSION['message'] = "Image not found.";
            $_SESSION['message_type'] = "error";
            header("Location: admin-dashboard.php");
        }
        exit;
    }
}

// If we get here, something went wrong
log_security_event('invalid_delete_request', [], 'medium');

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
} else {
    $_SESSION['message'] = "Invalid request.";
    $_SESSION['message_type'] = "error";
    header("Location: admin-dashboard.php");
}
exit; 