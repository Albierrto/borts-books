<?php
// Debug page for contact form
// Only accessible to admin session
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo 'Access denied';
    exit;
}

define('INCLUDED_FROM_APP', true);
require_once dirname(__DIR__) . '/includes/db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$messages = [];

// Check table structure
try {
    $messages[] = '<h2>customer_requests table structure</h2>';
    $cols = $db->query("SHOW COLUMNS FROM customer_requests")->fetchAll(PDO::FETCH_ASSOC);
    $messages[] = '<pre>' . print_r($cols, true) . '</pre>';
} catch (PDOException $e) {
    $messages[] = '<p style="color:red">Error reading table: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// Test insert
try {
    $stmt = $db->prepare("INSERT INTO customer_requests (name,email,email_hash,subject,message,inquiry_type,ip_address,user_agent,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
    $stmt->execute([
        'test',
        'test@example.com',
        hash('sha256','test@example.com'),
        'Debug Test',
        'This is a debug test message',
        'General',
        '127.0.0.1',
        'debug-agent'
    ]);
    $messages[] = '<p style="color:green">Insert success. Insert ID ' . $db->lastInsertId() . '</p>';
} catch (PDOException $e) {
    $messages[] = '<p style="color:red">Insert failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// Output
?><!DOCTYPE html><html><head><title>Contact Debug</title></head><body>
<h1>Contact Form Debug</h1>
<?php foreach ($messages as $m) echo $m; ?>
</body></html> 