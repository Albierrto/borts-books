<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/inventory-manager.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

// Initialize inventory manager
$inventoryManager = new InventoryManager($db);

// Handle form submissions
$message = '';
$messageType = '';

if ($_POST) {
    if (isset($_POST['restock_product'])) {
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        $reason = $_POST['reason'] ?? 'Manual restock';
        $admin_user = $_SESSION['admin_username'] ?? 'Admin';
        
        if ($inventoryManager->restockProduct($product_id, $quantity, $reason, $admin_user)) {
            $message = "Product restocked successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to restock product.";
            $messageType = "error";
        }
    }
    
    if (isset($_POST['update_settings'])) {
        $product_id = (int)$_POST['product_id'];
        $settings = [
            'low_stock_threshold' => (int)$_POST['low_stock_threshold'],
            'reorder_point' => (int)$_POST['reorder_point'],
            'max_stock_level' => (int)$_POST['max_stock_level'],
            'supplier_info' => $_POST['supplier_info'],
            'auto_reorder' => isset($_POST['auto_reorder'])
        ];
        
        if ($inventoryManager->setProductInventorySettings($product_id, $settings)) {
            $message = "Inventory settings updated successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to update settings.";
            $messageType = "error";
        }
    }
    
    if (isset($_POST['export_csv'])) {
        $filepath = $inventoryManager->exportInventoryCSV();
        if ($filepath && file_exists($filepath)) {
            header('Content-Type: application/csv');
            header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            unlink($filepath); // Clean up
            exit();
        }
    }
}

// Get analytics data
$analytics = $inventoryManager->getInventoryAnalytics(30);
$lowStockProducts = $inventoryManager->getLowStockProducts(20);

// Get recent activity
$stmt = $db->prepare("
    SELECT 
        il.*, 
        p.title as product_title,
        p.price
    FROM inventory_logs il
    JOIN products p ON il.product_id = p.id
    ORDER BY il.created_at DESC
    LIMIT 20
");
$stmt->execute();
$recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Bort's Books Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .nav-links {
            margin-top: 1rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            margin-right: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            background: rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
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

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .card h3 {
            color: #667eea;
            margin-bottom: 1rem;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: #667eea;
            display: block;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
        }

        .low-stock-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            background: #fff;
        }

        .low-stock-item.critical {
            border-color: #dc3545;
            background: #fff5f5;
        }

        .low-stock-info h4 {
            margin: 0;
            font-size: 1rem;
            color: #333;
        }

        .low-stock-info p {
            margin: 0;
            font-size: 0.9rem;
            color: #666;
        }

        .stock-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .stock-badge.critical {
            background: #dc3545;
            color: white;
        }

        .stock-badge.low {
            background: #ffc107;
            color: #333;
        }

        .btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 0.25rem;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .btn-secondary {
            background: linear-gradient(45deg, #6c757d, #495057);
        }

        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #c82333);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 12px;
            max-width: 500px;
            position: relative;
        }

        .close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid #e9ecef;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }

        .activity-icon.sale {
            background: #d4edda;
            color: #155724;
        }

        .activity-icon.restock {
            background: #cce5ff;
            color: #004085;
        }

        .activity-icon.adjustment {
            background: #fff3cd;
            color: #856404;
        }

        .chart-container {
            height: 300px;
            margin-top: 1rem;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 10px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-boxes"></i> Inventory Management</h1>
            <p>Monitor stock levels, track sales, and manage your inventory efficiently</p>
            <div class="nav-links">
                <a href="admin.php"><i class="fas fa-arrow-left"></i> Back to Admin</a>
                <a href="admin-dashboard.php"><i class="fas fa-chart-bar"></i> Dashboard</a>
                <a href="#" onclick="exportCSV()"><i class="fas fa-download"></i> Export CSV</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Analytics Overview -->
        <div class="dashboard-grid">
            <div class="card">
                <h3><i class="fas fa-chart-pie"></i> Inventory Overview</h3>
                <div class="stat-grid">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo number_format($analytics['overview']['total_products'] ?? 0); ?></span>
                        <div class="stat-label">Total Products</div>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo number_format($analytics['overview']['total_units'] ?? 0); ?></span>
                        <div class="stat-label">Total Units</div>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">$<?php echo number_format($analytics['overview']['total_value'] ?? 0, 2); ?></span>
                        <div class="stat-label">Total Value</div>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $analytics['low_stock_count'] ?? 0; ?></span>
                        <div class="stat-label">Low Stock Items</div>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alerts -->
            <div class="card">
                <h3><i class="fas fa-exclamation-triangle"></i> Low Stock Alerts</h3>
                <?php if (empty($lowStockProducts)): ?>
                    <p style="color: #28a745; font-weight: 600;">
                        <i class="fas fa-check-circle"></i> All products are well stocked!
                    </p>
                <?php else: ?>
                    <?php foreach ($lowStockProducts as $product): ?>
                        <div class="low-stock-item <?php echo $product['stock_quantity'] <= 2 ? 'critical' : ''; ?>">
                            <div class="low-stock-info">
                                <h4><?php echo htmlspecialchars($product['title']); ?></h4>
                                <p>$<?php echo number_format($product['price'], 2); ?> | Threshold: <?php echo $product['threshold']; ?></p>
                            </div>
                            <div>
                                <span class="stock-badge <?php echo $product['stock_quantity'] <= 2 ? 'critical' : 'low'; ?>">
                                    <?php echo $product['stock_quantity']; ?> left
                                </span>
                                <button class="btn btn-small" onclick="openRestockModal(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['title']); ?>')">
                                    <i class="fas fa-plus"></i> Restock
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Top Sellers -->
            <div class="card">
                <h3><i class="fas fa-trophy"></i> Top Sellers (30 Days)</h3>
                <?php if (empty($analytics['top_sellers'])): ?>
                    <p style="color: #666;">No sales data available for the last 30 days.</p>
                <?php else: ?>
                    <?php foreach (array_slice($analytics['top_sellers'], 0, 5) as $index => $seller): ?>
                        <div class="activity-item">
                            <div style="display: flex; align-items: center;">
                                <div class="activity-icon sale">
                                    <span style="font-weight: bold;">#<?php echo $index + 1; ?></span>
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-size: 1rem;"><?php echo htmlspecialchars($seller['title']); ?></h4>
                                    <p style="margin: 0; color: #666; font-size: 0.9rem;">
                                        <?php echo $seller['units_sold']; ?> units sold
                                    </p>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: 600; color: #28a745;">
                                    $<?php echo number_format($seller['revenue'], 2); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <h3><i class="fas fa-history"></i> Recent Activity</h3>
                <?php if (empty($recentActivity)): ?>
                    <p style="color: #666;">No recent inventory activity.</p>
                <?php else: ?>
                    <?php foreach (array_slice($recentActivity, 0, 8) as $activity): ?>
                        <div class="activity-item">
                            <div style="display: flex; align-items: center;">
                                <div class="activity-icon <?php echo $activity['action_type']; ?>">
                                    <i class="fas fa-<?php 
                                        echo $activity['action_type'] === 'sale' ? 'shopping-cart' : 
                                            ($activity['action_type'] === 'restock' ? 'plus' : 'edit'); 
                                    ?>"></i>
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($activity['product_title']); ?>
                                    </h4>
                                    <p style="margin: 0; color: #666; font-size: 0.8rem;">
                                        <?php echo ucfirst($activity['action_type']); ?>: 
                                        <?php echo $activity['quantity_change'] > 0 ? '+' : ''; ?><?php echo $activity['quantity_change']; ?> units
                                    </p>
                                </div>
                            </div>
                            <div style="text-align: right; font-size: 0.8rem; color: #666;">
                                <?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Restock Modal -->
    <div id="restockModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3><i class="fas fa-plus-circle"></i> Restock Product</h3>
            <form method="POST">
                <input type="hidden" id="restock_product_id" name="product_id">
                <div class="form-group">
                    <label>Product:</label>
                    <input type="text" id="restock_product_title" readonly style="background: #f8f9fa;">
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity to Add:</label>
                    <input type="number" id="quantity" name="quantity" min="1" required>
                </div>
                <div class="form-group">
                    <label for="reason">Reason:</label>
                    <input type="text" id="reason" name="reason" value="Manual restock" required>
                </div>
                <button type="submit" name="restock_product" class="btn">
                    <i class="fas fa-plus"></i> Add Stock
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Export Form -->
    <form id="exportForm" method="POST" style="display: none;">
        <input type="hidden" name="export_csv" value="1">
    </form>

    <script>
        function openRestockModal(productId, productTitle) {
            document.getElementById('restock_product_id').value = productId;
            document.getElementById('restock_product_title').value = productTitle;
            document.getElementById('restockModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('restockModal').style.display = 'none';
        }

        function exportCSV() {
            if (confirm('Export inventory data to CSV?')) {
                document.getElementById('exportForm').submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('restockModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Auto-refresh low stock alerts every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html> 