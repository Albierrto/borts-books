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
        $required = ['seller_name', 'seller_email', 'book_title'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                log_debug("Missing required field: $field");
                throw new Exception('Please fill in all required fields.');
            }
        }
        if (!filter_var($_POST['seller_email'], FILTER_VALIDATE_EMAIL)) {
            log_debug('Invalid email: ' . $_POST['seller_email']);
            throw new Exception('Please enter a valid email address.');
        }
        // Handle photo upload
        $photo_json = null;
        if (!empty($_FILES['photo']['name'])) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 10 * 1024 * 1024;
            $file_type = $_FILES['photo']['type'];
            $file_size = $_FILES['photo']['size'];
            if (!in_array($file_type, $allowed_types)) {
                log_debug('Invalid photo type: ' . $file_type);
                throw new Exception('Only JPG, PNG, GIF, and WebP images are allowed.');
            }
            if ($file_size > $max_size) {
                log_debug('Photo too large: ' . $file_size);
                throw new Exception('Photo must be less than 10MB.');
            }
            $upload_dir = dirname(__DIR__) . '/uploads/sell-submissions/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = bin2hex(random_bytes(16)) . '.' . $extension;
            $filepath = $upload_dir . $filename;
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $filepath)) {
                log_debug('Failed to move uploaded file.');
                throw new Exception('Failed to upload photo.');
            }
            $photo_json = json_encode([['filename' => $filename]]);
            log_debug('Photo uploaded: ' . $filename);
        }
        // Insert into database
        $stmt = $db->prepare("INSERT INTO sell_submissions (
            seller_name, seller_email, seller_phone, book_title, book_author, book_isbn, book_condition, description, photo_paths
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['seller_name'],
            $_POST['seller_email'],
            $_POST['seller_phone'] ?? null,
            $_POST['book_title'],
            $_POST['book_author'] ?? null,
            $_POST['book_isbn'] ?? null,
            $_POST['book_condition'] ?? null,
            $_POST['description'] ?? null,
            $photo_json
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
    <title>Sell Your Book</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; color: #222; }
        .container { max-width: 500px; margin: 2rem auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px #0001; padding: 2rem; }
        h1 { color: #2563eb; text-align: center; }
        .form-group { margin-bottom: 1.2rem; }
        label { display: block; font-weight: 600; margin-bottom: 0.3rem; }
        input, select, textarea { width: 100%; padding: 0.7rem; border: 1px solid #ccc; border-radius: 6px; }
        button { background: #2563eb; color: #fff; border: none; border-radius: 6px; padding: 0.8rem 2rem; font-size: 1rem; cursor: pointer; }
        .message { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; text-align: center; }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
<div class="container">
    <h1>Sell Your Book</h1>
    <?php if ($message): ?><div class="message success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="seller_name">Your Name *</label>
            <input type="text" id="seller_name" name="seller_name" required value="<?php echo htmlspecialchars($_POST['seller_name'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="seller_email">Email *</label>
            <input type="email" id="seller_email" name="seller_email" required value="<?php echo htmlspecialchars($_POST['seller_email'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="seller_phone">Phone</label>
            <input type="text" id="seller_phone" name="seller_phone" value="<?php echo htmlspecialchars($_POST['seller_phone'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="book_title">Book Title *</label>
            <input type="text" id="book_title" name="book_title" required value="<?php echo htmlspecialchars($_POST['book_title'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="book_author">Author</label>
            <input type="text" id="book_author" name="book_author" value="<?php echo htmlspecialchars($_POST['book_author'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="book_isbn">ISBN</label>
            <input type="text" id="book_isbn" name="book_isbn" value="<?php echo htmlspecialchars($_POST['book_isbn'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="book_condition">Condition</label>
            <select id="book_condition" name="book_condition">
                <option value="">Select condition...</option>
                <option value="new" <?php echo (($_POST['book_condition'] ?? '') === 'new') ? 'selected' : ''; ?>>New</option>
                <option value="like_new" <?php echo (($_POST['book_condition'] ?? '') === 'like_new') ? 'selected' : ''; ?>>Like New</option>
                <option value="very_good" <?php echo (($_POST['book_condition'] ?? '') === 'very_good') ? 'selected' : ''; ?>>Very Good</option>
                <option value="good" <?php echo (($_POST['book_condition'] ?? '') === 'good') ? 'selected' : ''; ?>>Good</option>
                <option value="fair" <?php echo (($_POST['book_condition'] ?? '') === 'fair') ? 'selected' : ''; ?>>Fair</option>
                <option value="poor" <?php echo (($_POST['book_condition'] ?? '') === 'poor') ? 'selected' : ''; ?>>Poor</option>
            </select>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="photo">Photo (JPG, PNG, GIF, WebP, max 10MB)</label>
            <input type="file" id="photo" name="photo" accept="image/*">
        </div>
        <div style="text-align:center;">
            <button type="submit">Submit</button>
        </div>
    </form>
</div>
</body>
</html>