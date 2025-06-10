<?php
// Debug page for Sell Submission form
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

$out = [];

// Show table structure
try {
    $out[] = '<h2>sell_submissions table structure</h2>';
    $struct = $db->query("SHOW COLUMNS FROM sell_submissions")->fetchAll(PDO::FETCH_ASSOC);
    $out[] = '<pre>' . print_r($struct, true) . '</pre>';
} catch (PDOException $e) {
    $out[] = '<p style="color:red">Table read error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// Attempt test insert
try {
    $stmt = $db->prepare("INSERT INTO sell_submissions (
        full_name,email,email_hash,phone,num_items,overall_condition,item_details,photo_paths,description,status,created_at
    ) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())");
    $testEmail = 'debug@example.com';
    $stmt->execute([
        'debug user',
        $testEmail,
        hash('sha256',$testEmail),
        '0000000000',
        1,
        'Good',
        json_encode([]),
        json_encode([]),
        'Debug submission',
        'pending'
    ]);
    $out[] = '<p style="color:green">Insert success, ID ' . $db->lastInsertId() . '</p>';
} catch (PDOException $e) {
    $out[] = '<p style="color:red">Insert failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

?><!DOCTYPE html>
<html><head><title>Sell Debug</title></head><body>
<h1>Sell Form Debug</h1>
<?php foreach ($out as $o) echo $o; ?>
</body></html> 