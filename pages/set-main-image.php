<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$image_id = $_POST['image_id'] ?? null;
$product_id = $_POST['product_id'] ?? null;

if (!$image_id || !$product_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $db->beginTransaction();
    
    // First, set all images for this product to not main
    $sql = "UPDATE product_images SET is_main = 0 WHERE product_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$product_id]);
    
    // Then set the specified image as main
    $sql = "UPDATE product_images SET is_main = 1 WHERE id = ? AND product_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$image_id, $product_id]);
    
    if ($stmt->rowCount() > 0) {
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Main image updated successfully']);
    } else {
        $db->rollback();
        echo json_encode(['success' => false, 'message' => 'Image not found or access denied']);
    }
    
} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?> 