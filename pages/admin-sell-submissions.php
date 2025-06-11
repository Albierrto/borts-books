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
            status ENUM('pending', 'in_progress', 'quoted', 'completed', 'rejected') DEFAULT 'pending',
            quote_amount DECIMAL(10,2),
            admin_notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            photo_paths JSON,
            item_details JSON
        )";
        
        $db->exec($sql);
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

if ($search) {
    $where_conditions[] = "(full_name LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
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
    $in_progress_submissions = $db->query("SELECT COUNT(*) FROM sell_submissions WHERE status = 'in_progress'")->fetchColumn();
    $quoted_submissions = $db->query("SELECT COUNT(*) FROM sell_submissions WHERE status = 'quoted'")->fetchColumn();
    $total_quoted_value = $db->query("SELECT SUM(quote_amount) FROM sell_submissions WHERE quote_amount IS NOT NULL")->fetchColumn() ?: 0;
} catch (PDOException $e) {
    $total_submissions = 0;
    $pending_submissions = 0;
    $in_progress_submissions = 0;
    $quoted_submissions = 0;
    $total_quoted_value = 0;
}

// Decrypt helper
function decrypt_field($encrypted, $encryption) {
    if (!$encrypted) return '';
    if (strlen($encrypted) > 100 || preg_match('/[^\x20-\x7E]/', $encrypted)) {
        try {
            return $encryption->decrypt($encrypted);
        } catch (Exception $e) {
            return $encrypted;
        }
    }
    return $encrypted;
}

$encryption = new DatabaseEncryption();
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
            color: var(--gray-900);
            margin-bottom: 0.5rem;
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
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }

        .search-form input:focus,
        .search-form select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .submissions-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            padding: 1rem;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            font-weight: 600;
            color: var(--gray-700);
        }

        .crm-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(37, 99, 235, 0.07);
            margin-bottom: 1.5rem;
            padding: 2rem;
            transition: box-shadow 0.2s;
        }

        .crm-card:hover {
            box-shadow: 0 8px 32px rgba(37, 99, 235, 0.12);
        }

        .crm-section-header {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.75rem;
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
        .crm-badge.in_progress { background: #dbeafe; color: #1e40af; }
        .crm-badge.quoted { background: #dcfce7; color: #166534; }
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
            transition: box-shadow 0.2s;
            cursor: pointer;
        }

        .crm-photo-grid img:hover {
            box-shadow: 0 4px 16px rgba(37,99,235,0.13);
        }

        .crm-edit-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .crm-edit-modal.active {
            display: flex;
        }

        .crm-edit-modal-content {
            background: white;
            border-radius: 14px;
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .crm-edit-modal-content h3 {
            margin-bottom: 1.5rem;
            color: var(--primary);
        }

        .crm-edit-modal-content label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .crm-edit-modal-content input,
        .crm-edit-modal-content select,
        .crm-edit-modal-content textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .crm-edit-modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .message.success {
            background: #dcfce7;
            color: #166534;
        }

        .message.error {
            background: #fee2e2;
            color: #991b1b;
        }

        @media (max-width: 768px) {
            .search-form {
                grid-template-columns: 1fr;
            }

            .crm-details-row {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">Sell Submissions</h1>
            <a href="admin-dashboard.php" class="btn">Back to Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

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
                <span class="stat-value"><?php echo $in_progress_submissions; ?></span>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat-card">
                <span class="stat-value">$<?php echo number_format($total_quoted_value, 2); ?></span>
                <div class="stat-label">Total Quoted Value</div>
            </div>
        </div>

        <div class="search-section">
            <form class="search-form" method="GET">
                <input type="text" name="search" placeholder="Search by seller name, email, or book title..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="quoted" <?php echo $status_filter === 'quoted' ? 'selected' : ''; ?>>Quoted</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
                <button type="submit" class="btn">Filter</button>
            </form>
        </div>

        <div class="submissions-table">
            <div class="table-header">
                Submissions (<?php echo count($submissions); ?>)
            </div>

            <?php if (empty($submissions)): ?>
                <div style="padding: 2rem; text-align: center; color: var(--gray-500);">
                    No submissions found.
                </div>
            <?php else: ?>
                <?php foreach ($submissions as $submission): ?>
                    <?php
                        $full_name = decrypt_field($submission['full_name'], $encryption);
                        $email = decrypt_field($submission['email'], $encryption);
                        $phone = decrypt_field($submission['phone'], $encryption);
                        $description = decrypt_field($submission['description'], $encryption);
                        $photos = json_decode($submission['photo_paths'] ?? '[]', true);
                    ?>
                    <div class="crm-card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <div>
                                <span class="crm-badge <?php echo $submission['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $submission['status'])); ?>
                                </span>
                                <strong style="font-size: 1.1em;"><?php echo htmlspecialchars($full_name); ?></strong>
                                <span style="color: var(--gray-400);">(<?php echo htmlspecialchars($email); ?>)</span>
                                <?php if ($phone): ?>
                                    <span style="color: var(--gray-400); margin-left: 0.5em;"><i class="fa fa-phone"></i> <?php echo htmlspecialchars($phone); ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="text-align: right;">
                                <span style="color: var(--gray-500);">Submitted</span><br>
                                <span style="font-weight: 600;"><?php echo date('M j, Y', strtotime($submission['created_at'])); ?></span>
                            </div>
                        </div>

                        <div class="crm-details-row">
                            <div>
                                <div class="crm-section-header">
                                    <i class="fas fa-align-left"></i> Description
                                </div>
                                <div style="background: var(--gray-50); padding: 1rem; border-radius: 8px;">
                                    <?php echo nl2br(htmlspecialchars($description)); ?>
                                </div>

                                <?php if (!empty($submission['item_details'])): ?>
                                    <div class="crm-section-header" style="margin-top: 1.5rem;">
                                        <i class="fas fa-list"></i> Item Details
                                    </div>
                                    <div style="background: var(--gray-50); padding: 1rem; border-radius: 8px;">
                                        <?php
                                            $items = json_decode($submission['item_details'], true);
                                            if (is_array($items)):
                                        ?>
                                            <ul style="list-style: disc inside; margin: 0; padding-left: 1.2em;">
                                                <?php foreach ($items as $item): ?>
                                                    <li>
                                                        <strong><?php echo htmlspecialchars($item['title'] ?? 'N/A'); ?></strong>
                                                        - Volumes: <?php echo htmlspecialchars($item['volumes'] ?? 'N/A'); ?>,
                                                        Condition: <?php echo htmlspecialchars($item['condition'] ?? 'N/A'); ?>
                                                        <?php if (!empty($item['expected_price'])): ?>
                                                            , Asking: $<?php echo htmlspecialchars($item['expected_price']); ?>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <span style="color: var(--gray-400);">No item details available</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div>
                                <div class="crm-section-header">
                                    <i class="fas fa-images"></i> Photos
                                </div>
                                <div class="crm-photo-grid">
                                    <?php if (!empty($photos)): ?>
                                        <?php foreach ($photos as $photo): ?>
                                            <?php
                                                $photoFilename = decrypt_field($photo['filename'] ?? '', $encryption);
                                                $photoToken = decrypt_field($photo['access_token'] ?? '', $encryption);
                                            ?>
                                            <img src="/uploads/sell-submissions/<?php echo htmlspecialchars($photoFilename); ?><?php echo $photoToken ? '?token=' . urlencode($photoToken) : ''; ?>"
                                                 alt="Submission photo"
                                                 loading="lazy"
                                                 onerror="this.src='/assets/img/photo-placeholder.png'">
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span style="color: var(--gray-400);">No photos</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem;">
                            <div>
                                <span style="color: var(--gray-500);">Quote:</span>
                                <?php if ($submission['quote_amount']): ?>
                                    <span style="font-weight: 600;">$<?php echo number_format($submission['quote_amount'], 2); ?></span>
                                <?php else: ?>
                                    <span style="color: var(--gray-400);">-</span>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <button onclick="editSubmission(<?php echo $submission['id']; ?>, '<?php echo $submission['status']; ?>', '<?php echo htmlspecialchars(addslashes($submission['admin_notes'] ?? '')); ?>', '<?php echo $submission['quote_amount']; ?>')"
                                        class="btn">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this submission?');" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_submission">
                                    <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                    <button type="submit" class="btn" style="background: var(--danger);">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="editModal" class="crm-edit-modal">
        <div class="crm-edit-modal-content">
            <h3>Edit Submission</h3>
            <form id="editForm" method="POST">
                <input type="hidden" name="action" value="update_submission">
                <input type="hidden" name="submission_id" id="editSubmissionId">
                
                <label for="editStatus">Status</label>
                <select id="editStatus" name="status" required>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="quoted">Quoted</option>
                    <option value="completed">Completed</option>
                    <option value="rejected">Rejected</option>
                </select>

                <label for="editQuote">Quote Amount ($)</label>
                <input type="number" id="editQuote" name="quote_amount" step="0.01" min="0">

                <label for="editNotes">Admin Notes</label>
                <textarea id="editNotes" name="admin_notes" rows="3"></textarea>

                <div class="crm-edit-modal-actions">
                    <button type="button" class="btn" style="background: var(--gray-500);" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn">Save Changes</button>
                </div>
            </form>
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
    </script>
</body>
</html> 