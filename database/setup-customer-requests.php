<?php
require_once __DIR__ . '/../includes/db.php';

try {
    // Read the SQL file
    $sql = file_get_contents(__DIR__ . '/customer_requests.sql');
    
    // Execute the SQL statements
    $db->exec($sql);
    
    echo "✅ Customer requests table created successfully!<br>";
    echo "📧 Contact form submissions will now be saved to the database.<br>";
    echo "🔧 Admin can view and manage requests at: /pages/admin-customer-requests.php<br>";
    
} catch (PDOException $e) {
    die("❌ Error creating customer requests table: " . $e->getMessage());
} 
?> 