<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Get all images for this product
        $stmt = $db->prepare("SELECT image_url FROM product_images WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Delete the images from the server
        foreach ($images as $image_url) {
            if (file_exists($image_url)) {
                unlink($image_url);
            }
        }
        
        // Delete the images from the database
        $stmt = $db->prepare("DELETE FROM product_images WHERE product_id = ?");
        $stmt->execute([$product_id]);
        
        // Delete the product
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        
        // Commit transaction
        $db->commit();
        
        $_SESSION['message'] = "Product deleted successfully!";
        $_SESSION['message_type'] = "success";
    } catch (PDOException $e) {
        // Rollback transaction on error
        $db->rollBack();
        $_SESSION['message'] = "Error deleting product: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
    
    header("Location: admin.php");
    exit;
}

// If we get here, something went wrong
$_SESSION['message'] = "Invalid request.";
$_SESSION['message_type'] = "error";
header("Location: admin.php");
exit; 