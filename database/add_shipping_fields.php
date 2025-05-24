<?php
require_once __DIR__ . '/../includes/db.php';

echo "<h2>Adding Shipping Fields to Products Table</h2>";

try {
    // Add weight column
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS weight DECIMAL(8,2) DEFAULT NULL COMMENT 'Weight in ounces'");
    echo "✅ Added weight column<br>";
    
    // Add dimensions column
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS dimensions VARCHAR(50) DEFAULT NULL COMMENT 'Length x Width x Height in inches'");
    echo "✅ Added dimensions column<br>";
    
    // Add shipping_option column
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS shipping_option ENUM('calculated', 'free', 'flat') DEFAULT 'calculated'");
    echo "✅ Added shipping_option column<br>";
    
    // Add flat_rate column
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS flat_rate DECIMAL(8,2) DEFAULT NULL COMMENT 'Flat rate shipping cost'");
    echo "✅ Added flat_rate column<br>";
    
    echo "<br><strong>✅ All shipping fields added successfully!</strong><br>";
    echo "<br>The following fields are now available:<br>";
    echo "• weight (decimal) - Product weight in ounces<br>";
    echo "• dimensions (varchar) - L x W x H in inches<br>";
    echo "• shipping_option (enum) - calculated, free, or flat<br>";
    echo "• flat_rate (decimal) - Custom flat rate amount<br>";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false || 
        strpos($e->getMessage(), 'already exists') !== false ||
        strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "ℹ️ Shipping columns already exist - no changes needed.<br>";
    } else {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
}

// Check current table structure
echo "<br><h3>Current Products Table Structure:</h3>";
try {
    $stmt = $db->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "Error checking table structure: " . $e->getMessage();
}

echo "<br><a href='../pages/admin-dashboard.php'>← Back to Admin Dashboard</a>";
?> 