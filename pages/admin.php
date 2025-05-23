<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

$pageTitle = "Admin Panel";
$currentPage = "admin";

// Fetch all products with their first image
$stmt = $db->query("
    SELECT p.*, pi.image_url 
    FROM products p 
    LEFT JOIN (
        SELECT product_id, MIN(id) as min_image_id
        FROM product_images
        GROUP BY product_id
    ) pim ON p.id = pim.product_id
    LEFT JOIN product_images pi ON pim.min_image_id = pi.id
    ORDER BY p.created_at DESC
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .admin-title {
            font-size: 2rem;
            font-weight: 700;
        }
        .admin-actions {
            display: flex;
            gap: 1rem;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .btn:hover {
            background: var(--primary-dark);
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .products-table th,
        .products-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .products-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .products-table tr:hover {
            background: #f8f9fa;
        }
        .product-image {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        .product-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .message.success {
            background: #e9f7ef;
            color: #1b5e20;
        }
        .message.error {
            background: #fdecea;
            color: #b71c1c;
        }
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

    <div class="admin-container">
        <a href="admin-dashboard.php" style="display:inline-block;margin-bottom:1.5rem;color:#232946;font-weight:600;text-decoration:underline;"><i class="fas fa-arrow-left"></i> Back to Admin Dashboard</a>
        <div class="admin-header">
            <h1 class="admin-title">Admin Panel</h1>
            <div class="admin-actions">
                <a href="ebay-import.php" class="btn btn-secondary">Import from eBay</a>
                <a href="add-product.php" class="btn">Add New Product</a>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <form id="mass-delete-form" action="admin-mass-delete.php" method="POST" onsubmit="return confirm('Are you sure you want to delete the selected products?');" style="margin-bottom:1.5rem;">
            <button type="submit" class="btn btn-danger" style="background:#e63946;">Delete Selected</button>
        </form>
        <table class="products-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all"></th>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Price</th>
                    <th>Condition</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><input type="checkbox" name="delete_ids[]" value="<?php echo $product['id']; ?>" form="mass-delete-form"></td>
                        <td>
                            <img src="<?php echo $product['image_url'] ? htmlspecialchars($product['image_url']) : '../assets/img/placeholder.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($product['title']); ?>" 
                                 class="product-image">
                        </td>
                        <td><?php echo htmlspecialchars($product['title']); ?></td>
                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($product['condition']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                        <td>
                            <div class="product-actions">
                                <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                <form action="delete-product.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="btn btn-sm" onclick="return confirm('Are you sure you want to delete this product?')">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    // Select all checkboxes functionality
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('input[name="delete_ids[]"]');
    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
    });
    </script>
</body>
</html> 