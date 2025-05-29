<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

require_once '../includes/db.php';

$pageTitle = "Customer Requests";
$message = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $request_id = (int)$_POST['request_id'];
        $status = $_POST['status'];
        $admin_notes = trim($_POST['admin_notes'] ?? '');
        
        try {
            $stmt = $db->prepare("
                UPDATE customer_requests 
                SET status = ?, admin_notes = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            if ($stmt->execute([$status, $admin_notes, $request_id])) {
                $message = '<div class="alert success"><i class="fas fa-check-circle"></i> Request updated successfully!</div>';
            } else {
                $message = '<div class="alert error"><i class="fas fa-exclamation-triangle"></i> Failed to update request.</div>';
            }
        } catch (Exception $e) {
            $message = '<div class="alert error"><i class="fas fa-exclamation-triangle"></i> Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
    
    if ($_POST['action'] === 'delete_request') {
        $request_id = (int)$_POST['request_id'];
        
        try {
            $stmt = $db->prepare("DELETE FROM customer_requests WHERE id = ?");
            if ($stmt->execute([$request_id])) {
                $message = '<div class="alert success"><i class="fas fa-check-circle"></i> Request deleted successfully!</div>';
            } else {
                $message = '<div class="alert error"><i class="fas fa-exclamation-triangle"></i> Failed to delete request.</div>';
            }
        } catch (Exception $e) {
            $message = '<div class="alert error"><i class="fas fa-exclamation-triangle"></i> Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$inquiry_filter = $_GET['inquiry_type'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if ($inquiry_filter) {
    $where_conditions[] = "inquiry_type = ?";
    $params[] = $inquiry_filter;
}

if ($search) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get customer requests
try {
    $stmt = $db->prepare("
        SELECT * FROM customer_requests 
        $where_clause 
        ORDER BY created_at DESC
    ");
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get counts for dashboard
    $stats_stmt = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count
        FROM customer_requests
    ");
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $requests = [];
    $stats = ['total' => 0, 'new_count' => 0, 'in_progress_count' => 0, 'resolved_count' => 0];
    $message = '<div class="alert error"><i class="fas fa-exclamation-triangle"></i> Error loading requests: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

function getStatusBadge($status) {
    $badges = [
        'new' => '<span class="status-badge status-new">New</span>',
        'in_progress' => '<span class="status-badge status-progress">In Progress</span>',
        'resolved' => '<span class="status-badge status-resolved">Resolved</span>',
        'closed' => '<span class="status-badge status-closed">Closed</span>'
    ];
    return $badges[$status] ?? '<span class="status-badge">Unknown</span>';
}

function getInquiryIcon($type) {
    $icons = [
        'order' => 'fas fa-shopping-cart',
        'shipping' => 'fas fa-truck',
        'return' => 'fas fa-undo',
        'selling' => 'fas fa-handshake',
        'technical' => 'fas fa-cog',
        'other' => 'fas fa-question-circle'
    ];
    return $icons[$type] ?? 'fas fa-envelope';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Bort's Books Admin</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f7f7fa; }
        .admin-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .admin-header {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(35,41,70,0.08);
            padding: 2rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .admin-title {
            font-size: 2rem;
            font-weight: 800;
            color: #232946;
            margin: 0;
        }
        .back-link {
            background: #eebbc3;
            color: #232946;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .back-link:hover {
            background: #232946;
            color: #fff;
            transform: translateY(-1px);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(35,41,70,0.08);
            padding: 1.5rem;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: #232946;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: #666;
            font-weight: 600;
        }
        .filters-section {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(35,41,70,0.08);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .filters-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        .filter-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #232946;
        }
        .filter-group input,
        .filter-group select {
            padding: 0.8rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
        }
        .filter-btn {
            background: #eebbc3;
            color: #232946;
            border: none;
            border-radius: 8px;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .filter-btn:hover {
            background: #232946;
            color: #fff;
        }
        .requests-table {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(35,41,70,0.08);
            overflow: hidden;
        }
        .table-header {
            background: #232946;
            color: #fff;
            padding: 1rem 1.5rem;
            font-weight: 700;
            font-size: 1.1rem;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #232946;
        }
        .table tr:hover {
            background: #f8f9fa;
        }
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-new {
            background: #fff3cd;
            color: #856404;
        }
        .status-progress {
            background: #cce5ff;
            color: #004085;
        }
        .status-resolved {
            background: #d4edda;
            color: #155724;
        }
        .status-closed {
            background: #f8d7da;
            color: #721c24;
        }
        .inquiry-type {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .btn-small {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .btn-view {
            background: #17a2b8;
            color: #fff;
        }
        .btn-view:hover {
            background: #138496;
        }
        .btn-delete {
            background: #dc3545;
            color: #fff;
        }
        .btn-delete:hover {
            background: #c82333;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eebbc3;
        }
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #232946;
        }
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: #000;
        }
        .request-details {
            margin-bottom: 1.5rem;
        }
        .detail-row {
            display: flex;
            margin-bottom: 1rem;
            gap: 1rem;
        }
        .detail-label {
            font-weight: 600;
            color: #232946;
            min-width: 100px;
        }
        .detail-value {
            flex: 1;
            color: #666;
        }
        .message-content {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #eebbc3;
            margin: 1rem 0;
        }
        .update-form {
            border-top: 2px solid #eebbc3;
            padding-top: 1.5rem;
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #eebbc3;
            color: #232946;
        }
        .btn-primary:hover {
            background: #232946;
            color: #fff;
        }
        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ccc;
        }
        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                text-align: center;
            }
            .filters-form {
                grid-template-columns: 1fr;
            }
            .table {
                font-size: 0.9rem;
            }
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">Customer Requests</h1>
            <a href="admin-dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>

        <?php echo $message; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['new_count']; ?></div>
                <div class="stat-label">New</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['in_progress_count']; ?></div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['resolved_count']; ?></div>
                <div class="stat-label">Resolved</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form class="filters-form" method="GET">
                <div class="filter-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, email, subject...">
                </div>
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>New</option>
                        <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="inquiry_type">Inquiry Type</label>
                    <select id="inquiry_type" name="inquiry_type">
                        <option value="">All Types</option>
                        <option value="order" <?php echo $inquiry_filter === 'order' ? 'selected' : ''; ?>>Order Support</option>
                        <option value="shipping" <?php echo $inquiry_filter === 'shipping' ? 'selected' : ''; ?>>Shipping</option>
                        <option value="return" <?php echo $inquiry_filter === 'return' ? 'selected' : ''; ?>>Returns</option>
                        <option value="selling" <?php echo $inquiry_filter === 'selling' ? 'selected' : ''; ?>>Selling</option>
                        <option value="technical" <?php echo $inquiry_filter === 'technical' ? 'selected' : ''; ?>>Technical</option>
                        <option value="other" <?php echo $inquiry_filter === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="filter-btn">
                        <i class="fas fa-search"></i>
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Requests Table -->
        <div class="requests-table">
            <div class="table-header">
                <i class="fas fa-inbox"></i>
                Customer Requests (<?php echo count($requests); ?>)
            </div>
            
            <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No requests found</h3>
                    <p>No customer requests match your current filters.</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($request['name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($request['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($request['subject']); ?></td>
                                <td>
                                    <div class="inquiry-type">
                                        <i class="<?php echo getInquiryIcon($request['inquiry_type']); ?>"></i>
                                        <?php echo ucfirst($request['inquiry_type'] ?: 'General'); ?>
                                    </div>
                                </td>
                                <td><?php echo getStatusBadge($request['status']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-small btn-view" onclick="viewRequest(<?php echo $request['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                            View
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this request?')">
                                            <input type="hidden" name="action" value="delete_request">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <button type="submit" class="btn-small btn-delete">
                                                <i class="fas fa-trash"></i>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Request Details Modal -->
    <div id="requestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Request Details</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div id="modalBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        // Store request data for modal
        const requestsData = <?php echo json_encode($requests); ?>;

        function viewRequest(requestId) {
            const request = requestsData.find(r => r.id == requestId);
            if (!request) return;

            const modalBody = document.getElementById('modalBody');
            modalBody.innerHTML = `
                <div class="request-details">
                    <div class="detail-row">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value">${escapeHtml(request.name)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value"><a href="mailto:${escapeHtml(request.email)}">${escapeHtml(request.email)}</a></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Subject:</span>
                        <span class="detail-value">${escapeHtml(request.subject)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Type:</span>
                        <span class="detail-value">${escapeHtml(request.inquiry_type || 'General')}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">${escapeHtml(request.status)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Date:</span>
                        <span class="detail-value">${new Date(request.created_at).toLocaleString()}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Message:</span>
                    </div>
                    <div class="message-content">
                        ${escapeHtml(request.message).replace(/\n/g, '<br>')}
                    </div>
                    ${request.admin_notes ? `
                        <div class="detail-row">
                            <span class="detail-label">Admin Notes:</span>
                        </div>
                        <div class="message-content">
                            ${escapeHtml(request.admin_notes).replace(/\n/g, '<br>')}
                        </div>
                    ` : ''}
                </div>
                
                <form method="POST" class="update-form">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="request_id" value="${request.id}">
                    
                    <div class="form-group">
                        <label for="status_${request.id}">Update Status:</label>
                        <select id="status_${request.id}" name="status" required>
                            <option value="new" ${request.status === 'new' ? 'selected' : ''}>New</option>
                            <option value="in_progress" ${request.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                            <option value="resolved" ${request.status === 'resolved' ? 'selected' : ''}>Resolved</option>
                            <option value="closed" ${request.status === 'closed' ? 'selected' : ''}>Closed</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_notes_${request.id}">Admin Notes:</label>
                        <textarea id="admin_notes_${request.id}" name="admin_notes" placeholder="Add internal notes about this request...">${escapeHtml(request.admin_notes || '')}</textarea>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Request</button>
                    </div>
                </form>
            `;

            document.getElementById('requestModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('requestModal').style.display = 'none';
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('requestModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html> 