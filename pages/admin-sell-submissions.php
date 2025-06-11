<?php
session_start();

// Simple admin check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

define('INCLUDED_FROM_APP', true);
require_once dirname(__DIR__) . '/includes/db.php';

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
                            <tr class="summary-row" onclick="toggleDetails(<?php echo $submission['id']; ?>)" role="button" tabindex="0" aria-expanded="false">
                                <td>
                                    <strong><?php echo htmlspecialchars($submission['seller_name']); ?></strong><br>
                                    <small style="color: var(--gray-500);"><?php echo htmlspecialchars($submission['seller_email']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($submission['book_title']); ?></strong><br>
                                    <small style="color: var(--gray-500);">by <?php echo htmlspecialchars($submission['book_author']); ?></small>
                                </td>
                                <td>
                                    <span class="badge status-<?php echo $submission['status']; ?>" role="status">
                                        <i class="fas fa-<?php 
                                            echo $submission['status'] === 'pending' ? 'clock' : 
                                                ($submission['status'] === 'quoted' ? 'dollar-sign' : 
                                                ($submission['status'] === 'completed' ? 'check' : 'times')); 
                                        ?>"></i>
                                        <?php echo ucfirst($submission['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($submission['quote_amount']): ?>
                                        <span class="quote-amount">$<?php echo number_format($submission['quote_amount'], 2); ?></span>
                                    <?php else: ?>
                                        <span style="color: var(--gray-400);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($submission['created_at'])); ?></td>
                                <td>
                                    <div class="submission-actions">
                                        <button onclick="event.stopPropagation();editSubmission(<?php echo $submission['id']; ?>, '<?php echo htmlspecialchars($submission['status']); ?>', '<?php echo htmlspecialchars($submission['admin_notes'] ?? ''); ?>', '<?php echo $submission['quote_amount']; ?>')" class="btn btn-sm" aria-label="Edit submission">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr id="details-<?php echo $submission['id']; ?>" class="details-row" style="display:none;">
                                <td colspan="6">
                                    <div class="details-content">
                                        <div class="details-section">
                                            <h4>Description</h4>
                                            <p><?php echo nl2br(htmlspecialchars($submission['description'] ?? 'N/A')); ?></p>
                                        </div>
                                        
                                        <div class="details-section">
                                            <h4>Item Details</h4>
                                            <pre style="white-space:pre-wrap;background:var(--gray-100);padding:var(--spacing-3);border-radius:8px;overflow:auto;max-height:200px;"><?php echo htmlspecialchars($submission['item_details'] ?? '{}'); ?></pre>
                                        </div>
                                        
                                        <?php if(!empty($submission['photo_paths'])): ?>
                                            <div class="details-section">
                                                <h4>Photos</h4>
                                                <div class="photo-grid">
                                                    <?php foreach (json_decode($submission['photo_paths'], true) ?? [] as $p): ?>
                                                        <img src="<?php echo htmlspecialchars($p['filename'] ?? $p); ?>" alt="Submission photo" loading="lazy">
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
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
        function editSubmission(id, status, notes, quote) {
            document.getElementById('edit_submission_id').value = id;
            document.getElementById('edit_status').value = status;
            document.getElementById('edit_admin_notes').value = notes;
            document.getElementById('edit_quote_amount').value = quote || '';
            document.getElementById('editModal').style.display = 'block';
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
</html> 