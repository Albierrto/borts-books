<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

// Handle mass delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ids'])) {
    $delete_ids = $_POST['delete_ids'];
    
    if (!empty($delete_ids)) {
        try {
            $db->beginTransaction();
            
            // First, delete associated product images
            $placeholders = str_repeat('?,', count($delete_ids) - 1) . '?';
            $stmt = $db->prepare("DELETE FROM product_images WHERE product_id IN ($placeholders)");
            $stmt->execute($delete_ids);
            
            // Then delete the products
            $stmt = $db->prepare("DELETE FROM products WHERE id IN ($placeholders)");
            $stmt->execute($delete_ids);
            
            $db->commit();
            
            $_SESSION['message'] = "Successfully deleted " . count($delete_ids) . " products and their associated images.";
            $_SESSION['message_type'] = "success";
            
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['message'] = "Error deleting products: " . $e->getMessage();
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "No products selected for deletion.";
        $_SESSION['message_type'] = "error";
    }
} else {
    $_SESSION['message'] = "Invalid request.";
    $_SESSION['message_type'] = "error";
}

// Redirect back to admin panel
header('Location: admin.php');
exit;
?> 