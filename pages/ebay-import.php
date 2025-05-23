<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}
$pageTitle = "Import from eBay";
$currentPage = "import";

// --- IMAGE CSV IMPORT LOGIC ---
$image_import_msg = '';
if (isset($_POST['import_images'])) {
    if (isset($_FILES['image_csv']) && $_FILES['image_csv']['error'] === UPLOAD_ERR_OK) {
        $csvFile = $_FILES['image_csv']['tmp_name'];
        $handle = fopen($csvFile, 'r');
        if ($handle !== false) {
            $header = fgetcsv($handle);
            $itemIdx = array_search('Item number', $header);
            $imagesIdx = array_search('Images', $header);
            $imported = 0;
            $errors = 0;
            $notFound = [];
            while (($row = fgetcsv($handle)) !== false) {
                $itemNumber = $row[$itemIdx];
                $images = explode('|', $row[$imagesIdx]);
                // Find product by ebay_item_id
                $stmt = $db->prepare("SELECT id FROM products WHERE ebay_item_id = ?");
                $stmt->execute([$itemNumber]);
                $product = $stmt->fetch();
                if ($product) {
                    $productId = $product['id'];
                    foreach ($images as $imgUrl) {
                        $imgUrl = trim($imgUrl);
                        if ($imgUrl !== '') {
                            $insert = $db->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
                            $insert->execute([$productId, $imgUrl]);
                        }
                    }
                    $imported++;
                } else {
                    $errors++;
                    $notFound[] = $itemNumber;
                }
            }
            fclose($handle);
            $image_import_msg = "<div class='alert alert-success'>Imported images for $imported products.";
            if ($errors > 0) {
                $image_import_msg .= " <br><span style='color:#b71c1c;'>$errors products not found:</span> <span style='font-family:monospace;'>" . htmlspecialchars(implode(', ', $notFound)) . "</span>";
            }
            $image_import_msg .= "</div>";
        } else {
            $image_import_msg = "<div class='alert alert-error'>Failed to open uploaded CSV.</div>";
        }
    } else {
        $image_import_msg = "<div class='alert alert-error'>No file uploaded or upload error.</div>";
    }
}
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
        .import-section {
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 2rem 0;
        }
        .import-form {
            max-width: 600px;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-group input:focus {
            border-color: var(--primary);
            outline: none;
        }
        .import-instructions {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 4px;
            margin-bottom: 2rem;
        }
        .import-instructions h3 {
            margin-bottom: 1rem;
            color: var(--primary);
        }
        .import-instructions ol {
            margin-left: 1.5rem;
        }
        .import-instructions li {
            margin-bottom: 0.5rem;
        }
        
        .import-results {
            margin-top: 2rem;
            padding: 1.5rem;
            border-radius: 4px;
        }
        
        .alert {
            padding: 1.5rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .import-errors {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(0,0,0,0.1);
        }
        
        .import-errors h4 {
            color: #721c24;
            margin-bottom: 0.5rem;
        }
        
        .import-errors ul {
            margin: 0;
            padding-left: 1.5rem;
        }
        
        .import-errors li {
            margin-bottom: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin-dashboard.php" style="display:inline-block;margin-bottom:1.5rem;color:#232946;font-weight:600;text-decoration:underline;"><i class="fas fa-arrow-left"></i> Back to Admin Dashboard</a>
    </div>
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
            <div class="search-cart">
                <a href="search.php" title="Search"><i class="fas fa-search"></i></a>
                <a href="cart.php" title="Shopping Cart" class="cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count">0</span>
                </a>
            </div>
        </div>
    </header>

    <section class="page-header">
        <div class="container">
            <h1>Import from eBay</h1>
            <p>Import your manga listings from eBay to Bort's Books</p>
        </div>
    </section>

    <section class="import-section">
        <div class="container">
            <div class="import-instructions">
                <h3>How to Import Your eBay Listings</h3>
                <ol>
                    <li>Export your listings from eBay as a CSV file (columns: title, description, price, condition, image_url)</li>
                    <li>Upload the CSV file below</li>
                    <li>Click "Import CSV" to start the import process</li>
                    <li>Prices will be automatically reduced by 10% during import</li>
                </ol>
            </div>

            <form class="import-form" action="process-ebay-import-enhanced.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="csv_file">Upload eBay Listings CSV</label>
                    <input type="file" id="csv_file" name="csv_file" accept=".csv">
                </div>
                <button type="submit" name="import_csv" class="btn">Import CSV</button>
            </form>

            <?php if (isset($_SESSION['import_result'])): ?>
            <div class="import-results">
                <?php if ($_SESSION['import_result']['success']): ?>
                    <div class="alert alert-success">
                        <h3>Import Successful!</h3>
                        <p>Successfully imported <?php echo $_SESSION['import_result']['imported']; ?> listings.</p>
                        <?php if (!empty($_SESSION['import_result']['errors'])): ?>
                            <div class="import-errors">
                                <h4>Some listings could not be imported:</h4>
                                <ul>
                                    <?php foreach ($_SESSION['import_result']['errors'] as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-error">
                        <h3>Import Failed</h3>
                        <p><?php echo htmlspecialchars($_SESSION['import_result']['error']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <?php 
            // Clear the import result from session
            unset($_SESSION['import_result']);
            endif; 
            ?>

            <?php if (isset($_SESSION['import_debug'])) {
                $debug = $_SESSION['import_debug'];
                echo '<div class="debug-box" style="background:#fffbe6;border:1px solid #ffe082;padding:1.5rem;margin:2rem 0;overflow-x:auto;">';
                echo '<h3 style="margin-top:0;">CSV Import Debug Output</h3>';
                echo '<strong>Imported:</strong> ' . htmlspecialchars($debug['imported']) . '<br>';
                if (!empty($debug['errors'])) {
                    echo '<div style="color:#b71c1c;font-weight:600;">Errors:<ul>';
                    foreach ($debug['errors'] as $err) {
                        echo '<li>' . htmlspecialchars($err) . '</li>';
                    }
                    echo '</ul></div>';
                }
                echo '<h4>Row Details</h4>';
                foreach ($debug['rows'] as $row) {
                    echo '<div style="margin-bottom:1.5rem;padding-bottom:1.5rem;border-bottom:1px solid #eee;">';
                    echo '<div><strong>Row:</strong> <span style="font-family:monospace;font-size:0.95em;">' . htmlspecialchars(json_encode($row['row'])) . '</span></div>';
                    if (!empty($row['images'])) {
                        echo '<div><strong>Images:</strong> ';
                        foreach ($row['images'] as $img) {
                            echo '<a href="' . htmlspecialchars($img) . '" target="_blank">' . htmlspecialchars($img) . '</a> ';
                        }
                        echo '</div>';
                    } else {
                        echo '<div style="color:#b71c1c;"><strong>No images found.</strong></div>';
                    }
                    if (!empty($row['error'])) {
                        echo '<div style="color:#b71c1c;"><strong>Error:</strong> ' . htmlspecialchars($row['error']) . '</div>';
                    }
                    // Always show image_debug info, even if empty or missing
                    if (isset($row['image_debug']) && is_array($row['image_debug']) && count($row['image_debug']) > 0) {
                        foreach ($row['image_debug'] as $imgDbg) {
                            echo '<div style="margin:0.5em 0 0.5em 1em;padding:0.5em;background:#f8f8f8;border:1px solid #eee;">';
                            echo '<strong>eBay Item ID:</strong> ' . htmlspecialchars($imgDbg['ebay_item_id'] ?? '') . '<br>';
                            echo '<strong>HTTP Code:</strong> ' . htmlspecialchars($imgDbg['http_code'] ?? '') . '<br>';
                            echo '<strong>Image Count:</strong> ' . htmlspecialchars($imgDbg['image_count'] ?? '') . '<br>';
                            if (!empty($imgDbg['error'])) {
                                echo '<strong>Error:</strong> <span style="color:#b71c1c;">' . htmlspecialchars($imgDbg['error']) . '</span><br>';
                            }
                            if (!empty($imgDbg['html_snippet'])) {
                                echo '<details><summary>HTML Snippet</summary><pre style="max-width:100%;overflow-x:auto;font-size:0.9em;background:#f4f4f4;">' . htmlspecialchars($imgDbg['html_snippet']) . '</pre></details>';
                            }
                            echo '</div>';
                        }
                    } else {
                        echo '<div style="margin:0.5em 0 0.5em 1em;padding:0.5em;background:#f8f8f8;border:1px solid #eee;color:#b71c1c;">No debug info available for this row.</div>';
                    }
                    echo '</div>';
                }
                echo '</div>';
                unset($_SESSION['import_debug']);
            }
            ?>

            <?php if (!empty($image_import_msg)) echo $image_import_msg; ?>
            <div class="import-instructions" style="margin-top:2em;">
                <h3>How to Import eBay Images from Scraper CSV</h3>
                <ol>
                    <li>Run the eBay image scraper to generate output.csv (columns: Item number, Images)</li>
                    <li>Upload the output.csv file below</li>
                    <li>Click "Import Images" to link images to your products</li>
                </ol>
            </div>
            <form class="import-form" method="post" enctype="multipart/form-data" style="margin-top:1.5em;">
                <div class="form-group">
                    <label for="image_csv">Upload output.csv from image scraper</label>
                    <input type="file" name="image_csv" id="image_csv" accept=".csv" required>
                </div>
                <button type="submit" name="import_images" class="btn">Import Images</button>
            </form>
        </div>
    </section>

    <footer>
        <div class="container footer-container">
            <div class="footer-section">
                <h3>Bort's Books</h3>
                <p>Your trusted source for manga collections since 2023.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="/index.php">Home</a></li>
                    <li><a href="/pages/shop.php">Shop</a></li>
                    <li><a href="/pages/sell.php">Sell Manga</a></li>
                    <li><a href="/pages/about.php">About</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Help</h3>
                <ul>
                    <li><a href="/pages/faq.php">FAQ</a></li>
                    <li><a href="/pages/shipping.php">Shipping</a></li>
                    <li><a href="/pages/returns.php">Returns</a></li>
                    <li><a href="/pages/contact.php">Contact Us</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact</h3>
                <ul>
                    <li><i class="fas fa-envelope"></i> info@bortsbooks.com</li>
                    <li><i class="fas fa-phone"></i> (123) 456-7890</li>
                    <li><i class="fas fa-map-marker-alt"></i> 123 Manga St, Anime City, AC 12345</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom container">
            <p>&copy; <?php echo date('Y'); ?> Bort's Books. All rights reserved.</p>
        </div>
    </footer>
</body>
</html> 