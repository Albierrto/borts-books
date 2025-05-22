<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}
$pageTitle = "Sell Submissions";
require_once '../includes/db.php';
// Fetch all sell submissions
$stmt = $db->query("SELECT * FROM sell_submissions ORDER BY submitted_at DESC");
$sellSubmissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Bort's Books</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .container { max-width: 1100px; margin: 2rem auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(35,41,70,0.08); padding: 2.5rem 2rem; }
        .page-title { font-size: 2rem; font-weight: 800; margin-bottom: 2rem; text-align: center; }
        .back-link { display:inline-block;margin-bottom:1.5rem;color:#232946;font-weight:600;text-decoration:underline; cursor:pointer; }
        .back-link i { margin-right: 0.5rem; }
        .submissions-table { width:100%; border-collapse:collapse; margin-top:1.5rem; }
        .submissions-table th, .submissions-table td { padding:0.7rem 0.6rem; border-bottom:1px solid #eee; text-align:left; }
        .submissions-table th { background:#f7f7fa; font-weight:700; }
        .submissions-table tr:hover { background:#f1f5f9; }
        .view-link { color:#2563eb; text-decoration:underline; cursor:pointer; font-weight:600; }
        .view-link:hover { color:#1d4ed8; }
        .no-submissions { text-align:center; color:#888; font-size:1.1rem; margin-top:2rem; }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="../index.php" class="logo">Bort's <span>Books</span></a>
            <nav>
                <ul>
                    <li><a href="/index.php">Home</a></li>
                    <li><a href="/pages/shop.php">Shop</a></li>
                    <li><a href="/pages/sell.php">Sell Manga</a></li>
                    <li><a href="/pages/about.php">About</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <div class="container">
        <a href="admin-dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Admin Dashboard</a>
        <div class="page-title">Sell Submissions</div>
        <?php if (count($sellSubmissions) === 0): ?>
            <div class="no-submissions">
                <i class="fas fa-inbox" style="font-size:2.5rem;color:#eebbc3;"></i>
                <p>No sell submissions found.</p>
            </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="submissions-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th># Items</th>
                    <th>Condition</th>
                    <th>Submitted At</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sellSubmissions as $sub): ?>
                <tr>
                    <td><?php echo htmlspecialchars($sub['id']); ?></td>
                    <td><?php echo htmlspecialchars($sub['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($sub['email']); ?></td>
                    <td><?php echo htmlspecialchars($sub['phone']); ?></td>
                    <td><?php echo htmlspecialchars($sub['num_items']); ?></td>
                    <td><?php echo htmlspecialchars($sub['overall_condition']); ?></td>
                    <td><?php echo htmlspecialchars($sub['submitted_at']); ?></td>
                    <td><a href="admin-sell-submissions.php?view=<?php echo $sub['id']; ?>" class="view-link">View</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</body>
</html> 