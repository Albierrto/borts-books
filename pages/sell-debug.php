<?php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/database-encryption.php';

$debug_log = dirname(__DIR__) . '/logs/sell-debug.log';
$log_lines = [];
if (file_exists($debug_log)) {
    $log_lines = array_slice(file(
        $debug_log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -50);
}

$error = '';
$rows = [];
$encryption = new DatabaseEncryption();
$table_info = [];

// Get table structure
try {
    $stmt = $db->query('DESCRIBE sell_submissions');
    $table_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error .= 'Table structure error: ' . $e->getMessage() . "\n";
}

// Get last 20 submissions
try {
    $stmt = $db->query('SELECT * FROM sell_submissions ORDER BY created_at DESC LIMIT 20');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error .= 'DB error: ' . $e->getMessage() . "\n";
}

function decrypt_field($val, $encryption) {
    if (!$val) return '';
    try {
        return $encryption->decrypt($val);
    } catch (Exception $e) {
        return '[decryption error]';
    }
}

function pretty_json($data) {
    if (is_string($data)) {
        $decoded = json_decode($data, true);
        if ($decoded !== null) {
            return '<pre>' . htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT)) . '</pre>';
        }
    }
    return '<pre>' . htmlspecialchars(print_r($data, true)) . '</pre>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sell Submissions Debug</title>
    <style>
        body { font-family: monospace, monospace; background: #f3f4f6; color: #222; }
        .container { max-width: 1200px; margin: 2rem auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px #0001; padding: 2rem; }
        h1 { color: #2563eb; text-align: center; }
        .section { margin-bottom: 2rem; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 1rem; }
        th, td { border: 1px solid #ccc; padding: 0.5rem; }
        th { background: #f3f4f6; }
        pre { background: #f9fafb; padding: 1rem; border-radius: 8px; overflow-x: auto; }
        .error { background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .log { background: #f1f5f9; color: #334155; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="container">
    <h1>Sell Submissions Debug Panel</h1>
    <?php if ($error): ?><div class="error"><?php echo nl2br(htmlspecialchars($error)); ?></div><?php endif; ?>
    <div class="section">
        <h2>PHP & DB Info</h2>
        <ul>
            <li><strong>PHP Version:</strong> <?php echo phpversion(); ?></li>
            <li><strong>DB DSN:</strong> <?php echo htmlspecialchars($db->getAttribute(PDO::ATTR_CONNECTION_STATUS)); ?></li>
        </ul>
    </div>
    <div class="section">
        <h2>Table Structure (sell_submissions)</h2>
        <table>
            <tr>
                <?php foreach ($table_info as $col): ?><th><?php echo htmlspecialchars($col['Field']); ?></th><?php endforeach; ?>
            </tr>
            <tr>
                <?php foreach ($table_info as $col): ?><td><?php echo htmlspecialchars($col['Type']); ?></td><?php endforeach; ?>
            </tr>
        </table>
    </div>
    <div class="section">
        <h2>Last 20 Submissions</h2>
        <table>
            <tr>
                <?php if (!empty($rows)): foreach (array_keys($rows[0]) as $col): ?><th><?php echo htmlspecialchars($col); ?></th><?php endforeach; endif; ?>
                <th>Decoded photo_paths</th>
            </tr>
            <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($row as $col => $val): ?>
                    <td><?php echo htmlspecialchars(is_string($val) && strlen($val) > 100 ? substr($val,0,100).'...' : $val); ?></td>
                <?php endforeach; ?>
                <td><?php echo pretty_json($row['photo_paths'] ?? ''); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <div class="section">
        <h2>Debug Log (Last 50 lines)</h2>
        <div class="log">
            <?php foreach ($log_lines as $line): echo htmlspecialchars($line) . "<br>"; endforeach; ?>
        </div>
    </div>
    <div class="section">
        <h2>_POST and _FILES</h2>
        <h3>
            $_POST
        </h3>
        <?php echo pretty_json($_POST); ?>
        <h3>
            $_FILES
        </h3>
        <?php echo pretty_json($_FILES); ?>
    </div>
</div>
</body>
</html> 