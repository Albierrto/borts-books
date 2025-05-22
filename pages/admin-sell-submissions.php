<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}
$pageTitle = "Sell Submissions";
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
        .container { max-width: 900px; margin: 2rem auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(35,41,70,0.08); padding: 2.5rem 2rem; }
        .page-title { font-size: 2rem; font-weight: 800; margin-bottom: 2rem; text-align: center; }
        .placeholder { text-align: center; color: #888; font-size: 1.1rem; margin-top: 2rem; }
        .back-link { display:inline-block;margin-bottom:1.5rem;color:#232946;font-weight:600;text-decoration:underline; }
        .back-link i { margin-right: 0.5rem; }
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
        <div class="placeholder">
            <i class="fas fa-inbox" style="font-size:2.5rem;color:#eebbc3;"></i>
            <p>This is a placeholder for the sell submissions page.<br>Here you will be able to review and manage manga sell requests from users.</p>
        </div>
    </div>
</body>
</html> 