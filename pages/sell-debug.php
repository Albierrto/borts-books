<?php
session_start();
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/database-encryption.php';

// Simple admin check (optional, comment out if not needed)
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     die('Admin only');
// }

$error = '';
$rows = [];
$encryption = new DatabaseEncryption();

try {
    $stmt = $db->query('SELECT * FROM sell_submissions ORDER BY created_at DESC LIMIT 10');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = $e->getMessage();
}

function decrypt_field($val, $encryption) {
    if (!$val) return '';
    try {
        return $encryption->decrypt($val);
    } catch (Exception $e) {
        return '[decryption error]';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sell Submissions Debug</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; color: #222; }
        .container { max-width: 900px; margin: 2rem auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px #0001; padding: 2rem; }
        h1 { color: #2563eb; }
        table { width: 100%; border-collapse: collapse; margin-top: 2rem; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #2563eb; color: #fff; }
        tr:nth-child(even) { background: #f9fafb; }
        .error { color: #b91c1c; background: #fee2e2; padding: 1em; border-radius: 8px; margin-bottom: 1em; }
        .decrypted { color: #059669; }
    </style>
</head>
<body>
<div class="container">
    <h1>Sell Submissions Debug</h1>
    <?php if ($error): ?>
        <div class="error">Error: <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <table>
        <tr>
            <th>ID</th>
            <th>Seller Name</th>
            <th>Email</th>
            <th>Book Title</th>
            <th>Status</th>
            <th>Created</th>
            <th>Description</th>
        </tr>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><span class="decrypted"><?php echo htmlspecialchars(decrypt_field($row['seller_name'], $encryption)); ?></span></td>
                <td><span class="decrypted"><?php echo htmlspecialchars(decrypt_field($row['seller_email'], $encryption)); ?></span></td>
                <td><?php echo htmlspecialchars($row['book_title']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                <td><span class="decrypted"><?php echo htmlspecialchars(decrypt_field($row['description'], $encryption)); ?></span></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <p style="margin-top:2em;color:#888;">If you see your test submission here, the database write is working. If not, check for errors above or in your PHP error log.</p>
</div>
</body>
</html> 