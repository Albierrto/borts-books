<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = "Sell Your Manga Sets";
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
    
    // Set details as JSON (changed to sets instead of individual items)
    $set_details = [];
    if (!empty($_POST['set_title'])) {
        $count = count($_POST['set_title']);
        for ($i = 0; $i < $count; $i++) {
            $title = trim(strip_tags($_POST['set_title'][$i]));
            $volumes = trim(strip_tags($_POST['set_volumes'][$i]));
            $condition = trim(strip_tags($_POST['set_condition'][$i]));
            $expected_price = trim(strip_tags($_POST['set_expected_price'][$i]));
            if ($title !== '' || $volumes !== '' || $expected_price !== '') {
                $set_details[] = [
                    'title' => $title,
                    'volumes' => $volumes,
                    'condition' => $condition,
                    'expected_price' => $expected_price
                ];
            }
        }
    }
    $set_details_json = json_encode($set_details);
    
    // Handle photo uploads
    $photo_paths = [];
    if (!empty($_FILES['collection_photos']['name'][0])) {
        $upload_dir = __DIR__ . '/../uploads/sell-photos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
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
    
    // SQL injection protection: using prepared statements
    if (count($photo_paths) > 0) {
        $stmt = $db->prepare('INSERT INTO sell_submissions (full_name, email, phone, num_items, overall_condition, item_details, photo_paths) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$full_name, $email, $phone, $num_items, $overall_condition, $set_details_json, $photo_paths_json]);
        $successMsg = 'Thank you for your submission! We will review your manga sets and contact you soon.';
    } else {
        $successMsg = '<span style="color:#b71c1c;">You must upload at least one photo of your collection.</span>';
    }
}

// Initialize cart for header
session_start();
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cart_count = count($_SESSION['cart']);
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
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background: #f8f9fa;
        }
        
        .sell-container {
            max-width: 800px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 3rem 2.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .sell-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #eebbc3, #232946);
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #232946;
            margin-bottom: 0.5rem;
            text-align: center;
        }
        
        .page-subtitle {
            color: #666;
            font-size: 1.2rem;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .info-banner {
            background: linear-gradient(135deg, #e8f5e8, #d4f1d4);
            color: #1b5e20;
            padding: 1.5rem;
            border-radius: 12px;
            font-size: 1.1rem;
            margin-bottom: 2.5rem;
            font-weight: 600;
            text-align: center;
            border: 2px solid #c8e6c9;
        }
        
        .how-it-works {
            margin-bottom: 3rem;
        }
        
        .how-it-works h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            text-align: center;
            color: #232946;
        }
        
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .step {
            text-align: center;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 12px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .step:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .step-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #eebbc3;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .step-title {
            font-weight: 700;
            color: #232946;
            margin-bottom: 0.5rem;
        }
        
        .step-desc {
            font-size: 0.95rem;
            color: #666;
            line-height: 1.5;
        }
        
        .form-section {
            margin-bottom: 2.5rem;
        }
        
        .form-section h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #232946;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #232946;
            margin-bottom: 0.5rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            font-family: 'Inter', sans-serif;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #eebbc3;
            box-shadow: 0 0 0 3px rgba(238, 187, 195, 0.1);
        }
        
        .set-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .btn {
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .btn-primary {
            background: #232946;
            color: #fff;
            padding: 1rem 2rem;
        }
        
        .btn-primary:hover {
            background: #1a1f35;
        }
        
        .btn-secondary {
            background: #eebbc3;
            color: #232946;
            padding: 0.75rem 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .btn-secondary:hover {
            background: #e5a4b0;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: #fff;
            padding: 1.2rem 3rem;
            font-size: 1.1rem;
            font-weight: 800;
            border-radius: 12px;
            width: 100%;
            margin-top: 2rem;
        }
        
        .btn-submit:hover {
            background: linear-gradient(135deg, #218838, #1ba085);
        }
        
        .success-message {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            padding: 1.5rem;
            border-radius: 12px;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            font-weight: 600;
            text-align: center;
            border: 2px solid #b8dabc;
        }
        
        .optional-note {
            font-weight: 400;
            font-size: 0.9rem;
            color: #888;
        }
        
        .helper-text {
            font-size: 0.95rem;
            color: #666;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #eebbc3;
        }
        
        .photo-list {
            list-style: none;
            padding: 0;
            margin-top: 1rem;
        }
        
        .photo-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 0.5rem;
            border: 1px solid #e9ecef;
        }
        
        .photo-remove {
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 0.25rem 0.75rem;
            font-size: 0.85rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .photo-remove:hover {
            background: #c82333;
        }
        
        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .sell-container {
                margin: 1rem;
                padding: 2rem 1.5rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .steps-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .step {
                padding: 1rem;
            }
            
            .step-number {
                font-size: 2rem;
            }
            
            .set-row {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            
            .btn-submit {
                padding: 1rem 2rem;
                font-size: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .sell-container {
                padding: 1.5rem 1rem;
            }
            
            .page-title {
                font-size: 1.75rem;
            }
            
            .page-subtitle {
                font-size: 1rem;
            }
            
            .info-banner {
                padding: 1rem;
                font-size: 1rem;
            }
            
            .form-group input,
            .form-group select {
                padding: 0.75rem;
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
            <div class="search-cart">
                <a href="cart.php" title="Shopping Cart" class="cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </a>
            </div>
        </div>
    </header>

    <section class="sell-container">
        <h1 class="page-title">Sell Your Manga Sets</h1>
        <p class="page-subtitle">Turn your manga collection into cash</p>
        
        <div class="info-banner">
            <i class="fas fa-star" style="margin-right:0.5rem;"></i>
            <span>We specialize in <strong>complete manga sets</strong> and pay <strong>50-70% of current market value</strong> for quality collections!</span>
        </div>
        
        <div class="how-it-works">
            <h2>How It Works</h2>
            <div class="steps-grid">
                <div class="step">
                    <span class="step-number">1</span>
                    <div class="step-title">List Your Sets</div>
                    <div class="step-desc">Tell us about your complete manga sets and upload photos</div>
                </div>
                <div class="step">
                    <span class="step-number">2</span>
                    <div class="step-title">Get an Offer</div>
                    <div class="step-desc">We'll evaluate your sets and provide a competitive cash offer</div>
                </div>
                <div class="step">
                    <span class="step-number">3</span>
                    <div class="step-title">Ship for Free</div>
                    <div class="step-desc">We provide prepaid shipping labels for accepted offers</div>
                </div>
                <div class="step">
                    <span class="step-number">4</span>
                    <div class="step-title">Get Paid Fast</div>
                    <div class="step-desc">Receive payment within 24 hours of delivery confirmation</div>
                </div>
            </div>
        </div>
        
        <?php if ($successMsg): ?>
            <div class="success-message">
                <i class="fas fa-check-circle" style="margin-right:0.5rem;"></i>
                <?php echo $successMsg; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <!-- Collection Overview -->
            <div class="form-section">
                <h3><i class="fas fa-books"></i> Collection Overview</h3>
                
                <div class="form-group">
                    <label>Approximate Number of Complete Sets</label>
                    <input type="number" name="num_items" required min="1" placeholder="e.g., 5 complete sets">
                </div>
                
                <div class="form-group">
                    <label>Overall Condition of Sets</label>
                    <select name="overall_condition" required>
                        <option value="">Select overall condition</option>
                        <option value="New">New/Sealed</option>
                        <option value="Like New">Like New (minimal wear)</option>
                        <option value="Very Good">Very Good (light wear)</option>
                        <option value="Good">Good (moderate wear)</option>
                        <option value="Acceptable">Acceptable (heavy wear)</option>
                    </select>
                </div>
            </div>

            <!-- Set Details -->
            <div class="form-section">
                <h3><i class="fas fa-list"></i> Manga Set Details <span class="optional-note">(Optional but helps with pricing)</span></h3>
                
                <div class="helper-text">
                    <strong>Complete sets get the best prices!</strong> List any complete manga series you have. Don't worry about listing every volume - just the main sets you want to sell.
                </div>
                
                <div id="sets-container">
                    <div class="set-row">
                        <input type="text" name="set_title[]" placeholder="Manga Series Title (e.g., Naruto, One Piece)">
                        <input type="text" name="set_volumes[]" placeholder="Volumes (e.g., 1-72, Complete)">
                        <select name="set_condition[]">
                            <option value="New">New</option>
                            <option value="Like New">Like New</option>
                            <option value="Very Good">Very Good</option>
                            <option value="Good">Good</option>
                            <option value="Acceptable">Acceptable</option>
                        </select>
                        <input type="number" name="set_expected_price[]" placeholder="Expected $" min="0" step="0.01">
                    </div>
                </div>
                
                <button type="button" onclick="addSetRow()" class="btn btn-secondary">
                    <i class="fas fa-plus"></i>
                    Add Another Set
                </button>
            </div>

            <!-- Contact Information -->
            <div class="form-section">
                <h3><i class="fas fa-user"></i> Contact Information</h3>
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required placeholder="Your full name">
                </div>
                
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="your.email@example.com">
                </div>
                
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" required placeholder="(555) 123-4567">
                </div>
            </div>

            <!-- Photo Upload -->
            <div class="form-section">
                <h3><i class="fas fa-camera"></i> Photos of Your Collection <span style="color:#dc3545;">*Required</span></h3>
                
                <div class="helper-text">
                    Please upload clear photos showing your complete sets. Include spines showing volume numbers and any special editions or box sets.
                </div>
                
                <div class="form-group">
                    <input type="file" name="collection_photos[]" multiple accept="image/*" required onchange="updatePhotoList()">
                    <ul id="photo-list" class="photo-list"></ul>
                </div>
            </div>

            <button type="submit" class="btn btn-submit">
                <i class="fas fa-paper-plane"></i>
                Submit My Collection for Review
            </button>
        </form>
    </section>

    <?php include '../includes/footer.php'; ?>

    <script>
        function addSetRow() {
            const container = document.getElementById('sets-container');
            const newRow = document.createElement('div');
            newRow.className = 'set-row';
            newRow.innerHTML = `
                <input type="text" name="set_title[]" placeholder="Manga Series Title (e.g., Attack on Titan)">
                <input type="text" name="set_volumes[]" placeholder="Volumes (e.g., 1-34, Complete)">
                <select name="set_condition[]">
                    <option value="New">New</option>
                    <option value="Like New">Like New</option>
                    <option value="Very Good">Very Good</option>
                    <option value="Good">Good</option>
                    <option value="Acceptable">Acceptable</option>
                </select>
                <input type="number" name="set_expected_price[]" placeholder="Expected $" min="0" step="0.01">
            `;
            container.appendChild(newRow);
        }

        function updatePhotoList() {
            const input = document.querySelector('input[name="collection_photos[]"]');
            const list = document.getElementById('photo-list');
            list.innerHTML = '';
            
            Array.from(input.files).forEach((file, idx) => {
                const li = document.createElement('li');
                li.className = 'photo-item';
                li.innerHTML = `
                    <span><i class="fas fa-image" style="margin-right:0.5rem;color:#666;"></i>${file.name}</span>
                    <button type="button" onclick="removePhoto(${idx})" class="photo-remove">Remove</button>
                `;
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

        // Initialize photo list on page load
        document.addEventListener('DOMContentLoaded', updatePhotoList);
    </script>
</body>
</html> 