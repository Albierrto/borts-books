<?php
session_start();
require_once '../includes/db.php';

// Require admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

$message = '';
$messageType = '';

// Handle bulk price update
if (isset($_POST['bulk_price_percent']) && is_numeric($_POST['bulk_price_percent'])) {
    $percent = floatval($_POST['bulk_price_percent']);
    $stmt = $db->prepare("UPDATE products SET price = ROUND(price * (? / 100.0), 2)");
    $stmt->execute([$percent]);
    $affected = $stmt->rowCount();
    $message = "Updated prices for $affected products by {$percent}%";
    $messageType = 'success';
}

// Handle bulk price adjustment (add/subtract fixed amount)
if (isset($_POST['bulk_price_adjustment']) && is_numeric($_POST['bulk_price_adjustment'])) {
    $adjustment = floatval($_POST['bulk_price_adjustment']);
    $operation = $_POST['price_operation'] ?? 'add';
    $operator = $operation === 'subtract' ? '-' : '+';
    
    $stmt = $db->prepare("UPDATE products SET price = GREATEST(0.01, ROUND(price $operator ?, 2))");
    $stmt->execute([$adjustment]);
    $affected = $stmt->rowCount();
    $action = $operation === 'subtract' ? 'decreased' : 'increased';
    $message = "Price $action by $" . number_format($adjustment, 2) . " for $affected products";
    $messageType = 'success';
}

// Handle bulk weight update
if (isset($_POST['bulk_weight']) && is_numeric($_POST['bulk_weight'])) {
    $weight = floatval($_POST['bulk_weight']);
    $stmt = $db->prepare("UPDATE products SET weight = ?");
    $stmt->execute([$weight]);
    $affected = $stmt->rowCount();
    $message = "Updated weight to {$weight} oz for $affected products";
    $messageType = 'success';
}

// Handle bulk shipping option update
if (isset($_POST['bulk_shipping_option'])) {
    $shippingOption = $_POST['bulk_shipping_option'];
    $flatRate = isset($_POST['bulk_flat_rate']) && is_numeric($_POST['bulk_flat_rate']) ? floatval($_POST['bulk_flat_rate']) : null;
    
    $stmt = $db->prepare("UPDATE products SET shipping_option = ?, flat_rate = ?");
    $stmt->execute([$shippingOption, $flatRate]);
    $affected = $stmt->rowCount();
    $message = "Updated shipping option to '$shippingOption' for $affected products";
    $messageType = 'success';
}

// Handle bulk description append/prepend
if (isset($_POST['bulk_description_text']) && !empty($_POST['bulk_description_text'])) {
    $text = trim($_POST['bulk_description_text']);
    $operation = $_POST['description_operation'] ?? 'append';
    
    if ($operation === 'replace') {
        $stmt = $db->prepare("UPDATE products SET description = ?");
        $stmt->execute([$text]);
    } elseif ($operation === 'prepend') {
        $stmt = $db->prepare("UPDATE products SET description = CONCAT(?, ' ', description)");
        $stmt->execute([$text]);
    } else { // append
        $stmt = $db->prepare("UPDATE products SET description = CONCAT(description, ' ', ?)");
        $stmt->execute([$text]);
    }
    
    $affected = $stmt->rowCount();
    $message = "Updated descriptions for $affected products ($operation operation)";
    $messageType = 'success';
}

// Handle bulk condition update
if (isset($_POST['bulk_condition'])) {
    $condition = $_POST['bulk_condition'];
    $stmt = $db->prepare("UPDATE products SET `condition` = ?");
    $stmt->execute([$condition]);
    $affected = $stmt->rowCount();
    $message = "Updated condition to '$condition' for $affected products";
    $messageType = 'success';
}

// Handle bulk dimensions update
if (isset($_POST['bulk_dimensions']) && !empty($_POST['bulk_dimensions'])) {
    $dimensions = trim($_POST['bulk_dimensions']);
    $stmt = $db->prepare("UPDATE products SET dimensions = ?");
    $stmt->execute([$dimensions]);
    $affected = $stmt->rowCount();
    $message = "Updated dimensions to '$dimensions' for $affected products";
    $messageType = 'success';
}

// Handle individual product updates
if (isset($_POST['save']) && isset($_POST['products']) && is_array($_POST['products'])) {
    $updated = 0;
    foreach ($_POST['products'] as $id => $prod) {
        $stmt = $db->prepare("UPDATE products SET title=?, price=?, description=?, `condition`=?, weight=?, dimensions=?, shipping_option=?, flat_rate=? WHERE id=?");
        $stmt->execute([
            $prod['title'],
            is_numeric($prod['price']) ? $prod['price'] : 0,
            $prod['description'],
            $prod['condition'],
            is_numeric($prod['weight']) ? $prod['weight'] : null,
            $prod['dimensions'],
            $prod['shipping_option'],
            is_numeric($prod['flat_rate']) ? $prod['flat_rate'] : null,
            $id
        ]);
        if ($stmt->rowCount() > 0) $updated++;
    }
    $message = "Updated $updated products individually";
    $messageType = 'success';
}

// Handle delete
if (isset($_POST['delete_selected']) && isset($_POST['delete_ids']) && is_array($_POST['delete_ids'])) {
    $ids = array_map('intval', $_POST['delete_ids']);
    if ($ids) {
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $db->prepare("DELETE FROM products WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $deleted = $stmt->rowCount();
        $message = "Deleted $deleted products";
        $messageType = 'error';
    }
}

// Fetch all products
$stmt = $db->query('SELECT * FROM products ORDER BY id DESC LIMIT 100'); // Limit for performance
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mass Edit Products - Bort's Books</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f6f7fb; font-family: 'Inter', sans-serif; }
        .mass-edit-container { 
            max-width: 1400px; 
            margin: 2rem auto; 
            background: #fff; 
            border-radius: 12px; 
            box-shadow: 0 4px 16px rgba(0,0,0,0.1); 
            padding: 2.5rem; 
        }
        .mass-edit-title { 
            font-size: 2.2rem; 
            font-weight: 800; 
            margin-bottom: 2rem; 
            color: #232946;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .bulk-operations {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        .bulk-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        .bulk-card:hover {
            border-color: #eebbc3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .bulk-card h3 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #232946;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .bulk-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .bulk-form label {
            font-weight: 600;
            color: #495057;
        }
        .bulk-form input, .bulk-form select {
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        .bulk-form input:focus, .bulk-form select:focus {
            outline: none;
            border-color: #eebbc3;
        }
        .bulk-btn {
            background: #eebbc3;
            color: #232946;
            border: none;
            border-radius: 6px;
            padding: 0.75rem 1.5rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .bulk-btn:hover {
            background: #232946;
            color: #fff;
            transform: translateY(-1px);
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        .products-table th {
            background: #232946;
            color: #fff;
            padding: 1rem 0.75rem;
            font-weight: 700;
            text-align: left;
        }
        .products-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
        }
        .products-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        .products-table tr:hover {
            background: #e3f2fd;
        }
        .products-table input, .products-table select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .products-table input:focus, .products-table select:focus {
            outline: none;
            border-color: #eebbc3;
        }
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-weight: 600;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        .save-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .save-btn {
            background: #28a745;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        .save-btn:hover {
            background: #218838;
            transform: translateY(-1px);
        }
        .delete-btn {
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        .delete-btn:hover {
            background: #c82333;
            transform: translateY(-1px);
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #e63946;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 2rem;
            transition: color 0.3s ease;
        }
        .back-link:hover {
            color: #232946;
        }
        .input-group {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        .input-group select {
            flex: 0 0 auto;
        }
        @media (max-width: 768px) {
            .bulk-operations {
                grid-template-columns: 1fr;
            }
            .products-table {
                font-size: 0.8rem;
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
                    <li><a href="/pages/track-order.php">Track Order</a></li>
                    <li><a href="/pages/sell.php">Sell Manga</a></li>
                    <li><a href="/pages/about.php">About</a></li>
                </ul>
            </nav>
    </div>
    </header>

    <div class="mass-edit-container">
        <a href="admin-dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>
        
        <div class="mass-edit-title">
            <i class="fas fa-edit"></i>
            Mass Edit Products
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="bulk-operations">
            <!-- Price Percentage Update -->
            <div class="bulk-card">
                <h3><i class="fas fa-percentage"></i> Price Percentage</h3>
                <form method="POST" class="bulk-form">
                    <label>Set all prices to percentage of current:</label>
                    <input type="number" name="bulk_price_percent" min="1" max="1000" step="0.1" placeholder="e.g., 110 for +10%">
                    <button type="submit" class="bulk-btn">
                        <i class="fas fa-calculator"></i>
                        Apply Percentage
                    </button>
                </form>
            </div>

            <!-- Price Fixed Adjustment -->
            <div class="bulk-card">
                <h3><i class="fas fa-dollar-sign"></i> Price Adjustment</h3>
                <form method="POST" class="bulk-form">
                    <label>Add or subtract fixed amount:</label>
                    <div class="input-group">
                        <select name="price_operation">
                            <option value="add">Add $</option>
                            <option value="subtract">Subtract $</option>
                        </select>
                        <input type="number" name="bulk_price_adjustment" min="0" step="0.01" placeholder="5.00">
                    </div>
                    <button type="submit" class="bulk-btn">
                        <i class="fas fa-plus-minus"></i>
                        Adjust Prices
                    </button>
                </form>
            </div>

            <!-- Weight Update -->
            <div class="bulk-card">
                <h3><i class="fas fa-weight"></i> Bulk Weight</h3>
                <form method="POST" class="bulk-form">
                    <label>Set weight for all products (oz):</label>
                    <input type="number" name="bulk_weight" min="0" step="0.1" placeholder="6.0">
                    <button type="submit" class="bulk-btn">
                        <i class="fas fa-balance-scale"></i>
                        Update Weight
                    </button>
                </form>
            </div>

            <!-- Shipping Options -->
            <div class="bulk-card">
                <h3><i class="fas fa-shipping-fast"></i> Shipping Options</h3>
                <form method="POST" class="bulk-form">
                    <label>Shipping method:</label>
                    <select name="bulk_shipping_option">
                        <option value="calculated">Calculated Shipping</option>
                        <option value="flat">Flat Rate</option>
                        <option value="free">Free Shipping</option>
                    </select>
                    <label>Flat rate amount (if selected):</label>
                    <input type="number" name="bulk_flat_rate" min="0" step="0.01" placeholder="5.00">
                    <button type="submit" class="bulk-btn">
                        <i class="fas fa-truck"></i>
                        Update Shipping
                    </button>
                </form>
            </div>

            <!-- Description Update -->
            <div class="bulk-card">
                <h3><i class="fas fa-align-left"></i> Description Update</h3>
                <form method="POST" class="bulk-form">
                    <label>Operation:</label>
                    <select name="description_operation">
                        <option value="append">Append to end</option>
                        <option value="prepend">Add to beginning</option>
                        <option value="replace">Replace all</option>
                    </select>
                    <label>Text:</label>
                    <input type="text" name="bulk_description_text" placeholder="Text to add/replace">
                    <button type="submit" class="bulk-btn">
                        <i class="fas fa-text-height"></i>
                        Update Descriptions
                    </button>
                </form>
            </div>

            <!-- Condition Update -->
            <div class="bulk-card">
                <h3><i class="fas fa-star"></i> Condition Update</h3>
                <form method="POST" class="bulk-form">
                    <label>Set condition for all:</label>
                    <select name="bulk_condition">
                        <option value="New">New</option>
                        <option value="Like New">Like New</option>
                        <option value="Very Good">Very Good</option>
                        <option value="Good">Good</option>
                        <option value="Acceptable">Acceptable</option>
                    </select>
                    <button type="submit" class="bulk-btn">
                        <i class="fas fa-medal"></i>
                        Update Condition
                    </button>
        </form>
            </div>

            <!-- Dimensions Update -->
            <div class="bulk-card">
                <h3><i class="fas fa-ruler-combined"></i> Dimensions</h3>
                <form method="POST" class="bulk-form">
                    <label>Set dimensions for all (LxWxH):</label>
                    <input type="text" name="bulk_dimensions" placeholder="7.5x5.0x0.8">
                    <button type="submit" class="bulk-btn">
                        <i class="fas fa-expand-arrows-alt"></i>
                        Update Dimensions
                    </button>
        </form>
            </div>
        </div>

        <!-- Individual Edit Table -->
        <form method="POST" id="mass-edit-form">
            <div class="save-section">
                <button type="submit" name="delete_selected" class="delete-btn" onclick="return confirm('Are you sure you want to delete the selected products? This cannot be undone.')">
                    <i class="fas fa-trash"></i>
                    Delete Selected
                </button>
                
                <div style="color: #666; font-size: 0.9rem;">
                    Showing first 100 products. Use bulk operations above for larger changes.
                </div>
            </div>

            <table class="products-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Price</th>
                        <th>Weight (oz)</th>
                        <th>Dimensions</th>
                        <th>Description</th>
                        <th>Condition</th>
                        <th>Shipping</th>
                        <th>Flat Rate</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $prod): ?>
                    <tr>
                        <td><input type="checkbox" name="delete_ids[]" value="<?php echo $prod['id']; ?>"></td>
                        <td style="font-weight: 700; color: #232946;"><?php echo $prod['id']; ?></td>
                        <td><input type="text" name="products[<?php echo $prod['id']; ?>][title]" value="<?php echo htmlspecialchars($prod['title']); ?>"></td>
                        <td><input type="number" step="0.01" name="products[<?php echo $prod['id']; ?>][price]" value="<?php echo htmlspecialchars($prod['price']); ?>"></td>
                        <td><input type="number" step="0.1" name="products[<?php echo $prod['id']; ?>][weight]" value="<?php echo htmlspecialchars($prod['weight'] ?? ''); ?>"></td>
                        <td><input type="text" name="products[<?php echo $prod['id']; ?>][dimensions]" value="<?php echo htmlspecialchars($prod['dimensions'] ?? ''); ?>" placeholder="7.5x5.0x0.8"></td>
                        <td><input type="text" name="products[<?php echo $prod['id']; ?>][description]" value="<?php echo htmlspecialchars($prod['description']); ?>"></td>
                        <td>
                            <select name="products[<?php echo $prod['id']; ?>][condition]">
                                <option value="New" <?php echo $prod['condition'] === 'New' ? 'selected' : ''; ?>>New</option>
                                <option value="Like New" <?php echo $prod['condition'] === 'Like New' ? 'selected' : ''; ?>>Like New</option>
                                <option value="Very Good" <?php echo $prod['condition'] === 'Very Good' ? 'selected' : ''; ?>>Very Good</option>
                                <option value="Good" <?php echo $prod['condition'] === 'Good' ? 'selected' : ''; ?>>Good</option>
                                <option value="Acceptable" <?php echo $prod['condition'] === 'Acceptable' ? 'selected' : ''; ?>>Acceptable</option>
                            </select>
                        </td>
                        <td>
                            <select name="products[<?php echo $prod['id']; ?>][shipping_option]">
                                <option value="calculated" <?php echo ($prod['shipping_option'] ?? 'calculated') === 'calculated' ? 'selected' : ''; ?>>Calculated</option>
                                <option value="flat" <?php echo ($prod['shipping_option'] ?? '') === 'flat' ? 'selected' : ''; ?>>Flat Rate</option>
                                <option value="free" <?php echo ($prod['shipping_option'] ?? '') === 'free' ? 'selected' : ''; ?>>Free</option>
                            </select>
                        </td>
                        <td><input type="number" step="0.01" name="products[<?php echo $prod['id']; ?>][flat_rate]" value="<?php echo htmlspecialchars($prod['flat_rate'] ?? ''); ?>"></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div class="save-section">
                <div style="color: #666;">
                    <i class="fas fa-info-circle"></i>
                    Make individual changes above, then click Save Changes
                </div>
                <button type="submit" name="save" class="save-btn">
                    <i class="fas fa-save"></i>
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    <script>
    document.getElementById('selectAll').addEventListener('change', function() {
        document.querySelectorAll('input[type=checkbox][name="delete_ids[]"]').forEach(cb => cb.checked = this.checked);
    });
    </script>
</body>
</html> 