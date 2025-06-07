<?php
// RATE LIMIT CLEARING TOOL
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Rate Limit Reset Tool</h1>";

require_once 'includes/config.php';
require_once 'includes/db.php';

global $db, $pdo;
$connection = $db ?? $pdo;

if (!$connection) {
    die("❌ No database connection available");
}

if (isset($_POST['clear_limits'])) {
    try {
        // Clear all rate limiting data
        $stmt = $connection->prepare("DELETE FROM rate_limits WHERE 1=1");
        $stmt->execute();
        
        $deleted = $stmt->rowCount();
        echo "✅ Cleared $deleted rate limit entries<br>";
        
        echo "<p><strong>Rate limits have been reset. You can now try admin login again.</strong></p>";
        
    } catch (Exception $e) {
        echo "❌ Error clearing rate limits: " . $e->getMessage() . "<br>";
    }
} else {
    // Show current rate limits
    try {
        $stmt = $connection->prepare("SELECT * FROM rate_limits ORDER BY last_attempt DESC");
        $stmt->execute();
        $limits = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>Current Rate Limits</h2>";
        if (empty($limits)) {
            echo "<p>✅ No rate limits currently active</p>";
        } else {
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>Key</th><th>Attempts</th><th>Last Attempt</th><th>Window Start</th></tr>";
            foreach ($limits as $limit) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($limit['rate_key']) . "</td>";
                echo "<td>" . $limit['attempts'] . "</td>";
                echo "<td>" . $limit['last_attempt'] . "</td>";
                echo "<td>" . $limit['window_start'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "⚠️ Could not read rate limits: " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>Clear Rate Limits</h2>";
    echo "<form method='POST'>";
    echo "<p>This will clear all rate limiting data, allowing fresh login attempts.</p>";
    echo "<button type='submit' name='clear_limits' style='background:#e63946;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;'>Clear All Rate Limits</button>";
    echo "</form>";
}

echo "<br><a href='debug-admin-comprehensive.php'>← Back to Admin Debug</a>";
?> 