<?php
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';

// Start secure session
secure_session_start();

// Set security headers
set_security_headers();

// Check admin authentication
check_admin_auth();

// Generate CSRF token for forms
$csrf_token = generate_csrf_token();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
        log_security_event('csrf_failure', ['page' => 'admin-sell-submissions']);
    } else {
        // Check rate limiting
        if (!check_rate_limit('submission_update', 15, 300)) {
            $error = 'Too many update attempts. Please try again later.';
            log_security_event('rate_limit_exceeded', ['page' => 'admin-sell-submissions']);
        } else {
            $action = sanitize_input($_POST['action'] ?? '');
            
            if ($action === 'update_submission') {
                $submission_id = validate_int($_POST['submission_id'] ?? '');
                $status = sanitize_input($_POST['status'] ?? '');
                $admin_notes = sanitize_input($_POST['admin_notes'] ?? '');
                $quote_amount = validate_float($_POST['quote_amount'] ?? '');
                
                if (!$submission_id || empty($status)) {
                    $error = 'Submission ID and status are required';
                } else {
                    try {
                        $stmt = $pdo->prepare("UPDATE sell_submissions SET status = ?, admin_notes = ?, quote_amount = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$status, $admin_notes, $quote_amount, $submission_id]);
                        
                        $message = 'Submission updated successfully';
                        log_security_event('submission_updated', ['id' => $submission_id, 'status' => $status]);
                    } catch (PDOException $e) {
                        $error = 'An error occurred while updating the submission';
                        log_security_event('database_error', ['error' => $e->getMessage()]);
                    }
                }
            } elseif ($action === 'delete_submission') {
                $submission_id = validate_int($_POST['submission_id'] ?? '');
                
                if (!$submission_id) {
                    $error = 'Invalid submission ID';
        } else {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM sell_submissions WHERE id = ?");
                        $stmt->execute([$submission_id]);
                        
                        $message = 'Submission deleted successfully';
                        log_security_event('submission_deleted', ['id' => $submission_id]);
                    } catch (PDOException $e) {
                        $error = 'An error occurred while deleting the submission';
                        log_security_event('database_error', ['error' => $e->getMessage()]);
                    }
                }
            }
        }
    }
}

// Get filter parameters
$status_filter = sanitize_input($_GET['status'] ?? '');
$search = sanitize_input($_GET['search'] ?? '');

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
    $stmt = $pdo->prepare("SELECT * FROM sell_submissions $where_clause ORDER BY created_at DESC");
$stmt->execute($params);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'An error occurred while fetching submissions';
    log_security_event('database_error', ['error' => $e->getMessage()]);
    $submissions = [];
}

// Get statistics
try {
    $total_submissions = $pdo->query("SELECT COUNT(*) FROM sell_submissions")->fetchColumn();
    $pending_submissions = $pdo->query("SELECT COUNT(*) FROM sell_submissions WHERE status = 'pending'")->fetchColumn();
    $quoted_submissions = $pdo->query("SELECT COUNT(*) FROM sell_submissions WHERE status = 'quoted'")->fetchColumn();
    $total_quoted_value = $pdo->query("SELECT SUM(quote_amount) FROM sell_submissions WHERE quote_amount IS NOT NULL")->fetchColumn() ?: 0;
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
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .title {
            font-size: 2rem;
            font-weight: 800;
            color: #232946;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
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
            font-size: 2rem;
            font-weight: 800;
            color: #667eea;
            display: block;
        }
        .stat-label {
            color: #666;
            margin-top: 0.5rem;
        }
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .filter-row {
            display: grid;
            grid-template-columns: 1fr 200px auto;
            gap: 1rem;
            align-items: end;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        .submissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
        }
        .submission-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .submission-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #232946;
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
        .status-reviewed {
            background: #cce5ff;
            color: #004085;
        }
        .status-quoted {
            background: #d4edda;
            color: #155724;
        }
        .status-accepted {
            background: #d1ecf1;
            color: #0c5460;
        }
        .status-completed {
            background: #e2e3e5;
            color: #383d41;
        }
        .submission-details {
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #666;
        }
        .submission-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .submit-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102,126,234,0.3);
        }
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .delete-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        .message {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        .success {
            background: #d4edda;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
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
            max-width: 600px;
            position: relative;
        }
        .close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        .quote-highlight {
            background: #d4edda;
            color: #155724;
            padding: 0.5rem;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 0.5rem;
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
        
        <!-- Filters -->
        <div class="filters">
            <form method="GET">
                <div class="filter-row">
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Seller name, email, or book title...">
                    </div>
                    <div class="form-group">
                        <label for="status_filter">Status</label>
                        <select name="status" id="status_filter">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="reviewed" <?php echo $status_filter === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                            <option value="quoted" <?php echo $status_filter === 'quoted' ? 'selected' : ''; ?>>Quoted</option>
                            <option value="accepted" <?php echo $status_filter === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
            </select>
                    </div>
                    <button type="submit" class="submit-btn">Filter</button>
                        </div>
                                </form>
                            </div>
        
        <!-- Submissions Grid -->
        <div class="submissions-grid">
            <?php foreach ($submissions as $submission): ?>
                <div class="submission-card">
                    <div class="submission-header">
                        <div class="submission-title">
                            <?php echo htmlspecialchars($submission['book_title'] ?? 'Book Submission'); ?>
                        </div>
                        <span class="status-badge status-<?php echo $submission['status']; ?>">
                            <?php echo ucfirst($submission['status']); ?>
                        </span>
                    </div>
                    
                    <div class="submission-details">
                        <strong>Seller:</strong> <?php echo htmlspecialchars($submission['seller_name']); ?><br>
                        <strong>Email:</strong> <?php echo htmlspecialchars($submission['seller_email']); ?><br>
                        <?php if ($submission['seller_phone']): ?>
                            <strong>Phone:</strong> <?php echo htmlspecialchars($submission['seller_phone']); ?><br>
                        <?php endif; ?>
                        <strong>Author:</strong> <?php echo htmlspecialchars($submission['book_author'] ?? 'N/A'); ?><br>
                        <strong>ISBN:</strong> <?php echo htmlspecialchars($submission['book_isbn'] ?? 'N/A'); ?><br>
                        <strong>Condition:</strong> <?php echo htmlspecialchars($submission['book_condition'] ?? 'N/A'); ?><br>
                        <strong>Submitted:</strong> <?php echo date('M j, Y g:i A', strtotime($submission['created_at'])); ?><br>
                        <?php if ($submission['description']): ?>
                            <strong>Description:</strong> <?php echo htmlspecialchars($submission['description']); ?><br>
                        <?php endif; ?>
                        <?php if ($submission['quote_amount']): ?>
                            <div class="quote-highlight">
                                Quote: $<?php echo number_format($submission['quote_amount'], 2); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($submission['admin_notes']): ?>
                            <strong>Admin Notes:</strong> <?php echo htmlspecialchars($submission['admin_notes']); ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="submission-actions">
                        <button class="submit-btn btn-small" onclick="openUpdateModal(<?php echo $submission['id']; ?>, '<?php echo htmlspecialchars($submission['status']); ?>', '<?php echo htmlspecialchars($submission['admin_notes'] ?? ''); ?>', '<?php echo $submission['quote_amount'] ?? ''; ?>')">
                            Update
                        </button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="action" value="delete_submission">
                            <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                            <button type="submit" class="delete-btn btn-small" onclick="return confirm('Are you sure you want to delete this submission?')">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Update Modal -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Update Submission</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="update_submission">
                <input type="hidden" id="update_submission_id" name="submission_id">
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" required>
                        <option value="pending">Pending</option>
                        <option value="reviewed">Reviewed</option>
                        <option value="quoted">Quoted</option>
                        <option value="accepted">Accepted</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="quote_amount">Quote Amount ($)</label>
                    <input type="number" name="quote_amount" id="quote_amount" step="0.01" min="0">
                </div>
                
                <div class="form-group">
                    <label for="admin_notes">Admin Notes</label>
                    <textarea name="admin_notes" id="admin_notes" rows="4"></textarea>
                </div>
                
                <button type="submit" class="submit-btn">Update Submission</button>
                <button type="button" class="delete-btn" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function openUpdateModal(submissionId, currentStatus, currentNotes, currentQuote) {
            document.getElementById('update_submission_id').value = submissionId;
            document.getElementById('status').value = currentStatus;
            document.getElementById('admin_notes').value = currentNotes;
            document.getElementById('quote_amount').value = currentQuote;
            document.getElementById('updateModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('updateModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('updateModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html> 