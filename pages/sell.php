<?php
$pageTitle = "Sell Your Manga Sets";
$currentPage = "sell";
include dirname(__DIR__) . '/includes/mobile-nav-header.php';
require_once dirname(__DIR__) . '/includes/db.php';

function log_debug($msg) {
    $logfile = dirname(__DIR__) . '/logs/sell-debug.log';
    $line = date('Y-m-d H:i:s') . ' ' . $msg . "\n";
    file_put_contents($logfile, $line, FILE_APPEND);
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        log_debug('Form submitted: ' . json_encode($_POST));
        // Validate required fields
        $required = ['full_name', 'email'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                log_debug("Missing required field: $field");
                throw new Exception('Please fill in all required fields.');
            }
        }
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            log_debug('Invalid email: ' . $_POST['email']);
            throw new Exception('Please enter a valid email address.');
        }
        // Encrypt sensitive fields
        require_once dirname(__DIR__) . '/includes/database-encryption.php';
        $encryption = new DatabaseEncryption();
        $encrypted_data = $encryption->encryptFields([
            'full_name' => $_POST['full_name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'] ?? '',
            'description' => $_POST['description'] ?? ''
        ], ['full_name', 'email', 'phone', 'description']);
        // Handle multiple manga sets
        $sets = [];
        if (!empty($_POST['set_title']) && is_array($_POST['set_title'])) {
            foreach ($_POST['set_title'] as $i => $title) {
                $title = trim($title);
                $volumes = trim($_POST['set_volumes'][$i] ?? '');
                $condition = trim($_POST['set_condition'][$i] ?? '');
                $expected_price = trim($_POST['set_expected_price'][$i] ?? '');
                if ($title !== '') {
                    $sets[] = [
                        'title' => $title,
                        'volumes' => $volumes,
                        'condition' => $condition,
                        'expected_price' => $expected_price
                    ];
                }
            }
        }
        if (empty($sets)) {
            throw new Exception('Please enter at least one manga set.');
        }
        $item_details = json_encode($sets);
        // Handle multiple photo uploads
        $photo_json = null;
        $photos = [];
        if (!empty($_FILES['photos']['name'][0])) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 10 * 1024 * 1024;
            $upload_dir = dirname(__DIR__) . '/uploads/sell-submissions/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            foreach ($_FILES['photos']['name'] as $i => $name) {
                $file_type = $_FILES['photos']['type'][$i];
                $file_size = $_FILES['photos']['size'][$i];
                $tmp_name = $_FILES['photos']['tmp_name'][$i];
                if (!in_array($file_type, $allowed_types)) {
                    log_debug('Invalid photo type: ' . $file_type);
                    throw new Exception('Only JPG, PNG, GIF, and WebP images are allowed.');
                }
                if ($file_size > $max_size) {
                    log_debug('Photo too large: ' . $file_size);
                    throw new Exception('Each photo must be less than 10MB.');
                }
                $extension = pathinfo($name, PATHINFO_EXTENSION);
                $filename = bin2hex(random_bytes(16)) . '.' . $extension;
                $filepath = $upload_dir . $filename;
                if (!move_uploaded_file($tmp_name, $filepath)) {
                    log_debug('Failed to move uploaded file.');
                    throw new Exception('Failed to upload photo.');
                }
                $photos[] = ['filename' => $filename];
                log_debug('Photo uploaded: ' . $filename);
            }
            $photo_json = json_encode($photos);
        }
        if (empty($photos)) {
            throw new Exception('Please upload at least one photo of your collection.');
        }
        // Insert into database
        $stmt = $db->prepare("INSERT INTO sell_submissions (
            full_name, email, phone, overall_condition, description, photo_paths, item_details
        ) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $encrypted_data['full_name'],
            $encrypted_data['email'],
            $encrypted_data['phone'],
            $_POST['overall_condition'] ?? null,
            $encrypted_data['description'],
            $photo_json,
            $item_details
        ]);
        log_debug('Submission inserted successfully.');
        $message = 'Thank you for your submission! We will review it and contact you soon.';
        $_POST = [];
    } catch (Exception $e) {
        $error = $e->getMessage();
        log_debug('Error: ' . $error);
    }
}
?>
<?php if ($message): ?><div class="message success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="message error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<div class="container">
    <div class="header">
        <h1><i class="fa fa-book-open"></i> Sell Your Manga Sets</h1>
        <p>Turn your manga collection into cash! We buy complete sets in good condition.</p>
    </div>
    <div class="guarantee">
        <i class="fa fa-clock"></i> 24-Hour Quote Guarantee<br>
        Submit your collection today and receive a detailed quote within 24 hours!
    </div>
    <div class="info-box">
        <strong>What We're Looking For</strong>
        <ul>
            <li><b>Complete series:</b> We prefer full sets or substantial partial sets.</li>
            <li><b>Great condition:</b> Books should include dust jackets and be free of major damage.</li>
            <li><b>Popular series:</b> Naruto, One Piece, Attack on Titan, etc.</li>
            <li><b>English printings:</b> We currently only accept English manga.</li>
            <li><b>Competitive offers:</b> We pay top dollar for high quality series.</li>
        </ul>
    </div>
    <div class="payment-methods">
        <div class="payment-method"><i class="fab fa-cc-paypal"></i><br>PayPal</div>
        <div class="payment-method"><i class="fa fa-money-bill-wave"></i><br>Venmo</div>
        <div class="payment-method"><i class="fa fa-university"></i><br>Zelle</div>
    </div>
    <div class="note">
        <b>Note:</b> Your personal information is encrypted and stored securely. Payouts are covered for security and safety via PayPal, Venmo, Zelle, and major US banks only.
    </div>
    <form method="POST" enctype="multipart/form-data">
        <div class="section">
            <div class="section-title"><i class="fa fa-user"></i> Contact Information</div>
            <div class="form-row">
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone (recommended)</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="zip">ZIP Code</label>
                    <input type="text" id="zip" name="zip" placeholder="For shipping estimate" value="<?php echo htmlspecialchars($_POST['zip'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="description">Additional Description</label>
                <textarea id="description" name="description" placeholder="Describe your collection, any special editions, damage, etc."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>
        </div>
        <div class="section">
            <div class="section-title"><i class="fa fa-layer-group"></i> Your Manga Sets</div>
            <div id="sets-list"></div>
            <button id="add-set-btn" class="add-set-btn">+ Add Another Set</button>
        </div>
        <div class="section">
            <div class="section-title"><i class="fa fa-images"></i> Photos of Your Collection *</div>
            <div class="photo-upload-box" id="photo-upload-box">
                <label for="photos" class="photo-upload-label">
                    <i class="fa fa-upload"></i> Click to Upload Photos<br>
                    <span style="font-size:0.95em; color:#666;">Or drag and drop images here<br>JPG, PNG, GIF, WebP only, max 10MB each</span>
                </label>
                <input type="file" id="photos" name="photos[]" accept="image/*" multiple>
                <div class="photo-list" id="photo-list"></div>
            </div>
        </div>
        <button type="submit" class="submit-btn"><i class="fa fa-paper-plane"></i> Submit for Review</button>
    </form>
</div>
<?php include dirname(__DIR__) . '/includes/mobile-nav-footer.php'; ?>