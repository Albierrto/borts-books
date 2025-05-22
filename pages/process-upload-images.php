<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    $upload_dir = '../uploads/';
    
    // Create uploads directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $success = true;
    $errors = [];
    
    // Handle multiple file uploads
    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
            $filename = basename($_FILES['images']['name'][$key]);
            $target = $upload_dir . uniqid() . '_' . $filename;
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['images']['type'][$key], $allowed_types)) {
                $errors[] = "Invalid file type for $filename. Only JPG, PNG and GIF are allowed.";
                continue;
            }
            
            // Move uploaded file
            if (move_uploaded_file($tmp_name, $target)) {
                // Insert into database
                $stmt = $db->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
                if (!$stmt->execute([$product_id, $target])) {
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
    
    // Set session message
    if (empty($errors)) {
        $_SESSION['message'] = "Images uploaded successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Some images failed to upload: " . implode(", ", $errors);
        $_SESSION['message_type'] = "error";
    }
    
    // Redirect back to product edit page
    header("Location: edit-product.php?id=$product_id");
    exit;
} else {
    header("Location: admin.php");
    exit;
} 