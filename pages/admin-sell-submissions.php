<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Initialize encryption
try {
    $encryption = new DatabaseEncryption();
} catch (Exception $e) {
    $error = 'Error initializing encryption: ' . $e->getMessage();
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
        }
    } elseif ($action === 'delete_submission') {
        $submission_id = intval($_POST['submission_id'] ?? 0);
        
        if ($submission_id > 0) {
            try {
                // First get the submission to delete associated photos
                $stmt = $db->prepare("SELECT photo_paths FROM sell_submissions WHERE id = ?");
                $stmt->execute([$submission_id]);
                $submission = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($submission && !empty($submission['photo_paths'])) {
                    $photos = json_decode($submission['photo_paths'], true);
                    if (is_array($photos)) {
                        foreach ($photos as $photo) {
                            if (!empty($photo['filename'])) {
                                $filepath = dirname(__DIR__) . '/uploads/sell-submissions/' . $photo['filename'];
                                if (file_exists($filepath)) {
                                    unlink($filepath);
                                }
                            }
                        }
                    }
                }
                
                // Then delete the submission
                $stmt = $db->prepare("DELETE FROM sell_submissions WHERE id = ?");
                $stmt->execute([$submission_id]);
                $message = 'Submission deleted successfully';
            } catch (PDOException $e) {
                $error = 'Error deleting submission: ' . $e->getMessage();
            }
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

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";
$query = "SELECT * FROM sell_submissions $where_clause ORDER BY created_at DESC";

// Get submissions
try {
    $stmt = $db->prepare($query);
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
    $in_progress_submissions = $db->query("SELECT COUNT(*) FROM sell_submissions WHERE status = 'in_progress'")->fetchColumn();
    $total_quoted_value = $db->query("SELECT COALESCE(SUM(quote_amount), 0) FROM sell_submissions WHERE quote_amount IS NOT NULL")->fetchColumn();
} catch (PDOException $e) {
    $error .= ' Error fetching statistics: ' . $e->getMessage();
    $total_submissions = 0;
    $pending_submissions = 0;
    $in_progress_submissions = 0;
    $total_quoted_value = 0;
}

// Decrypt helper function
function decrypt_field($encrypted, $encryption, $fieldName) {
    if (empty($encrypted)) {
        return '';
    }
    try {
        $decrypted = $encryption->decrypt($encrypted, $fieldName);
        return htmlspecialchars($decrypted);
    } catch (Exception $e) {
        error_log("Decryption error for field: " . $fieldName . ", Error: " . $e->getMessage());
        return '[Decryption Error]';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell Submissions - Bort's Books</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.5;
            color: var(--gray-900);
            background: var(--gray-100);
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .title {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0;
        }

        .back-btn {
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .back-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-value {
            display: block;
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--gray-600);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Filters */
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .search-box {
            display: flex;
            gap: 1rem;
        }

        .search-box input,
        .search-box select {
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            font-size: 1rem;
        }

        .search-box input {
            flex: 1;
        }

        .search-box select {
            min-width: 150px;
        }

        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #047857;
        }

        /* Submission Cards */
        .submission-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1;
            border: 2px solid red;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .submissions {
            position: relative;
            z-index: 1;
            border: 2px solid blue;
            margin-top: 20px;
            display: block !important;
        }

        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .submission-info h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--gray-900);
        }

        .contact-info {
            margin-bottom: 0.75rem;
            color: var(--gray-600);
        }

        .contact-info a {
            color: var(--primary);
            text-decoration: none;
        }

        .contact-info a:hover {
            text-decoration: underline;
        }

        .submission-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.in_progress {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-badge.quoted {
            background: #f3e8ff;
            color: #6b21a8;
        }

        .status-badge.completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-badge.rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .submission-actions {
            display: flex;
            gap: 0.75rem;
        }

        /* Manga Sets */
        .manga-sets {
            margin-bottom: 1.5rem;
        }

        .manga-sets h4 {
            margin-bottom: 1rem;
            color: var(--gray-900);
        }

        .sets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }

        .set-card {
            background: var(--gray-50);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid var(--gray-200);
        }

        .set-info > * {
            margin-bottom: 0.5rem;
        }

        .set-info > *:last-child {
            margin-bottom: 0;
        }

        /* Photos */
        .photos-section {
            margin-bottom: 1.5rem;
        }

        .photos-section h4 {
            margin-bottom: 1rem;
            color: var(--gray-900);
        }

        .photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }

        .photo-card {
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--gray-200);
        }

        .submission-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Description */
        .description-section {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: 8px;
        }

        .description-section h4 {
            margin-bottom: 0.75rem;
            color: var(--gray-900);
        }

        /* Admin Form */
        .admin-section {
            background: var(--gray-50);
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-700);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .search-box {
                flex-direction: column;
            }

            .submission-header {
                flex-direction: column;
                gap: 1rem;
            }

            .submission-actions {
                width: 100%;
                justify-content: stretch;
            }

            .submission-actions button {
                flex: 1;
            }

            .sets-grid,
            .photos-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">Sell Submissions</h1>
            <a href="admin-dashboard.php" class="back-btn">Back to Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-value"><?php echo $total_submissions; ?></span>
                <span class="stat-label">Total Submissions</span>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?php echo $pending_submissions; ?></span>
                <span class="stat-label">Pending</span>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?php echo $in_progress_submissions; ?></span>
                <span class="stat-label">In Progress</span>
            </div>
            <div class="stat-card">
                <span class="stat-value">$<?php echo number_format($total_quoted_value, 2); ?></span>
                <span class="stat-label">Total Quoted Value</span>
            </div>
        </div>

        <div class="filters">
            <div class="search-box">
                <input type="text" id="search" placeholder="Search by seller name, email, or book title..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <select id="status-filter">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="quoted" <?php echo $status_filter === 'quoted' ? 'selected' : ''; ?>>Quoted</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
                <button id="filter-btn" class="btn btn-primary">Filter</button>
            </div>
        </div>

        <div class="submissions">
            <?php foreach ($submissions as $submission): ?>
                <?php
                    try {
                        // Decrypt sensitive data
                        $decrypted_name = decrypt_field($submission['full_name'], $encryption, 'full_name');
                        $decrypted_email = decrypt_field($submission['email'], $encryption, 'email');
                        $decrypted_phone = !empty($submission['phone']) ? decrypt_field($submission['phone'], $encryption, 'phone') : '';
                        $decrypted_description = !empty($submission['description']) ? decrypt_field($submission['description'], $encryption, 'description') : '';
                        
                        // Parse manga sets
                        $manga_sets = json_decode($submission['item_details'], true) ?: [];
                        
                        // Get photos
                        $photos = json_decode($submission['photo_paths'], true) ?: [];
                ?>
                        <div style="border: 2px solid #000; margin: 20px 0; padding: 15px; background: #fff;">
                            <h3 style="margin: 0 0 10px 0;"><?php echo $decrypted_name; ?></h3>
                            <p style="margin: 5px 0;">Email: <?php echo $decrypted_email; ?></p>
                            <p style="margin: 5px 0;">Status: <?php echo ucfirst($submission['status']); ?></p>
                            <p style="margin: 5px 0;">Submitted: <?php echo date('M j, Y g:i A', strtotime($submission['created_at'])); ?></p>
                            
                            <?php if (!empty($manga_sets)): ?>
                                <div style="margin: 10px 0;">
                                    <h4 style="margin: 5px 0;">Manga Sets:</h4>
                                    <?php foreach ($manga_sets as $set): ?>
                                        <div style="margin: 5px 0; padding: 5px; background: #f5f5f5;">
                                            <strong><?php echo htmlspecialchars($set['title']); ?></strong>
                                            <br>Volumes: <?php echo htmlspecialchars($set['volumes']); ?>
                                            <br>Condition: <?php echo htmlspecialchars($set['condition']); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($decrypted_description)): ?>
                                <div style="margin: 10px 0; padding: 10px; background: #f5f5f5; border-radius: 4px;">
                                    <h4 style="margin: 0 0 5px 0;">Additional Description:</h4>
                                    <p style="margin: 0;"><?php echo nl2br($decrypted_description); ?></p>
                                </div>
                            <?php endif; ?>

                            <!-- Edit Form -->
                            <div style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 4px; border: 1px solid #dee2e6;">
                                <h4 style="margin: 0 0 15px 0;">Update Submission</h4>
                                <form method="POST" style="display: grid; gap: 15px;">
                                    <input type="hidden" name="action" value="update_submission">
                                    <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                    
                                    <div style="display: grid; gap: 5px;">
                                        <label style="font-weight: 500; color: #495057;">Status:</label>
                                        <select name="status" required style="padding: 8px; border: 1px solid #ced4da; border-radius: 4px; width: 100%;">
                                            <option value="pending" <?php echo $submission['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="in_progress" <?php echo $submission['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="quoted" <?php echo $submission['status'] === 'quoted' ? 'selected' : ''; ?>>Quoted</option>
                                            <option value="completed" <?php echo $submission['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="rejected" <?php echo $submission['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                        </select>
                                    </div>

                                    <div style="display: grid; gap: 5px;">
                                        <label style="font-weight: 500; color: #495057;">Quote Amount ($):</label>
                                        <input type="number" name="quote_amount" step="0.01" min="0" 
                                               value="<?php echo $submission['quote_amount'] ?? ''; ?>"
                                               style="padding: 8px; border: 1px solid #ced4da; border-radius: 4px; width: 100%;">
                                    </div>

                                    <div style="display: grid; gap: 5px;">
                                        <label style="font-weight: 500; color: #495057;">Admin Notes:</label>
                                        <textarea name="admin_notes" rows="3" 
                                                  style="padding: 8px; border: 1px solid #ced4da; border-radius: 4px; width: 100%; resize: vertical;"
                                        ><?php echo htmlspecialchars($submission['admin_notes'] ?? ''); ?></textarea>
                                    </div>

                                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                                        <button type="submit" 
                                                style="padding: 8px 16px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 500;">
                                            Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                <?php
                    } catch (Exception $e) {
                        error_log("Error processing submission " . $submission['id'] . ": " . $e->getMessage());
                        continue;
                    }
                ?>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function editSubmission(id, status, adminNotes, quoteAmount) {
            const modal = document.getElementById('editModal');
            document.getElementById('editSubmissionId').value = id;
            document.getElementById('editStatus').value = status;
            document.getElementById('editNotes').value = adminNotes;
            document.getElementById('editQuote').value = quoteAmount || '';
            modal.classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });

        // Handle form submission
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch(window.location.pathname, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving changes.');
            });
        });

        // Filter functionality
        document.getElementById('filter-btn').addEventListener('click', function() {
            const search = document.getElementById('search').value;
            const status = document.getElementById('status-filter').value;
            const params = new URLSearchParams(window.location.search);
            
            if (search) params.set('search', search);
            else params.delete('search');
            
            if (status) params.set('status', status);
            else params.delete('status');
            
            window.location.href = window.location.pathname + '?' + params.toString();
        });

        // Delete confirmation
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this submission? This cannot be undone.')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete_submission">
                        <input type="hidden" name="submission_id" value="${this.dataset.id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    </script>
</body>
</html> 