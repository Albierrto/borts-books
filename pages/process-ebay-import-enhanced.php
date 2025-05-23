<?php
session_start();
require_once '../includes/db.php';

// Function to fetch images from eBay listing with enhanced anti-detection
function fetchEbayImages($ebayItemId) {
    $images = [];
    
    // Construct eBay URL
    $ebayUrl = "https://www.ebay.com/itm/" . $ebayItemId;
    
    // Initialize cURL with enhanced settings
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $ebayUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // Enhanced user agent rotation
    $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.107 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:90.0) Gecko/20100101 Firefox/90.0'
    ];
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgents[array_rand($userAgents)]);
    
    // Add random delay to mimic human behavior
    usleep(rand(1000000, 3000000)); // 1-3 second delay
    
    // Set additional headers to appear more like a real browser
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
        'Cache-Control: max-age=0'
    ]);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $html) {
        // Create DOM parser
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        
        // Method 1: Try to find images in the main image viewer
        $imageNodes = $xpath->query('//div[contains(@class, "ux-image-carousel-item")]//img[@data-src]');
        foreach ($imageNodes as $img) {
            $src = $img->getAttribute('data-src');
            if ($src && strpos($src, 'ebayimg.com') !== false) {
                $highRes = str_replace(['/s-l64.', '/s-l140.', '/s-l225.', '/s-l500.'], '/s-l1600.', $src);
                $images[] = $highRes;
            }
        }
        
        // Method 2: Try alternative image container
        if (empty($images)) {
            $imageNodes = $xpath->query('//img[contains(@src, "ebayimg.com")]');
            foreach ($imageNodes as $img) {
                $src = $img->getAttribute('src');
                if ($src && strpos($src, 'ebayimg.com') !== false && strpos($src, 's-l') !== false) {
                    $highRes = preg_replace('/s-l\d+\./', 's-l1600.', $src);
                    if (!in_array($highRes, $images)) {
                        $images[] = $highRes;
                    }
                }
            }
        }
        
        // Method 3: Look for JavaScript variable containing images
        if (empty($images)) {
            preg_match_all('/"imageUrl":"([^"]+)"/', $html, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $url) {
                    $url = str_replace('\/', '/', $url);
                    if (strpos($url, 'ebayimg.com') !== false) {
                        $highRes = preg_replace('/s-l\d+\./', 's-l1600.', $url);
                        if (!in_array($highRes, $images)) {
                            $images[] = $highRes;
                        }
                    }
                }
            }
        }
        
        // Method 4: Look for image URLs in JSON data
        if (empty($images)) {
            preg_match_all('/"imageUrl":"([^"]+)"/', $html, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $url) {
                    $url = str_replace('\/', '/', $url);
                    if (strpos($url, 'ebayimg.com') !== false) {
                        $highRes = preg_replace('/s-l\d+\./', 's-l1600.', $url);
                        if (!in_array($highRes, $images)) {
                            $images[] = $highRes;
                        }
                    }
                }
            }
        }
    }
    
    return array_unique($images);
}

// Function to download and save image with retry mechanism
function downloadImage($url, $productId, $imageIndex) {
    $uploadDir = '../uploads/products/' . $productId;
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Get file extension
    $extension = 'jpg'; // Default
    if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $url, $matches)) {
        $extension = strtolower($matches[1]);
    }
    
    $filename = 'ebay_' . $productId . '_' . $imageIndex . '.' . $extension;
    $filepath = $uploadDir . '/' . $filename;
    
    // Download image with retry mechanism
    $maxRetries = 3;
    $retryCount = 0;
    
    while ($retryCount < $maxRetries) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 && $imageData) {
            file_put_contents($filepath, $imageData);
            return $filepath;
        }
        
        $retryCount++;
        if ($retryCount < $maxRetries) {
            // Exponential backoff
            sleep(pow(2, $retryCount));
        }
    }
    
    return false;
}

// Enhanced CSV import with image fetching
if (isset($_POST['import_csv']) && isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
    $csvFile = $_FILES['csv_file']['tmp_name'];
    $importedCount = 0;
    $errors = [];
    $debugRows = [];
    $fetchImages = isset($_POST['fetch_images']) && $_POST['fetch_images'] == '1';
    
    if (($handle = fopen($csvFile, 'r')) !== false) {
        $header = fgetcsv($handle); // Read header row
        $ebayItemIndex = array_search('Item number', $header);
        if ($ebayItemIndex === false) {
            $ebayItemIndex = array_search('eBay Item ID', $header);
        }
        while (($row = fgetcsv($handle)) !== false) {
            $rowDebug = [
                'row' => $row,
                'images' => [],
                'error' => null
            ];
            try {
                $data = array_combine($header, $row);
                $title = $data['Title'] ?? '';
                $description = $data['Description'] ?? $data['Variation details'] ?? '';
                $rawPrice = $data['Start price'] ?? $data['Current price'] ?? '';
                $numericPrice = floatval(preg_replace('/[^0-9.]/', '', $rawPrice));
                $price = $numericPrice > 0 ? number_format($numericPrice * 0.9, 2, '.', '') : '0.00';
                $condition = $data['Condition'] ?? '';
                $ebayItemId = $ebayItemIndex !== false ? $row[$ebayItemIndex] : ($data['Item number'] ?? '');
                $stmt = $db->prepare("INSERT INTO products (title, description, price, `condition`, ebay_item_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$title, $description, $price, $condition, $ebayItemId]);
                $productId = $db->lastInsertId();
                if ($fetchImages && !empty($ebayItemId) && is_numeric($ebayItemId)) {
                    $images = fetchEbayImages($ebayItemId);
                    $rowDebug['images'] = $images;
                    if (!empty($images)) {
                        $imageIndex = 1;
                        foreach ($images as $imageUrl) {
                            $savedPath = downloadImage($imageUrl, $productId, $imageIndex);
                            if ($savedPath) {
                                $stmt = $db->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)");
                                $stmt->execute([$productId, $savedPath, $imageIndex == 1 ? 1 : 0]);
                                $imageIndex++;
                            }
                            if ($imageIndex > 24) break;
                        }
                    }
                }
                $importedCount++;
            } catch (Exception $e) {
                $rowDebug['error'] = $e->getMessage();
                $errors[] = "Failed to import listing: " . $e->getMessage();
            }
            $debugRows[] = $rowDebug;
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
    // Store debug info in session for display after redirect
    $_SESSION['import_debug'] = [
        'imported' => $importedCount,
        'errors' => $errors,
        'rows' => $debugRows
    ];
    header('Location: ebay-import.php');
    exit;
} 