<?php
require_once '../includes/security.php';
require_once '../includes/admin-auth.php';
require_once '../includes/db.php';

// Start secure session
secure_session_start();

// Set security headers
set_security_headers();

// Check admin authentication
if (!admin_is_logged_in()) {
    header('Location: admin-login.php');
    exit;
}

// Verify CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['message'] = 'Invalid request. Please try again.';
    $_SESSION['message_type'] = 'error';
    header('Location: admin-dashboard.php');
    exit;
}

// Check rate limiting
if (!check_rate_limit('product_edit', 20, 300)) {
    $_SESSION['message'] = 'Too many edit attempts. Please wait before trying again.';
    $_SESSION['message_type'] = 'error';
    header('Location: admin-dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate and sanitize inputs
        $product_id = validate_int($_POST['product_id'] ?? '');
        $title = sanitize_input($_POST['title'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $price = validate_float($_POST['price'] ?? '');
        $condition = sanitize_input($_POST['condition'] ?? '');
        $category = isset($_POST['category']) ? sanitize_input($_POST['category']) : null;

        // Weight calculations with validation
        $weight_lbs = validate_float($_POST['weight_lbs'] ?? '0') ?: 0;
        $weight_oz = validate_float($_POST['weight_oz'] ?? '0') ?: 0;
        $total_weight = $weight_lbs + ($weight_oz / 16); // Convert to total pounds

        // Dimensions with validation
        $length = sanitize_input($_POST['length'] ?? '');
        $width = sanitize_input($_POST['width'] ?? '');
        $height = sanitize_input($_POST['height'] ?? '');
        
        $dimensions = '';
        if (!empty($length) && !empty($width) && !empty($height)) {
            $dimensions = $length . 'x' . $width . 'x' . $height;
        }

        // Shipping options with validation
        $shipping_option = sanitize_input($_POST['shipping_option'] ?? 'calculated');
        $flat_rate = isset($_POST['flat_rate']) ? validate_float($_POST['flat_rate']) : null;

        // Validation checks
        if (!$product_id || $product_id <= 0) {
            throw new Exception('Invalid product ID.');
        }
        
        if (empty($title) || strlen($title) > 255) {
            throw new Exception('Product title is required and must be less than 255 characters.');
        }
        
        if ($price === false || $price < 0 || $price > 10000) {
            throw new Exception('Price must be a valid number between 0 and 10000.');
        }
        
        $allowed_conditions = ['New', 'Like New', 'Very Good', 'Good', 'Acceptable'];
        if (!in_array($condition, $allowed_conditions)) {
            throw new Exception('Invalid condition selected.');
        }
        
        $allowed_shipping = ['calculated', 'free', 'flat'];
        if (!in_array($shipping_option, $allowed_shipping)) {
            throw new Exception('Invalid shipping option selected.');
        }
        
        if ($shipping_option === 'flat' && ($flat_rate === false || $flat_rate < 0)) {
            throw new Exception('Flat rate shipping requires a valid rate amount.');
        }
        
        if ($total_weight < 0 || $total_weight > 100) {
            throw new Exception('Weight must be between 0 and 100 pounds.');
        }
        
        if (strlen($description) > 1000) {
            throw new Exception('Description must be less than 1000 characters.');
        }

        // Update the product in database
        $sql = "UPDATE products SET 
                title = ?, 
                description = ?, 
                price = ?, 
                `condition` = ?, 
                category = ?,
                weight = ?, 
                dimensions = ?, 
                shipping_option = ?, 
                flat_rate = ?
                WHERE id = ?";
                
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            $title, 
            $description, 
            $price, 
            $condition, 
            $category,
            $total_weight, 
            $dimensions, 
            $shipping_option, 
            $flat_rate, 
            $product_id
        ]);

        if ($result) {
            $_SESSION['message'] = 'Product updated successfully!';
            $_SESSION['message_type'] = 'success';
            
            // Log successful edit
            log_security_event('product_edited', [
                'product_id' => $product_id,
                'title' => $title
            ], 'low');
        } else {
            throw new Exception('Failed to update product in database.');
        }

    } catch (Exception $e) {
        $_SESSION['message'] = 'Error: ' . htmlspecialchars($e->getMessage());
        $_SESSION['message_type'] = 'error';
        
        log_security_event('product_edit_error', [
            'product_id' => $product_id ?? 'unknown',
            'error' => $e->getMessage()
        ], 'medium');
    }

    // Redirect back to the edit page
    header('Location: edit-product-clean.php?id=' . ($product_id ?? ''));
    exit;
} else {
    header('Location: admin-dashboard.php');
    exit;
} 