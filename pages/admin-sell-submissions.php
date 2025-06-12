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

// Debug function
function debug_log($msg, $data = null) {
    error_log("DEBUG: $msg");
    if ($data !== null) {
        error_log("DATA: " . print_r($data, true));
    }
}

debug_log("Starting admin-sell-submissions.php");

// Check if table exists and create if needed
try {
    $result = $db->query("SHOW TABLES LIKE 'sell_submissions'");
    if ($result->rowCount() == 0) {
        // Table doesn't exist, create it
        $sql = "CREATE TABLE sell_submissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARBINARY(1024) NOT NULL,
            email VARBINARY(1024) NOT NULL,
            phone VARBINARY(1024),
            overall_condition VARCHAR(50),
            description VARBINARY(8192),
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

debug_log("Filters:", ['status' => $status_filter, 'search' => $search]);

// Initialize encryption
try {
    $encryption = new DatabaseEncryption();
    debug_log("Encryption initialized successfully");
} catch (Exception $e) {
    debug_log("Encryption initialization error: " . $e->getMessage());
    $error = 'Error initializing encryption: ' . $e->getMessage();
}

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";
$query = "SELECT * FROM sell_submissions $where_clause ORDER BY created_at DESC";

debug_log("Query:", ['sql' => $query, 'params' => $params]);

// Get submissions
try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    debug_log("Found submissions:", ['count' => count($submissions)]);

    // Filter encrypted fields if search is present
    if ($search) {
        debug_log("Filtering by search term:", ['search' => $search]);
        $filtered_submissions = [];
        foreach ($submissions as $submission) {
            try {
                $decrypted_name = decrypt_field($submission['full_name'], $encryption);
                $decrypted_email = decrypt_field($submission['email'], $encryption);
                
                if (stripos($decrypted_name, $search) !== false || 
                    stripos($decrypted_email, $search) !== false) {
                    $filtered_submissions[] = $submission;
                }
            } catch (Exception $e) {
                debug_log("Decryption error during search:", ['error' => $e->getMessage()]);
            }
        }
        $submissions = $filtered_submissions;
        debug_log("After search filter:", ['filtered_count' => count($submissions)]);
    }
} catch (PDOException $e) {
    debug_log("Database error:", ['error' => $e->getMessage()]);
    $error = 'Error fetching submissions: ' . $e->getMessage();
    $submissions = [];
}

// Get statistics
try {
    debug_log("Fetching statistics");
    $total_submissions = $db->query("SELECT COUNT(*) FROM sell_submissions")->fetchColumn();
    $pending_submissions = $db->query("SELECT COUNT(*) FROM sell_submissions WHERE status = 'pending'")->fetchColumn();
    $in_progress_submissions = $db->query("SELECT COUNT(*) FROM sell_submissions WHERE status = 'in_progress'")->fetchColumn();
    $total_quoted_value = $db->query("SELECT COALESCE(SUM(quote_amount), 0) FROM sell_submissions WHERE quote_amount IS NOT NULL")->fetchColumn();
    
    debug_log("Statistics:", [
        'total' => $total_submissions,
        'pending' => $pending_submissions,
        'in_progress' => $in_progress_submissions,
        'quoted_value' => $total_quoted_value
    ]);
} catch (PDOException $e) {
    debug_log("Statistics error:", ['error' => $e->getMessage()]);
    $error .= ' Error fetching statistics: ' . $e->getMessage();
    $total_submissions = 0;
    $pending_submissions = 0;
    $in_progress_submissions = 0;
    $total_quoted_value = 0;
}

// Debug information visible to admins
echo '<!-- Debug Information -->';
echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc;">';
echo '<h3>Debug Information (Admin Only)</h3>';
echo '<pre>';
echo "PHP Version: " . phpversion() . "\n";
echo "Database Connection Status: " . ($db ? "Connected" : "Not Connected") . "\n";
echo "Encryption Status: " . (isset($encryption) ? "Initialized" : "Not Initialized") . "\n";
echo "Total Submissions Found: " . (isset($submissions) ? count($submissions) : "N/A") . "\n";
echo "Current Filters: \n";
echo "- Status Filter: " . htmlspecialchars($status_filter) . "\n";
echo "- Search Term: " . htmlspecialchars($search) . "\n";
echo "Query Used: " . htmlspecialchars($query) . "\n";
echo "Parameters: " . print_r($params, true) . "\n";
if (!empty($error)) {
    echo "Errors: " . htmlspecialchars($error) . "\n";
}
echo "\nFirst Submission Raw Data (if any):\n";
if (!empty($submissions)) {
    echo print_r($submissions[0], true) . "\n";
} else {
    echo "No submissions found in array\n";
}
echo '</pre>';
echo '</div>';

// Decrypt helper function
function decrypt_field($encrypted, $encryption) {
    if (empty($encrypted)) {
        debug_log("Empty field to decrypt");
        return '';
    }
    try {
        $decrypted = $encryption->decrypt($encrypted);
        debug_log("Successfully decrypted field");
        return $decrypted;
    } catch (Exception $e) {
        debug_log("Decryption error:", ['error' => $e->getMessage()]);
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
            <!-- Debug: Start of submissions loop -->
            <div style="background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc;">
                <pre>
Number of submissions to display: <?php echo count($submissions); ?>

<?php foreach ($submissions as $index => $submission): ?>
    Submission <?php echo $index + 1; ?>: ID <?php echo $submission['id']; ?>
<?php endforeach; ?>
                </pre>
            </div>
            <!-- Debug: End of debug output -->

            <?php foreach ($submissions as $submission): ?>
                <?php
                    // Decrypt sensitive data
                    $decrypted_name = htmlspecialchars(decrypt_field($submission['full_name'], $encryption));
                    $decrypted_email = htmlspecialchars(decrypt_field($submission['email'], $encryption));
                    $decrypted_phone = htmlspecialchars(decrypt_field($submission['phone'], $encryption));
                    $decrypted_description = htmlspecialchars(decrypt_field($submission['description'], $encryption));
                    
                    // Parse manga sets
                    $manga_sets = json_decode($submission['item_details'], true) ?: [];
                    
                    // Get photos
                    $photos = json_decode($submission['photo_paths'], true) ?: [];
                ?>
                <div class="submission-card">
                    <div class="submission-header">
                        <div class="submission-info">
                            <h3><?php echo $decrypted_name; ?></h3>
                            <div class="contact-info">
                                <a href="mailto:<?php echo $decrypted_email; ?>"><?php echo $decrypted_email; ?></a>
                                <?php if ($decrypted_phone): ?>
                                    <span>|</span>
                                    <a href="tel:<?php echo $decrypted_phone; ?>"><?php echo $decrypted_phone; ?></a>
                                <?php endif; ?>
                            </div>
                            <div class="submission-meta">
                                <span class="status-badge <?php echo $submission['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $submission['status'])); ?>
                                </span>
                                <span class="date">
                                    Submitted: <?php echo date('M j, Y g:i A', strtotime($submission['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                        <div class="submission-actions">
                            <button class="btn btn-primary edit-btn" data-id="<?php echo $submission['id']; ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-danger delete-btn" data-id="<?php echo $submission['id']; ?>">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>

                    <div class="manga-sets">
                        <h4>Manga Sets</h4>
                        <div class="sets-grid">
                            <?php foreach ($manga_sets as $set): ?>
                                <div class="set-card">
                                    <div class="set-info">
                                        <strong><?php echo htmlspecialchars($set['title']); ?></strong>
                                        <div>Volumes: <?php echo htmlspecialchars($set['volumes']); ?></div>
                                        <div>Condition: <?php echo htmlspecialchars($set['condition']); ?></div>
                                        <?php if (!empty($set['expected_price'])): ?>
                                            <div>Expected: $<?php echo htmlspecialchars($set['expected_price']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ($photos): ?>
                        <div class="photos-section">
                            <h4>Photos</h4>
                            <div class="photos-grid">
                                <?php foreach ($photos as $photo): ?>
                                    <div class="photo-card">
                                        <img src="../uploads/sell-submissions/<?php echo htmlspecialchars($photo['filename']); ?>" 
                                             alt="Submission photo" class="submission-photo">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($decrypted_description): ?>
                        <div class="description-section">
                            <h4>Additional Description</h4>
                            <p><?php echo nl2br($decrypted_description); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="admin-section">
                        <form class="update-form" method="POST">
                            <input type="hidden" name="action" value="update_submission">
                            <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                            
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" required>
                                    <option value="pending" <?php echo $submission['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="in_progress" <?php echo $submission['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="quoted" <?php echo $submission['status'] === 'quoted' ? 'selected' : ''; ?>>Quoted</option>
                                    <option value="completed" <?php echo $submission['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="rejected" <?php echo $submission['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Quote Amount ($)</label>
                                <input type="number" name="quote_amount" step="0.01" min="0" 
                                       value="<?php echo $submission['quote_amount'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Admin Notes</label>
                                <textarea name="admin_notes"><?php echo htmlspecialchars($submission['admin_notes'] ?? ''); ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
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