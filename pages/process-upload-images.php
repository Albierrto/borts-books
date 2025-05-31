<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    } else {
        header('Location: admin.php');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    $upload_dir = '../uploads/';
    
    // Create uploads directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $success = true;
    $errors = [];
    $uploaded_count = 0;
    
    // Handle multiple file uploads
    if (isset($_FILES['images']) && is_array($_FILES['images']['tmp_name'])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $filename = basename($_FILES['images']['name'][$key]);
                $target = $upload_dir . uniqid() . '_' . $filename;
                
                // Validate file type
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($_FILES['images']['type'][$key], $allowed_types)) {
                    $errors[] = "Invalid file type for $filename. Only JPG, PNG, GIF and WebP are allowed.";
                    continue;
                }
                
                // Validate file size (max 10MB)
                if ($_FILES['images']['size'][$key] > 10 * 1024 * 1024) {
                    $errors[] = "File $filename is too large. Maximum size is 10MB.";
                    continue;
                }
                
                // Move uploaded file
                if (move_uploaded_file($tmp_name, $target)) {
                    // Insert into database
                    $stmt = $db->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
                    if ($stmt->execute([$product_id, $target])) {
                        $uploaded_count++;
                    } else {
                        $errors[] = "Failed to save image $filename to database.";
                        unlink($target); // Delete the file if database insert fails
                    }
                } else {
                    $errors[] = "Failed to upload $filename.";
                }
            } else {
                $errors[] = "Error uploading file: " . $_FILES['images']['name'][$key];
            }
        }
    }
    
    // Determine if this is an AJAX request
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    
    if ($isAjax) {
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
    } else {
        // Set session message for regular form submissions
        if (empty($errors) && $uploaded_count > 0) {
            $_SESSION['message'] = "$uploaded_count image(s) uploaded successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = empty($errors) ? 'No images were uploaded.' : "Some images failed to upload: " . implode(", ", $errors);
            $_SESSION['message_type'] = "error";
        }
        
        // Redirect back to product edit page
        header("Location: edit-product.php?id=$product_id");
    }
    exit;
} else {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    } else {
        header("Location: admin.php");
    }
    exit;
} 