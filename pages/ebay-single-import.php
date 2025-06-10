<?php
// Define constant for secure database access
define('INCLUDED_FROM_APP', true);

// Use the same secure authentication as admin dashboard
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/db.php';

// Start secure session
secure_session_start();

// Set security headers  
set_security_headers();

// Check admin authentication (same as dashboard)
check_admin_auth();

// Generate CSRF token for forms
$csrf_token = generate_csrf_token();

$message = '';
$error = '';
$importedProduct = null;

// Include the enhanced image fetching function
require_once 'process-ebay-import-enhanced.php';

// Extract eBay item ID from URL or direct ID
function extractEbayItemId($input) {
    // Remove whitespace
    $input = trim($input);
    
    // If it's just a number, return it
    if (is_numeric($input)) {
        return $input;
    }
    
    // Extract from eBay URL patterns
    if (preg_match('/(?:ebay\.com\/itm\/|\/itm\/)(\d+)/', $input, $matches)) {
        return $matches[1];
    }
    
    // Extract from item ID in URL parameters
    if (preg_match('/[?&]item=(\d+)/', $input, $matches)) {
        return $matches[1];
    }
    
    return false;
}

// Fetch eBay listing data
function fetchEbayListingData($itemId) {
    $url = "https://www.ebay.com/itm/" . $itemId;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$html) {
        return false;
    }
    
    $data = [
        'title' => '',
        'description' => '',
        'price' => 0,
        'condition' => 'Good',
        'category' => 'Manga'
    ];
    
    // Extract title
    if (preg_match('/<title>([^<]+)<\/title>/', $html, $matches)) {
        $title = html_entity_decode($matches[1]);
        $title = preg_replace('/\s*\|\s*eBay.*$/', '', $title); // Remove eBay suffix
        $data['title'] = trim($title);
    }
    
    // Extract price
    if (preg_match('/"buyNowPrice":\{"value":"([^"]+)"/', $html, $matches)) {
        $data['price'] = floatval($matches[1]);
    } elseif (preg_match('/"currentPrice":\{"value":"([^"]+)"/', $html, $matches)) {
        $data['price'] = floatval($matches[1]);
    }
    
    // Extract condition
    if (preg_match('/"conditionDisplayName":"([^"]+)"/', $html, $matches)) {
        $condition = $matches[1];
        // Map eBay conditions to our conditions
        $conditionMap = [
            'New' => 'New',
            'New with tags' => 'New',
            'New without tags' => 'Like New',
            'Like New' => 'Like New',
            'Excellent' => 'Very Good',
            'Very Good' => 'Very Good',
            'Good' => 'Good',
            'Acceptable' => 'Acceptable',
            'Used' => 'Good'
        ];
        $data['condition'] = $conditionMap[$condition] ?? 'Good';
    }
    
    // Extract basic description
    if (preg_match('/"shortDescription":"([^"]+)"/', $html, $matches)) {
        $data['description'] = html_entity_decode($matches[1]);
    }
    
    return $data;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $ebayInput = trim($_POST['ebay_input'] ?? '');
        $importImages = isset($_POST['import_images']);
    
    if (empty($ebayInput)) {
        $error = 'Please enter an eBay item ID or URL';
    } else {
        $itemId = extractEbayItemId($ebayInput);
        
        if (!$itemId) {
            $error = 'Could not extract eBay item ID from input';
        } else {
            // Check if item already exists
            $stmt = $db->prepare("SELECT id, title FROM products WHERE ebay_item_id = ?");
            $stmt->execute([$itemId]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $error = "This eBay listing is already imported: <a href='edit-product.php?id=" . $existing['id'] . "'>" . htmlspecialchars($existing['title']) . "</a>";
            } else {
                // Fetch listing data
                $listingData = fetchEbayListingData($itemId);
                
                if (!$listingData || empty($listingData['title'])) {
                    $error = 'Could not fetch listing data from eBay. Please check the item ID.';
                } else {
                    try {
                        // Insert product
                        $stmt = $db->prepare("
                            INSERT INTO products (title, description, price, `condition`, category, ebay_item_id, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, NOW())
                        ");
                        
                        $stmt->execute([
                            $listingData['title'],
                            $listingData['description'],
                            $listingData['price'],
                            $listingData['condition'],
                            $listingData['category'],
                            $itemId
                        ]);
                        
                        $productId = $db->lastInsertId();
                        
                        // Import images if requested
                        $imageCount = 0;
                        if ($importImages) {
                            $debugInfo = [];
                            $images = fetchEbayImages($itemId, $debugInfo);
                            
                            foreach ($images as $index => $imageUrl) {
                                try {
                                    $stmt = $db->prepare("INSERT INTO product_images (product_id, image_url, is_main) VALUES (?, ?, ?)");
                                    $isMain = ($index === 0) ? 1 : 0; // First image is main
                                    $stmt->execute([$productId, $imageUrl, $isMain]);
                                    $imageCount++;
                                } catch (Exception $e) {
                                    // Continue if image insert fails
                                }
                            }
                        }
                        
                        $importedProduct = [
                            'id' => $productId,
                            'title' => $listingData['title'],
                            'price' => $listingData['price'],
                            'condition' => $listingData['condition'],
                            'image_count' => $imageCount
                        ];
                        
                        $message = "Successfully imported listing! " . ($imageCount > 0 ? "Imported $imageCount images." : "");
                        
                    } catch (Exception $e) {
                        $error = 'Database error: ' . $e->getMessage();
                                         }
                 }
             }
         }
     }
    } // Close the CSRF verification else block
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Single eBay Listing - Bort's Books</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .title {
            font-size: 2rem;
            font-weight: 800;
            color: #232946;
            margin: 0 0 0.5rem 0;
        }
        
        .subtitle {
            color: #666;
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
        
        .import-form {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #232946;
        }
        
        .form-group input[type="text"],
        .form-group input[type="url"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #eebbc3;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
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
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #232946;
            color: white;
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
        
        .import-result {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .product-preview {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1rem;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .product-info h3 {
            margin: 0 0 0.5rem 0;
            color: #232946;
        }
        
        .product-info p {
            margin: 0;
            color: #666;
        }
        
        .product-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        .instructions {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .instructions h3 {
            margin: 0 0 1rem 0;
            color: #232946;
        }
        
        .instructions ul {
            margin: 0;
            padding-left: 1.5rem;
        }
        
        .instructions li {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin-inventory.php" class="back-link">← Back to Inventory</a>
        
        <div class="header">
            <h1 class="title">Import Single eBay Listing</h1>
            <p class="subtitle">Quickly import a single eBay listing with photos</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message success">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="instructions">
            <h3>How to Import</h3>
            <ul>
                <li><strong>Find your eBay listing</strong> and copy the URL or item ID</li>
                <li><strong>Paste it below</strong> - works with full URLs or just the item number</li>
                <li><strong>Choose to import images</strong> for automatic photo import</li>
                <li><strong>Click Import</strong> and edit the listing details as needed</li>
            </ul>
        </div>
        
                 <div class="import-form">
             <form method="POST">
                 <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                 <div class="form-group">
                    <label for="ebay_input">eBay Item URL or ID</label>
                    <input 
                        type="text" 
                        id="ebay_input" 
                        name="ebay_input" 
                        placeholder="https://www.ebay.com/itm/123456789 or just 123456789"
                        required
                        value="<?php echo htmlspecialchars($_POST['ebay_input'] ?? ''); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input 
                            type="checkbox" 
                            id="import_images" 
                            name="import_images" 
                            checked
                        >
                        <label for="import_images">Import photos automatically</label>
                    </div>
                </div>
                
                <button type="submit" class="btn">Import Listing</button>
            </form>
        </div>
        
        <?php if ($importedProduct): ?>
            <div class="import-result">
                <h3>✅ Import Successful!</h3>
                <div class="product-preview">
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($importedProduct['title']); ?></h3>
                        <p>
                            <strong>Price:</strong> $<?php echo number_format($importedProduct['price'], 2); ?> | 
                            <strong>Condition:</strong> <?php echo htmlspecialchars($importedProduct['condition']); ?>
                            <?php if ($importedProduct['image_count'] > 0): ?>
                                | <strong>Images:</strong> <?php echo $importedProduct['image_count']; ?> imported
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="product-actions">
                        <a href="edit-product.php?id=<?php echo $importedProduct['id']; ?>" class="btn btn-sm">Edit Product</a>
                        <a href="admin-inventory.php" class="btn btn-sm">View Inventory</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 