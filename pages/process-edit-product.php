<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $condition = $_POST['condition'];
    $category = $_POST['category'] ?? null;
    
    // Calculate total weight in ounces
    $weight_lbs = floatval($_POST['weight_lbs'] ?? 0);
    $weight_oz = floatval($_POST['weight_oz'] ?? 0);
    $total_weight_oz = ($weight_lbs * 16) + $weight_oz;
    
    // Combine dimensions
    $length = $_POST['length'] ?? '';
    $width = $_POST['width'] ?? '';
    $height = $_POST['height'] ?? '';
    $dimensions = '';
    if ($length && $width && $height) {
        $dimensions = $length . ' x ' . $width . ' x ' . $height;
    }
    
    // Get shipping fields
    $shipping_option = $_POST['shipping_option'] ?? 'calculated';
    $flat_rate = $_POST['flat_rate'] ?? null;
    
    // Convert flat_rate to null if empty or if not flat shipping
    if ($shipping_option !== 'flat' || empty($flat_rate)) {
        $flat_rate = null;
    }

    try {
        $stmt = $db->prepare("UPDATE products SET title = ?, description = ?, price = ?, `condition` = ?, category = ?, weight = ?, dimensions = ?, shipping_option = ?, flat_rate = ? WHERE id = ?");
        $stmt->execute([$title, $description, $price, $condition, $category, $total_weight_oz, $dimensions, $shipping_option, $flat_rate, $product_id]);

        $_SESSION['message'] = "Product updated successfully!";
        $_SESSION['message_type'] = "success";
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error updating product: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }

    header("Location: edit-product.php?id=" . $product_id);
    exit;
}

// If we get here, something went wrong
$_SESSION['message'] = "Invalid request.";
$_SESSION['message_type'] = "error";
header("Location: admin.php");
exit; 