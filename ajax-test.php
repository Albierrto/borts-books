<?php
// Simple AJAX test endpoint
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "AJAX Test Endpoint Called<br>";
echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "POST data:<br>";
print_r($_POST);

if (isset($_POST['calculate_shipping_only'])) {
    echo "<br>This is the shipping calculation request!<br>";
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'AJAX test successful',
        'received_data' => $_POST
    ]);
    exit;
}
?> 