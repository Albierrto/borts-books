<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action']) || !isset($input['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $input['action'];
$product_id = (int)$input['product_id'];

try {
    switch ($action) {
        case 'update_description':
            if (!isset($input['description'])) {
                echo json_encode(['success' => false, 'message' => 'Description not provided']);
                exit;
            }
            
            $description = $input['description'];
            
            // Basic HTML sanitization - allow specific tags
            $allowed_tags = '<a><b><i><br><strong><em><p><ul><ol><li>';
            $description = strip_tags($description, $allowed_tags);
            
            $stmt = $db->prepare("UPDATE products SET description = ? WHERE id = ?");
            $stmt->execute([$description, $product_id]);
            
            echo json_encode(['success' => true, 'message' => 'Description updated successfully']);
            break;
            
        case 'update_price':
            if (!isset($input['price'])) {
                echo json_encode(['success' => false, 'message' => 'Price not provided']);
                exit;
            }
            
            $price = (float)$input['price'];
            
            if ($price < 0) {
                echo json_encode(['success' => false, 'message' => 'Price cannot be negative']);
                exit;
            }
            
            $stmt = $db->prepare("UPDATE products SET price = ? WHERE id = ?");
            $stmt->execute([$price, $product_id]);
            
            echo json_encode(['success' => true, 'message' => 'Price updated successfully']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            break;
    }
} catch (Exception $e) {
    error_log("Admin quick edit error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?> 