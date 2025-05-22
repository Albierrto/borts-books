<?php
session_start();

// Simple hardcoded credentials (change these for production)
$ADMIN_USER = 'admin';
$ADMIN_PASS = 'bortbooks123';

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header('Location: admin.php');
    exit;
}

if (isset($_POST['username'], $_POST['password'])) {
    if ($_POST['username'] === $ADMIN_USER && $_POST['password'] === $ADMIN_PASS) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}

$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Bort's Books</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .admin-container { max-width: 400px; margin: 4rem auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); padding: 2rem; }
        .admin-title { font-size: 2rem; font-weight: 700; margin-bottom: 1.5rem; text-align: center; }
        .admin-form label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .admin-form input { width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 1.2rem; font-size: 1rem; }
        .admin-form button { width: 100%; padding: 0.8rem; background: #e63946; color: #fff; border: none; border-radius: 4px; font-weight: 600; font-size: 1.1rem; cursor: pointer; }
        .admin-form button:hover { background: #b71c2b; }
        .admin-links { text-align: center; margin-top: 2rem; }
        .admin-links a { color: #e63946; text-decoration: none; font-weight: 600; margin: 0 1rem; }
        .admin-links a:hover { text-decoration: underline; }
        .admin-error { color: #e63946; margin-bottom: 1rem; text-align: center; }
    </style>
</head>
<body>
    <div class="admin-header" style="display:flex;align-items:center;gap:2rem;padding:1.2rem 2rem 0.5rem 2rem;">
        <a href="../index.php" class="logo" style="font-size:2rem;font-weight:800;color:#e63946;text-decoration:none;">Bort's <span style='color:#2a9d8f;'>Books</span></a>
    </div>
    <div class="admin-container">
        <div class="admin-title">Admin Login</div>
        <?php if (!$isLoggedIn): ?>
            <?php if (isset($error)): ?><div class="admin-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <form class="admin-form" method="POST">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <button type="submit">Login</button>
            </form>
        <?php else: ?>
            <div class="admin-links">
                <a href="ebay-import.php" class="admin-link">Import from eBay/CSV</a>
                <a href="mass-edit.php" class="admin-link">Mass Edit Listings</a>
                <a href="admin-sell-submissions.php" class="admin-link">View Sell Submissions</a>
                <a href="admin.php?logout=1">Logout</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 