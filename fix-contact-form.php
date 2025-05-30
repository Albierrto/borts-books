<?php
// Simple script to create the customer_requests table for the contact form
// This uses default XAMPP settings

$host = 'localhost';
$dbname = 'borts_books'; // Adjust this to your actual database name
$username = 'root';      // Default XAMPP username
$password = '';          // Default XAMPP password (empty)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database successfully!<br><br>";
    
    // Create customer_requests table
    $sql = "CREATE TABLE IF NOT EXISTS customer_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        inquiry_type VARCHAR(50) DEFAULT NULL,
        status ENUM('new', 'in_progress', 'resolved', 'closed') DEFAULT 'new',
        admin_notes TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_status (status),
        INDEX idx_inquiry_type (inquiry_type),
        INDEX idx_created_at (created_at)
    )";
    
    $pdo->exec($sql);
    echo "✅ Customer requests table created successfully!<br><br>";
    
    // Test if table exists and show structure
    $result = $pdo->query("DESCRIBE customer_requests");
    echo "<h3>📋 Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br>🎉 <strong>Contact form should now work properly!</strong><br>";
    echo "📧 Customer submissions will be saved to the database.<br>";
    echo "🔧 Admin can view requests at: <a href='pages/admin-customer-requests.php'>Admin Customer Requests</a><br><br>";
    
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Test the contact form on your website</li>";
    echo "<li>Check the admin panel to view submissions</li>";
    echo "<li>Delete this file (fix-contact-form.php) when done</li>";
    echo "</ol>";
    
} catch (PDOException $e) {
    echo "❌ <strong>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br><br>";
    echo "<p><strong>Common solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Make sure XAMPP MySQL is running</li>";
    echo "<li>Check if the database name 'borts_books' exists</li>";
    echo "<li>Update the database name in this script if different</li>";
    echo "<li>Verify MySQL credentials (usually root with no password for XAMPP)</li>";
    echo "</ul>";
}
?> 