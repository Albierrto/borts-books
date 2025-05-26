<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = "Sell Your Manga";
$currentPage = "sell";
require_once '../includes/db.php';

$successMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize all user inputs
    $full_name = trim(strip_tags($_POST['full_name'] ?? ''));
    $email = trim(strip_tags($_POST['email'] ?? ''));
    $phone = trim(strip_tags($_POST['phone'] ?? ''));
    $num_items = intval($_POST['num_items'] ?? 0);
    $overall_condition = trim(strip_tags($_POST['overall_condition'] ?? ''));
    // Item details as JSON
    $item_details = [];
    if (!empty($_POST['item_title'])) {
        $count = count($_POST['item_title']);
        for ($i = 0; $i < $count; $i++) {
            $title = trim(strip_tags($_POST['item_title'][$i]));
            $volume = trim(strip_tags($_POST['item_volume'][$i]));
            $condition = trim(strip_tags($_POST['item_condition'][$i]));
            $expected_price = trim(strip_tags($_POST['item_expected_price'][$i]));
            if ($title !== '' || $volume !== '' || $expected_price !== '') {
                $item_details[] = [
                    'title' => $title,
                    'volume' => $volume,
                    'condition' => $condition,
                    'expected_price' => $expected_price
                ];
            }
        }
    }
    $item_details_json = json_encode($item_details);
    // Handle photo uploads
    $photo_paths = [];
    if (!empty($_FILES['collection_photos']['name'][0])) {
        $upload_dir = __DIR__ . '/../uploads/sell-photos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true); // Use 0755 for security, not 0777
        }
        foreach ($_FILES['collection_photos']['tmp_name'] as $idx => $tmp_name) {
            if ($_FILES['collection_photos']['error'][$idx] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['collection_photos']['name'][$idx], PATHINFO_EXTENSION);
                $filename = uniqid('sell_', true) . '.' . $ext;
                $dest = $upload_dir . $filename;
                if (move_uploaded_file($tmp_name, $dest)) {
                    $photo_paths[] = 'uploads/sell-photos/' . $filename;
                }
            }
        }
    }
    $photo_paths_json = json_encode($photo_paths);
    // SQL injection protection: using prepared statements and sanitized inputs
    if (count($photo_paths) > 0) {
        $stmt = $db->prepare('INSERT INTO sell_submissions (full_name, email, phone, num_items, overall_condition, item_details, photo_paths) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$full_name, $email, $phone, $num_items, $overall_condition, $item_details_json, $photo_paths_json]);
        $successMsg = 'Thank you for your submission! We will review your collection and contact you soon.';
    } else {
        $successMsg = '<span style="color:#b71c1c;">You must upload at least one photo of your collection.</span>';
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
            <div class="search-cart">
                <a href="cart.php" title="Shopping Cart" class="cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count">0</span>
                </a>
            </div>
        </div>
    </header>

    <section class="page-header">
        <div class="container">
            <h1>Sell Your Manga Collection</h1>
            <p>Get the best value for your manga collection</p>
        </div>
    </section>

    <section class="container sell-container" style="max-width:600px;margin:2rem auto;background:#fff;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.08);padding:2.5rem 2rem;">
        <h1 style="font-size:2rem;font-weight:700;margin-bottom:0.5rem;">Sell Your Manga Collection</h1>
        <p style="color:#555;margin-bottom:1.2rem;">Get the best value for your manga collection</p>
        <div style="background:#e9f7ef;color:#1b5e20;padding:1rem 1.2rem;border-radius:6px;font-size:1.05rem;margin-bottom:1.2rem;font-weight:600;">
            <i class="fas fa-info-circle" style="margin-right:0.5rem;"></i>
            <span>We are currently only buying <b>English manga</b>. We pay <b>50-70% of recent eBay prices</b> for most collections.</span>
        </div>
        <div class="how-it-works" style="margin-bottom:2.5rem;">
            <h2 style="font-size:1.2rem;font-weight:700;margin-bottom:1rem;">How It Works</h2>
            <div style="display:flex;gap:1.5rem;flex-wrap:wrap;justify-content:space-between;">
                <div style="flex:1;min-width:120px;text-align:center;">
                    <div style="font-size:2rem;font-weight:800;color:#2a9d8f;margin-bottom:0.3rem;">1</div>
                    <div style="font-weight:600;">List Your Collection</div>
                    <div style="font-size:0.97rem;color:#666;">Fill out our simple form with details about your manga collection</div>
                </div>
                <div style="flex:1;min-width:120px;text-align:center;">
                    <div style="font-size:2rem;font-weight:800;color:#2a9d8f;margin-bottom:0.3rem;">2</div>
                    <div style="font-weight:600;">Get an Offer</div>
                    <div style="font-size:0.97rem;color:#666;">We'll review your collection and provide a competitive offer</div>
                </div>
                <div style="flex:1;min-width:120px;text-align:center;">
                    <div style="font-size:2rem;font-weight:800;color:#2a9d8f;margin-bottom:0.3rem;">3</div>
                    <div style="font-weight:600;">Ship for Free</div>
                    <div style="font-size:0.97rem;color:#666;">We'll provide a prepaid shipping label for your convenience</div>
                </div>
                <div style="flex:1;min-width:120px;text-align:center;">
                    <div style="font-size:2rem;font-weight:800;color:#2a9d8f;margin-bottom:0.3rem;">4</div>
                    <div style="font-weight:600;">Get Paid</div>
                    <div style="font-size:0.97rem;color:#666;">Receive payment via your preferred method once we receive your items</div>
                </div>
            </div>
        </div>
        <?php if ($successMsg): ?>
            <div style="background:#e9f7ef;color:#1b5e20;padding:1.2rem 1.5rem;border-radius:6px;font-size:1.1rem;margin-bottom:1.5rem;font-weight:600;text-align:center;"><i class="fas fa-check-circle" style="margin-right:0.5rem;"></i><?php echo $successMsg; ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" style="margin-bottom:0;">
            <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:1rem;">Collection Details</h3>
            <div class="form-group" style="margin-bottom:1.2rem;">
                <label>Approximate Number of Items</label>
                <input type="number" name="num_items" required style="width:100%;padding:0.7rem;border:1px solid #ddd;border-radius:4px;font-size:1rem;">
            </div>
            <div class="form-group" style="margin-bottom:2rem;">
                <label>Overall Condition</label>
                <select name="overall_condition" required style="width:100%;padding:0.7rem;border:1px solid #ddd;border-radius:4px;font-size:1rem;">
                    <option value="">Select condition</option>
                    <option value="New">New</option>
                    <option value="Like New">Like New</option>
                    <option value="Very Good">Very Good</option>
                    <option value="Good">Good</option>
                    <option value="Acceptable">Acceptable</option>
                </select>
            </div>
            <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:1rem;">Item Details <span style='font-weight:400;font-size:0.98rem;color:#888;'>(Optional)</span></h3>
            <div style="font-size:0.98rem;color:#888;margin-bottom:0.7rem;">You do <b>not</b> need to list every item. You can just upload photos below and we'll review your collection for you!</div>
            <div class="form-group" style="margin-bottom:1.2rem;display:flex;gap:0.7rem;flex-wrap:wrap;">
                <input type="text" name="item_title[]" placeholder="Title" style="flex:2 1 120px;padding:0.7rem;border:1px solid #ddd;border-radius:4px;font-size:1rem;">
                <input type="text" name="item_volume[]" placeholder="Volume" style="flex:1 1 80px;padding:0.7rem;border:1px solid #ddd;border-radius:4px;font-size:1rem;">
                <select name="item_condition[]" style="flex:1 1 120px;padding:0.7rem;border:1px solid #ddd;border-radius:4px;font-size:1rem;">
                    <option value="New">New</option>
                    <option value="Like New">Like New</option>
                    <option value="Very Good">Very Good</option>
                    <option value="Good">Good</option>
                    <option value="Acceptable">Acceptable</option>
                </select>
                <input type="number" name="item_expected_price[]" placeholder="Expected Price" style="flex:1 1 100px;padding:0.7rem;border:1px solid #ddd;border-radius:4px;font-size:1rem;">
            </div>
            <button type="button" onclick="addItemRow()" style="background:#e63946;color:#fff;border:none;border-radius:4px;padding:0.6rem 1.2rem;font-weight:600;font-size:1rem;margin-bottom:1.5rem;">Add Another Item</button>
            <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:1rem;">Contact Information</h3>
            <div class="form-group" style="margin-bottom:1.2rem;">
                <label>Full Name</label>
                <input type="text" name="full_name" required style="width:100%;padding:0.7rem;border:1px solid #ddd;border-radius:4px;font-size:1rem;">
            </div>
            <div class="form-group" style="margin-bottom:1.2rem;">
                <label>Email</label>
                <input type="email" name="email" required style="width:100%;padding:0.7rem;border:1px solid #ddd;border-radius:4px;font-size:1rem;">
            </div>
            <div class="form-group" style="margin-bottom:2rem;">
                <label>Phone Number</label>
                <input type="text" name="phone" required style="width:100%;padding:0.7rem;border:1px solid #ddd;border-radius:4px;font-size:1rem;">
            </div>
            <!-- Photo upload field -->
            <div class="form-group" style="margin-bottom:2rem;">
                <label>Upload Photos of Your Collection <span style='color:#b71c1c;font-weight:600;'>(Required)</span></label>
                <input type="file" name="collection_photos[]" multiple accept="image/*" required style="width:100%;padding:0.7rem;border:1px solid #ddd;border-radius:4px;font-size:1rem;" onchange="updatePhotoList()">
                <ul id="photo-list" style="list-style:none;padding:0;margin-top:0.7rem;"></ul>
            </div>
            <button type="submit" style="background:#2a9d8f;color:#fff;border:none;border-radius:4px;padding:0.8rem 2.2rem;font-weight:700;font-size:1.1rem;">Submit Collection</button>
        </form>
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
                    <li><a href="/pages/track-order.php">Track Order</a></li>
                    <li><a href="/pages/sell.php">Sell Manga</a></li>
                    <li><a href="/pages/about.php">About</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Help</h3>
                <ul>
                    <li><a href="/pages/faq.php">FAQ</a></li>
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
    <script>
    function addItemRow() {
        const form = document.querySelector('.sell-container form');
        const photoField = form.querySelector('input[type="file"]').parentElement;
        const itemRow = document.createElement('div');
        itemRow.className = 'form-group';
        itemRow.style = 'margin-bottom:1.2rem;display:flex;gap:0.7rem;flex-wrap:wrap;';
        itemRow.innerHTML = `
            <input type="text" name="item_title[]" placeholder="Title" style="flex:2 1 120px;padding:0.7rem;border:1px solid #ddd;border-radius:4px;font-size:1rem;">
            <input type="text" name="item_volume[]" placeholder="Volume" style="flex:1 1 80px;padding:0.7rem;border:1px solid #ddd;border-radius:4px;font-size:1rem;">
            <select name="item_condition[]" style="flex:1 1 120px;padding:0.7rem;border:1px solid #ddd;border-radius:4px;font-size:1rem;">
                <option value="New">New</option>
                <option value="Like New">Like New</option>
                <option value="Very Good">Very Good</option>
                <option value="Good">Good</option>
                <option value="Acceptable">Acceptable</option>
            </select>
            <input type="number" name="item_expected_price[]" placeholder="Expected Price" style="flex:1 1 100px;padding:0.7rem;border:1px solid #ddd;border-radius:4px;font-size:1rem;">
        `;
        form.insertBefore(itemRow, photoField);
    }

    // Show selected photo filenames and allow removal
    function updatePhotoList() {
        const input = document.querySelector('input[name="collection_photos[]"]');
        const list = document.getElementById('photo-list');
        list.innerHTML = '';
        Array.from(input.files).forEach((file, idx) => {
            const li = document.createElement('li');
            li.style = 'margin-bottom:0.3rem;display:flex;align-items:center;gap:0.5rem;';
            li.innerHTML = `<span>${file.name}</span> <button type="button" onclick="removePhoto(${idx})" style="background:#e63946;color:#fff;border:none;border-radius:3px;padding:0.2rem 0.7rem;font-size:0.95rem;">Remove</button>`;
            list.appendChild(li);
        });
    }
    function removePhoto(idx) {
        const input = document.querySelector('input[name="collection_photos[]"]');
        const dt = new DataTransfer();
        Array.from(input.files).forEach((file, i) => {
            if (i !== idx) dt.items.add(file);
        });
        input.files = dt.files;
        updatePhotoList();
    }
    // Initialize photo list on page load (in case of browser autofill)
    document.addEventListener('DOMContentLoaded', updatePhotoList);
    </script>
</body>
</html> 