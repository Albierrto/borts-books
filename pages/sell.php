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
        // Handle multiple book titles (series)
        $series = [];
        if (!empty($_POST['book_titles']) && is_array($_POST['book_titles'])) {
            foreach ($_POST['book_titles'] as $title) {
                $title = trim($title);
                if ($title !== '') {
                    $series[] = $title;
                }
            }
        }
        if (empty($series)) {
            throw new Exception('Please enter at least one book or series title.');
        }
        $series_json = json_encode($series);
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
        // Insert into database
        $stmt = $db->prepare("INSERT INTO sell_submissions (
            full_name, email, phone, overall_condition, description, photo_paths, item_details
        ) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $item_details = json_encode(['series' => $series]);
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
    <title>Sell Your Books or Series</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; color: #222; }
        .container { max-width: 600px; margin: 2rem auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px #0001; padding: 2rem; }
        h1 { color: #2563eb; text-align: center; }
        .form-group { margin-bottom: 1.2rem; }
        label { display: block; font-weight: 600; margin-bottom: 0.3rem; }
        input, select, textarea { width: 100%; padding: 0.7rem; border: 1px solid #ccc; border-radius: 6px; }
        button { background: #2563eb; color: #fff; border: none; border-radius: 6px; padding: 0.8rem 2rem; font-size: 1rem; cursor: pointer; }
        .message { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; text-align: center; }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
        .series-list { margin-bottom: 1rem; }
        .series-item { display: flex; gap: 0.5rem; margin-bottom: 0.5rem; }
        .series-item input { flex: 1; }
        .series-item button { background: #dc2626; color: #fff; padding: 0.5rem 1rem; border-radius: 6px; border: none; cursor: pointer; }
        .add-series-btn { background: #059669; color: #fff; margin-top: 0.5rem; }
    </style>
    <script>
        function addSeriesField(value = '') {
            const list = document.getElementById('series-list');
            const div = document.createElement('div');
            div.className = 'series-item';
            div.innerHTML = `<input type="text" name="book_titles[]" placeholder="Book or Series Title" value="${value.replace(/"/g, '&quot;')}">
                <button type="button" onclick="this.parentNode.remove()">Remove</button>`;
            list.appendChild(div);
        }
        window.onload = function() {
            // Add at least one field
            const existing = <?php echo json_encode($_POST['book_titles'] ?? ['']); ?>;
            if (existing.length === 0) addSeriesField();
            else existing.forEach(addSeriesField);
            document.getElementById('add-series-btn').onclick = function(e) {
                e.preventDefault();
                addSeriesField();
            };
        };
    </script>
</head>
<body>
<div class="container">
    <h1>Sell Your Books or Series</h1>
    <?php if ($message): ?><div class="message success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="full_name">Your Name *</label>
            <input type="text" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="phone">Phone (recommended)</label>
            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Books or Series *</label>
            <div id="series-list" class="series-list"></div>
            <button id="add-series-btn" class="add-series-btn">Add Another Book/Series</button>
        </div>
        <div class="form-group">
            <label for="overall_condition">Overall Condition</label>
            <select id="overall_condition" name="overall_condition">
                <option value="">Select condition...</option>
                <option value="new" <?php echo (($_POST['overall_condition'] ?? '') === 'new') ? 'selected' : ''; ?>>New</option>
                <option value="like_new" <?php echo (($_POST['overall_condition'] ?? '') === 'like_new') ? 'selected' : ''; ?>>Like New</option>
                <option value="very_good" <?php echo (($_POST['overall_condition'] ?? '') === 'very_good') ? 'selected' : ''; ?>>Very Good</option>
                <option value="good" <?php echo (($_POST['overall_condition'] ?? '') === 'good') ? 'selected' : ''; ?>>Good</option>
                <option value="fair" <?php echo (($_POST['overall_condition'] ?? '') === 'fair') ? 'selected' : ''; ?>>Fair</option>
                <option value="poor" <?php echo (($_POST['overall_condition'] ?? '') === 'poor') ? 'selected' : ''; ?>>Poor</option>
            </select>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="photos">Photos (JPG, PNG, GIF, WebP, max 10MB each, multiple allowed)</label>
            <input type="file" id="photos" name="photos[]" accept="image/*" multiple>
        </div>
        <div style="text-align:center;">
            <button type="submit">Submit</button>
        </div>
    </form>
</div>
</body>
</html>