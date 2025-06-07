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
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .title {
            font-size: 2rem;
            font-weight: 800;
            color: #232946;
            margin: 0;
        }
        
        .back-link {
            display: inline-block;
            color: #e63946;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .back-link:hover {
            color: #232946;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-value {
            display: block;
            font-size: 2rem;
            font-weight: 700;
            color: #232946;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-weight: 600;
        }
        
        .search-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
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
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .add-form {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #232946;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            grid-column: 1 / -1;
            min-height: 80px;
            resize: vertical;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            background: #eebbc3;
            color: #232946;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #232946;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .products-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: #232946;
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 700;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #232946;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .product-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        .bulk-actions {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: none;
        }
        
        .bulk-actions.show {
            display: block;
        }
        
        .bulk-actions h3 {
            margin: 0 0 1rem 0;
            color: #232946;
        }
        
        .bulk-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .bulk-action-group {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
        }
        
        .bulk-action-group h4 {
            margin: 0 0 1rem 0;
            color: #232946;
            font-size: 1rem;
        }
        
        .bulk-form {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .bulk-form input,
        .bulk-form select {
            padding: 0.5rem;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .checkbox-cell {
            width: 40px;
            text-align: center;
        }
        
        .select-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: none;
        }
        
        .select-info.show {
            display: block;
        }
        
        .selected-count {
            font-weight: 600;
            color: #232946;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin-dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="header">
            <h1 class="title">Inventory Management</h1>
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
                                <td><strong><?php echo htmlspecialchars($product['title']); ?></strong></td>
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