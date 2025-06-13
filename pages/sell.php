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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell Your Manga Sets - Bort's Books</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    /* Base Styles */
    body { 
        background: #f3f4f6; 
        font-family: 'Segoe UI', Arial, sans-serif;
        line-height: 1.5;
    }
    .container { 
        max-width: 800px; 
        margin: 2rem auto; 
        background: #fff; 
        border-radius: 14px; 
        box-shadow: 0 2px 16px rgba(37,99,235,0.07); 
        padding: 2rem;
    }

    /* Header Styles */
    .header { 
        border-bottom: 2px solid #e0e7ef; 
        margin-bottom: 1.5rem; 
        padding-bottom: 1rem; 
    }
    .header h1 { 
        color: #1d4ed8; 
        font-size: 2rem; 
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Info Boxes */
    .guarantee, .note { 
        background: #f1f5fd; 
        color: #2563eb; 
        border-radius: 8px; 
        padding: 1rem 1.2rem; 
        margin-bottom: 1.5rem; 
        font-size: 1rem;
        line-height: 1.6;
    }
    .info-box { 
        background: #f9fafb; 
        border-left: 4px solid #2563eb; 
        border-radius: 8px; 
        padding: 1.2rem; 
        margin-bottom: 1.5rem;
    }
    .info-box ul {
        margin: 0.5rem 0;
        padding-left: 1.2rem;
    }
    .info-box li {
        margin: 0.4rem 0;
    }

    /* Payment Methods */
    .payment-methods { 
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
        margin: 2rem auto;
        max-width: 600px;
        padding: 0 1rem;
    }
    .payment-method { 
        text-align: center; 
        font-size: 1.1rem; 
        color: #374151;
        padding: 1.5rem;
        background: #f9fafb;
        border-radius: 8px;
        transition: all 0.2s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .payment-method:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .payment-method i {
        font-size: 2rem;
        margin-bottom: 0.75rem;
        color: #2563eb;
        display: block;
    }

    /* Form Sections */
    .section { 
        background: #f9fafb; 
        border-radius: 8px; 
        padding: 1.5rem; 
        margin-bottom: 1.5rem;
    }
    .section-title { 
        font-weight: 700; 
        color: #2563eb; 
        margin-bottom: 1rem; 
        font-size: 1.2rem; 
        display: flex; 
        align-items: center; 
        gap: 0.5rem;
    }

    /* Form Elements */
    .form-row { 
        display: flex; 
        gap: 1.2rem;
        margin-bottom: 1rem;
    }
    .form-group { 
        flex: 1; 
        display: flex; 
        flex-direction: column;
    }
    .form-group label { 
        font-weight: 600; 
        margin-bottom: 0.4rem;
        color: #374151;
    }
    input[type="text"], 
    input[type="email"], 
    textarea,
    select { 
        border: 1px solid #d1d5db; 
        border-radius: 8px; 
        padding: 0.7rem; 
        font-size: 1rem;
        background: #fff;
    }
    input[type="text"]:focus, 
    input[type="email"]:focus, 
    textarea:focus,
    select:focus { 
        outline: 2px solid #2563eb; 
        border-color: #2563eb;
    }
    textarea { 
        min-height: 80px; 
        resize: vertical;
    }

    /* Manga Sets */
    .add-set-btn { 
        background: #e0e7ef; 
        color: #2563eb; 
        border: none; 
        border-radius: 8px; 
        padding: 0.7rem 1.2rem; 
        font-weight: 600; 
        cursor: pointer; 
        margin-top: 1rem;
        transition: all 0.2s;
    }
    .add-set-btn:hover { 
        background: #2563eb; 
        color: #fff;
    }
    #sets-list .manga-set { 
        background: #fff; 
        border: 1px solid #e5e7eb; 
        border-radius: 8px; 
        padding: 1.2rem; 
        margin-bottom: 1rem;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        align-items: end;
    }
    .remove-set-btn { 
        background: #fee2e2; 
        color: #dc2626; 
        border: none; 
        border-radius: 6px; 
        padding: 0.5rem 1rem; 
        font-weight: 600; 
        cursor: pointer;
        transition: all 0.2s;
        width: fit-content;
    }
    .remove-set-btn:hover { 
        background: #dc2626; 
        color: #fff;
    }

    /* Photo Upload */
    .photo-upload-box { 
        border: 2px dashed #2563eb; 
        border-radius: 8px; 
        padding: 2rem; 
        text-align: center; 
        background: #f1f5fd; 
        margin-bottom: 1rem;
        transition: all 0.2s;
    }
    .photo-upload-box:hover {
        border-color: #1d4ed8;
        background: #e5edff;
    }
    .photo-upload-label { 
        cursor: pointer; 
        display: block; 
        font-weight: 600; 
        color: #2563eb;
    }
    .photo-list { 
        display: flex; 
        flex-wrap: wrap; 
        gap: 1rem; 
        margin-top: 1rem;
    }
    .photo-thumb { 
        width: 100px; 
        height: 100px; 
        object-fit: cover; 
        border-radius: 8px; 
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* Submit Button */
    .submit-btn { 
        background: #2563eb; 
        color: #fff; 
        border: none; 
        border-radius: 8px; 
        padding: 1rem 2rem; 
        font-size: 1.1rem; 
        font-weight: 700; 
        cursor: pointer; 
        margin-top: 1rem;
        width: 100%;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    .submit-btn:hover { 
        background: #1d4ed8;
        transform: translateY(-1px);
    }

    /* Messages */
    .message { 
        border-radius: 8px; 
        padding: 1rem 1.2rem; 
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .message.success { 
        background: #dcfce7; 
        color: #166534;
    }
    .message.error { 
        background: #fee2e2; 
        color: #991b1b;
    }

    /* Loading Overlay */
    #loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(255,255,255,0.9);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }
    .spinner {
        border: 6px solid #e5e7eb;
        border-top: 6px solid #2563eb;
        border-radius: 50%;
        width: 48px;
        height: 48px;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Responsive Design */
    @media (max-width: 768px) { 
        .container { 
            margin: 1rem;
            padding: 1rem;
        }
        .form-row { 
            flex-direction: column; 
            gap: 1rem;
        }
        .payment-methods { 
            grid-template-columns: 1fr;
            gap: 1rem;
            margin: 1.5rem auto;
        }
        .manga-set {
            grid-template-columns: 1fr !important;
        }
    }
    </style>
</head>
<body>
<div class="container">
    <div id="loading-overlay">
        <div class="spinner"></div>
        <div style="margin-top:1rem;font-weight:600;color:#2563eb;">Submitting...</div>
    </div>

    <?php if ($message): ?>
        <div class="message success">
            <i class="fa fa-check-circle"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error">
            <i class="fa fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

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
        <div class="payment-method">
            <i class="fab fa-paypal"></i>
            PayPal
        </div>
        <div class="payment-method">
            <i class="fa fa-money-bill-wave"></i>
            Venmo
        </div>
        <div class="payment-method">
            <i class="fa fa-university"></i>
            Zelle
        </div>
    </div>
    <div class="note">
        <b>Note:</b> Your personal information is encrypted and stored securely. Payouts are covered for security and safety via PayPal, Venmo, Zelle, and major US banks only.
    </div>
    <form method="POST" enctype="multipart/form-data">
        <div class="section">
            <div class="section-title">
                <i class="fas fa-user"></i> Contact Information
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone Number (Optional)</label>
                    <input type="tel" id="phone" name="phone" placeholder="(123) 456-7890" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="zip">ZIP Code</label>
                <input type="text" id="zip" name="zip" placeholder="For shipping estimate" value="<?php echo htmlspecialchars($_POST['zip'] ?? ''); ?>">
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
document.addEventListener('DOMContentLoaded', function() {
    // Manga condition options
    const conditionOptions = [
        { value: 'G5', label: 'G5 - Like New/Never Read' },
        { value: 'G4', label: 'G4 - Very Good (Light shelf wear)' },
        { value: 'G3', label: 'G3 - Good (Some wear, all pages intact)' },
        { value: 'G2', label: 'G2 - Fair (Noticeable wear)' },
        { value: 'G1', label: 'G1 - Poor (Significant wear/damage)' }
    ];

    // Dynamic manga set entry
    function createConditionSelect() {
        const select = document.createElement('select');
        select.name = 'set_condition[]';
        select.required = true;
        
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Select condition...';
        select.appendChild(defaultOption);

        conditionOptions.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option.value;
            optionElement.textContent = option.label;
            select.appendChild(optionElement);
        });

        return select;
    }

    function renderSets() {
        const setsList = document.getElementById('sets-list');
        setsList.innerHTML = '';

        const sets = window.mangaSets || [];
        if (sets.length === 0) {
            addSet();
            return;
        }

        sets.forEach((set, index) => {
            const setDiv = document.createElement('div');
            setDiv.className = 'manga-set';
            
            const titleGroup = document.createElement('div');
            titleGroup.className = 'form-group';
            titleGroup.innerHTML = `
                <label>Series Title *</label>
                <input type="text" name="set_title[]" required value="${set.title || ''}" placeholder="e.g. Naruto">
            `;

            const volumesGroup = document.createElement('div');
            volumesGroup.className = 'form-group';
            volumesGroup.innerHTML = `
                <label>Volumes *</label>
                <input type="text" name="set_volumes[]" required value="${set.volumes || ''}" placeholder="e.g. 1-72 or 1,2,3,5">
            `;

            const conditionGroup = document.createElement('div');
            conditionGroup.className = 'form-group';
            const conditionLabel = document.createElement('label');
            conditionLabel.textContent = 'Condition *';
            const conditionSelect = createConditionSelect();
            if (set.condition) {
                conditionSelect.value = set.condition;
            }
            conditionGroup.appendChild(conditionLabel);
            conditionGroup.appendChild(conditionSelect);

            const priceGroup = document.createElement('div');
            priceGroup.className = 'form-group';
            priceGroup.innerHTML = `
                <label>Expected Price</label>
                <input type="text" name="set_expected_price[]" value="${set.expected_price || ''}" placeholder="$">
            `;

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'remove-set-btn';
            removeButton.innerHTML = '<i class="fa fa-trash"></i> Remove';
            removeButton.onclick = () => removeSet(index);

            setDiv.appendChild(titleGroup);
            setDiv.appendChild(volumesGroup);
            setDiv.appendChild(conditionGroup);
            setDiv.appendChild(priceGroup);
            setDiv.appendChild(removeButton);
            setsList.appendChild(setDiv);
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
    renderSets();
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
            const dt = new DataTransfer();
            files.forEach(f=>dt.items.add(f));
            photoInput.files = dt.files;
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
});
</script>
<?php include dirname(__DIR__) . '/includes/mobile-nav-footer.php'; ?>