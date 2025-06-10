<?php
// Admin-only schema upgrade script to align tables with encryption sizes
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo 'Access denied';
    exit;
}

define('INCLUDED_FROM_APP', true);
require_once dirname(__DIR__) . '/includes/db.php';

header('Content-Type: text/html; charset=utf-8');

echo '<h1>Schema Upgrade Tool</h1>'; echo '<style>body{font-family:Arial;margin:20px} .success{color:green} .error{color:red}</style>';

$changes = [
    'customer_requests' => [
        'name' => 'VARBINARY(512) NOT NULL',
        'email' => 'VARBINARY(512) NOT NULL',
        'message' => 'VARBINARY(4096) NOT NULL',
        'email_hash' => 'CHAR(64) NULL',
        'ip_address' => 'VARCHAR(45) NULL',
        'user_agent' => 'TEXT NULL',
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ],
    'sell_submissions' => [
        'full_name' => 'VARBINARY(1024) NOT NULL',
        'email' => 'VARBINARY(1024) NOT NULL',
        'phone' => 'VARBINARY(1024) NULL',
        'email_hash' => 'CHAR(64) NULL',
        'num_items' => 'INT DEFAULT 0',
        'overall_condition' => 'VARCHAR(50) NULL',
        'item_details' => 'JSON NULL',
        'photo_paths' => 'JSON NULL',
        'description' => 'VARBINARY(8192) NULL',
        'status' => "ENUM('pending','quoted','completed','rejected') DEFAULT 'pending'",
        'quote_amount' => 'DECIMAL(10,2) NULL',
        'admin_notes' => 'TEXT NULL',
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ]
];

foreach ($changes as $table => $cols) {
    echo "<h2>Processing $table</h2>";
    try {
        $existingCols = $db->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        echo "<p class='error'>Table $table not found: {$e->getMessage()}</p>";
        continue;
    }
    foreach ($cols as $col => $definition) {
        try {
            if (!in_array($col, $existingCols)) {
                $db->exec("ALTER TABLE $table ADD COLUMN $col $definition");
                echo "<p class='success'>Added column $col</p>";
            } else {
                // Modify size/type
                $db->exec("ALTER TABLE $table MODIFY COLUMN $col $definition");
                echo "<p class='success'>Modified column $col</p>";
            }
        } catch (PDOException $e) {
            echo "<p class='error'>Error altering $col: {$e->getMessage()}</p>";
        }
    }
}

echo '<p>Schema upgrade completed.</p>'; 