<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}
$pageTitle = "Admin Dashboard";
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
        body { background: #f7f7fa; }
        .dashboard-container {
            max-width: 900px;
            margin: 3rem auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(35,41,70,0.08);
            padding: 2.5rem 2rem;
        }
        .dashboard-title {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 2rem;
            text-align: center;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 2rem;
        }
        .dashboard-card {
            background: #f7f7fa;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(35,41,70,0.07);
            padding: 2rem 1.2rem;
            text-align: center;
            transition: transform 0.13s, box-shadow 0.13s;
            cursor: pointer;
            text-decoration: none;
            color: #232946;
        }
        .dashboard-card:hover {
            transform: translateY(-6px) scale(1.04);
            box-shadow: 0 4px 16px rgba(35,41,70,0.13);
            background: #eebbc3;
            color: #232946;
        }
        .dashboard-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #e63946;
        }
        .dashboard-card h3 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .dashboard-card p {
            color: #555;
            font-size: 1rem;
        }
        .logout-link {
            display: block;
            text-align: center;
            margin-top: 2.5rem;
            color: #e63946;
            font-weight: 700;
            text-decoration: none;
        }
        .logout-link:hover { text-decoration: underline; }
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
    <div class="dashboard-container">
        <div class="dashboard-title">Admin Dashboard</div>
        <div class="dashboard-grid">
            <a href="admin.php" class="dashboard-card">
                <i class="fas fa-list"></i>
                <h3>View & Manage Products</h3>
                <p>See all products, edit, delete, and manage images.</p>
            </a>
            <a href="add-product.php" class="dashboard-card">
                <i class="fas fa-plus-circle"></i>
                <h3>Add New Product</h3>
                <p>Add a new manga product to your store.</p>
            </a>
            <a href="ebay-import.php" class="dashboard-card">
                <i class="fas fa-file-import"></i>
                <h3>Import from eBay/CSV</h3>
                <p>Bulk import products from eBay or CSV files.</p>
            </a>
            <a href="admin-sell-submissions.php" class="dashboard-card">
                <i class="fas fa-inbox"></i>
                <h3>View Sell Submissions</h3>
                <p>Review and manage manga sell requests from users.</p>
            </a>
            <a href="admin-email.php" class="dashboard-card">
                <i class="fas fa-envelope"></i>
                <h3>Email Marketing</h3>
                <p>Manage newsletter subscribers and email campaigns.</p>
            </a>
            <a href="track-order.php" class="dashboard-card">
                <i class="fas fa-search"></i>
                <h3>Track Orders</h3>
                <p>Look up customer orders and tracking information.</p>
            </a>
        </div>
        <a href="admin-login.php?logout=1" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</body>
</html> 