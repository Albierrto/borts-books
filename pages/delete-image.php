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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image_id'])) {
    $image_id = (int)$_POST['image_id'];
    
    // Get image info before deleting
    $stmt = $db->prepare("SELECT product_id, filename FROM product_images WHERE id = ?");
    $stmt->execute([$image_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    
    if ($image) {
        try {
            // Delete the file
            $file_path = '../assets/img/products/' . $image['filename'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Delete from database
            $stmt = $db->prepare("DELETE FROM product_images WHERE id = ?");
            if ($stmt->execute([$image_id])) {
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
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            } else {
                $_SESSION['message'] = $e->getMessage();
                $_SESSION['message_type'] = "error";
                header("Location: edit-product.php?id=" . $image['product_id']);
            }
            exit;
        }
    } else {
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => 'Image not found']);
        } else {
            $_SESSION['message'] = "Image not found.";
            $_SESSION['message_type'] = "error";
            header("Location: admin.php");
        }
        exit;
    }
}

// If we get here, something went wrong
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
} else {
    $_SESSION['message'] = "Invalid request.";
    $_SESSION['message_type'] = "error";
    header("Location: admin.php");
}
exit; 