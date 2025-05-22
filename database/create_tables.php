<?php
require_once __DIR__ . '/../includes/db.php';

try {
    // Read the SQL file
    $sql = file_get_contents(__DIR__ . '/tables.sql');
    
    // Execute the SQL statements
    $db->exec($sql);
    
    echo "Tables created successfully!";
} catch (PDOException $e) {
    die("Error creating tables: " . $e->getMessage());
} 