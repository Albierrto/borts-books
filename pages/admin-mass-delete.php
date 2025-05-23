<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}
if (isset($_POST['delete_ids']) && is_array($_POST['delete_ids'])) {
    $ids = array_map('intval', $_POST['delete_ids']);
    if (count($ids) > 0) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("DELETE FROM products WHERE id IN ($in)");
        $stmt->execute($ids);
        $_SESSION['message'] = 'Selected products deleted successfully.';
        $_SESSION['message_type'] = 'success';
    }
}
header('Location: admin.php');
exit; 