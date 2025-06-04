<?php
session_start();
require_once '../includes/admin-auth.php';

// Check admin authentication
check_admin_auth();

require_once '../includes/db.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

$pageTitle = "Admin Panel";
$currentPage = "admin";

// Handle mass edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mass_edit_submit'])) {
    $selected_ids = $_POST['edit_ids'] ?? [];
    $updates = [];
    $params = [];
    
    if (!empty($selected_ids)) {
        // Build update query based on filled fields
        if (!empty($_POST['mass_price']) && is_numeric($_POST['mass_price'])) {
            $updates[] = "price = ?";
            $params[] = floatval($_POST['mass_price']);
        }
        
        if (!empty($_POST['mass_condition'])) {
            $updates[] = "condition = ?";
            $params[] = $_POST['mass_condition'];
        }
        
        if (!empty($_POST['mass_category'])) {
            $updates[] = "category = ?";
            $params[] = $_POST['mass_category'];
        }
        
        // Handle description actions
        if (!empty($_POST['mass_description_action'])) {
            $action = $_POST['mass_description_action'];
            $text = $_POST['mass_description_text'] ?? '';
            
            switch ($action) {
                case 'replace':
                    $updates[] = "description = ?";
                    $params[] = $text;
                    break;
                case 'append':
                    if (!empty($text)) {
                        $updates[] = "description = CONCAT(COALESCE(description, ''), ?)";
                        $params[] = $text;
                    }
                    break;
                case 'prepend':
                    if (!empty($text)) {
                        $updates[] = "description = CONCAT(?, COALESCE(description, ''))";
                        $params[] = $text;
                    }
                    break;
                case 'clear':
                    $updates[] = "description = ''";
                    break;
            }
        }
        
        if (isset($_POST['mass_price_adjustment']) && !empty($_POST['mass_price_adjustment'])) {
            $adjustment = floatval($_POST['mass_price_adjustment']);
            $adjustment_type = $_POST['price_adjustment_type'] ?? 'add';
            
            if ($adjustment_type === 'add') {
                $updates[] = "price = price + ?";
            } elseif ($adjustment_type === 'subtract') {
                $updates[] = "price = price - ?";
            } elseif ($adjustment_type === 'multiply') {
                $updates[] = "price = price * ?";
            } elseif ($adjustment_type === 'percentage_increase') {
                $updates[] = "price = price * (1 + ? / 100)";
            } elseif ($adjustment_type === 'percentage_decrease') {
                $updates[] = "price = price * (1 - ? / 100)";
            }
            $params[] = $adjustment;
        }
        
        if ($updates) {
            try {
                $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
                $sql = "UPDATE products SET " . implode(', ', $updates) . " WHERE id IN ($placeholders)";
                $params = array_merge($params, $selected_ids);
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                
                $_SESSION['message'] = "Successfully updated " . count($selected_ids) . " products!";
                $_SESSION['message_type'] = "success";
            } catch (Exception $e) {
                $_SESSION['message'] = "Error updating products: " . $e->getMessage();
                $_SESSION['message_type'] = "error";
            }
        } else {
            $_SESSION['message'] = "No fields selected for update.";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "No products selected for mass edit.";
        $_SESSION['message_type'] = "error";
    }
    
    header('Location: admin.php');
    exit;
}

// Build filter conditions
$where = [];
$params = [];

if (!empty($_GET['title'])) {
    $where[] = "p.title LIKE ?";
    $params[] = '%' . $_GET['title'] . '%';
}
if (!empty($_GET['min_price'])) {
    $where[] = "p.price >= ?";
    $params[] = $_GET['min_price'];
}
if (!empty($_GET['max_price'])) {
    $where[] = "p.price <= ?";
    $params[] = $_GET['max_price'];
}
if (!empty($_GET['condition'])) {
    $where[] = "p.condition = ?";
    $params[] = $_GET['condition'];
}
if (!empty($_GET['date_from'])) {
    $where[] = "p.created_at >= ?";
    $params[] = $_GET['date_from'] . ' 00:00:00';
}
if (!empty($_GET['date_to'])) {
    $where[] = "p.created_at <= ?";
    $params[] = $_GET['date_to'] . ' 23:59:59';
}

$sql = "
    SELECT p.*, pi.image_url 
    FROM products p 
    LEFT JOIN (
        SELECT product_id, MIN(id) as min_image_id
        FROM product_images
        GROUP BY product_id
    ) pim ON p.id = pim.product_id
    LEFT JOIN product_images pi ON pim.min_image_id = pi.id
";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sort = $_GET['sort'] ?? '';
if ($sort == 'price_asc') $sql .= " ORDER BY p.price ASC";
elseif ($sort == 'price_desc') $sql .= " ORDER BY p.price DESC";
elseif ($sort == 'date_asc') $sql .= " ORDER BY p.created_at ASC";
elseif ($sort == 'date_desc') $sql .= " ORDER BY p.created_at DESC";
else $sql .= " ORDER BY p.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique categories for mass edit dropdown
$cat_stmt = $db->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
$categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
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
        .admin-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #eebbc3;
            color: #232946;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background: #232946;
            color: #fff;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-danger {
            background: #dc3545;
            color: #fff;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-success {
            background: #28a745;
            color: #fff;
        }
        .btn-success:hover {
            background: #218838;
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
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
            transition: border-color 0.3s ease;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #eebbc3;
        }
        .mass-actions-section {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(35,41,70,0.08);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .mass-actions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .mass-actions-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #232946;
        }
        .selected-count {
            background: #eebbc3;
            color: #232946;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .mass-edit-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .mass-edit-group {
            display: flex;
            flex-direction: column;
        }
        .mass-edit-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #232946;
        }
        .mass-edit-group input,
        .mass-edit-group select,
        .mass-edit-group textarea {
            padding: 0.8rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
        }
        .mass-edit-group textarea {
            min-height: 80px;
            resize: vertical;
        }
        .price-adjustment-group {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 0.5rem;
            align-items: end;
        }
        .mass-actions-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .products-table-container {
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
        }
        .products-table th,
        .products-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        .products-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #232946;
        }
        .products-table tr:hover {
            background: #f8f9fa;
        }
        .product-title-link {
            color: #232946;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        .product-title-link:hover {
            color: #eebbc3;
            text-decoration: underline;
        }
        .product-image {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .message.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .message.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .checkbox-cell {
            width: 40px;
        }
        .image-cell {
            width: 80px;
        }
        .actions-cell {
            width: 150px;
        }
        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                text-align: center;
            }
            .filters-form {
                grid-template-columns: 1fr;
            }
            .mass-edit-form {
                grid-template-columns: 1fr;
            }
            .mass-actions-buttons {
                flex-direction: column;
            }
            .products-table {
                font-size: 0.9rem;
            }
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
            <h1 class="admin-title"><i class="fas fa-cogs"></i> Product Management</h1>
            <div class="admin-actions">
                <a href="admin-mass-shipping.php" class="btn btn-success">
                    <i class="fas fa-shipping-fast"></i>
                    Mass Shipping Editor
                </a>
                <a href="ebay-import.php" class="btn btn-secondary">
                    <i class="fas fa-file-import"></i>
                    Import from eBay
                </a>
                <a href="add-product.php" class="btn">
                    <i class="fas fa-plus"></i>
                    Add New Product
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <i class="fas fa-<?php echo $_SESSION['message_type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Filters Section -->
        <div class="filters-section">
            <form class="filters-form" method="GET">
                <div class="filter-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" placeholder="Search by title..." value="<?php echo htmlspecialchars($_GET['title'] ?? ''); ?>">
                </div>
                <div class="filter-group">
                    <label for="min_price">Min Price</label>
                    <input type="number" id="min_price" name="min_price" placeholder="0.00" step="0.01" value="<?php echo htmlspecialchars($_GET['min_price'] ?? ''); ?>">
                </div>
                <div class="filter-group">
                    <label for="max_price">Max Price</label>
                    <input type="number" id="max_price" name="max_price" placeholder="999.99" step="0.01" value="<?php echo htmlspecialchars($_GET['max_price'] ?? ''); ?>">
                </div>
                <div class="filter-group">
                    <label for="condition">Condition</label>
                    <select id="condition" name="condition">
                        <option value="">Any Condition</option>
                        <option value="New" <?php if(($_GET['condition'] ?? '')=='New') echo 'selected'; ?>>New</option>
                        <option value="Like New" <?php if(($_GET['condition'] ?? '')=='Like New') echo 'selected'; ?>>Like New</option>
                        <option value="Very Good" <?php if(($_GET['condition'] ?? '')=='Very Good') echo 'selected'; ?>>Very Good</option>
                        <option value="Good" <?php if(($_GET['condition'] ?? '')=='Good') echo 'selected'; ?>>Good</option>
                        <option value="Acceptable" <?php if(($_GET['condition'] ?? '')=='Acceptable') echo 'selected'; ?>>Acceptable</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="date_from">From Date</label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>">
                </div>
                <div class="filter-group">
                    <label for="date_to">To Date</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>">
                </div>
                <div class="filter-group">
                    <label for="sort">Sort By</label>
                    <select id="sort" name="sort">
                        <option value="">Default</option>
                        <option value="price_asc" <?php if(($_GET['sort'] ?? '')=='price_asc') echo 'selected'; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php if(($_GET['sort'] ?? '')=='price_desc') echo 'selected'; ?>>Price: High to Low</option>
                        <option value="date_desc" <?php if(($_GET['sort'] ?? '')=='date_desc') echo 'selected'; ?>>Newest First</option>
                        <option value="date_asc" <?php if(($_GET['sort'] ?? '')=='date_asc') echo 'selected'; ?>>Oldest First</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn">
                        <i class="fas fa-search"></i>
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Mass Actions Section -->
        <div class="mass-actions-section">
            <div class="mass-actions-header">
                <h3 class="mass-actions-title"><i class="fas fa-edit"></i> Mass Actions</h3>
                <span class="selected-count" id="selected-count">0 selected</span>
            </div>
            
            <form method="POST" id="mass-edit-form">
                <div class="mass-edit-form">
                    <div class="mass-edit-group">
                        <label for="mass_price">Set Price ($)</label>
                        <input type="number" id="mass_price" name="mass_price" step="0.01" placeholder="e.g., 15.99">
                    </div>
                    
                    <div class="mass-edit-group">
                        <label for="mass_condition">Set Condition</label>
                        <select id="mass_condition" name="mass_condition">
                            <option value="">Don't Change</option>
                            <option value="New">New</option>
                            <option value="Like New">Like New</option>
                            <option value="Very Good">Very Good</option>
                            <option value="Good">Good</option>
                            <option value="Acceptable">Acceptable</option>
                        </select>
                    </div>
                    
                    <div class="mass-edit-group">
                        <label for="mass_category">Set Category</label>
                        <select id="mass_category" name="mass_category">
                            <option value="">Don't Change</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                            <?php endforeach; ?>
                            <option value="Manga">Manga</option>
                            <option value="Light Novel">Light Novel</option>
                            <option value="Anime">Anime</option>
                            <option value="Collectibles">Collectibles</option>
                        </select>
                    </div>
                    
                    <div class="mass-edit-group">
                        <label for="price_adjustment_type">Price Adjustment</label>
                        <div class="price-adjustment-group">
                            <input type="number" name="mass_price_adjustment" step="0.01" placeholder="Amount/Percentage">
                            <select name="price_adjustment_type">
                                <option value="add">Add ($)</option>
                                <option value="subtract">Subtract ($)</option>
                                <option value="multiply">Multiply (×)</option>
                                <option value="percentage_increase">Increase (%)</option>
                                <option value="percentage_decrease">Decrease (%)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mass-edit-group" style="margin-bottom: 1rem;">
                    <label for="mass_description_action">Description Action</label>
                    <select id="mass_description_action" name="mass_description_action" style="margin-bottom: 0.5rem;">
                        <option value="">Don't Change Descriptions</option>
                        <option value="replace">Replace All Descriptions</option>
                        <option value="append">Append to Descriptions</option>
                        <option value="prepend">Prepend to Descriptions</option>
                        <option value="clear">Clear All Descriptions</option>
                    </select>
                    <textarea id="mass_description_text" name="mass_description_text" placeholder="Enter description text..." rows="3" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-family: inherit; resize: vertical;"></textarea>
                    <small style="color: #666; font-size: 0.9rem;">
                        Replace: Overwrites existing descriptions | Append: Adds to end | Prepend: Adds to beginning | Clear: Removes all descriptions
                    </small>
                </div>
                
                <div class="mass-actions-buttons">
                    <button type="submit" name="mass_edit_submit" class="btn btn-success" id="mass-edit-btn" disabled>
                        <i class="fas fa-edit"></i>
                        Update Selected Products
                    </button>
                    <button type="submit" form="mass-delete-form" class="btn btn-danger" id="mass-delete-btn" disabled>
                        <i class="fas fa-trash"></i>
                        Delete Selected Products
                    </button>
                </div>
            </form>
        </div>

        <!-- Hidden form for mass delete -->
        <form id="mass-delete-form" action="admin-mass-delete.php" method="POST" onsubmit="return confirm('Are you sure you want to delete the selected products? This action cannot be undone.');" style="display: none;">
        </form>

        <!-- Products Table -->
        <div class="products-table-container">
            <div class="table-header">
                <span><i class="fas fa-list"></i> Products (<?php echo count($products); ?>)</span>
                <label style="cursor: pointer;">
                    <input type="checkbox" id="select-all" style="margin-right: 0.5rem;">
                    Select All
                </label>
            </div>
            
            <table class="products-table">
                <thead>
                    <tr>
                        <th class="checkbox-cell">Select</th>
                        <th class="image-cell">Image</th>
                        <th>Title</th>
                        <th>Price</th>
                        <th>Condition</th>
                        <th>Category</th>
                        <th>Created</th>
                        <th class="actions-cell">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 3rem; color: #666;">
                                <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: #ccc;"></i>
                                No products found matching your criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="checkbox-cell">
                                    <input type="checkbox" name="edit_ids[]" value="<?php echo $product['id']; ?>" form="mass-edit-form" class="product-checkbox">
                                    <input type="checkbox" name="delete_ids[]" value="<?php echo $product['id']; ?>" form="mass-delete-form" class="product-checkbox-delete" style="display: none;">
                                </td>
                                <td class="image-cell">
                                    <img src="<?php echo $product['image_url'] ? htmlspecialchars($product['image_url']) : '../assets/img/placeholder.png'; ?>" 
                                         alt="<?php echo htmlspecialchars($product['title']); ?>" 
                                         class="product-image">
                                </td>
                                <td>
                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="product-title-link">
                                        <?php echo htmlspecialchars($product['title']); ?>
                                    </a>
                                </td>
                                <td><strong>$<?php echo number_format($product['price'], 2); ?></strong></td>
                                <td><?php echo htmlspecialchars($product['condition']); ?></td>
                                <td><?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?></td>
                                <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                                <td class="actions-cell">
                                    <div class="product-actions">
                                        <a href="edit-product-clean.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="delete-product.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product?')" title="Delete Product">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Enhanced checkbox functionality
        const selectAll = document.getElementById('select-all');
        const editCheckboxes = document.querySelectorAll('input[name="edit_ids[]"]');
        const deleteCheckboxes = document.querySelectorAll('input[name="delete_ids[]"]');
        const selectedCount = document.getElementById('selected-count');
        const massEditBtn = document.getElementById('mass-edit-btn');
        const massDeleteBtn = document.getElementById('mass-delete-btn');

        function updateSelectedCount() {
            const checkedCount = document.querySelectorAll('input[name="edit_ids[]"]:checked').length;
            selectedCount.textContent = `${checkedCount} selected`;
            
            // Enable/disable mass action buttons
            massEditBtn.disabled = checkedCount === 0;
            massDeleteBtn.disabled = checkedCount === 0;
            
            // Sync delete checkboxes
            deleteCheckboxes.forEach((deleteCheckbox, index) => {
                deleteCheckbox.checked = editCheckboxes[index].checked;
            });
        }

        // Select all functionality
        selectAll.addEventListener('change', function() {
            editCheckboxes.forEach(cb => {
                cb.checked = selectAll.checked;
            });
            updateSelectedCount();
        });

        // Individual checkbox change
        editCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                // Update select all checkbox
                selectAll.checked = Array.from(editCheckboxes).every(cb => cb.checked);
                selectAll.indeterminate = Array.from(editCheckboxes).some(cb => cb.checked) && !selectAll.checked;
                updateSelectedCount();
            });
        });

        // Initialize count
        updateSelectedCount();

        // Form validation
        document.getElementById('mass-edit-form').addEventListener('submit', function(e) {
            const checkedCount = document.querySelectorAll('input[name="edit_ids[]"]:checked').length;
            
            if (checkedCount === 0) {
                e.preventDefault();
                alert('Please select at least one product to edit.');
                return;
            }

            // Check if at least one field is filled
            const price = document.getElementById('mass_price').value;
            const condition = document.getElementById('mass_condition').value;
            const category = document.getElementById('mass_category').value;
            const description = document.getElementById('mass_description_text').value;
            const priceAdjustment = document.querySelector('input[name="mass_price_adjustment"]').value;

            if (!price && !condition && !category && !description && !priceAdjustment) {
                e.preventDefault();
                alert('Please fill in at least one field to update.');
                return;
            }

            if (!confirm(`Are you sure you want to update ${checkedCount} selected products?`)) {
                e.preventDefault();
            }
        });

        // Enhanced filter form auto-submit on change
        const filterInputs = document.querySelectorAll('#adminFilters input, #adminFilters select');
        filterInputs.forEach(input => {
            input.addEventListener('change', function() {
                // Auto-submit after a short delay to allow for multiple quick changes
                clearTimeout(window.filterTimeout);
                window.filterTimeout = setTimeout(() => {
                    document.querySelector('.filters-form').submit();
                }, 500);
            });
        });
    </script>
</body>
</html> 