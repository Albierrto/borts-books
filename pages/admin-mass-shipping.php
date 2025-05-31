<?php
session_start();
require_once '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin/login.php');
    exit;
}

$message = '';
$error = '';

// Handle mass updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_selected':
                    $selectedIds = $_POST['selected_products'] ?? [];
                    if (!empty($selectedIds)) {
                        $updates = [];
                        $params = [];
                        
                        // Handle weight - convert pounds and ounces to total ounces
                        if (isset($_POST['weight_lbs']) && $_POST['weight_lbs'] !== '' || isset($_POST['weight_oz']) && $_POST['weight_oz'] !== '') {
                            $weight_lbs = floatval($_POST['weight_lbs'] ?? 0);
                            $weight_oz = floatval($_POST['weight_oz'] ?? 0);
                            $total_weight_oz = ($weight_lbs * 16) + $weight_oz;
                            $updates[] = "weight = ?";
                            $params[] = $total_weight_oz;
                        }
                        
                        // Handle dimensions
                        if (!empty($_POST['dimensions'])) {
                            $updates[] = "dimensions = ?";
                            $params[] = $_POST['dimensions'];
                        }
                        
                        // Handle shipping option
                        if (!empty($_POST['shipping_option'])) {
                            $updates[] = "shipping_option = ?";
                            $params[] = $_POST['shipping_option'];
                        }
                        
                        // Handle flat rate
                        if (!empty($_POST['flat_rate'])) {
                            $updates[] = "flat_rate = ?";
                            $params[] = (float)$_POST['flat_rate'];
                        }
                        
                        if (!empty($updates)) {
                            $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
                            $sql = "UPDATE products SET " . implode(', ', $updates) . " WHERE id IN ($placeholders)";
                            $params = array_merge($params, $selectedIds);
                            
                            $stmt = $db->prepare($sql);
                            $stmt->execute($params);
                            
                            $rowsAffected = $stmt->rowCount();
                            $message = "Updated " . $rowsAffected . " out of " . count($selectedIds) . " selected products successfully!";
                            
                            // Debug information (remove this later)
                            if (isset($_POST['weight_lbs']) || isset($_POST['weight_oz'])) {
                                $message .= " Weight data: {$_POST['weight_lbs']}lbs {$_POST['weight_oz']}oz = {$total_weight_oz}oz";
                            }
                        } else {
                            $message = "No updates to apply. Please fill in at least one field.";
                        }
                    } else {
                        $message = "No products selected. Please select products to update.";
                    }
                    break;
                    
                case 'auto_calculate':
                    // Auto-calculate weights based on product type/category
                    $sql = "UPDATE products SET 
                            weight = CASE 
                                WHEN title LIKE '%box set%' OR title LIKE '%complete%' THEN 32
                                WHEN title LIKE '%omnibus%' OR title LIKE '%deluxe%' THEN 16
                                WHEN title LIKE '%light novel%' THEN 4
                                ELSE 6
                            END,
                            dimensions = CASE 
                                WHEN title LIKE '%box set%' OR title LIKE '%complete%' THEN '10.0x8.0x6.0'
                                WHEN title LIKE '%omnibus%' OR title LIKE '%deluxe%' THEN '8.5x6.0x1.5'
                                WHEN title LIKE '%light novel%' THEN '7.0x4.2x0.8'
                                ELSE '10.0x8.0x6.0'
                            END
                            WHERE weight IS NULL OR weight = 0 OR dimensions IS NULL OR dimensions = ''";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute();
                    
                    $message = "Auto-calculated shipping properties for products based on type!";
                    break;
                    
                case 'set_media_mail':
                    // Set all manga/books to Media Mail eligible
                    $sql = "UPDATE products SET shipping_option = 'calculated' WHERE shipping_option IS NULL OR shipping_option = ''";
                    $stmt = $db->prepare($sql);
                    $stmt->execute();
                    
                    $message = "Set all products to calculated shipping (Media Mail eligible)!";
                    break;
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all products with shipping info
$sql = "SELECT id, title, weight, dimensions, shipping_option, flat_rate, price FROM products ORDER BY title";
$stmt = $db->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = [
    'total' => count($products),
    'no_weight' => count(array_filter($products, fn($p) => empty($p['weight']) || $p['weight'] == 0)),
    'no_dimensions' => count(array_filter($products, fn($p) => empty($p['dimensions']))),
    'no_shipping_option' => count(array_filter($products, fn($p) => empty($p['shipping_option']))),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mass Shipping Editor - Admin</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .stat-label {
            color: #666;
            margin-top: 0.5rem;
        }
        
        .quick-actions {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        
        .action-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .action-btn.primary {
            background: var(--primary);
            color: white;
        }
        
        .action-btn.success {
            background: #28a745;
            color: white;
        }
        
        .action-btn.warning {
            background: #ffc107;
            color: #212529;
        }
        
        .mass-edit-form {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
            color: #333;
        }
        
        .form-group input,
        .form-group select {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .products-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: #f8f9fa;
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .search-box {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 250px;
        }
        
        .products-grid {
            max-height: 600px;
            overflow-y: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .product-title {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .weight-input,
        .dimension-input {
            width: 80px;
            padding: 0.25rem;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        .shipping-select {
            width: 120px;
            padding: 0.25rem;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        .missing-data {
            background: #fff3cd !important;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .select-all-checkbox {
            margin-right: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .table-controls {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .search-box {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div style="margin-bottom: 1rem;">
            <a href="admin.php" style="display: inline-flex; align-items: center; gap: 0.5rem; color: #666; text-decoration: none; font-weight: 600;">
                <i class="fas fa-arrow-left"></i> Back to Admin Dashboard
            </a>
        </div>
        
        <div class="admin-header">
            <h1><i class="fas fa-shipping-fast"></i> Mass Shipping Editor</h1>
            <p>Bulk edit weights, dimensions, and shipping options for accurate USPS rates</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #dc3545;"><?php echo $stats['no_weight']; ?></div>
                <div class="stat-label">Missing Weight</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #fd7e14;"><?php echo $stats['no_dimensions']; ?></div>
                <div class="stat-label">Missing Dimensions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #6f42c1;"><?php echo $stats['no_shipping_option']; ?></div>
                <div class="stat-label">Missing Shipping Option</div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            <div class="action-buttons">
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="auto_calculate">
                    <button type="submit" class="action-btn success">
                        <i class="fas fa-magic"></i> Auto-Calculate Weights & Dimensions
                    </button>
                </form>
                
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="set_media_mail">
                    <button type="submit" class="action-btn primary">
                        <i class="fas fa-envelope"></i> Enable Media Mail for All
                    </button>
                </form>
                
                <a href="../pages/admin.php" class="action-btn warning">
                    <i class="fas fa-arrow-left"></i> Back to Admin
                </a>
            </div>
        </div>
        
        <!-- Mass Edit Form -->
        <form method="post" id="massEditForm" class="mass-edit-form">
            <input type="hidden" name="action" value="update_selected">
            <h3><i class="fas fa-edit"></i> Mass Edit Selected Products</h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="weight_lbs">Weight - Pounds</label>
                    <input type="number" id="weight_lbs" name="weight_lbs" step="0.1" placeholder="0" min="0">
                    <small>Enter pounds (will be converted to ounces)</small>
                </div>
                
                <div class="form-group">
                    <label for="weight_oz">Weight - Ounces</label>
                    <input type="number" id="weight_oz" name="weight_oz" step="0.1" placeholder="6.0" min="0" max="15.9">
                    <small>Typical manga: 6 oz, Light novel: 4 oz, Omnibus: 16 oz</small>
                </div>
                
                <div class="form-group">
                    <label for="dimensions">Dimensions (LxWxH inches)</label>
                    <input type="text" id="dimensions" name="dimensions" placeholder="e.g., 10.0x8.0x6.0">
                    <small>Default: 10x8x6, Light novel: 7.0x4.2x0.8, Omnibus: 8.5x6.0x1.5</small>
                </div>
                
                <div class="form-group">
                    <label for="shipping_option">Shipping Option</label>
                    <select id="shipping_option" name="shipping_option">
                        <option value="">Don't change</option>
                        <option value="calculated">Calculated (Media Mail eligible)</option>
                        <option value="flat">Flat Rate</option>
                        <option value="free">Free Shipping</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="flat_rate">Flat Rate ($)</label>
                    <input type="number" id="flat_rate" name="flat_rate" step="0.01" placeholder="e.g., 5.00">
                    <small>Only used if Flat Rate is selected</small>
                </div>
            </div>
            
            <button type="submit" class="action-btn primary" id="updateSelectedBtn" disabled>
                <i class="fas fa-save"></i> Update Selected Products
            </button>
            
            <!-- Products Table -->
            <div class="products-table" style="margin-top: 2rem;">
                <div class="table-header">
                    <h3><i class="fas fa-table"></i> Products Shipping Data</h3>
                    <div class="table-controls">
                        <input type="text" id="searchBox" class="search-box" placeholder="Search products...">
                        <label>
                            <input type="checkbox" id="selectAll" class="select-all-checkbox"> Select All
                        </label>
                    </div>
                </div>
                
                <div class="products-grid">
                    <table>
                        <thead>
                            <tr>
                                <th>Select</th>
                                <th>Product</th>
                                <th>Weight</th>
                                <th>Dimensions</th>
                                <th>Shipping Option</th>
                                <th>Flat Rate</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <?php foreach ($products as $product): ?>
                                <tr class="<?php echo (empty($product['weight']) || empty($product['dimensions']) || empty($product['shipping_option'])) ? 'missing-data' : ''; ?>">
                                    <td>
                                        <input type="checkbox" name="selected_products[]" value="<?php echo $product['id']; ?>" class="product-checkbox">
                                    </td>
                                    <td class="product-title" title="<?php echo htmlspecialchars($product['title']); ?>">
                                        <?php echo htmlspecialchars($product['title']); ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $weight_oz = $product['weight'] ?: 0;
                                        $lbs = floor($weight_oz / 16);
                                        $oz = $weight_oz % 16;
                                        $weight_display = '';
                                        if ($lbs > 0) {
                                            $weight_display .= $lbs . 'lb ';
                                        }
                                        if ($oz > 0 || $lbs == 0) {
                                            $weight_display .= number_format($oz, 1) . 'oz';
                                        }
                                        echo $weight_display ?: 'Not set';
                                        ?>
                                        <input type="number" class="weight-input" 
                                               value="<?php echo $product['weight'] ?: ''; ?>" 
                                               step="0.1" 
                                               data-product-id="<?php echo $product['id']; ?>"
                                               data-field="weight"
                                               placeholder="oz"
                                               style="display: block; margin-top: 0.25rem; font-size: 0.8rem;">
                                    </td>
                                    <td>
                                        <input type="text" class="dimension-input" 
                                               value="<?php echo htmlspecialchars($product['dimensions'] ?: ''); ?>" 
                                               data-product-id="<?php echo $product['id']; ?>"
                                               data-field="dimensions"
                                               placeholder="10.0x8.0x6.0">
                                    </td>
                                    <td>
                                        <select class="shipping-select" 
                                                data-product-id="<?php echo $product['id']; ?>"
                                                data-field="shipping_option">
                                            <option value="">Not set</option>
                                            <option value="calculated" <?php echo $product['shipping_option'] === 'calculated' ? 'selected' : ''; ?>>Calculated</option>
                                            <option value="flat" <?php echo $product['shipping_option'] === 'flat' ? 'selected' : ''; ?>>Flat Rate</option>
                                            <option value="free" <?php echo $product['shipping_option'] === 'free' ? 'selected' : ''; ?>>Free</option>
                                        </select>
                                    </td>
                                    <td>
                                        $<?php echo number_format($product['flat_rate'] ?: 0, 2); ?>
                                    </td>
                                    <td>
                                        $<?php echo number_format($product['price'], 2); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
    
    <script>
        // Search functionality
        document.getElementById('searchBox').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#productsTableBody tr');
            
            rows.forEach(row => {
                const title = row.querySelector('.product-title').textContent.toLowerCase();
                if (title.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Select all functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.product-checkbox');
            checkboxes.forEach(checkbox => {
                if (checkbox.closest('tr').style.display !== 'none') {
                    checkbox.checked = this.checked;
                }
            });
            updateSelectedCount();
        });
        
        // Update selected count
        function updateSelectedCount() {
            const selectedCount = document.querySelectorAll('.product-checkbox:checked').length;
            const updateBtn = document.getElementById('updateSelectedBtn');
            
            if (selectedCount > 0) {
                updateBtn.disabled = false;
                updateBtn.innerHTML = `<i class="fas fa-save"></i> Update ${selectedCount} Selected Products`;
            } else {
                updateBtn.disabled = true;
                updateBtn.innerHTML = '<i class="fas fa-save"></i> Update Selected Products';
            }
        }
        
        // Listen for checkbox changes
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('product-checkbox')) {
                updateSelectedCount();
            }
        });
        
        // Auto-save individual field changes
        document.addEventListener('change', function(e) {
            if (e.target.dataset.productId && e.target.dataset.field) {
                const productId = e.target.dataset.productId;
                const field = e.target.dataset.field;
                let value = e.target.value;
                
                // Validate dimensions format
                if (field === 'dimensions' && value) {
                    const dimensionPattern = /^\d+(\.\d+)?\s*x\s*\d+(\.\d+)?\s*x\s*\d+(\.\d+)?$/i;
                    if (!dimensionPattern.test(value)) {
                        e.target.style.background = '#f8d7da';
                        e.target.title = 'Invalid format. Use: LengthxWidthxHeight (e.g., 10.0x8.0x6.0)';
                        return;
                    } else {
                        e.target.title = '';
                    }
                }
                
                // Validate weight
                if (field === 'weight' && value) {
                    const weight = parseFloat(value);
                    if (isNaN(weight) || weight < 0) {
                        e.target.style.background = '#f8d7da';
                        e.target.title = 'Weight must be a positive number';
                        return;
                    } else {
                        e.target.title = '';
                    }
                }
                
                // Auto-save via AJAX
                fetch('admin-mass-shipping.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=update_single&product_id=${productId}&field=${field}&value=${encodeURIComponent(value)}`
                })
                .then(response => response.text())
                .then(data => {
                    if (data === 'OK') {
                        // Visual feedback for success
                        e.target.style.background = '#d4edda';
                        setTimeout(() => {
                            e.target.style.background = '';
                        }, 1000);
                        
                        // Update weight display if it's a weight field
                        if (field === 'weight' && value) {
                            updateWeightDisplay(e.target, parseFloat(value));
                        }
                    } else {
                        throw new Error(data);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    e.target.style.background = '#f8d7da';
                    e.target.title = 'Error saving: ' + error.message;
                });
            }
        });
        
        // Function to update weight display
        function updateWeightDisplay(input, weightOz) {
            const lbs = Math.floor(weightOz / 16);
            const oz = weightOz % 16;
            let display = '';
            if (lbs > 0) {
                display += lbs + 'lb ';
            }
            if (oz > 0 || lbs === 0) {
                display += oz.toFixed(1) + 'oz';
            }
            
            // Find the weight display element (previous sibling text)
            const cell = input.parentElement;
            const textNode = cell.firstChild;
            if (textNode && textNode.nodeType === Node.TEXT_NODE) {
                textNode.textContent = display || 'Not set';
            }
        }
        
        // Add helpful tooltips
        document.addEventListener('DOMContentLoaded', function() {
            // Add tooltips to dimension inputs
            document.querySelectorAll('.dimension-input').forEach(input => {
                input.title = 'Format: LengthxWidthxHeight (e.g., 10.0x8.0x6.0)';
            });
            
            // Add tooltips to weight inputs
            document.querySelectorAll('.weight-input').forEach(input => {
                input.title = 'Weight in ounces (16 oz = 1 lb)';
            });
        });
        
        // Initialize
        updateSelectedCount();
    </script>
</body>
</html>

<?php
// Handle single field updates via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_single') {
    $productId = (int)$_POST['product_id'];
    $field = $_POST['field'];
    $value = $_POST['value'];
    
    // Validate field name for security
    $allowedFields = ['weight', 'dimensions', 'shipping_option', 'flat_rate'];
    if (in_array($field, $allowedFields)) {
        // Special handling for weight - ensure it's a valid number
        if ($field === 'weight') {
            $value = floatval($value);
        }
        // Special handling for flat_rate - ensure it's a valid number
        elseif ($field === 'flat_rate') {
            $value = floatval($value);
        }
        // For dimensions, trim whitespace
        elseif ($field === 'dimensions') {
            $value = trim($value);
        }
        
        $sql = "UPDATE products SET `$field` = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$value, $productId]);
        echo "OK";
    } else {
        echo "Invalid field";
    }
    exit;
}
?> 