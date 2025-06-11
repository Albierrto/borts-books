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
<div class="container">
    <div id="loading-overlay" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(255,255,255,0.7);z-index:9999;align-items:center;justify-content:center;flex-direction:column;">
        <div style="border:6px solid #eee;border-top:6px solid #7c3aed;border-radius:50%;width:48px;height:48px;animation:spin 1s linear infinite;"></div>
        <div style="margin-top:1rem;font-weight:600;color:#7c3aed;">Submitting...</div>
    </div>
    <style>
    body { background: #f3f4f6; font-family: 'Segoe UI', Arial, sans-serif; }
    .container { max-width: 700px; margin: 2rem auto; background: #fff; border-radius: 14px; box-shadow: 0 2px 16px rgba(37,99,235,0.07); padding: 2rem; }
    .header { border-bottom: 2px solid #e0e7ef; margin-bottom: 1.5rem; padding-bottom: 1rem; }
    .header h1 { color: #1d4ed8; font-size: 2rem; margin-bottom: 0.5rem; }
    .guarantee, .note { background: #f1f5fd; color: #2563eb; border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1rem; font-size: 1rem; }
    .info-box { background: #f9fafb; border-left: 4px solid #2563eb; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
    .payment-methods { display: flex; gap: 2rem; margin-bottom: 1rem; }
    .payment-method { text-align: center; font-size: 1rem; color: #374151; }
    .section { background: #f9fafb; border-radius: 8px; padding: 1.2rem 1rem; margin-bottom: 1.5rem; }
    .section-title { font-weight: 700; color: #2563eb; margin-bottom: 0.7rem; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5em; }
    .form-row { display: flex; gap: 1rem; }
    .form-group { flex: 1; display: flex; flex-direction: column; margin-bottom: 1rem; }
    .form-group label { font-weight: 600; margin-bottom: 0.3rem; }
    input[type="text"], input[type="email"], textarea { border: 1px solid #d1d5db; border-radius: 6px; padding: 0.6em; font-size: 1em; }
    input[type="text"]:focus, input[type="email"]:focus, textarea:focus { outline: 2px solid #2563eb; border-color: #2563eb; }
    textarea { min-height: 60px; resize: vertical; }
    .add-set-btn { background: #e0e7ef; color: #2563eb; border: none; border-radius: 6px; padding: 0.5em 1em; font-weight: 600; cursor: pointer; margin-top: 0.5em; }
    .add-set-btn:hover { background: #2563eb; color: #fff; }
    #sets-list .manga-set { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1em; margin-bottom: 0.7em; display: flex; gap: 1em; align-items: flex-end; flex-wrap: wrap; }
    #sets-list .manga-set input { margin-bottom: 0; }
    .remove-set-btn { background: #fee2e2; color: #dc2626; border: none; border-radius: 6px; padding: 0.3em 0.7em; font-weight: 600; cursor: pointer; margin-left: 0.5em; }
    .remove-set-btn:hover { background: #dc2626; color: #fff; }
    .photo-upload-box { border: 2px dashed #2563eb; border-radius: 8px; padding: 1.5em; text-align: center; background: #f1f5fd; margin-bottom: 1em; position: relative; }
    .photo-upload-label { cursor: pointer; display: block; font-weight: 600; color: #2563eb; }
    input[type="file"] { display: none; }
    .photo-list { display: flex; flex-wrap: wrap; gap: 0.7em; margin-top: 0.7em; }
    .photo-thumb { width: 70px; height: 70px; object-fit: cover; border-radius: 6px; border: 1px solid #e5e7eb; }
    .submit-btn { background: #2563eb; color: #fff; border: none; border-radius: 8px; padding: 0.8em 2em; font-size: 1.1em; font-weight: 700; cursor: pointer; margin-top: 1em; transition: background 0.2s; }
    .submit-btn:hover { background: #1d4ed8; }
    .message.success { background: #dcfce7; color: #166534; border-radius: 8px; padding: 1em; margin-bottom: 1em; }
    .message.error { background: #fee2e2; color: #991b1b; border-radius: 8px; padding: 1em; margin-bottom: 1em; }
    @media (max-width: 700px) { .container { padding: 1rem; } .form-row { flex-direction: column; gap: 0; } .payment-methods { flex-direction: column; gap: 0.5rem; } }
    </style>
    <?php if ($message): ?><div class="message success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
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
<script>
// Dynamic manga set entry
function renderSets() {
    const setsList = document.getElementById('sets-list');
    setsList.innerHTML = '';
    window.mangaSets.forEach((set, idx) => {
        const div = document.createElement('div');
        div.className = 'manga-set';
        div.innerHTML = `
            <input type="text" name="set_title[]" placeholder="Series Title" required value="${set.title || ''}" style="flex:2;">
            <input type="text" name="set_volumes[]" placeholder="Volumes (e.g. 1-12)" required value="${set.volumes || ''}" style="flex:1;">
            <input type="text" name="set_condition[]" placeholder="Condition" required value="${set.condition || ''}" style="flex:1;">
            <input type="text" name="set_expected_price[]" placeholder="Asking $ (optional)" value="${set.expected_price || ''}" style="flex:1;">
            <button type="button" class="remove-set-btn" onclick="removeSet(${idx})">&times;</button>
        `;
        setsList.appendChild(div);
    });
}
window.mangaSets = window.mangaSets || [{title:'',volumes:'',condition:'',expected_price:''}];
function addSet(e) {
    e.preventDefault();
    window.mangaSets.push({title:'',volumes:'',condition:'',expected_price:''});
    renderSets();
}
function removeSet(idx) {
    window.mangaSets.splice(idx,1);
    if(window.mangaSets.length===0) window.mangaSets.push({title:'',volumes:'',condition:'',expected_price:''});
    renderSets();
}
document.getElementById('add-set-btn').addEventListener('click', addSet);
document.addEventListener('DOMContentLoaded', renderSets);
// Drag-and-drop photo upload
const photoBox = document.getElementById('photo-upload-box');
const photoInput = document.getElementById('photos');
const photoList = document.getElementById('photo-list');
photoBox.addEventListener('dragover', e => { e.preventDefault(); photoBox.style.background='#e0e7ef'; });
photoBox.addEventListener('dragleave', e => { e.preventDefault(); photoBox.style.background=''; });
photoBox.addEventListener('drop', e => {
    e.preventDefault();
    photoBox.style.background='';
    const files = Array.from(e.dataTransfer.files).filter(f=>f.type.startsWith('image/'));
    if(files.length) {
        photoInput.files = new DataTransfer();
        files.forEach(f=>photoInput.files.items.add(f));
        updatePhotoList();
    }
});
photoInput.addEventListener('change', updatePhotoList);
function updatePhotoList() {
    photoList.innerHTML = '';
    Array.from(photoInput.files).forEach(f => {
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'photo-thumb';
            photoList.appendChild(img);
        };
        reader.readAsDataURL(f);
    });
}
// Loading overlay on submit
    document.querySelector('form').addEventListener('submit', function() {
        document.getElementById('loading-overlay').style.display = 'flex';
    });
</script>
<?php include dirname(__DIR__) . '/includes/mobile-nav-footer.php'; ?>