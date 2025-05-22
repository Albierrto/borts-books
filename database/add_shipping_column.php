<?php
require_once __DIR__ . '/../includes/db.php';
try {
    $db->exec("ALTER TABLE products ADD COLUMN shipping VARCHAR(100)");
    echo "Shipping column added successfully!";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        echo "Shipping column already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
} 