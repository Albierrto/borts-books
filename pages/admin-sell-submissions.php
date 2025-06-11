<?php
session_start();

// Simple admin check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

define('INCLUDED_FROM_APP', true);
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/database-encryption.php';

$message = '';
$error = '';

// Check if table exists and create if needed
try {
    $result = $db->query("SHOW TABLES LIKE 'sell_submissions'");
    if ($result->rowCount() == 0) {
        // Table doesn't exist, create it
        $sql = "CREATE TABLE sell_submissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            seller_name VARCHAR(255) NOT NULL,
            seller_email VARCHAR(255) NOT NULL,
            seller_phone VARCHAR(20),
            book_title VARCHAR(255) NOT NULL,
            book_author VARCHAR(255),
            book_isbn VARCHAR(20),
            book_condition VARCHAR(50),
            description TEXT,
            status ENUM('pending', 'quoted', 'completed', 'rejected') DEFAULT 'pending',
            quote_amount DECIMAL(10,2),
            admin_notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $db->exec($sql);
        
        // Add some sample data
        $sample_sql = "INSERT INTO sell_submissions (seller_name, seller_email, book_title, book_author, status, quote_amount) VALUES 
            ('John Doe', 'john@example.com', 'Attack on Titan Vol 1', 'Hajime Isayama', 'pending', NULL),
            ('Jane Smith', 'jane@example.com', 'Demon Slayer Vol 5', 'Koyoharu Gotouge', 'quoted', 15.50),
            ('Mike Johnson', 'mike@example.com', 'One Piece Vol 100', 'Eiichiro Oda', 'completed', 25.00)";
        
        $db->exec($sample_sql);
        $message = 'Sell submissions table created with sample data!';
    }
    
    // NEW: Ensure required columns exist (backward compatibility)
    $columnsRes = $db->query("SHOW COLUMNS FROM sell_submissions");
    $existingColumns = $columnsRes->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('created_at', $existingColumns)) {
        // Add created_at column if missing
        $db->exec("ALTER TABLE sell_submissions ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER admin_notes");
    }
    if (!in_array('updated_at', $existingColumns)) {
        $db->exec("ALTER TABLE sell_submissions ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
    }
} catch (PDOException $e) {
    $error = 'Error setting up database: ' . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_submission') {
        $submission_id = intval($_POST['submission_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $admin_notes = trim($_POST['admin_notes'] ?? '');
        $quote_amount = floatval($_POST['quote_amount'] ?? 0);
        
        if ($submission_id > 0 && !empty($status)) {
            try {
                $stmt = $db->prepare("UPDATE sell_submissions SET status = ?, admin_notes = ?, quote_amount = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$status, $admin_notes, $quote_amount, $submission_id]);
                $message = 'Submission updated successfully';
            } catch (PDOException $e) {
                $error = 'Error updating submission: ' . $e->getMessage();
            }
        } else {
            $error = 'Submission ID and status are required';
        }
    } elseif ($action === 'delete_submission') {
        $submission_id = intval($_POST['submission_id'] ?? 0);
        
        if ($submission_id > 0) {
            try {
                $stmt = $db->prepare("DELETE FROM sell_submissions WHERE id = ?");
                $stmt->execute([$submission_id]);
                $message = 'Submission deleted successfully';
            } catch (PDOException $e) {
                $error = 'Error deleting submission: ' . $e->getMessage();
            }
        } else {
            $error = 'Invalid submission ID';
        }
    }
}

// Get filter parameters
$status_filter = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(seller_name LIKE ? OR seller_email LIKE ? OR book_title LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get submissions
try {
    $stmt = $db->prepare("SELECT * FROM sell_submissions $where_clause ORDER BY created_at DESC");
    $stmt->execute($params);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If created_at column is missing, add it dynamically and retry once
    if ($e->errorInfo[1] == 1054) { // Unknown column
        try {
            $db->exec("ALTER TABLE sell_submissions ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
            $db->exec("ALTER TABLE sell_submissions ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            // Retry the query
            $stmt = $db->prepare("SELECT * FROM sell_submissions $where_clause ORDER BY created_at DESC");
            $stmt->execute($params);
            $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $error = '';
        } catch (PDOException $inner) {
            $error = 'Error fixing schema: ' . $inner->getMessage();
            $submissions = [];
        }
    } else {
        $error = 'Error fetching submissions: ' . $e->getMessage();
        $submissions = [];
    }
}

// Get statistics
try {
    $total_submissions = $db->query("SELECT COUNT(*) FROM sell_submissions")->fetchColumn();
    $pending_submissions = $db->query("SELECT COUNT(*) FROM sell_submissions WHERE status = 'pending'")->fetchColumn();
    $quoted_submissions = $db->query("SELECT COUNT(*) FROM sell_submissions WHERE status = 'quoted'")->fetchColumn();
    $total_quoted_value = $db->query("SELECT SUM(quote_amount) FROM sell_submissions WHERE quote_amount IS NOT NULL")->fetchColumn() ?: 0;
} catch (PDOException $e) {
    $total_submissions = 0;
    $pending_submissions = 0;
    $quoted_submissions = 0;
    $total_quoted_value = 0;
}

// Decrypt helper
function decrypt_field($encrypted, $encryption) {
    if (!$encrypted) return '';
    // Only decrypt if it looks encrypted (base64 or long binary)
    if (strlen($encrypted) > 100 || preg_match('/[^\x20-\x7E]/', $encrypted)) {
        try {
            return $encryption->decrypt($encrypted);
        } catch (Exception $e) {
            return $encrypted; // fallback to raw value
        }
    }
    return $encrypted;
}

$encryption = new DatabaseEncryption();

// --- DEBUG PANEL ---
$debug = true; // Set to false to hide debug info
if ($debug) {
    $keyFile = __DIR__ . '/../includes/config/encryption.key';
    $saltFile = __DIR__ . '/../includes/config/encryption.salt';
    $key = file_exists($keyFile) ? file_get_contents($keyFile) : '[missing]';
    $salt = file_exists($saltFile) ? file_get_contents($saltFile) : '[missing]';
    echo '<div style="background:#fffbe6;border:2px solid #ffe58f;padding:1em 2em;margin-bottom:2em;border-radius:10px;font-size:0.95em;">';
    echo '<strong>DEBUG PANEL</strong><br>';
    echo 'Encryption Key Hash: <code>' . htmlspecialchars(substr(hash('sha256', $key),0,16)) . '</code><br>';
    echo 'Salt Hash: <code>' . htmlspecialchars(substr(hash('sha256', $salt),0,16)) . '</code><br>';
    if ($key === '[missing]' || $salt === '[missing]') {
        echo '<span style="color:red;font-weight:bold;">WARNING: Encryption key or salt is missing!</span><br>';
    }
    echo '<hr style="margin:0.7em 0;">';
    foreach ($submissions as $submission) {
        echo '<div style="margin-bottom:1em;">';
        $raw_name = $submission['full_name'] ?? $submission['seller_name'] ?? '';
        $raw_email = $submission['email'] ?? $submission['seller_email'] ?? '';
        $raw_desc = $submission['description'] ?? '';
        $decrypted_name = decrypt_field($raw_name, $encryption);
        $decrypted_email = decrypt_field($raw_email, $encryption);
        $decrypted_desc = decrypt_field($raw_desc, $encryption);
        echo '<b>ID:</b> ' . $submission['id'] . ' | <b>Raw Name:</b> <code>' . htmlspecialchars($raw_name) . '</code> | <b>Decrypted:</b> <code>' . htmlspecialchars($decrypted_name) . '</code><br>';
        echo '<b>Raw Email:</b> <code>' . htmlspecialchars($raw_email) . '</code> | <b>Decrypted:</b> <code>' . htmlspecialchars($decrypted_email) . '</code><br>';
        echo '<b>Raw Desc:</b> <code>' . htmlspecialchars($raw_desc) . '</code> | <b>Decrypted:</b> <code>' . htmlspecialchars($decrypted_desc) . '</code><br>';
        if (!empty($submission['photo_paths'])) {
            $photos = json_decode($submission['photo_paths'], true);
            if (is_array($photos)) {
                foreach ($photos as $p) {
                    $raw_fn = $p['filename'] ?? '';
                    $raw_tok = $p['access_token'] ?? '';
                    $dec_fn = decrypt_field($raw_fn, $encryption);
                    $dec_tok = decrypt_field($raw_tok, $encryption);
                    echo '<span style="font-size:0.95em;">Photo: <code>' . htmlspecialchars($raw_fn) . '</code> → <code>' . htmlspecialchars($dec_fn) . '</code></span><br>';
                }
            }
        }
        echo '</div>';
    }
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell Submissions - Bort's Books</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        :root {
            /* Color System */
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #059669;
            --warning: #d97706;
            --danger: #dc2626;
            --info: #0284c7;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            
            /* Spacing */
            --spacing-1: 0.25rem;
            --spacing-2: 0.5rem;
            --spacing-3: 0.75rem;
            --spacing-4: 1rem;
            --spacing-6: 1.5rem;
            --spacing-8: 2rem;
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: var(--spacing-4);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-8);
            padding: var(--spacing-6);
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
        }

        .title {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: var(--spacing-6);
            margin-bottom: var(--spacing-8);
        }

        .stat-card {
            background: white;
            padding: var(--spacing-6);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-value {
            display: block;
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: var(--spacing-2);
        }

        .stat-label {
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--gray-500);
            font-weight: 600;
        }

        .search-section {
            background: white;
            padding: var(--spacing-6);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-8);
        }

        .search-form {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: var(--spacing-4);
            align-items: end;
        }

        .search-form input,
        .search-form select {
            padding: var(--spacing-3);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            font-size: 0.875rem;
            transition: border-color 0.2s ease;
        }

        .search-form input:focus,
        .search-form select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn {
            padding: var(--spacing-3) var(--spacing-6);
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .submissions-table {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        .table-header {
            padding: var(--spacing-4);
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            font-weight: 600;
            color: var(--gray-700);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: var(--spacing-4);
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-700);
            border-bottom: 1px solid var(--gray-200);
        }

        td {
            padding: var(--spacing-4);
            border-bottom: 1px solid var(--gray-200);
            color: var(--gray-600);
        }

        .summary-row {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .summary-row:hover {
            background: var(--gray-50);
        }

        .summary-row td {
            position: relative;
        }

        .summary-row td:first-child::before {
            content: '▸';
            position: absolute;
            left: -20px;
            color: var(--gray-400);
            transition: transform 0.2s ease;
        }

        .summary-row.expanded td:first-child::before {
            transform: rotate(90deg);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: var(--spacing-1) var(--spacing-3);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge i {
            margin-right: var(--spacing-1);
        }

        .badge.status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge.status-quoted {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge.status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .badge.status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .details-row {
            background: var(--gray-50);
        }

        .details-row td {
            padding: var(--spacing-6);
        }

        .details-content {
            display: grid;
            gap: var(--spacing-4);
        }

        .details-section {
            background: white;
            padding: var(--spacing-4);
            border-radius: 8px;
            border: 1px solid var(--gray-200);
        }

        .details-section h4 {
            margin: 0 0 var(--spacing-2);
            color: var(--gray-700);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: var(--spacing-2);
        }

        .photo-grid img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--gray-200);
        }

        @media (max-width: 768px) {
            .search-form {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .submissions-table {
                display: block;
                overflow-x: auto;
            }

            .photo-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            :root {
                --gray-50: #111827;
                --gray-100: #1f2937;
                --gray-200: #374151;
                --gray-300: #4b5563;
                --gray-400: #6b7280;
                --gray-500: #9ca3af;
                --gray-600: #d1d5db;
                --gray-700: #e5e7eb;
                --gray-800: #f3f4f6;
                --gray-900: #f9fafb;
            }

            body {
                background: var(--gray-50);
                color: var(--gray-900);
            }

            .header,
            .stat-card,
            .search-section,
            .submissions-table {
                background: var(--gray-100);
            }

            .table-header,
            th {
                background: var(--gray-200);
                color: var(--gray-700);
            }

            .summary-row:hover {
                background: var(--gray-200);
            }

            .details-row {
                background: var(--gray-200);
            }

            .details-section {
                background: var(--gray-100);
                border-color: var(--gray-300);
            }
        }

        /* CRM Card Layout */
        .crm-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(37, 99, 235, 0.07), 0 1.5px 4px rgba(0,0,0,0.04);
            margin-bottom: 2.5rem;
            padding: 2rem 2.5rem;
            transition: box-shadow 0.2s;
            position: relative;
        }
        .crm-card:hover {
            box-shadow: 0 8px 32px rgba(37, 99, 235, 0.12), 0 2px 8px rgba(0,0,0,0.06);
        }
        .crm-section-header {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.75rem;
            letter-spacing: 0.03em;
            display: flex;
            align-items: center;
            gap: 0.5em;
        }
        .crm-details-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 900px) {
            .crm-details-row {
                grid-template-columns: 1fr;
            }
        }
        .crm-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.3em 1em;
            border-radius: 999px;
            font-size: 0.85em;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-right: 0.5em;
        }
        .crm-badge.pending { background: #fef3c7; color: #92400e; }
        .crm-badge.quoted { background: #dbeafe; color: #1e40af; }
        .crm-badge.completed { background: #dcfce7; color: #166534; }
        .crm-badge.rejected { background: #fee2e2; color: #991b1b; }
        .crm-photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-top: 0.5rem;
        }
        .crm-photo-grid img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--gray-200);
            background: #f3f4f6;
            transition: box-shadow 0.2s;
            cursor: pointer;
        }
        .crm-photo-grid img:hover {
            box-shadow: 0 4px 16px rgba(37,99,235,0.13);
        }
        .crm-item-list {
            list-style: disc inside;
            margin: 0;
            padding-left: 1.2em;
            color: #374151;
        }
        .crm-item-list li {
            margin-bottom: 0.5em;
            font-size: 1em;
        }
        .crm-edit-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.18);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s;
        }
        .crm-edit-modal.active {
            display: flex;
        }
        .crm-edit-modal-content {
            background: white;
            border-radius: 14px;
            box-shadow: 0 8px 32px rgba(37,99,235,0.13);
            padding: 2rem 2.5rem;
            min-width: 320px;
            max-width: 95vw;
            width: 400px;
            animation: slideUp 0.2s;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(40px); opacity: 0; } to { transform: none; opacity: 1; } }
        .crm-edit-modal-content h3 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: var(--primary);
            font-size: 1.2rem;
            font-weight: 700;
        }
        .crm-edit-modal-content label {
            font-weight: 600;
            margin-bottom: 0.3em;
            display: block;
        }
        .crm-edit-modal-content input,
        .crm-edit-modal-content select,
        .crm-edit-modal-content textarea {
            width: 100%;
            padding: 0.7em 1em;
            border: 1.5px solid var(--gray-200);
            border-radius: 7px;
            margin-bottom: 1em;
            font-size: 1em;
            background: #f9fafb;
            transition: border 0.2s;
        }
        .crm-edit-modal-content input:focus,
        .crm-edit-modal-content select:focus,
        .crm-edit-modal-content textarea:focus {
            border-color: var(--primary);
            outline: none;
        }
        .crm-edit-modal-actions {
            display: flex;
            gap: 1em;
            justify-content: flex-end;
        }
        .crm-edit-modal-actions button {
            padding: 0.7em 1.5em;
            border-radius: 7px;
            border: none;
            font-weight: 700;
            font-size: 1em;
            cursor: pointer;
            background: var(--primary);
            color: white;
            transition: background 0.2s;
        }
        .crm-edit-modal-actions .cancel {
            background: #e5e7eb;
            color: #374151;
        }
        .crm-edit-modal-actions button:hover {
            background: var(--primary-dark);
        }
        .crm-edit-modal-actions .cancel:hover {
            background: #d1d5db;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin-dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="header">
            <h1 class="title">Sell Submissions</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="message success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-value"><?php echo $total_submissions; ?></span>
                <div class="stat-label">Total Submissions</div>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?php echo $pending_submissions; ?></span>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?php echo $quoted_submissions; ?></span>
                <div class="stat-label">Quoted</div>
            </div>
            <div class="stat-card">
                <span class="stat-value">$<?php echo number_format($total_quoted_value, 2); ?></span>
                <div class="stat-label">Total Quoted Value</div>
            </div>
        </div>
        
        <!-- Search -->
        <div class="search-section">
            <form class="search-form" method="GET">
                <input type="text" name="search" placeholder="Search by seller name, email, or book title..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php if($status_filter=='pending') echo 'selected'; ?>>Pending</option>
                    <option value="quoted" <?php if($status_filter=='quoted') echo 'selected'; ?>>Quoted</option>
                    <option value="completed" <?php if($status_filter=='completed') echo 'selected'; ?>>Completed</option>
                    <option value="rejected" <?php if($status_filter=='rejected') echo 'selected'; ?>>Rejected</option>
                </select>
                <button type="submit" class="btn">Filter</button>
            </form>
        </div>
        
        <!-- Submissions Table -->
        <div class="submissions-table">
            <div class="table-header">
                Submissions (<?php echo count($submissions); ?>)
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th scope="col">Seller</th>
                        <th scope="col">Book Title</th>
                        <th scope="col">Status</th>
                        <th scope="col">Quote</th>
                        <th scope="col">Submitted</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($submissions)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: var(--spacing-8); color: var(--gray-500);">
                                No submissions found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($submissions as $submission): ?>
                            <?php
                                // Decrypt fields if needed
                                $seller_name = isset($submission['seller_name']) ? $submission['seller_name'] : decrypt_field($submission['full_name'] ?? '', $encryption);
                                $seller_email = isset($submission['seller_email']) ? $submission['seller_email'] : decrypt_field($submission['email'] ?? '', $encryption);
                                $description = isset($submission['description']) ? $submission['description'] : decrypt_field($submission['description'] ?? '', $encryption);
                                $item_details = $submission['item_details'] ?? '';
                                $item_details_fmt = '';
                                if ($item_details) {
                                    $arr = json_decode($item_details, true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($arr)) {
                                        $item_details_fmt = '<ul class="crm-item-list">';
                                        foreach ($arr as $item) {
                                            $item_details_fmt .= '<li>';
                                            $item_details_fmt .= '<strong>' . htmlspecialchars($item['title'] ?? 'N/A') . '</strong> - Volumes: ' . htmlspecialchars($item['volumes'] ?? 'N/A') . ', Condition: ' . htmlspecialchars($item['condition'] ?? 'N/A');
                                            if (!empty($item['expected_price'])) $item_details_fmt .= ', Asking: $' . htmlspecialchars($item['expected_price']);
                                            $item_details_fmt .= '</li>';
                                        }
                                        $item_details_fmt .= '</ul>';
                                    } else {
                                        $item_details_fmt = '[Unreadable]';
                                    }
                                } else {
                                    $item_details_fmt = 'N/A';
                                }
                                // Photos
                                $photos = [];
                                if (!empty($submission['photo_paths'])) {
                                    $photos = json_decode($submission['photo_paths'], true);
                                    if (!is_array($photos)) $photos = [];
                                }
                            ?>
                            <tr>
                                <td colspan="6" style="padding:0; border:none;">
                                    <div class="crm-card">
                                        <div style="display:flex;justify-content:space-between;align-items:center;gap:1.5em;flex-wrap:wrap;">
                                            <div>
                                                <span class="crm-badge <?php echo htmlspecialchars($submission['status']); ?>">
                                                    <i class="fas fa-<?php 
                                                        echo $submission['status'] === 'pending' ? 'clock' : 
                                                            ($submission['status'] === 'quoted' ? 'dollar-sign' : 
                                                            ($submission['status'] === 'completed' ? 'check' : 'times')); 
                                                    ?>"></i>
                                                    <?php echo ucfirst($submission['status']); ?>
                                                </span>
                                                <strong style="font-size:1.1em;"> <?php echo htmlspecialchars($seller_name ?: 'N/A'); ?> </strong>
                                                <span style="color:var(--gray-400);font-size:0.95em;">(<?php echo htmlspecialchars($seller_email ?: 'N/A'); ?>)</span>
                                            </div>
                                            <div style="text-align:right;min-width:120px;">
                                                <span style="color:var(--gray-500);font-size:0.95em;">Submitted</span><br>
                                                <span style="font-weight:600;"> <?php echo date('M j, Y', strtotime($submission['created_at'])); ?> </span>
                                            </div>
                                        </div>
                                        <div class="crm-details-row">
                                            <div>
                                                <div class="crm-section-header"><i class="fas fa-align-left"></i> Description</div>
                                                <div style="background:var(--gray-100);padding:1em;border-radius:8px;min-height:48px;"> <?php echo nl2br(htmlspecialchars($description ?: 'N/A')); ?> </div>
                                                <div class="crm-section-header" style="margin-top:1.5em;"><i class="fas fa-list"></i> Item Details</div>
                                                <div style="background:var(--gray-100);padding:1em;border-radius:8px;min-height:48px;"> <?php echo $item_details_fmt; ?> </div>
                                            </div>
                                            <div>
                                                <div class="crm-section-header"><i class="fas fa-images"></i> Photos</div>
                                                <div class="crm-photo-grid">
                                                    <?php if (!empty($photos)): ?>
                                                        <?php foreach ($photos as $p): ?>
                                                            <?php
                                                                $photoFilename = !empty($p['filename']) ? decrypt_field($p['filename'], $encryption) : '';
                                                                $photoToken = !empty($p['access_token']) ? decrypt_field($p['access_token'], $encryption) : '';
                                                            ?>
                                                            <img src="/uploads/sell-submissions/<?php echo htmlspecialchars($photoFilename); ?><?php echo $photoToken ? '?token=' . urlencode($photoToken) : ''; ?>" alt="Submission photo" loading="lazy" onerror="this.src='/assets/img/photo-placeholder.png'">
                                                            <div style="font-size:10px;color:#888;word-break:break-all;">[<?php echo htmlspecialchars($photoFilename); ?>]</div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span style="color:var(--gray-400);">No photos</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:space-between;align-items:center;gap:1.5em;flex-wrap:wrap;margin-top:1.5em;">
                                            <div>
                                                <span style="color:var(--gray-500);font-size:0.95em;">Quote:</span>
                                                <?php if ($submission['quote_amount']): ?>
                                                    <span class="quote-amount">$<?php echo number_format($submission['quote_amount'], 2); ?></span>
                                                <?php else: ?>
                                                    <span style="color: var(--gray-400);">-</span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="display:flex;gap:0.5em;">
                                                <button onclick="editSubmission(<?php echo $submission['id']; ?>, '<?php echo htmlspecialchars($submission['status']); ?>', '<?php echo htmlspecialchars($submission['admin_notes'] ?? ''); ?>', '<?php echo $submission['quote_amount']; ?>')" class="btn btn-sm" aria-label="Edit submission" style="min-width:100px;"> <i class="fas fa-edit"></i> Edit </button>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this submission?');" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete_submission">
                                                    <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" style="min-width:80px;">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Submission</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_submission">
                <input type="hidden" name="submission_id" id="edit_submission_id">
                
                <div class="form-group">
                    <label for="edit_status">Status</label>
                    <select id="edit_status" name="status" required>
                        <option value="pending">Pending</option>
                        <option value="quoted">Quoted</option>
                        <option value="completed">Completed</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_quote_amount">Quote Amount ($)</label>
                    <input type="number" id="edit_quote_amount" name="quote_amount" step="0.01" min="0">
                </div>
                
                <div class="form-group">
                    <label for="edit_admin_notes">Admin Notes</label>
                    <textarea id="edit_admin_notes" name="admin_notes" placeholder="Internal notes..."></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeModal()" class="btn">Cancel</button>
                    <button type="submit" class="btn btn-success">Update Submission</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Robust edit modal logic for submissions
        let currentEditId = null;
        function editSubmission(id, status, adminNotes, quoteAmount) {
            currentEditId = id;
            let modal = document.getElementById('crmEditModal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'crmEditModal';
                modal.className = 'crm-edit-modal';
                modal.innerHTML = `
                    <div class="crm-edit-modal-content">
                        <h3>Edit Submission</h3>
                        <form id="crmEditForm">
                            <label for="editStatus">Status</label>
                            <select id="editStatus" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="quoted">Quoted</option>
                                <option value="completed">Completed</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            <label for="editQuote">Quote Amount ($)</label>
                            <input type="number" id="editQuote" name="quote_amount" step="0.01" min="0">
                            <label for="editNotes">Admin Notes</label>
                            <textarea id="editNotes" name="admin_notes" rows="3"></textarea>
                            <div id="crmEditFeedback" style="margin-bottom:0.7em;font-size:0.97em;"></div>
                            <div class="crm-edit-modal-actions">
                                <button type="button" class="cancel" onclick="closeEditModal()">Cancel</button>
                                <button type="submit">Save</button>
                                <button type="button" class="btn btn-danger" id="deleteSubmissionBtn" style="margin-left:auto;">Delete</button>
                            </div>
                            <input type="hidden" name="action" value="update_submission">
                            <input type="hidden" name="submission_id" id="editSubmissionId">
                        </form>
                    </div>
                `;
                document.body.appendChild(modal);
            }
            // Always update values
            document.getElementById('editStatus').value = status;
            document.getElementById('editQuote').value = quoteAmount || '';
            document.getElementById('editNotes').value = adminNotes || '';
            document.getElementById('editSubmissionId').value = id;
            document.getElementById('crmEditFeedback').innerHTML = '';
            modal.classList.add('active');
            // Submit handler
            document.getElementById('crmEditForm').onsubmit = function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                fetch(window.location.pathname, {
                    method: 'POST',
                    body: formData,
                }).then(r => r.text()).then(html => {
                    document.getElementById('crmEditFeedback').innerHTML = '<span style="color:green;">Saved!</span>';
                    setTimeout(() => { modal.classList.remove('active'); window.location.reload(); }, 700);
                }).catch(() => {
                    document.getElementById('crmEditFeedback').innerHTML = '<span style="color:red;">Error saving changes.</span>';
                });
            };
            // Delete handler
            document.getElementById('deleteSubmissionBtn').onclick = function() {
                if (!confirm('Are you sure you want to delete this submission?')) return;
                const formData = new FormData();
                formData.append('action', 'delete_submission');
                formData.append('submission_id', id);
                fetch(window.location.pathname, {
                    method: 'POST',
                    body: formData,
                }).then(r => r.text()).then(html => {
                    document.getElementById('crmEditFeedback').innerHTML = '<span style="color:green;">Deleted!</span>';
                    setTimeout(() => { modal.classList.remove('active'); window.location.reload(); }, 700);
                }).catch(() => {
                    document.getElementById('crmEditFeedback').innerHTML = '<span style="color:red;">Error deleting submission.</span>';
                });
            };
        }
        function closeEditModal() {
            let modal = document.getElementById('crmEditModal');
            if (modal) modal.classList.remove('active');
        }
        
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        function toggleDetails(id) {
            const row = document.getElementById('details-' + id);
            const summaryRow = row.previousElementSibling;
            const isExpanded = row.style.display !== 'none';
            
            // Close all other expanded rows
            document.querySelectorAll('.details-row').forEach(detailRow => {
                if (detailRow.id !== 'details-' + id) {
                    detailRow.style.display = 'none';
                    detailRow.previousElementSibling.classList.remove('expanded');
                    detailRow.previousElementSibling.setAttribute('aria-expanded', 'false');
                }
            });
            
            // Toggle current row
            row.style.display = isExpanded ? 'none' : '';
            summaryRow.classList.toggle('expanded');
            summaryRow.setAttribute('aria-expanded', !isExpanded);
            
            // Add animation
            if (!isExpanded) {
                row.style.opacity = '0';
                row.style.transform = 'translateY(-10px)';
                row.style.transition = 'all 0.2s ease';
                
                requestAnimationFrame(() => {
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                });
            }
        }

        // Add keyboard navigation
        document.querySelectorAll('.summary-row').forEach(row => {
            row.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    const id = row.getAttribute('onclick').match(/\d+/)[0];
                    toggleDetails(id);
                }
            });
        });
    </script>
</body>
</html> 