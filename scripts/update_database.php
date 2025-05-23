<?php
require_once __DIR__ . '/../includes/db.php';

try {
    // Start transaction
    $db->beginTransaction();

    // Update orders table
    $db->exec("ALTER TABLE orders
        ADD COLUMN stripe_session_id VARCHAR(255) AFTER id,
        ADD COLUMN customer_phone VARCHAR(20) AFTER customer_email,
        ADD COLUMN shipping_address JSON AFTER customer_phone,
        ADD COLUMN payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending' AFTER total_amount,
        ADD INDEX idx_stripe_session (stripe_session_id)");

    // Update order_items table
    $db->exec("ALTER TABLE order_items
        ADD COLUMN payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending' AFTER image_url");

    // Commit transaction
    $db->commit();
    
    echo "Database updates completed successfully!\n";
    echo "Added Stripe integration columns to orders and order_items tables.\n";

} catch (PDOException $e) {
    // Rollback transaction on error
    $db->rollBack();
    
    // Check if columns already exist
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Some columns already exist. No changes were made.\n";
    } else {
        echo "Error updating database: " . $e->getMessage() . "\n";
    }
} 