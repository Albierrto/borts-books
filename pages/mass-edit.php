<?php
session_start();
require_once '../includes/db.php';

// Require admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

// Handle bulk price update
if (isset($_POST['bulk_price_percent']) && is_numeric($_POST['bulk_price_percent'])) {
    $percent = floatval($_POST['bulk_price_percent']);
    $db->exec("UPDATE products SET price = price * ($percent / 100.0)");
    header('Location: mass-edit.php');
    exit;
}

// Handle bulk shipping update
if (isset($_POST['bulk_shipping'])) {
    $shipping = trim($_POST['bulk_shipping']);
    $db->exec("UPDATE products SET shipping = '" . addslashes($shipping) . "'");
    header('Location: mass-edit.php');
    exit;
}

// Handle save edits
if (isset($_POST['save']) && isset($_POST['products']) && is_array($_POST['products'])) {
    foreach ($_POST['products'] as $id => $prod) {
        $stmt = $db->prepare("UPDATE products SET title=?, price=?, description=?, \"condition\"=?, shipping=? WHERE id=?");
        $stmt->execute([
            $prod['title'],
            is_numeric($prod['price']) ? $prod['price'] : 0,
            $prod['description'],
            $prod['condition'],
            $prod['shipping'],
            $id
        ]);
    }
    header('Location: mass-edit.php?saved=1');
    exit;
}

// Handle delete
if (isset($_POST['delete_selected']) && isset($_POST['delete_ids']) && is_array($_POST['delete_ids'])) {
    $ids = array_map('intval', $_POST['delete_ids']);
    if ($ids) {
        $db->exec('DELETE FROM products WHERE id IN (' . implode(',', $ids) . ')');
    }
    header('Location: mass-edit.php?deleted=1');
    exit;
}

// Fetch all products
$stmt = $db->query('SELECT * FROM products ORDER BY id DESC');
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mass Edit Listings - Bort's Books</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body { background: #f6f7fb; }
        .mass-edit-container { max-width: 1200px; margin: 2rem auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); padding: 2rem; }
        .mass-edit-title { font-size: 2rem; font-weight: 700; margin-bottom: 1.5rem; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; }
        th, td { padding: 0.7rem 0.5rem; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f4f4f4; font-weight: 600; }
        tr:last-child td { border-bottom: none; }
        input[type="text"], input[type="number"] { width: 100%; padding: 0.4rem; border: 1px solid #ddd; border-radius: 3px; }
        .actions { display: flex; gap: 1rem; margin-bottom: 2rem; }
        .actions input, .actions button { padding: 0.5rem 1.2rem; border-radius: 4px; border: 1px solid #ddd; font-size: 1rem; }
        .actions button { background: #e63946; color: #fff; border: none; font-weight: 600; cursor: pointer; }
        .actions button:hover { background: #b71c2b; }
        .delete-btn { background: #888; color: #fff; border: none; border-radius: 4px; padding: 0.4rem 1rem; cursor: pointer; }
        .delete-btn:hover { background: #c0392b; }
        .save-btn { background: #2a9d8f; color: #fff; border: none; border-radius: 4px; padding: 0.6rem 1.5rem; font-size: 1.1rem; font-weight: 600; cursor: pointer; }
        .save-btn:hover { background: #21867a; }
        .success-msg { color: #2a9d8f; font-weight: 600; margin-bottom: 1rem; }
        .delete-msg { color: #e63946; font-weight: 600; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="admin-header" style="display:flex;align-items:center;gap:2rem;padding:1.2rem 2rem 0.5rem 2rem;">
        <a href="../index.php" class="logo" style="font-size:2rem;font-weight:800;color:#e63946;text-decoration:none;">Bort's <span style='color:#2a9d8f;'>Books</span></a>
    </div>
    <div style="margin-top:2rem;margin-bottom:1rem;text-align:left;">
        <a href="admin.php" style="display:inline-block;color:#2a9d8f;font-weight:600;text-decoration:underline;">&larr; Back to Admin Portal</a>
    </div>
    <div class="mass-edit-container">
        <div class="mass-edit-title">Mass Edit Listings</div>
        <?php if (isset($_GET['saved'])): ?><div class="success-msg">Changes saved!</div><?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?><div class="delete-msg">Selected listings deleted!</div><?php endif; ?>
        <form method="POST" class="actions" style="gap:2rem;">
            <label>Set all prices to <input type="number" name="bulk_price_percent" min="1" max="1000" step="0.1" style="width:80px;"> % of current</label>
            <button type="submit">Apply</button>
        </form>
        <form method="POST" class="actions" style="gap:2rem;">
            <label>Set shipping for all: <input type="text" name="bulk_shipping" placeholder="e.g. Free, $3.99, etc" style="width:120px;"></label>
            <button type="submit">Apply</button>
        </form>
        <form method="POST" style="margin-bottom:1.5rem;">
            <button type="submit" name="delete_selected" class="delete-btn" onclick="return confirm('Delete selected listings?')">Delete Selected</button>
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Price</th>
                        <th>Description</th>
                        <th>Condition</th>
                        <th>Shipping</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $prod): ?>
                    <tr>
                        <td><input type="checkbox" name="delete_ids[]" value="<?php echo $prod['id']; ?>"></td>
                        <td><?php echo $prod['id']; ?></td>
                        <td><a href="product.php?id=<?php echo $prod['id']; ?>" target="_blank" style="color:#e63946;font-weight:600;text-decoration:underline;">View</a><br><input type="text" name="products[<?php echo $prod['id']; ?>][title]" value="<?php echo htmlspecialchars($prod['title']); ?>"></td>
                        <td><input type="number" step="0.01" name="products[<?php echo $prod['id']; ?>][price]" value="<?php echo htmlspecialchars($prod['price']); ?>"></td>
                        <td><input type="text" name="products[<?php echo $prod['id']; ?>][description]" value="<?php echo htmlspecialchars($prod['description']); ?>"></td>
                        <td><input type="text" name="products[<?php echo $prod['id']; ?>][condition]" value="<?php echo htmlspecialchars($prod['condition']); ?>"></td>
                        <td><input type="text" name="products[<?php echo $prod['id']; ?>][shipping]" value="<?php echo htmlspecialchars($prod['shipping'] ?? ''); ?>"></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </form>
        <form method="POST">
            <button type="submit" name="save" class="save-btn">Save Changes</button>
        </form>
    </div>
    <script>
    document.getElementById('selectAll').addEventListener('change', function() {
        document.querySelectorAll('input[type=checkbox][name="delete_ids[]"]').forEach(cb => cb.checked = this.checked);
    });
    </script>
</body>
</html> 