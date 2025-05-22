<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image_id'])) {
    $image_id = $_POST['image_id'];
    
    // Get image info before deleting
    $stmt = $db->prepare("SELECT product_id, image_url FROM product_images WHERE id = ?");
    $stmt->execute([$image_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($image) {
        // Delete the file
        if (file_exists($image['image_url'])) {
            unlink($image['image_url']);
        }
        
        // Delete from database
        $stmt = $db->prepare("DELETE FROM product_images WHERE id = ?");
        if ($stmt->execute([$image_id])) {
            $_SESSION['message'] = "Image deleted successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Failed to delete image from database.";
            $_SESSION['message_type'] = "error";
        }
        
        // Redirect back to product edit page
        header("Location: edit-product.php?id=" . $image['product_id']);
        exit;
    }
}

// If we get here, something went wrong
$_SESSION['message'] = "Invalid request.";
$_SESSION['message_type'] = "error";
header("Location: admin.php");
exit; 