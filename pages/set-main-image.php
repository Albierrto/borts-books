<?php
require_once '../includes/security.php';
require_once '../includes/admin-auth.php';
require_once '../includes/db.php';

// Start secure session
secure_session_start();

// Set security headers
set_security_headers();

header('Content-Type: application/json');

// Check if user is admin
if (!admin_is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Verify CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

// Check rate limiting
if (!check_rate_limit('set_main_image', 20, 300)) {
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please wait before trying again.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate and sanitize input
$image_id = isset($_POST['image_id']) ? validate_int($_POST['image_id']) : null;
$product_id = isset($_POST['product_id']) ? validate_int($_POST['product_id']) : null;

if (!$image_id || !$product_id || $image_id <= 0 || $product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid required parameters']);
    exit;
}

try {
    $db->beginTransaction();
    
    // Verify the image belongs to the product (security check)
    $verifyStmt = $db->prepare("SELECT id FROM product_images WHERE id = ? AND product_id = ?");
    $verifyStmt->execute([$image_id, $product_id]);
    if (!$verifyStmt->fetch()) {
        $db->rollback();
        log_security_event('unauthorized_main_image_attempt', [
            'image_id' => $image_id,
            'product_id' => $product_id
        ], 'medium');
        echo json_encode(['success' => false, 'message' => 'Image not found or access denied']);
        exit;
    }
    
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
        log_security_event('main_image_updated', [
            'image_id' => $image_id,
            'product_id' => $product_id
        ], 'low');
        echo json_encode(['success' => true, 'message' => 'Main image updated successfully']);
    } else {
        $db->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to update main image']);
    }
    
} catch (Exception $e) {
    $db->rollback();
    log_security_event('main_image_error', [
        'error' => $e->getMessage(),
        'image_id' => $image_id,
        'product_id' => $product_id
    ], 'high');
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?> 