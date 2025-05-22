<?php
session_start();
require_once '../includes/db.php';

// Function to make eBay Finding API request by seller username
function makeEbayFindingRequestBySeller($username, $app_id) {
    $endpoint = 'https://svcs.ebay.com/services/search/FindingService/v1';
    $params = [
        'OPERATION-NAME' => 'findItemsAdvanced',
        'SERVICE-VERSION' => '1.13.0',
        'SECURITY-APPNAME' => $app_id,
        'RESPONSE-DATA-FORMAT' => 'JSON',
        'paginationInput.entriesPerPage' => 100,
        'itemFilter(0).name' => 'Seller',
        'itemFilter(0).value' => $username
    ];
    $url = $endpoint . '?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// Function to import a single listing
function importListing($item, $db) {
    $title = $item['title'][0] ?? '';
    $description = $item['subtitle'][0] ?? '';
    $price = $item['sellingStatus'][0]['currentPrice'][0]['__value__'] ?? 0;
    $condition = $item['condition'][0]['conditionDisplayName'][0] ?? '';
    $images = [$item['galleryURL'][0] ?? ''];

    $stmt = $db->prepare("INSERT INTO products (title, description, price, `condition`, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$title, $description, $price, $condition]);
    $productId = $db->lastInsertId();

    foreach ($images as $image) {
        if ($image) {
            $stmt = $db->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
            $stmt->execute([$productId, $image]);
        }
    }
    return $productId;
}

// CSV import block FIRST
if (isset($_POST['import_csv']) && isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
    $csvFile = $_FILES['csv_file']['tmp_name'];
    $importedCount = 0;
    $errors = [];
    if (($handle = fopen($csvFile, 'r')) !== false) {
        $header = fgetcsv($handle); // Read header row
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            $title = $data['Title'] ?? '';
            $description = isset($data['Description']) ? $data['Description'] : '';
            $rawPrice = $data['Start price'] ?? '';
            $numericPrice = floatval(preg_replace('/[^0-9.]/', '', $rawPrice));
            $price = $numericPrice > 0 ? number_format(round($numericPrice * 0.9, 2), 2, '.', '') : '0.00';
            $condition = $data['Condition'] ?? '';
            $image_url = ''; // No image data in CSV
            try {
                $stmt = $db->prepare("INSERT INTO products (title, description, price, `condition`, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$title, $description, $price, $condition]);
                $productId = $db->lastInsertId();
                // No image insert
                $importedCount++;
            } catch (Exception $e) {
                $errors[] = "Failed to import listing: " . $e->getMessage();
            }
        }
        fclose($handle);
    } else {
        $errors[] = "Failed to open uploaded CSV file.";
    }
    $_SESSION['import_result'] = [
        'success' => count($errors) === 0,
        'imported' => $importedCount,
        'errors' => $errors
    ];
    // Debug: If there are errors, output them before redirecting
    if (count($errors) > 0) {
        echo '<h2>CSV Import Debug Output</h2>';
        echo '<pre>' . print_r($errors, true) . '</pre>';
        exit;
    }
    header('Location: ebay-import.php');
    exit;
}

// eBay API import block SECOND
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Accept either a username or a store URL, but prefer username
    $seller_username = '';
    if (!empty($_POST['ebay_store_url'])) {
        // Try to extract username from /usr/ URL
        $matches = [];
        if (preg_match('/ebay.com\/usr\/([^\/]+)/', $_POST['ebay_store_url'], $matches)) {
            $seller_username = $matches[1];
        } else {
            // fallback: treat input as username directly
            $seller_username = trim($_POST['ebay_store_url']);
        }
    }
    $app_id = $_POST['ebay_app_id'];

    try {
        $response = makeEbayFindingRequestBySeller($seller_username, $app_id);
        file_put_contents(__DIR__ . '/ebay_api_debug.json', json_encode($response, JSON_PRETTY_PRINT));
        $items = $response['findItemsAdvancedResponse'][0]['searchResult'][0]['item'] ?? [];

        if ($items) {
            $importedCount = 0;
            $errors = [];
            foreach ($items as $item) {
                try {
                    importListing($item, $db);
                    $importedCount++;
                } catch (Exception $e) {
                    $errors[] = "Failed to import listing: " . $e->getMessage();
                }
            }
            $_SESSION['import_result'] = [
                'success' => true,
                'imported' => $importedCount,
                'errors' => $errors
            ];
        } else {
            throw new Exception("No listings found or invalid response from eBay Finding API for this seller");
        }
    } catch (Exception $e) {
        $_SESSION['import_result'] = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
    header('Location: ebay-import.php');
    exit;
} 