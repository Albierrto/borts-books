<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $condition = $_POST['condition'];
    
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Insert the product
        $stmt = $db->prepare("INSERT INTO products (title, description, price, `condition`, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$title, $description, $price, $condition]);
        $product_id = $db->lastInsertId();
        
        // Create uploads directory if it doesn't exist
        $upload_dir = '../uploads/products/' . $product_id;
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Handle image uploads
        if (isset($_FILES['images'])) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $uploaded_files = $_FILES['images'];
            $file_count = count($uploaded_files['name']);
            
            for ($i = 0; $i < $file_count; $i++) {
                if ($uploaded_files['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $uploaded_files['tmp_name'][$i];
                    $name = $uploaded_files['name'][$i];
                    $type = $uploaded_files['type'][$i];
                    
                    // Check file type
                    if (!in_array($type, $allowed_types)) {
                        throw new Exception("Invalid file type: " . $type);
                    }
                    
                    // Generate unique filename
                    $extension = pathinfo($name, PATHINFO_EXTENSION);
                    $filename = uniqid() . '.' . $extension;
                    $target_path = $upload_dir . '/' . $filename;
                    
                    // Move uploaded file
                    if (move_uploaded_file($tmp_name, $target_path)) {
                        // Insert image record
                        $stmt = $db->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
                        $stmt->execute([$product_id, $target_path]);
                    } else {
                        throw new Exception("Failed to move uploaded file: " . $name);
                    }
                }
            }
        }
        
        // Commit transaction
        $db->commit();
        
        $_SESSION['message'] = "Product added successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: edit-product-clean.php?id=" . $product_id);
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        $_SESSION['message'] = "Error adding product: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: add-product.php");
        exit;
    }
}

// If we get here, something went wrong
$_SESSION['message'] = "Invalid request.";
$_SESSION['message_type'] = "error";
header("Location: add-product.php");
exit; 