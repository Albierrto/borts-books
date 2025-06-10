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
    $error = 'Error fetching submissions: ' . $e->getMessage();
    $submissions = [];
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
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .title {
            font-size: 2rem;
            font-weight: 800;
            color: #232946;
            margin: 0;
        }
        
        .back-link {
            display: inline-block;
            color: #e63946;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .back-link:hover {
            color: #232946;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-value {
            display: block;
            font-size: 2rem;
            font-weight: 700;
            color: #232946;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-weight: 600;
        }
        
        .search-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .search-form {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }
        
        .search-form input,
        .search-form select {
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            background: #eebbc3;
            color: #232946;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #232946;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .submissions-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: #232946;
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 700;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #232946;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-quoted {
            background: #d4edda;
            color: #155724;
        }
        
        .status-completed {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .submission-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #232946;
        }
        
        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #232946;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        
        .form-group textarea {
            min-height: 80px;
            resize: vertical;
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
                        <th>Seller</th>
                        <th>Book Title</th>
                        <th>Status</th>
                        <th>Quote</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($submissions)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem; color: #666;">
                                No submissions found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($submission['seller_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($submission['seller_email']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($submission['book_title']); ?></strong><br>
                                    <small>by <?php echo htmlspecialchars($submission['book_author']); ?></small>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $submission['status']; ?>">
                                        <?php echo ucfirst($submission['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($submission['quote_amount']): ?>
                                        $<?php echo number_format($submission['quote_amount'], 2); ?>
                                    <?php else: ?>
                                        <span style="color: #666;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($submission['created_at'])); ?></td>
                                <td>
                                    <div class="submission-actions">
                                        <button onclick="editSubmission(<?php echo $submission['id']; ?>, '<?php echo htmlspecialchars($submission['status']); ?>', '<?php echo htmlspecialchars($submission['admin_notes'] ?? ''); ?>', '<?php echo $submission['quote_amount']; ?>')" class="btn btn-sm">
                                            Edit
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_submission">
                                            <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this submission?')">Delete</button>
                                        </form>
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
    </script>
</body>
</html> 