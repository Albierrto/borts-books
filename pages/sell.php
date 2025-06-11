<?php
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
            $_POST['full_name'],
            $_POST['email'],
            $_POST['phone'] ?? null,
            $_POST['overall_condition'] ?? null,
            $_POST['description'] ?? null,
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell Your Manga Sets</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f3f4f6; font-family: 'Segoe UI', Arial, sans-serif; color: #222; }
        .container { max-width: 650px; margin: 2rem auto; background: #fff; border-radius: 16px; box-shadow: 0 2px 16px #0002; padding: 0 0 2rem 0; }
        .header { background: #7c3aed; color: #fff; border-radius: 16px 16px 0 0; padding: 2rem 2rem 1rem 2rem; text-align: center; }
        .header h1 { margin: 0; font-size: 2.2rem; font-weight: 700; }
        .header p { margin: 0.5rem 0 0 0; font-size: 1.1rem; }
        .guarantee { background: #22c55e; color: #fff; font-weight: 600; text-align: center; padding: 0.7rem 1rem; border-radius: 0 0 0 0; margin-bottom: 1.2rem; font-size: 1.1rem; }
        .info-box { background: #f1f5f9; border-left: 5px solid #7c3aed; padding: 1rem 1.5rem; margin: 1.2rem 2rem; border-radius: 8px; }
        .info-box ul { margin: 0.5rem 0 0 1.2rem; }
        .payment-methods { display: flex; justify-content: center; gap: 2rem; margin: 1.2rem 0; }
        .payment-method { background: #f3f4f6; border-radius: 8px; padding: 1rem 1.5rem; text-align: center; box-shadow: 0 1px 4px #0001; font-size: 1.1rem; }
        .payment-method i { font-size: 2rem; margin-bottom: 0.3rem; color: #7c3aed; }
        .note { background: #e0f2fe; color: #0369a1; border-left: 5px solid #22c55e; padding: 0.7rem 1.2rem; margin: 1.2rem 2rem; border-radius: 8px; font-size: 0.98rem; }
        .section { background: #f9fafb; border-radius: 12px; margin: 1.5rem 2rem; padding: 1.5rem 1.5rem 1rem 1.5rem; box-shadow: 0 1px 4px #0001; }
        .section-title { font-size: 1.2rem; font-weight: 600; color: #7c3aed; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .form-row { display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .form-group { flex: 1 1 180px; min-width: 140px; display: flex; flex-direction: column; }
        label { font-weight: 600; margin-bottom: 0.3rem; }
        input, select, textarea { padding: 0.7rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem; }
        textarea { min-height: 60px; }
        .add-set-btn { background: #7c3aed; color: #fff; border: none; border-radius: 6px; padding: 0.6rem 1.5rem; font-size: 1rem; margin-top: 0.5rem; cursor: pointer; }
        .remove-set-btn { background: #dc2626; color: #fff; border: none; border-radius: 6px; padding: 0.4rem 1rem; font-size: 0.95rem; margin-left: 0.5rem; cursor: pointer; }
        .photo-upload-box { border: 2px dashed #7c3aed; background: #f3f4f6; border-radius: 10px; padding: 2rem; text-align: center; margin-bottom: 1rem; transition: border-color 0.2s; }
        .photo-upload-box.dragover { border-color: #22c55e; }
        .photo-upload-box input[type="file"] { display: none; }
        .photo-upload-label { display: block; color: #7c3aed; font-weight: 600; cursor: pointer; }
        .photo-list { margin-top: 0.7rem; font-size: 0.98rem; color: #444; }
        .submit-btn { background: #2563eb; color: #fff; border: none; border-radius: 8px; padding: 1rem 2.5rem; font-size: 1.1rem; font-weight: 600; margin: 1.5rem auto 0 auto; display: block; cursor: pointer; box-shadow: 0 2px 8px #0001; transition: background 0.2s; }
        .submit-btn:hover { background: #1d4ed8; }
        .message { padding: 1rem; border-radius: 8px; margin: 1.5rem 2rem 0.5rem 2rem; text-align: center; font-size: 1.1rem; }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
        @media (max-width: 700px) {
            .container { max-width: 98vw; margin: 0.5rem; padding: 0; }
            .header, .guarantee, .info-box, .note, .section { margin: 0.5rem; }
            .form-row { flex-direction: column; gap: 0.5rem; }
        }
    </style>
    <script>
        // Dynamic manga set fields
        function addSetRow(title = '', volumes = '', condition = '', price = '') {
            const setsDiv = document.getElementById('sets-list');
            const div = document.createElement('div');
            div.className = 'form-row';
            div.innerHTML = `
                <div class="form-group">
                    <label>Series Title *</label>
                    <input type="text" name="set_title[]" placeholder="e.g. Naruto, One Piece" value="${title.replace(/"/g, '&quot;')}">
                </div>
                <div class="form-group">
                    <label>Volumes *</label>
                    <input type="text" name="set_volumes[]" placeholder="e.g. 1-12, 1-5, 5-20" value="${volumes.replace(/"/g, '&quot;')}">
                </div>
                <div class="form-group">
                    <label>Condition</label>
                    <select name="set_condition[]">
                        <option value="">Select...</option>
                        <option value="new" ${condition==='new'?'selected':''}>New</option>
                        <option value="like_new" ${condition==='like_new'?'selected':''}>Like New</option>
                        <option value="very_good" ${condition==='very_good'?'selected':''}>Very Good</option>
                        <option value="good" ${condition==='good'?'selected':''}>Good</option>
                        <option value="fair" ${condition==='fair'?'selected':''}>Fair</option>
                        <option value="poor" ${condition==='poor'?'selected':''}>Poor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Asking Price</label>
                    <input type="text" name="set_expected_price[]" placeholder="Optional - your asking price" value="${price.replace(/"/g, '&quot;')}">
                </div>
                <button type="button" class="remove-set-btn" onclick="this.parentNode.remove()">Remove</button>
            `;
            setsDiv.appendChild(div);
        }
        window.onload = function() {
            // Manga sets
            const sets = <?php echo json_encode($_POST['set_title'] ?? ['']); ?>;
            const vols = <?php echo json_encode($_POST['set_volumes'] ?? ['']); ?>;
            const conds = <?php echo json_encode($_POST['set_condition'] ?? ['']); ?>;
            const prices = <?php echo json_encode($_POST['set_expected_price'] ?? ['']); ?>;
            if (sets.length === 0) addSetRow();
            else for (let i = 0; i < sets.length; i++) addSetRow(sets[i], vols[i]||'', conds[i]||'', prices[i]||'');
            document.getElementById('add-set-btn').onclick = function(e) {
                e.preventDefault();
                addSetRow();
            };
            // Photo upload drag-and-drop
            const box = document.getElementById('photo-upload-box');
            const input = document.getElementById('photos');
            box.addEventListener('dragover', function(e) { e.preventDefault(); box.classList.add('dragover'); });
            box.addEventListener('dragleave', function(e) { e.preventDefault(); box.classList.remove('dragover'); });
            box.addEventListener('drop', function(e) {
                e.preventDefault(); box.classList.remove('dragover');
                input.files = e.dataTransfer.files;
                updatePhotoList();
            });
            input.addEventListener('change', updatePhotoList);
            function updatePhotoList() {
                const list = document.getElementById('photo-list');
                list.innerHTML = '';
                for (let i = 0; i < input.files.length; i++) {
                    list.innerHTML += `<div>${input.files[i].name}</div>`;
                }
            }
        };
    </script>
</head>
<body>
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
    <?php if ($message): ?><div class="message success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
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
</body>
</html>