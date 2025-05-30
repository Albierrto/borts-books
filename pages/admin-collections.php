<?php
session_start();
require_once '../includes/db.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_POST) {
    if (isset($_POST['update_submission'])) {
        $id = (int)$_POST['submission_id'];
        $status = $_POST['status'];
        $admin_notes = $_POST['admin_notes'];
        $quote_amount = !empty($_POST['quote_amount']) ? (float)$_POST['quote_amount'] : null;
        
        $stmt = $db->prepare("
            UPDATE collection_submissions 
            SET status = ?, admin_notes = ?, quote_amount = ? 
            WHERE id = ?
        ");
        
        if ($stmt->execute([$status, $admin_notes, $quote_amount, $id])) {
            $message = "Submission updated successfully!";
            $messageType = "success";
            
            // Send email notification if status changed to quoted
            if ($status === 'quoted' && $quote_amount > 0) {
                $stmt = $db->prepare("SELECT * FROM collection_submissions WHERE id = ?");
                $stmt->execute([$id]);
                $submission = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($submission) {
                    $subject = "Your Collection Quote from Bort's Books";
                    $email_message = "
                        <html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                                .content { background: #f9f9f9; padding: 20px; }
                                .quote-box { background: #28a745; color: white; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0; }
                                .footer { background: #333; color: white; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; }
                            </style>
                        </head>
                        <body>
                            <div class='container'>
                                <div class='header'>
                                    <h1>💰 Your Collection Quote is Ready!</h1>
                                    <p>Hello " . htmlspecialchars($submission['name']) . ",</p>
                                </div>
                                
                                <div class='content'>
                                    <h2>Great News!</h2>
                                    <p>We've reviewed your collection submission and are pleased to offer:</p>
                                    
                                    <div class='quote-box'>
                                        <h2 style='margin: 0; font-size: 2.5rem;'>$" . number_format($quote_amount, 2) . "</h2>
                                        <p style='margin: 0; font-size: 1.2rem;'>For your " . htmlspecialchars($submission['collection_type']) . " collection</p>
                                    </div>
                                    
                                    <h3>Collection Details:</h3>
                                    <p><strong>Type:</strong> " . htmlspecialchars($submission['collection_type']) . "</p>
                                    <p><strong>Estimated Items:</strong> " . $submission['estimated_items'] . "</p>
                                    <p><strong>Condition:</strong> " . htmlspecialchars($submission['condition_description']) . "</p>
                                    
                                    <h3>Next Steps:</h3>
                                    <p>✅ <strong>Accept Quote:</strong> Reply to this email or call us</p>
                                    <p>📦 <strong>Arrange Pickup:</strong> We'll schedule convenient collection</p>
                                    <p>💰 <strong>Get Paid:</strong> Payment within 48 hours of receipt</p>
                                    
                                    <p><strong>This quote is valid for 7 days.</strong></p>
                                    
                                    <h3>Questions or Ready to Proceed?</h3>
                                    <p>📧 Email: collections@bortsbooks.com</p>
                                    <p>📱 Call/Text: (555) 123-4567</p>
                                    <p>⏰ Response Time: Same day</p>
                                </div>
                                
                                <div class='footer'>
                                    <p><strong>Bort's Books - Collection Buyers</strong></p>
                                    <p>🌟 Fair Prices | 📦 Free Pickup | 💰 Fast Payment</p>
                                </div>
                            </div>
                        </body>
                        </html>
                    ";
                    
                    $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: Bort's Books <collections@bortsbooks.com>\r\n";
                    mail($submission['email'], $subject, $email_message, $headers);
                }
            }
        } else {
            $message = "Failed to update submission.";
            $messageType = "error";
        }
    }
    
    if (isset($_POST['delete_submission'])) {
        $id = (int)$_POST['submission_id'];
        $stmt = $db->prepare("DELETE FROM collection_submissions WHERE id = ?");
        
        if ($stmt->execute([$id])) {
            $message = "Submission deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to delete submission.";
            $messageType = "error";
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get submissions
$stmt = $db->prepare("
    SELECT * FROM collection_submissions 
    $where_clause 
    ORDER BY submitted_at DESC
");
$stmt->execute($params);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = [];
$stmt = $db->query("SELECT status, COUNT(*) as count FROM collection_submissions GROUP BY status");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats[$row['status']] = $row['count'];
}

$total_submissions = array_sum($stats);
$total_quoted = $db->query("SELECT SUM(quote_amount) FROM collection_submissions WHERE quote_amount IS NOT NULL")->fetchColumn() ?: 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collection Submissions - Bort's Books Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .nav-links {
            margin-top: 1rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            margin-right: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            background: rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
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
            grid-template-columns: 200px 300px 1fr auto;
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-group select,
        .form-group input {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }

        .btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .btn-success {
            background: linear-gradient(45deg, #28a745, #20c997);
        }

        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #c82333);
        }

        .submissions-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
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
            color: #333;
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
            margin: 2% auto;
            padding: 2rem;
            border-radius: 12px;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
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

        .submission-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .detail-group {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
        }

        .detail-group h4 {
            margin-bottom: 0.5rem;
            color: #667eea;
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
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

        @media (max-width: 768px) {
            .filter-row {
                grid-template-columns: 1fr;
            }
            
            .submissions-table {
                overflow-x: auto;
            }
            
            .submission-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-handshake"></i> Collection Submissions</h1>
            <p>Manage collection buying leads and quotes</p>
            <div class="nav-links">
                <a href="admin.php"><i class="fas fa-arrow-left"></i> Back to Admin</a>
                <a href="admin-inventory.php"><i class="fas fa-boxes"></i> Inventory</a>
                <a href="admin-email.php"><i class="fas fa-envelope"></i> Email Management</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-value"><?php echo $total_submissions; ?></span>
                <div class="stat-label">Total Submissions</div>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?php echo $stats['pending'] ?? 0; ?></span>
                <div class="stat-label">Pending Review</div>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?php echo $stats['quoted'] ?? 0; ?></span>
                <div class="stat-label">Quotes Sent</div>
            </div>
            <div class="stat-card">
                <span class="stat-value">$<?php echo number_format($total_quoted, 2); ?></span>
                <div class="stat-label">Total Quoted Value</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET">
                <div class="filter-row">
                    <div class="form-group">
                        <label for="status">Status Filter:</label>
                        <select name="status" id="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="reviewed" <?php echo $status_filter === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                            <option value="quoted" <?php echo $status_filter === 'quoted' ? 'selected' : ''; ?>>Quoted</option>
                            <option value="accepted" <?php echo $status_filter === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="search">Search:</label>
                        <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, email, or description...">
                    </div>
                    <div></div>
                    <button type="submit" class="btn">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Submissions Table -->
        <div class="submissions-table">
            <table>
                <thead>
                    <tr>
                        <th>Submitted</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Collection</th>
                        <th>Status</th>
                        <th>Quote</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($submissions)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: #666;">
                                No submissions found. 
                                <a href="../pages/sell-your-collection.php" target="_blank">View landing page</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td>
                                    <?php echo date('M j, Y', strtotime($submission['submitted_at'])); ?><br>
                                    <small style="color: #666;"><?php echo date('g:i A', strtotime($submission['submitted_at'])); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($submission['name']); ?></strong><br>
                                    <small style="color: #666;"><?php echo htmlspecialchars($submission['location'] ?: 'No location'); ?></small>
                                </td>
                                <td>
                                    <div style="font-size: 0.9rem;">
                                        📧 <?php echo htmlspecialchars($submission['email']); ?><br>
                                        📱 <?php echo htmlspecialchars($submission['phone']); ?><br>
                                        <small style="color: #666;">Prefers: <?php echo ucfirst($submission['preferred_contact']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 0.9rem;">
                                        <strong><?php echo ucfirst($submission['collection_type'] ?: 'Not specified'); ?></strong><br>
                                        📚 ~<?php echo $submission['estimated_items'] ?: 'Unknown'; ?> items<br>
                                        <small style="color: #666;"><?php echo ucfirst($submission['condition_description'] ?: 'No condition'); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $submission['status']; ?>">
                                        <?php echo ucfirst($submission['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($submission['quote_amount']): ?>
                                        <strong style="color: #28a745;">$<?php echo number_format($submission['quote_amount'], 2); ?></strong>
                                    <?php else: ?>
                                        <span style="color: #666;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-small" onclick="viewSubmission(<?php echo $submission['id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Submission Detail Modal -->
    <div id="submissionModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <div id="submissionDetails">
                <!-- Content loaded via JavaScript -->
            </div>
        </div>
    </div>

    <script>
        function viewSubmission(id) {
            // Get submission data
            const submissions = <?php echo json_encode($submissions); ?>;
            const submission = submissions.find(s => s.id == id);
            
            if (!submission) return;
            
            const statusOptions = ['pending', 'reviewed', 'quoted', 'accepted', 'completed'];
            const statusSelect = statusOptions.map(status => 
                `<option value="${status}" ${submission.status === status ? 'selected' : ''}>${status.charAt(0).toUpperCase() + status.slice(1)}</option>`
            ).join('');
            
            document.getElementById('submissionDetails').innerHTML = `
                <h2>Collection Submission Details</h2>
                
                <div class="submission-details">
                    <div class="detail-group">
                        <h4>Contact Information</h4>
                        <p><strong>Name:</strong> ${submission.name}</p>
                        <p><strong>Email:</strong> <a href="mailto:${submission.email}">${submission.email}</a></p>
                        <p><strong>Phone:</strong> <a href="tel:${submission.phone}">${submission.phone}</a></p>
                        <p><strong>Location:</strong> ${submission.location || 'Not provided'}</p>
                        <p><strong>Preferred Contact:</strong> ${submission.preferred_contact}</p>
                    </div>
                    
                    <div class="detail-group">
                        <h4>Collection Details</h4>
                        <p><strong>Type:</strong> ${submission.collection_type || 'Not specified'}</p>
                        <p><strong>Estimated Items:</strong> ${submission.estimated_items || 'Unknown'}</p>
                        <p><strong>Condition:</strong> ${submission.condition_description || 'Not specified'}</p>
                        <p><strong>Timeline:</strong> ${submission.timeline || 'Not specified'}</p>
                    </div>
                </div>
                
                <div class="detail-group">
                    <h4>Description</h4>
                    <p>${submission.description || 'No description provided'}</p>
                </div>
                
                <div class="detail-group">
                    <h4>Submission Info</h4>
                    <p><strong>Submitted:</strong> ${new Date(submission.submitted_at).toLocaleString()}</p>
                    <p><strong>Current Status:</strong> <span class="status-badge status-${submission.status}">${submission.status.charAt(0).toUpperCase() + submission.status.slice(1)}</span></p>
                    ${submission.quote_amount ? `<p><strong>Quote Amount:</strong> $${parseFloat(submission.quote_amount).toFixed(2)}</p>` : ''}
                </div>
                
                <form method="POST" style="margin-top: 2rem;">
                    <input type="hidden" name="submission_id" value="${submission.id}">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label for="status_update">Update Status:</label>
                            <select name="status" id="status_update" required>
                                ${statusSelect}
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="quote_amount">Quote Amount ($):</label>
                            <input type="number" name="quote_amount" id="quote_amount" step="0.01" min="0" value="${submission.quote_amount || ''}">
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label for="admin_notes">Admin Notes:</label>
                        <textarea name="admin_notes" id="admin_notes" rows="4" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">${submission.admin_notes || ''}</textarea>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" name="update_submission" class="btn btn-success">
                            <i class="fas fa-save"></i> Update Submission
                        </button>
                        <button type="submit" name="delete_submission" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this submission?')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                        <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                    </div>
                </form>
            `;
            
            document.getElementById('submissionModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('submissionModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('submissionModal');
            if (event.target === modal) {
                closeModal();
            }
        }
        
        // Auto-refresh every 2 minutes to check for new submissions
        setInterval(function() {
            if (!document.getElementById('submissionModal').style.display || document.getElementById('submissionModal').style.display === 'none') {
                location.reload();
            }
        }, 120000);
    </script>
</body>
</html> 