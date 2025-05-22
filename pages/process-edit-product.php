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

    try {
        $stmt = $db->prepare("UPDATE products SET title = ?, description = ?, price = ?, `condition` = ? WHERE id = ?");
        $stmt->execute([$title, $description, $price, $condition, $product_id]);

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