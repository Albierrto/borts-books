<?php
session_start();

// Define constant for secure database access
define('INCLUDED_FROM_APP', true);

// Simple admin check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

require_once dirname(__DIR__) . '/includes/db.php';

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_book') {
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $isbn = trim($_POST['isbn'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $condition = $_POST['condition'] ?? '';
        $description = trim($_POST['description'] ?? '');
        
        if (empty($title) || empty($author) || $price <= 0) {
            $error = 'Title, author, and price are required';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO products (title, author, isbn, price, `condition`, description) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $author, $isbn, $price, $condition, $description]);
                $message = 'Product added successfully';
            } catch (PDOException $e) {
                $error = 'Error adding product: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete_book') {
        $book_id = intval($_POST['book_id'] ?? 0);
        
        if ($book_id > 0) {
            try {
                $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$book_id]);
                $message = 'Product deleted successfully';
            } catch (PDOException $e) {
                $error = 'Error deleting product: ' . $e->getMessage();
            }
        } else {
            $error = 'Invalid product ID';
        }
    } elseif ($action === 'bulk_delete') {
        $selected_ids = $_POST['selected_products'] ?? [];
        
        if (!empty($selected_ids)) {
            try {
                $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
                $stmt = $db->prepare("DELETE FROM products WHERE id IN ($placeholders)");
                $stmt->execute($selected_ids);
                $message = count($selected_ids) . ' products deleted successfully';
            } catch (PDOException $e) {
                $error = 'Error deleting products: ' . $e->getMessage();
            }
        } else {
            $error = 'No products selected';
        }
    } elseif ($action === 'bulk_update_price') {
        $selected_ids = $_POST['selected_products'] ?? [];
        $price_adjustment = floatval($_POST['price_adjustment'] ?? 0);
        $adjustment_type = $_POST['adjustment_type'] ?? 'add';
        
        if (!empty($selected_ids) && $price_adjustment != 0) {
            try {
                $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
                if ($adjustment_type === 'multiply') {
                    $stmt = $db->prepare("UPDATE products SET price = price * ? WHERE id IN ($placeholders)");
                    $params = array_merge([$price_adjustment], $selected_ids);
                } else {
                    $operator = $adjustment_type === 'subtract' ? '-' : '+';
                    $stmt = $db->prepare("UPDATE products SET price = price $operator ? WHERE id IN ($placeholders)");
                    $params = array_merge([$price_adjustment], $selected_ids);
                }
                $stmt->execute($params);
                $message = count($selected_ids) . ' products updated successfully';
            } catch (PDOException $e) {
                $error = 'Error updating prices: ' . $e->getMessage();
            }
        } else {
            $error = 'No products selected or invalid price adjustment';
        }
    } elseif ($action === 'bulk_update_condition') {
        $selected_ids = $_POST['selected_products'] ?? [];
        $new_condition = $_POST['new_condition'] ?? '';
        
        if (!empty($selected_ids) && !empty($new_condition)) {
            try {
                $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
                $stmt = $db->prepare("UPDATE products SET `condition` = ? WHERE id IN ($placeholders)");
                $params = array_merge([$new_condition], $selected_ids);
                $stmt->execute($params);
                $message = count($selected_ids) . ' products updated successfully';
            } catch (PDOException $e) {
                $error = 'Error updating condition: ' . $e->getMessage();
            }
        } else {
            $error = 'No products selected or condition not specified';
        }
    }
}

// Get search parameters
$search = trim($_GET['search'] ?? '');
$condition_filter = $_GET['condition'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($condition_filter) {
    $where_conditions[] = "`condition` = ?";
    $params[] = $condition_filter;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get products
try {
    $stmt = $db->prepare("SELECT * FROM products $where_clause ORDER BY title");
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching products: ' . $e->getMessage();
    $products = [];
}

// Get statistics
try {
    $total_products = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $total_value = $db->query("SELECT SUM(price) FROM products")->fetchColumn() ?: 0;
} catch (PDOException $e) {
    $total_products = 0;
    $total_value = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Bort's Books</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
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
            --spacing-1: 0.25rem;
            --spacing-2: 0.5rem;
            --spacing-3: 0.75rem;
            --spacing-4: 1rem;
            --spacing-6: 1.5rem;
            --spacing-8: 2rem;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }
        body {
            background: var(--gray-50);
            color: var(--gray-900);
        }
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: var(--spacing-4);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-8);
            padding: var(--spacing-6);
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
        }
        .title {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0;
        }
        .back-link {
            display: inline-block;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: var(--spacing-4);
            transition: color 0.2s;
        }
        .back-link:hover {
            color: var(--primary-dark);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: var(--spacing-6);
            margin-bottom: var(--spacing-8);
        }
        .stat-card {
            background: white;
            padding: var(--spacing-6);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            text-align: center;
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
            margin-bottom: var(--spacing-2);
        }
        .stat-label {
            color: var(--gray-500);
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .search-section {
            background: white;
            padding: var(--spacing-6);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-8);
        }
        .search-form {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: var(--spacing-4);
            align-items: end;
        }
        .search-form input,
        .search-form select {
            padding: var(--spacing-3);
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
            padding: var(--spacing-3) var(--spacing-6);
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-2);
            transition: all 0.2s;
        }
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        .btn-danger:hover {
            background: #b91c1c;
        }
        .products-table {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }
        .table-header {
            padding: var(--spacing-4);
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            font-weight: 600;
            color: var(--gray-700);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            text-align: left;
            padding: var(--spacing-4);
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-700);
            border-bottom: 1px solid var(--gray-200);
        }
        td {
            padding: var(--spacing-4);
            border-bottom: 1px solid var(--gray-200);
            color: var(--gray-600);
        }
        tr:hover {
            background: var(--gray-100);
        }
        .message {
            padding: var(--spacing-4);
            margin-bottom: var(--spacing-4);
            border-radius: 8px;
            font-weight: 600;
        }
        .message.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .product-actions {
            display: flex;
            gap: var(--spacing-2);
        }
        .btn-sm {
            padding: var(--spacing-2) var(--spacing-4);
            font-size: 0.875rem;
        }
        .bulk-actions {
            background: white;
            padding: var(--spacing-6);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-8);
            display: none;
        }
        .bulk-actions.show {
            display: block;
        }
        .bulk-actions h3 {
            margin: 0 0 var(--spacing-4) 0;
            color: var(--gray-900);
        }
        .bulk-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: var(--spacing-6);
        }
        .bulk-action-group {
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            padding: var(--spacing-4);
        }
        .bulk-action-group h4 {
            margin: 0 0 var(--spacing-4) 0;
            color: var(--gray-900);
            font-size: 1rem;
        }
        .bulk-form {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-2);
        }
        .bulk-form input,
        .bulk-form select {
            padding: var(--spacing-2);
            border: 1px solid var(--gray-200);
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .checkbox-cell {
            width: 40px;
            text-align: center;
        }
        .select-info {
            background: var(--gray-100);
            padding: var(--spacing-4);
            border-radius: 8px;
            margin-bottom: var(--spacing-4);
            display: none;
        }
        .select-info.show {
            display: block;
        }
        .selected-count {
            font-weight: 600;
            color: var(--primary);
        }
        .product-title-link {
            color: var(--primary) !important;
            text-decoration: none;
            transition: color 0.2s;
        }
        .product-title-link:hover {
            color: var(--primary-dark) !important;
            text-decoration: underline;
        }
        .add-form {
            background: white;
            padding: var(--spacing-6);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-8);
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-4);
            margin-bottom: var(--spacing-4);
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-weight: 600;
            margin-bottom: var(--spacing-2);
            color: var(--gray-900);
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: var(--spacing-3);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            font-size: 0.875rem;
        }
        .form-group textarea {
            grid-column: 1 / -1;
            min-height: 80px;
            resize: vertical;
        }
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--spacing-4);
            }
            .stats-grid, .bulk-actions-grid {
                grid-template-columns: 1fr;
            }
            .search-form {
                grid-template-columns: 1fr;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .products-table {
                display: block;
                overflow-x: auto;
            }
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --gray-50: #111827;
                --gray-100: #1f2937;
                --gray-200: #374151;
                --gray-300: #4b5563;
                --gray-400: #6b7280;
                --gray-500: #9ca3af;
                --gray-600: #d1d5db;
                --gray-700: #e5e7eb;
                --gray-800: #f3f4f6;
                --gray-900: #f9fafb;
            }
            body {
                background: var(--gray-50);
                color: var(--gray-900);
            }
            .header,
            .stat-card,
            .search-section,
            .products-table,
            .bulk-actions,
            .add-form {
                background: var(--gray-100);
            }
            .table-header,
            th {
                background: var(--gray-200);
                color: var(--gray-700);
            }
            tr:hover {
                background: var(--gray-200);
            }
            .bulk-action-group {
                border-color: var(--gray-300);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin-dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="header">
            <h1 class="title">Inventory Management</h1>
            <div style="display: flex; gap: 1rem;">
                <a href="ebay-single-import.php" class="btn" style="background: #28a745;">
                    📦 Import eBay Listing
                </a>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-value"><?php echo $total_products; ?></span>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card">
                <span class="stat-value">$<?php echo number_format($total_value, 2); ?></span>
                <div class="stat-label">Total Value</div>
            </div>
        </div>
        
        <!-- Search -->
        <div class="search-section">
            <form class="search-form" method="GET">
                <input type="text" name="search" placeholder="Search by title, author, or ISBN..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="condition">
                    <option value="">All Conditions</option>
                    <option value="New" <?php if($condition_filter=='New') echo 'selected'; ?>>New</option>
                    <option value="Like New" <?php if($condition_filter=='Like New') echo 'selected'; ?>>Like New</option>
                    <option value="Very Good" <?php if($condition_filter=='Very Good') echo 'selected'; ?>>Very Good</option>
                    <option value="Good" <?php if($condition_filter=='Good') echo 'selected'; ?>>Good</option>
                    <option value="Acceptable" <?php if($condition_filter=='Acceptable') echo 'selected'; ?>>Acceptable</option>
                </select>
                <button type="submit" class="btn">Search</button>
            </form>
        </div>
        
        <!-- Bulk Actions -->
        <div class="bulk-actions" id="bulkActions">
            <div class="select-info" id="selectInfo">
                <span class="selected-count" id="selectedCount">0</span> products selected
                <button type="button" class="btn btn-sm" onclick="clearSelection()" style="margin-left: 1rem;">Clear Selection</button>
            </div>
            
            <h3>Bulk Actions</h3>
            <div class="bulk-actions-grid">
                <!-- Bulk Delete -->
                <div class="bulk-action-group">
                    <h4>Delete Selected</h4>
                    <form method="POST" class="bulk-form" onsubmit="return confirmBulkDelete()">
                        <input type="hidden" name="action" value="bulk_delete">
                        <input type="hidden" name="selected_products" id="deleteSelectedProducts">
                        <button type="submit" class="btn btn-danger">Delete Selected Products</button>
                    </form>
                </div>
                
                <!-- Bulk Price Update -->
                <div class="bulk-action-group">
                    <h4>Update Prices</h4>
                    <form method="POST" class="bulk-form">
                        <input type="hidden" name="action" value="bulk_update_price">
                        <input type="hidden" name="selected_products" id="priceSelectedProducts">
                        <select name="adjustment_type" required>
                            <option value="add">Add to price</option>
                            <option value="subtract">Subtract from price</option>
                            <option value="multiply">Multiply price by</option>
                        </select>
                        <input type="number" name="price_adjustment" step="0.01" placeholder="Amount" required>
                        <button type="submit" class="btn">Update Prices</button>
                    </form>
                </div>
                
                <!-- Bulk Condition Update -->
                <div class="bulk-action-group">
                    <h4>Update Condition</h4>
                    <form method="POST" class="bulk-form">
                        <input type="hidden" name="action" value="bulk_update_condition">
                        <input type="hidden" name="selected_products" id="conditionSelectedProducts">
                        <select name="new_condition" required>
                            <option value="">Select condition</option>
                            <option value="New">New</option>
                            <option value="Like New">Like New</option>
                            <option value="Very Good">Very Good</option>
                            <option value="Good">Good</option>
                            <option value="Acceptable">Acceptable</option>
                        </select>
                        <button type="submit" class="btn">Update Condition</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Add Product Form -->
        <div class="add-form">
            <h3 style="margin-bottom: 1rem; color: #232946;">Add New Product</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_book">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="author">Author *</label>
                        <input type="text" id="author" name="author" required>
                    </div>
                    <div class="form-group">
                        <label for="isbn">ISBN</label>
                        <input type="text" id="isbn" name="isbn">
                    </div>
                    <div class="form-group">
                        <label for="price">Price *</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="condition">Condition</label>
                        <select id="condition" name="condition">
                            <option value="New">New</option>
                            <option value="Like New">Like New</option>
                            <option value="Very Good">Very Good</option>
                            <option value="Good">Good</option>
                            <option value="Acceptable">Acceptable</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Optional description..."></textarea>
                    </div>
                </div>
                <button type="submit" class="btn">Add Product</button>
            </form>
        </div>
        
        <!-- Products Table -->
        <div class="products-table">
            <div class="table-header">
                Products (<?php echo count($products); ?>)
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th class="checkbox-cell">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                        </th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>ISBN</th>
                        <th>Price</th>
                        <th>Condition</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 3rem; color: #666;">
                                No products found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="checkbox-cell">
                                    <input type="checkbox" class="product-checkbox" value="<?php echo $product['id']; ?>" onchange="updateSelection()">
                                </td>
                                <td>
                                    <strong>
                                        <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="product-title-link">
                                            <?php echo htmlspecialchars($product['title']); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td><?php echo htmlspecialchars($product['author']); ?></td>
                                <td><?php echo htmlspecialchars($product['isbn']); ?></td>
                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($product['condition']); ?></td>
                                <td>
                                    <div class="product-actions">
                                        <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm">Edit</a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_book">
                                            <input type="hidden" name="book_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product?')">Delete</button>
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
        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const productCheckboxes = document.querySelectorAll('.product-checkbox');
            
            productCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            
            updateSelection();
        }
        
        function updateSelection() {
            const productCheckboxes = document.querySelectorAll('.product-checkbox');
            const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
            const selectAllCheckbox = document.getElementById('selectAll');
            const bulkActions = document.getElementById('bulkActions');
            const selectInfo = document.getElementById('selectInfo');
            const selectedCount = document.getElementById('selectedCount');
            
            // Update select all checkbox state
            if (checkedBoxes.length === 0) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = false;
            } else if (checkedBoxes.length === productCheckboxes.length) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = true;
            } else {
                selectAllCheckbox.indeterminate = true;
            }
            
            // Show/hide bulk actions
            if (checkedBoxes.length > 0) {
                bulkActions.classList.add('show');
                selectInfo.classList.add('show');
                selectedCount.textContent = checkedBoxes.length;
                
                // Update hidden inputs with selected product IDs
                const selectedIds = Array.from(checkedBoxes).map(cb => cb.value);
                document.getElementById('deleteSelectedProducts').value = selectedIds.join(',');
                document.getElementById('priceSelectedProducts').value = selectedIds.join(',');
                document.getElementById('conditionSelectedProducts').value = selectedIds.join(',');
            } else {
                bulkActions.classList.remove('show');
                selectInfo.classList.remove('show');
            }
        }
        
        function clearSelection() {
            const productCheckboxes = document.querySelectorAll('.product-checkbox');
            const selectAllCheckbox = document.getElementById('selectAll');
            
            productCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
            
            updateSelection();
        }
        
        function confirmBulkDelete() {
            const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
            const count = checkedBoxes.length;
            
            if (count === 0) {
                alert('Please select products to delete.');
                return false;
            }
            
            return confirm(`Are you sure you want to delete ${count} selected product${count > 1 ? 's' : ''}? This action cannot be undone.`);
        }
        
        // Handle bulk action form submissions
        document.querySelectorAll('.bulk-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
                
                if (checkedBoxes.length === 0) {
                    e.preventDefault();
                    alert('Please select products first.');
                    return false;
                }
                
                // Update the hidden input for this specific form
                const hiddenInput = this.querySelector('input[name="selected_products"]');
                if (hiddenInput) {
                    const selectedIds = Array.from(checkedBoxes).map(cb => cb.value);
                    hiddenInput.value = selectedIds.join(',');
                }
            });
        });
    </script>
</body>
</html> 