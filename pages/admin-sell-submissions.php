<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}
require_once '../includes/db.php';
$pageTitle = "Sell Submissions";

$debugMsg = '';

try {
    // Handle delete
    if (isset($_POST['delete_id'])) {
        $delId = (int)$_POST['delete_id'];
        $db->prepare('DELETE FROM sell_submissions WHERE id = ?')->execute([$delId]);
        $db->prepare('DELETE FROM sell_submission_notes WHERE submission_id = ?')->execute([$delId]);
        header('Location: admin-sell-submissions.php');
        exit;
    }
    // Handle status update (no note)
    if (isset($_POST['update_id']) && isset($_POST['status']) && !isset($_POST['add_note'])) {
        $updId = (int)$_POST['update_id'];
        $status = $_POST['status'] ?? 'Incomplete';
        $db->prepare('UPDATE sell_submissions SET status=?, status_updated_at=NOW() WHERE id=?')->execute([$status, $updId]);
        header('Location: admin-sell-submissions.php');
        exit;
    }
    // Handle add note (with optional status update)
    if (isset($_POST['update_id']) && isset($_POST['add_note'])) {
        $updId = (int)$_POST['update_id'];
        $status = $_POST['status'] ?? 'Incomplete';
        $note = $_POST['note'] ?? '';
        if (trim($note) !== '') {
            $db->prepare('INSERT INTO sell_submission_notes (submission_id, note, status) VALUES (?, ?, ?)')->execute([$updId, $note, $status]);
            $db->prepare('UPDATE sell_submissions SET status=?, note=?, status_updated_at=NOW() WHERE id=?')->execute([$status, $note, $updId]);
        } else {
            $db->prepare('UPDATE sell_submissions SET status=?, status_updated_at=NOW() WHERE id=?')->execute([$status, $updId]);
        }
        header('Location: admin-sell-submissions.php');
        exit;
    }
    // Handle delete note
    if (isset($_POST['delete_note_id'])) {
        $noteId = (int)$_POST['delete_note_id'];
        $db->prepare('DELETE FROM sell_submission_notes WHERE id = ?')->execute([$noteId]);
        header('Location: admin-sell-submissions.php');
        exit;
    }
} catch (Exception $e) {
    $debugMsg = 'DEBUG ERROR: ' . $e->getMessage();
}

// Status filter
$statusFilter = $_GET['filter'] ?? 'all';
$statusSql = '';
$params = [];
if (in_array($statusFilter, ['Incomplete', 'Working On', 'Completed'])) {
    $statusSql = 'WHERE status = ?';
    $params[] = $statusFilter;
}
$stmt = $db->prepare('SELECT * FROM sell_submissions ' . $statusSql . ' ORDER BY submitted_at ASC');
$stmt->execute($params);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
function statusColor($status) {
    switch (strtolower($status)) {
        case 'completed': return 'rgba(44,182,125,0.18)'; // teal glass
        case 'working on': return 'rgba(255,216,3,0.13)'; // gold glass
        case 'incomplete': default: return 'rgba(242,95,76,0.13)'; // coral glass
    }
}
function statusBorder($status) {
    switch (strtolower($status)) {
        case 'completed': return '#2CB67D';
        case 'working on': return '#FFD803';
        case 'incomplete': default: return '#F25F4C';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell Submissions - Bort's Books</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: #181A20;
            font-family: 'Inter', Arial, sans-serif;
            color: #F7F7F7;
            min-height: 100vh;
        }
        .admin-header { display:flex;align-items:center;gap:2rem;padding:1.2rem 2rem 0.5rem 2rem; }
        .admin-header .logo { font-size:2rem;font-weight:800;color:#7F5AF0;text-decoration:none; letter-spacing: 1px; }
        .admin-header .logo span { color:#2CB67D; }
        .submissions-container { max-width: 1100px; margin: 2.5rem auto; background: transparent; border-radius: 16px; box-shadow: none; padding: 0; }
        .submission-card {
            background: rgba(34,38,49,0.98);
            border: 2px solid #23263A;
            border-left: 8px solid;
            border-left-color: inherit;
            border-radius: 18px;
            box-shadow: 0 6px 32px 0 rgba(0,0,0,0.18);
            padding: 2.2rem 2rem 2rem 2rem;
            margin-bottom: 2.2rem;
            display: flex; flex-wrap: wrap; gap: 2.2rem;
            transition: box-shadow 0.25s, transform 0.18s, background 0.3s, border-color 0.3s;
        }
        .submission-card:hover {
            box-shadow: 0 12px 40px 0 rgba(127,90,240,0.13), 0 2px 12px rgba(44,182,125,0.10);
            transform: translateY(-2px) scale(1.012);
        }
        .submission-main { flex: 2 1 350px; min-width: 320px; }
        .note-history {
            flex: 1 1 220px; min-width: 220px; margin-top:0;
            background: rgba(34,38,49,0.93);
            border-radius:12px; padding:1.2rem 1rem; height:fit-content; align-self: flex-start;
            box-shadow: 0 2px 12px 0 rgba(127,90,240,0.07);
            backdrop-filter: blur(7px);
        }
        .note-history-entry { margin-bottom:0.7rem; display: flex; align-items: center; color: #F7F7F7; font-size: 1.08rem; }
        .note-history-date { color:#FFD803; font-size:0.97rem; margin-left:0.7rem; }
        .note-delete-btn { background:none; border:none; color:#F25F4C; font-size:1.1rem; margin-left:0.7rem; cursor:pointer; padding:0; }
        .note-delete-btn:hover { color:#FFD803; }
        .submission-header { font-size: 1.3rem; font-weight: 700; margin-bottom: 0.7rem; color: #2CB67D; letter-spacing: 0.5px; }
        .submission-info { margin-bottom: 0.5rem; color: #F7F7F7; font-size: 1.08rem; }
        .submission-label { font-weight: 700; color: #FFD803; }
        .submission-photos { margin-top: 0.7rem; }
        .submission-photos img { max-width: 120px; max-height: 120px; margin-right: 0.5rem; border-radius: 8px; border: 2px solid #FFD803; box-shadow: 0 2px 8px 0 rgba(44,182,125,0.08); transition: box-shadow 0.2s, border-color 0.2s; background: #23263A; }
        .submission-photos img:hover { box-shadow: 0 4px 16px 0 #7F5AF0; border-color: #2CB67D; }
        .item-table { width: 100%; border-collapse: collapse; margin-top: 0.7rem; margin-bottom: 0.7rem; }
        .item-table th, .item-table td { border: 1.5px solid #FFD803; padding: 0.5rem 0.8rem; text-align: left; }
        .item-table th { background: #2CB67D; font-weight: 700; color: #181A20; }
        .item-table td { color: #F7F7F7; background: rgba(34,38,49,0.98); }
        .back-link { display:inline-block;margin-bottom:1.5rem;color:#2CB67D;font-weight:700;text-decoration:underline; font-size: 1.08rem; }
        .status-select { font-weight:700;padding:0.4rem 0.9rem;border-radius:4px;border:2px solid #FFD803; background: #23263A; color:#FFD803; font-size: 1.08rem; }
        .note-input { width:100%;padding:0.5rem;border-radius:4px;border:2px solid #FFD803;margin-top:0.3rem;background:#23263A;color:#F7F7F7; font-size: 1.08rem; }
        .admin-actions { margin-top:0.7rem;display:flex;gap:1rem;align-items:center; flex-wrap:wrap; }
        .delete-btn { background:#F25F4C;color:#fff;border:none;border-radius:6px;padding:0.6rem 1.3rem;font-weight:700;cursor:pointer; box-shadow: 0 2px 8px 0 rgba(242,95,76,0.10); transition: background 0.18s, color 0.18s; font-size: 1.08rem; border: 2px solid #FFD803; }
        .delete-btn:hover { background:#FFD803; color:#23263A; border-color: #2CB67D; }
        .update-btn { background:#7F5AF0;color:#fff;border:none;border-radius:6px;padding:0.6rem 1.3rem;font-weight:700;cursor:pointer; box-shadow: 0 2px 8px 0 rgba(127,90,240,0.10); transition: background 0.18s, color 0.18s; font-size: 1.08rem; border: 2px solid #FFD803; }
        .update-btn:hover { background:#2CB67D; color:#23263A; border-color: #7F5AF0; }
        .status-dot { display:inline-block;width:16px;height:16px;border-radius:50%;margin-right:0.5rem;vertical-align:middle; box-shadow: 0 0 0 2px #fff; border: 2px solid #FFD803; }
        .dates { font-size:1.08rem;color:#FFD803;margin-bottom:0.5rem; }
        .status-filter { margin-bottom:2rem; }
        .status-filter label { font-weight:700; margin-right:1rem; color: #FFD803; font-size: 1.08rem; }
        .status-filter select { padding:0.5rem 1.3rem; border-radius:4px; border:2px solid #FFD803; font-size:1.08rem; background: #23263A; color: #FFD803; }
        h1 { color: #FFD803; font-weight: 800; letter-spacing: 1px; font-size: 2.2rem; }
        .status-select.status-dropdown, .status-select.status-dropdown option {
            background: #23263A !important;
            color: #FFD803 !important;
            font-weight: 700;
            font-size: 1.08rem;
        }
        .status-select.status-dropdown:focus, .status-select.status-dropdown option:focus {
            outline: 2px solid #FFD803;
        }
        .update-btn, .delete-btn {
            min-width: 90px;
        }
        @media (max-width: 900px) {
            .submission-card { flex-direction: column; gap: 1.5rem; padding: 1.2rem 1rem; }
            .submissions-container { padding: 0.5rem; }
        }
        .photo-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            overflow: auto;
        }
        .modal-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90vh;
            object-fit: contain;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .close-modal {
            position: absolute;
            right: 25px;
            top: 15px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 10000;
        }
        .modal-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            padding: 16px;
            user-select: none;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s;
        }
        .modal-nav:hover {
            background: rgba(0, 0, 0, 0.8);
        }
        .prev {
            left: 20px;
        }
        .next {
            right: 20px;
        }
        .submission-photos img {
            cursor: pointer;
            transition: transform 0.2s;
        }
        .submission-photos img:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <a href="../index.php" class="logo">Bort's <span>Books</span></a>
        <a href="admin-dashboard.php" class="back-link">&larr; Back to Admin Dashboard</a>
    </div>
    <div class="submissions-container">
        <?php if ($debugMsg): ?>
            <div style="background:#F25F4C;color:#fff;padding:1rem 1.5rem;border-radius:8px;margin-bottom:1.5rem;font-weight:700;">
                <?php echo $debugMsg; ?>
            </div>
        <?php endif; ?>
        <h1 style="font-size:2rem;font-weight:700;margin-bottom:1.5rem;">Sell Submissions</h1>
        <form class="status-filter" method="get" style="margin-bottom:2rem;">
            <label for="filter">Filter by Status:</label>
            <select name="filter" id="filter" onchange="this.form.submit()">
                <option value="all" <?php if($statusFilter==='all') echo 'selected'; ?>>All</option>
                <option value="Incomplete" <?php if($statusFilter==='Incomplete') echo 'selected'; ?>>Incomplete</option>
                <option value="Working On" <?php if($statusFilter==='Working On') echo 'selected'; ?>>Working On</option>
                <option value="Completed" <?php if($statusFilter==='Completed') echo 'selected'; ?>>Completed</option>
            </select>
        </form>
        <?php if (empty($submissions)): ?>
            <p>No submissions yet.</p>
        <?php else: ?>
            <?php foreach ($submissions as $sub): ?>
                <div class="submission-card" style="background:<?php echo statusColor($sub['status']); ?>;border-color:<?php echo statusBorder($sub['status']); ?>;">
                    <div class="submission-main">
                        <div class="dates">
                            Submitted: <?php echo date('Y-m-d H:i', strtotime($sub['submitted_at'])); ?>
                            | Last Status Update: <?php echo $sub['status_updated_at'] ? date('Y-m-d H:i', strtotime($sub['status_updated_at'])) : '-'; ?>
                        </div>
                        <div class="submission-header">
                            <span class="status-dot" style="background:<?php echo statusColor($sub['status']); ?>;"></span>
                            <?php echo htmlspecialchars($sub['status'] ?? 'Incomplete'); ?>
                            <div style="float:right;display:inline-flex;gap:0.7rem;align-items:center;">
                                <form method="POST" class="admin-actions" style="display:inline-flex;align-items:center;gap:0.5rem;margin:0;">
                                    <input type="hidden" name="update_id" value="<?php echo $sub['id']; ?>">
                                    <select name="status" class="status-select status-dropdown">
                                        <option value="Incomplete" <?php if (($sub['status'] ?? '') === 'Incomplete') echo 'selected'; ?>>Incomplete</option>
                                        <option value="Working On" <?php if (($sub['status'] ?? '') === 'Working On') echo 'selected'; ?>>Working On</option>
                                        <option value="Completed" <?php if (($sub['status'] ?? '') === 'Completed') echo 'selected'; ?>>Completed</option>
                                    </select>
                                    <button type="submit" class="update-btn">Save</button>
                                </form>
                            </div>
                        </div>
                        <form method="POST" class="admin-actions" style="margin-bottom:0.7rem;gap:0.7rem;align-items:center;">
                            <input type="hidden" name="update_id" value="<?php echo $sub['id']; ?>">
                            <input type="hidden" name="add_note" value="1">
                            <input type="text" name="note" class="note-input" placeholder="Add note..." value="">
                            <select name="status" class="status-select status-dropdown" style="display:none;">
                                <option value="Incomplete" <?php if (($sub['status'] ?? '') === 'Incomplete') echo 'selected'; ?>>Incomplete</option>
                                <option value="Working On" <?php if (($sub['status'] ?? '') === 'Working On') echo 'selected'; ?>>Working On</option>
                                <option value="Completed" <?php if (($sub['status'] ?? '') === 'Completed') echo 'selected'; ?>>Completed</option>
                            </select>
                            <button type="submit" class="update-btn" style="background:#7F5AF0;">Add Note</button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Delete this submission?');" style="display:inline;">
                            <input type="hidden" name="delete_id" value="<?php echo $sub['id']; ?>">
                            <button type="submit" class="delete-btn">Delete</button>
                        </form>
                        <div class="submission-info"><span class="submission-label">Name:</span> <?php echo htmlspecialchars($sub['full_name']); ?></div>
                        <div class="submission-info"><span class="submission-label">Email:</span> <?php echo htmlspecialchars($sub['email']); ?></div>
                        <div class="submission-info"><span class="submission-label">Phone:</span> <?php echo htmlspecialchars($sub['phone']); ?></div>
                        <div class="submission-info"><span class="submission-label"># Items:</span> <?php echo htmlspecialchars($sub['num_items']); ?></div>
                        <div class="submission-info"><span class="submission-label">Overall Condition:</span> <?php echo htmlspecialchars($sub['overall_condition']); ?></div>
                        <?php $items = json_decode($sub['item_details'], true); if ($items && count($items)): ?>
                            <div class="submission-info"><span class="submission-label">Item Details:</span></div>
                            <table class="item-table">
                                <tr><th>Title</th><th>Volume</th><th>Condition</th><th>Expected Price</th></tr>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['title']); ?></td>
                                        <td><?php echo htmlspecialchars($item['volume']); ?></td>
                                        <td><?php echo htmlspecialchars($item['condition']); ?></td>
                                        <td><?php echo htmlspecialchars($item['expected_price']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        <?php endif; ?>
                        <?php $photos = json_decode($sub['photo_paths'], true); if ($photos && count($photos)): ?>
                            <div class="submission-photos"><span class="submission-label">Photos:</span><br>
                                <?php foreach ($photos as $photo): ?>
                                    <a href="../<?php echo htmlspecialchars($photo); ?>" target="_blank"><img src="../<?php echo htmlspecialchars($photo); ?>" alt="Collection Photo"></a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="note-history">
                        <div style="font-weight:600;margin-bottom:0.5rem;">Note History:</div>
                        <?php
                        $notesStmt = $db->prepare('SELECT * FROM sell_submission_notes WHERE submission_id = ? ORDER BY created_at DESC');
                        $notesStmt->execute([$sub['id']]);
                        $notes = $notesStmt->fetchAll(PDO::FETCH_ASSOC);
                        if ($notes):
                            foreach ($notes as $n): ?>
                                <div class="note-history-entry">
                                    <span><?php echo htmlspecialchars($n['note']); ?></span>
                                    <span class="note-history-date"><?php echo date('Y-m-d H:i', strtotime($n['created_at'])); ?></span>
                                    <form method="POST" style="display:inline;margin:0;">
                                        <input type="hidden" name="delete_note_id" value="<?php echo $n['id']; ?>">
                                        <button type="submit" class="note-delete-btn" title="Delete note" onclick="return confirm('Delete this note?');">&times;</button>
                                    </form>
                                </div>
                        <?php endforeach;
                        else: ?>
                            <div style="color:#aaa;">No notes yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <!-- Full Screen Photo Gallery Modal -->
    <div id="photoModal" class="photo-modal">
        <span class="close-modal">&times;</span>
        <img class="modal-content" id="modalImg">
        <div class="modal-nav prev" onclick="prevPhoto()">❮</div>
        <div class="modal-nav next" onclick="nextPhoto()">❯</div>
    </div>

    <script>
        let currentPhotoIndex = 0;
        let currentPhotos = [];
        const modal = document.getElementById('photoModal');
        const modalImg = document.getElementById('modalImg');
        const closeBtn = document.querySelector('.close-modal');

        // Open modal with photo
        function openPhotoModal(photos, index) {
            currentPhotos = photos;
            currentPhotoIndex = index;
            modal.style.display = "block";
            modalImg.src = photos[index];
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }

        // Close modal
        function closePhotoModal() {
            modal.style.display = "none";
            document.body.style.overflow = 'auto'; // Re-enable scrolling
        }

        // Navigate photos
        function nextPhoto() {
            currentPhotoIndex = (currentPhotoIndex + 1) % currentPhotos.length;
            modalImg.src = currentPhotos[currentPhotoIndex];
        }

        function prevPhoto() {
            currentPhotoIndex = (currentPhotoIndex - 1 + currentPhotos.length) % currentPhotos.length;
            modalImg.src = currentPhotos[currentPhotoIndex];
        }

        // Close modal when clicking the close button
        closeBtn.onclick = closePhotoModal;

        // Close modal when clicking outside the image
        modal.onclick = function(e) {
            if (e.target === modal) {
                closePhotoModal();
            }
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (modal.style.display === "block") {
                if (e.key === "ArrowRight") {
                    nextPhoto();
                } else if (e.key === "ArrowLeft") {
                    prevPhoto();
                } else if (e.key === "Escape") {
                    closePhotoModal();
                }
            }
        });

        // Add click handlers to all submission photos
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.submission-photos').forEach(function(photoContainer) {
                const photos = Array.from(photoContainer.querySelectorAll('img')).map(img => img.src);
                photoContainer.querySelectorAll('img').forEach(function(img, index) {
                    img.onclick = function() {
                        openPhotoModal(photos, index);
                    }
                });
            });
        });
    </script>
</body>
</html> 