<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/security.php';
require_once '../includes/secure-upload.php';
require_once '../includes/secure-email.php';
require_once '../includes/database-encryption.php';
require_once '../includes/db.php';

// Start secure session
secure_session_start();

// Set security headers
set_security_headers();

// Generate CSRF token
$csrf_token = generate_csrf_token();

$pageTitle = "Sell Your Manga Sets";
$currentPage = "sell";

$successMsg = '';
$errorMsg = '';

// Initialize security components and constants
$encryption = new DatabaseEncryption();
$emailSystem = new SecureEmailSystem();

// Ensure sell_submissions table has required columns for public submissions
try {
    $res = $pdo->query("SHOW TABLES LIKE 'sell_submissions'");
    if ($res->rowCount() == 0) {
        // Create new table with secure columns
        $pdo->exec("CREATE TABLE sell_submissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARBINARY(1024) NOT NULL,
            email VARBINARY(1024) NOT NULL,
            email_hash CHAR(64) NOT NULL,
            phone VARBINARY(1024),
            num_items INT DEFAULT 0,
            overall_condition VARCHAR(50),
            item_details JSON,
            photo_paths JSON,
            description VARBINARY(8192),
            status ENUM('pending','quoted','completed','rejected') DEFAULT 'pending',
            quote_amount DECIMAL(10,2),
            admin_notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email_hash (email_hash),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    } else {
        $cols = $pdo->query("SHOW COLUMNS FROM sell_submissions")->fetchAll(PDO::FETCH_COLUMN);
        $required = [
            'full_name','email','email_hash','phone','num_items','overall_condition',
            'item_details','photo_paths','description','status','quote_amount','admin_notes','created_at','updated_at'
        ];
        foreach ($required as $col) {
            if (!in_array($col, $cols)) {
                switch ($col) {
                    case 'full_name':
                    case 'email':
                    case 'phone':
                        $pdo->exec("ALTER TABLE sell_submissions ADD COLUMN $col VARBINARY(1024)");
                        break;
                    case 'email_hash':
                        $pdo->exec("ALTER TABLE sell_submissions ADD COLUMN email_hash CHAR(64)");
                        break;
                    case 'num_items':
                        $pdo->exec("ALTER TABLE sell_submissions ADD COLUMN num_items INT DEFAULT 0");
                        break;
                    case 'overall_condition':
                        $pdo->exec("ALTER TABLE sell_submissions ADD COLUMN overall_condition VARCHAR(50)");
                        break;
                    case 'item_details':
                    case 'photo_paths':
                        $pdo->exec("ALTER TABLE sell_submissions ADD COLUMN $col JSON");
                        break;
                    case 'description':
                        $pdo->exec("ALTER TABLE sell_submissions ADD COLUMN description VARBINARY(8192)");
                        break;
                    case 'status':
                        $pdo->exec("ALTER TABLE sell_submissions ADD COLUMN status ENUM('pending','quoted','completed','rejected') DEFAULT 'pending'");
                        break;
                    case 'quote_amount':
                        $pdo->exec("ALTER TABLE sell_submissions ADD COLUMN quote_amount DECIMAL(10,2)");
                        break;
                    case 'admin_notes':
                        $pdo->exec("ALTER TABLE sell_submissions ADD COLUMN admin_notes TEXT");
                        break;
                    case 'created_at':
                        $pdo->exec("ALTER TABLE sell_submissions ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
                        break;
                    case 'updated_at':
                        $pdo->exec("ALTER TABLE sell_submissions ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
                        break;
                }
            }
        }
    }
} catch (PDOException $e) {
    error_log('sell_submissions schema check error: ' . $e->getMessage());
}

$debug = true; // Set to false to hide debug info
if ($debug) {
    $keyFile = __DIR__ . '/../includes/config/encryption.key';
    $saltFile = __DIR__ . '/../includes/config/encryption.salt';
    $key = file_exists($keyFile) ? file_get_contents($keyFile) : '[missing]';
    $salt = file_exists($saltFile) ? file_get_contents($saltFile) : '[missing]';
    echo '<div style="background:#fffbe6;border:2px solid #ffe58f;padding:1em 2em;margin-bottom:2em;border-radius:10px;font-size:0.95em;">';
    echo '<strong>DEBUG PANEL</strong><br>';
    echo 'Encryption Key Hash: <code>' . htmlspecialchars(substr(hash('sha256', $key),0,16)) . '</code><br>';
    echo 'Salt Hash: <code>' . htmlspecialchars(substr(hash('sha256', $salt),0,16)) . '</code><br>';
    if ($key === '[missing]' || $salt === '[missing]') {
        echo '<span style="color:red;font-weight:bold;">WARNING: Encryption key or salt is missing!</span><br>';
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo '<hr style="margin:0.7em 0;">';
        echo '<b>Raw Name:</b> <code>' . htmlspecialchars($_POST['full_name'] ?? '') . '</code><br>';
        echo '<b>Raw Email:</b> <code>' . htmlspecialchars($_POST['email'] ?? '') . '</code><br>';
        echo '<b>Raw Desc:</b> <code>' . htmlspecialchars($_POST['description'] ?? '') . '</code><br>';
        if (!empty($uploaded_files)) {
            foreach ($uploaded_files as $uf) {
                echo '<span style="font-size:0.95em;">Photo: <code>' . htmlspecialchars($uf['filename']) . '</code> | Token: <code>' . htmlspecialchars($uf['access_token']) . '</code></span><br>';
            }
        }
        if (!empty($encrypted_data)) {
            echo '<b>Encrypted Name:</b> <code>' . htmlspecialchars($encrypted_data['full_name'] ?? '') . '</code><br>';
            echo '<b>Encrypted Email:</b> <code>' . htmlspecialchars($encrypted_data['email'] ?? '') . '</code><br>';
            echo '<b>Encrypted Desc:</b> <code>' . htmlspecialchars($encrypted_data['description'] ?? '') . '</code><br>';
        }
    }
    echo '</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Invalid request. Please try again.';
        log_security_event('csrf_failure', ['page' => 'sell']);
    } else {
        // Check rate limiting
        $clientId = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!check_rate_limit('sell_submission', 3, 3600)) {
            $errorMsg = 'Too many submissions. Please try again later.';
            log_security_event('rate_limit_exceeded', ['page' => 'sell', 'ip' => $clientId]);
        } else {
            try {
                // Initialize encryption and secure upload
                $encryption = new DatabaseEncryption();
                $secureUpload = new SecureFileUpload();
                $emailSystem = new SecureEmailSystem();
                
                // Sanitize and validate inputs
                $full_name = sanitize_input($_POST['full_name'] ?? '');
                $email = validate_email($_POST['email'] ?? '');
                $phone = sanitize_input($_POST['phone'] ?? '');
                $num_items = validate_int($_POST['num_items'] ?? '');
                $overall_condition = sanitize_input($_POST['overall_condition'] ?? '');
                $description = sanitize_input($_POST['description'] ?? '');
    
                // Validate required fields
                if (empty($full_name) || !$email || !$num_items) {
                    throw new Exception('Please fill in all required fields.');
                }
                
                // Process set details
    $set_details = [];
    if (!empty($_POST['set_title'])) {
        $count = count($_POST['set_title']);
        for ($i = 0; $i < $count; $i++) {
                        $title = sanitize_input($_POST['set_title'][$i] ?? '');
                        $volumes = sanitize_input($_POST['set_volumes'][$i] ?? '');
                        $condition = sanitize_input($_POST['set_condition'][$i] ?? '');
                        $expected_price = validate_float($_POST['set_expected_price'][$i] ?? '');
                        
                        if (!empty($title) || !empty($volumes) || $expected_price > 0) {
                $set_details[] = [
                    'title' => $title,
                    'volumes' => $volumes,
                    'condition' => $condition,
                    'expected_price' => $expected_price
                ];
            }
        }
    }
    
                // Handle secure photo uploads
                $uploaded_files = [];
    if (!empty($_FILES['collection_photos']['name'][0])) {
                    // Check upload rate limiting
                    if (!UploadRateLimit::checkLimit($clientId, 20, 3600)) {
                        throw new Exception('Too many file uploads. Please try again later.');
        }
                    
        foreach ($_FILES['collection_photos']['tmp_name'] as $idx => $tmp_name) {
            if ($_FILES['collection_photos']['error'][$idx] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $_FILES['collection_photos']['name'][$idx],
                                'type' => $_FILES['collection_photos']['type'][$idx],
                                'tmp_name' => $tmp_name,
                                'error' => $_FILES['collection_photos']['error'][$idx],
                                'size' => $_FILES['collection_photos']['size'][$idx]
                            ];
                            
                            $result = $secureUpload->processUpload($file, 'sell-submissions');
                            
                            if ($result['success']) {
                                $uploaded_files[] = [
                                    'filename' => $result['filename'],
                                    'access_token' => $result['access_token'],
                                    'size' => $result['size'],
                                    'mime_type' => $result['mime_type']
                                ];
                            } else {
                                throw new Exception('File upload failed: ' . implode(', ', $result['errors']));
                }
            }
        }
    }
                
                if (empty($uploaded_files)) {
                    throw new Exception('At least one photo of your collection is required.');
                }
                
                // Encrypt sensitive data
                $encrypted_data = $encryption->encryptFields([
                    'full_name' => $full_name,
                    'email' => $email,
                    'phone' => $phone,
                    'description' => $description
                ], ['full_name', 'email', 'phone', 'description']);
                
                // Create searchable hash for email
                $email_hash = $encryption->createSearchHash($email, 'email');
                
                // Store in database with encryption
                $stmt = $pdo->prepare('
                    INSERT INTO sell_submissions (
                        full_name, email, email_hash, phone, num_items, 
                        overall_condition, item_details, photo_paths, description,
                        status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ');
                
                $stmt->execute([
                    $encrypted_data['full_name'],
                    $encrypted_data['email'],
                    $email_hash,
                    $encrypted_data['phone'],
                    $num_items,
                    $overall_condition,
                    json_encode($set_details),
                    json_encode($uploaded_files),
                    $encrypted_data['description'],
                    'pending'
                ]);
                
                // Send confirmation email
                try {
                    $emailSystem->sendEmail(
                        $email,
                        'Sell Submission Received - Bort\'s Books',
                        "
                        <h2>Thank you for your submission!</h2>
                        <p>Dear $full_name,</p>
                        <p>We have received your manga collection submission with $num_items items.</p>
                        <p>Our team will review your submission and contact you within 2-3 business days with our assessment and quote.</p>
                        <p>Submission Details:</p>
                        <ul>
                            <li>Number of items: $num_items</li>
                            <li>Overall condition: $overall_condition</li>
                            <li>Photos submitted: " . count($uploaded_files) . "</li>
                        </ul>
                        <p>If you have any questions, please don't hesitate to contact us.</p>
                        <p>Best regards,<br>The Bort's Books Team</p>
                        ",
                        ['template' => 'transactional']
                    );
                } catch (Exception $e) {
                    // Email failed but submission succeeded
                    error_log("Confirmation email failed: " . $e->getMessage());
                }
                
                // Log successful submission
                log_security_event('sell_submission', [
                    'email_hash' => $email_hash,
                    'num_items' => $num_items,
                    'files_uploaded' => count($uploaded_files)
                ]);
                
        $successMsg = 'Thank you for your submission! We will review your manga sets and contact you soon.';
                
                // Clear form data on success
                $_POST = [];
                
            } catch (Exception $e) {
                $errorMsg = htmlspecialchars($e->getMessage());
                log_security_event('sell_submission_error', [
                    'error' => $e->getMessage(),
                    'ip' => $clientId
                ]);
            }
        }
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
    <title>Sell Your Manga Sets - Bort's Books</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='grad' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%23667eea;stop-opacity:1' /%3E%3Cstop offset='100%25' style='stop-color:%23764ba2;stop-opacity:1' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='15' fill='url(%23grad)'/%3E%3Cpath d='M25 20h50c2.5 0 4.5 2 4.5 4.5v51c0 2.5-2 4.5-4.5 4.5H25c-2.5 0-4.5-2-4.5-4.5v-51c0-2.5 2-4.5 4.5-4.5z' fill='white'/%3E%3Cpath d='M30 30h40v5H30z' fill='%23667eea'/%3E%3Cpath d='M30 40h35v3H30z' fill='%23999'/%3E%3Cpath d='M30 47h30v3H30z' fill='%23999'/%3E%3Cpath d='M30 54h25v3H30z' fill='%23999'/%3E%3Cpath d='M30 61h20v3H30z' fill='%23999'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Enhanced Security Styling */
        .security-notice {
            background: #e8f5e8;
            border: 1px solid #4caf50;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .file-upload-security {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 6px;
            padding: 10px;
            margin-top: 10px;
            font-size: 12px;
        }
        
        .upload-progress {
            width: 100%;
            height: 6px;
            background: #f0f0f0;
            border-radius: 3px;
            margin-top: 10px;
            overflow: hidden;
            display: none;
        }
        
        .upload-progress-bar {
            height: 100%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            transition: width 0.3s ease;
            width: 0%;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        /* Mobile-First Responsive Design */
        .sell-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        /* Header and Navigation Styles */
        header {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            max-width: 1200px;
            margin: 0 auto;
            padding-left: 20px;
            padding-right: 20px;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logo span {
            color: #667eea;
        }

        .logo:hover {
            transform: translateY(-1px);
        }

        nav ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 2rem;
        }

        nav a {
            color: #333;
            text-decoration: none;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            position: relative;
        }

        nav a:hover,
        nav a.active {
            color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }

        .search-cart {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .cart-link {
            position: relative;
            color: #333;
            font-size: 1.2rem;
            text-decoration: none;
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .cart-link:hover {
            color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Footer Styles */
        footer {
            background: #2c3e50;
            color: #fff;
            margin-top: 3rem;
        }

        footer .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 20px 1rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .footer-section h3 {
            margin-bottom: 1rem;
            color: #fff;
            font-size: 1.2rem;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-section ul li {
            margin-bottom: 0.5rem;
        }

        .footer-section ul li a {
            color: #bdc3c7;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section ul li a:hover {
            color: #fff;
        }

        .footer-bottom {
            background: #1a252f;
            padding: 1rem 0;
            text-align: center;
            border-top: 1px solid #34495e;
        }

        .footer-bottom .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-bottom p {
            margin: 0;
            color: #bdc3c7;
            font-size: 0.9rem;
        }

        /* Mobile Navigation */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #333;
            cursor: pointer;
            padding: 0.5rem;
        }

        .mobile-menu-toggle:hover {
            color: #667eea;
        }

        @media (max-width: 768px) {
            .header-container {
                position: relative;
            }

            .mobile-menu-toggle {
                display: block;
            }

            nav {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                border-radius: 0 0 12px 12px;
                display: none;
                z-index: 1000;
            }

            nav.active {
                display: block;
            }

            nav ul {
                flex-direction: column;
                gap: 0;
                padding: 1rem 0;
            }

            nav ul li {
                width: 100%;
            }

            nav a {
                display: block;
                padding: 1rem 2rem;
                border-radius: 0;
                border-bottom: 1px solid #f0f0f0;
            }

            nav a:last-child {
                border-bottom: none;
            }

            .search-cart {
                position: absolute;
                right: 60px;
                top: 50%;
                transform: translateY(-50%);
            }

            footer .container {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }

        .sell-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
        }

        .sell-header h1 {
            margin: 0 0 10px 0;
            font-size: clamp(1.8rem, 4vw, 2.5rem);
        }

        .sell-header p {
            margin: 0;
            font-size: clamp(1rem, 2.5vw, 1.2rem);
            opacity: 0.9;
        }

        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            background: #f8f9fa;
        }

        .form-section h3 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: clamp(1.2rem, 3vw, 1.5rem);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: clamp(0.9rem, 2.5vw, 1rem);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: clamp(0.9rem, 2.5vw, 1rem);
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .manga-set {
            background: white;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
        }

        .manga-set h4 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: clamp(1.1rem, 2.8vw, 1.3rem);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .remove-set-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .remove-set-btn:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }

        @media (min-width: 768px) {
            .form-row {
                grid-template-columns: 1fr 1fr;
            }
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: clamp(1rem, 2.5vw, 1.1rem);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            min-height: 50px;
            box-sizing: border-box;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .button-group {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 30px;
        }

        @media (min-width: 768px) {
            .button-group {
                flex-direction: row;
                justify-content: center;
            }
        }

        .info-box {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .info-box h3 {
            margin: 0 0 15px 0;
            color: #1976d2;
            font-size: clamp(1.1rem, 2.8vw, 1.3rem);
        }

        .info-box ul {
            margin: 0;
            padding-left: 20px;
        }

        .info-box li {
            margin-bottom: 8px;
            font-size: clamp(0.9rem, 2.3vw, 1rem);
        }

        /* Mobile optimizations */
        @media (max-width: 767px) {
            .sell-container {
                margin: 10px;
                padding: 15px;
            }
            
            .form-section {
                padding: 15px;
            }
            
            .manga-set {
                padding: 15px;
            }
            
            .btn {
                width: 100%;
                padding: 18px 20px;
            }
        }

        /* Loading state */
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .loading {
            display: none;
        }

        .btn.loading .loading {
            display: inline-block;
        }

        .btn.loading .btn-text {
            display: none;
        }

        /* Success message */
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        /* Photo Upload Styles */
        .upload-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .upload-info p {
            margin: 0 0 10px 0;
            font-size: clamp(0.9rem, 2.3vw, 1rem);
        }

        .upload-info p:last-child {
            margin-bottom: 0;
        }

        .upload-area {
            display: block;
            width: 100%;
            min-height: 150px;
            border: 3px dashed #667eea;
            border-radius: 12px;
            background: #f8f9ff;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }

        .upload-area:hover {
            border-color: #5a6fd8;
            background: #f0f3ff;
            transform: translateY(-2px);
        }

        .upload-area.dragover {
            border-color: #28a745;
            background: #f0fff4;
        }

        .upload-content {
            text-align: center;
            padding: 30px 20px;
            color: #667eea;
        }

        .upload-content i {
            font-size: clamp(2rem, 5vw, 3rem);
            margin-bottom: 15px;
            display: block;
        }

        .upload-content h4 {
            margin: 0 0 10px 0;
            font-size: clamp(1.1rem, 2.8vw, 1.3rem);
            color: #333;
        }

        .upload-content p {
            margin: 0 0 10px 0;
            font-size: clamp(0.9rem, 2.3vw, 1rem);
            color: #666;
        }

        .upload-note {
            font-size: clamp(0.8rem, 2vw, 0.9rem);
            color: #888;
            font-style: italic;
        }

        .photo-preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        @media (min-width: 768px) {
            .photo-preview-container {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }

        .photo-preview {
            position: relative;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            aspect-ratio: 1;
        }

        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .photo-remove {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .photo-remove:hover {
            background: #c82333;
            transform: scale(1.1);
        }

        .photo-name {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px;
            font-size: 0.8rem;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .upload-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .photo-count {
            font-size: clamp(0.9rem, 2.3vw, 1rem);
            color: #666;
            font-weight: 500;
        }

        /* Mobile optimizations for photo upload */
        @media (max-width: 767px) {
            .upload-content {
                padding: 20px 15px;
            }
            
            .photo-preview-container {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                gap: 10px;
            }
            
            .upload-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .upload-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="/index.php" class="logo">Bort's <span>Books</span></a>
            <nav id="mobileNav">
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

            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <main>
        <div class="sell-container">
            <div class="sell-header">
                <h1><i class="fas fa-book-open"></i> Sell Your Manga Sets</h1>
                <p>Turn your manga collection into cash! We buy complete sets in good condition.</p>
                <div style="margin-top: 20px;">
                    <a href="/pages/how-it-works-sell.php" class="btn btn-secondary" style="background: rgba(255,255,255,0.2); color: white; text-decoration: none; padding: 12px 24px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; font-weight: 600; transition: all 0.3s ease;">
                        <i class="fas fa-question-circle"></i>
                        How It Works
                    </a>
        </div>
            </div>

            <!-- Quote Guarantee Banner -->
            <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; border-radius: 12px; text-align: center; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);">
                <h3 style="margin: 0 0 10px 0; font-size: 1.4rem; font-weight: 700;">
                    <i class="fas fa-clock" style="margin-right: 10px;"></i>
                    24-Hour Quote Guarantee
                </h3>
                <p style="margin: 0; font-size: 1.1rem; opacity: 0.95;">
                    Submit your collection today and receive a detailed quote within 24 hours!
                </p>
        </div>

            <div class="info-box">
                <h3><i class="fas fa-info-circle"></i> What We're Looking For</h3>
                <ul>
                    <li><strong>Complete manga sets</strong> - We prefer full series or substantial partial sets</li>
                    <li><strong>Good condition</strong> - Books should be readable with minimal wear</li>
                    <li><strong>Popular series</strong> - Mainstream and sought-after titles get better prices</li>
                    <li><strong>English language</strong> - We currently only accept English manga</li>
                    <li><strong>Competitive prices</strong> - We pay up to 80% of current eBay market prices</li>
                </ul>
                </div>

            <!-- Payment Methods Section -->
            <div style="background: #f8f9fa; border-radius: 12px; padding: 25px; margin-bottom: 30px; border: 2px solid #e1e5e9;">
                <h3 style="margin: 0 0 20px 0; color: #333; font-size: 1.3rem; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-credit-card" style="color: #667eea;"></i>
                    Payment Methods
                </h3>
                <p style="margin: 0 0 15px 0; color: #666; font-size: 1rem;">
                    Once we accept your collection, choose how you'd like to receive payment:
                </p>
                <div style="display: flex; gap: 20px; flex-wrap: wrap; justify-content: center; margin-top: 20px;">
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 15px; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); min-width: 100px;">
                        <i class="fab fa-paypal" style="font-size: 2rem; color: #0070ba;"></i>
                        <span style="font-weight: 600; color: #333;">PayPal</span>
                </div>
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 15px; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); min-width: 100px;">
                        <i class="fas fa-mobile-alt" style="font-size: 2rem; color: #6c5ce7;"></i>
                        <span style="font-weight: 600; color: #333;">Zelle</span>
                </div>
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 15px; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); min-width: 100px;">
                        <i class="fas fa-dollar-sign" style="font-size: 2rem; color: #00d632;"></i>
                        <span style="font-weight: 600; color: #333;">CashApp</span>
                </div>
            </div>
                <p style="margin: 15px 0 0 0; color: #666; font-size: 0.95rem; text-align: center; font-style: italic;">
                    Fast, secure payments sent within 24-48 hours after receiving your shipment
                </p>
        </div>

            <div class="security-notice">
                <i class="fas fa-shield-alt"></i>
                <strong>Secure Submission:</strong> Your personal information is encrypted and stored securely. 
                File uploads are scanned for security and only image files (JPG, PNG, WebP) are accepted.
            </div>

            <?php if ($successMsg): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($successMsg); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($errorMsg): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($errorMsg); ?>
                </div>
            <?php endif; ?>

            <form id="sellForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> Contact Information</h3>
                    
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
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="zip">ZIP Code *</label>
                            <input type="text" id="zip" name="zip" required placeholder="For shipping estimate">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Additional Description</label>
                        <textarea id="description" name="description" rows="4" placeholder="Describe your collection, any special editions, damage, etc."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-layer-group"></i> Your Manga Sets</h3>
                    
                    <div id="mangaSets">
                        <!-- Initial set -->
                        <div class="manga-set" data-set="1">
                            <h4>
                                <span><i class="fas fa-book"></i> Set #1</span>
                                <button type="button" class="remove-set-btn" onclick="removeSet(1)" style="display: none;">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </h4>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="title_1">Series Title *</label>
                                    <input type="text" id="title_1" name="sets[1][title]" required placeholder="e.g., Naruto, One Piece, Attack on Titan">
                                </div>
                                
                                <div class="form-group">
                                    <label for="volumes_1">Volumes *</label>
                                    <input type="text" id="volumes_1" name="sets[1][volumes]" required placeholder="e.g., 1-72, 1-15, 5-20">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="condition_1">Condition *</label>
                                    <select id="condition_1" name="sets[1][condition]" required>
                                        <option value="">Select condition...</option>
                                        <option value="like_new">Like New - Minimal wear, looks almost new</option>
                                        <option value="very_good">Very Good - Light wear, all pages intact</option>
                                        <option value="good">Good - Moderate wear, readable condition</option>
                                        <option value="fair">Fair - Heavy wear but complete</option>
                                        <option value="ex_library">Ex-Library - Former library books</option>
                </select>
            </div>
                                
                                <div class="form-group">
                                    <label for="asking_price_1">Asking Price</label>
                                    <input type="number" id="asking_price_1" name="sets[1][asking_price]" step="0.01" placeholder="Optional - your asking price">
            </div>
            </div>
            </div>
            </div>

                    <div class="button-group">
                        <button type="button" id="addSetBtn" class="btn btn-secondary">
                            <i class="fas fa-plus"></i>
                            <span class="btn-text">Add Another Set</span>
                        </button>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-camera"></i> Photos of Your Collection *</h3>
                    
                    <div class="upload-info">
                        <p><i class="fas fa-info-circle"></i> <strong>Required:</strong> Please upload clear photos showing your manga sets. Include spines with volume numbers and any special editions.</p>
                        <p><strong>Tips:</strong> Good lighting, multiple angles, and close-ups of condition help us give better offers!</p>
                    </div>

                    <div class="photo-upload-container">
                        <label for="collection_photos" class="upload-area">
                            <div class="upload-content">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <h4>Click to Upload Photos</h4>
                                <p>Or drag and drop images here</p>
                                <span class="upload-note">JPG, PNG, HEIC (Max 10MB each)</span>
                            </div>
                        </label>
                        <input type="file" id="collection_photos" name="collection_photos[]" multiple accept="image/*" required style="display: none;">
                        
                        <div id="photoPreviewContainer" class="photo-preview-container"></div>
                        
                        <div class="upload-actions">
                            <button type="button" id="addMorePhotos" class="btn btn-secondary" style="display: none;">
                                <i class="fas fa-plus"></i>
                                <span class="btn-text">Add More Photos</span>
                            </button>
                            <span id="photoCount" class="photo-count">0 photos selected</span>
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" id="submitBtn" class="btn btn-primary">
                        <i class="fas fa-paper-plane loading" style="display: none;"></i>
                        <i class="fas fa-paper-plane btn-icon"></i>
                        <span class="btn-text">Submit for Review</span>
                        <span class="loading">Submitting...</span>
                    </button>
                </div>
        </form>
        </div>
    </main>

    <footer>
        <div class="container">
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
                <h3>Connect</h3>
                <ul>
                    <li><a href="#"><i class="fab fa-facebook"></i> Facebook</a></li>
                    <li><a href="#"><i class="fab fa-twitter"></i> Twitter</a></li>
                    <li><a href="#"><i class="fab fa-instagram"></i> Instagram</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="container">
                <p>&copy; 2024 Bort's Books. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        let setCounter = 1;

        // Add new manga set
        document.getElementById('addSetBtn').addEventListener('click', function() {
            setCounter++;
            const mangaSetsContainer = document.getElementById('mangaSets');
            
            const newSet = document.createElement('div');
            newSet.className = 'manga-set';
            newSet.setAttribute('data-set', setCounter);
            
            newSet.innerHTML = `
                <h4>
                    <span><i class="fas fa-book"></i> Set #${setCounter}</span>
                    <button type="button" class="remove-set-btn" onclick="removeSet(${setCounter})">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </h4>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="title_${setCounter}">Series Title *</label>
                        <input type="text" id="title_${setCounter}" name="sets[${setCounter}][title]" required placeholder="e.g., Naruto, One Piece, Attack on Titan">
                    </div>
                    
                    <div class="form-group">
                        <label for="volumes_${setCounter}">Volumes *</label>
                        <input type="text" id="volumes_${setCounter}" name="sets[${setCounter}][volumes]" required placeholder="e.g., 1-72, 1-15, 5-20">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="condition_${setCounter}">Condition *</label>
                        <select id="condition_${setCounter}" name="sets[${setCounter}][condition]" required>
                            <option value="">Select condition...</option>
                            <option value="like_new">Like New - Minimal wear, looks almost new</option>
                            <option value="very_good">Very Good - Light wear, all pages intact</option>
                            <option value="good">Good - Moderate wear, readable condition</option>
                            <option value="fair">Fair - Heavy wear but complete</option>
                            <option value="ex_library">Ex-Library - Former library books</option>
            </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="asking_price_${setCounter}">Asking Price</label>
                        <input type="number" id="asking_price_${setCounter}" name="sets[${setCounter}][asking_price]" step="0.01" placeholder="Optional - your asking price">
                    </div>
                </div>
            `;
            
            mangaSetsContainer.appendChild(newSet);
            
            // Show remove button on first set if there are now multiple sets
            if (setCounter > 1) {
                const firstSetRemoveBtn = document.querySelector('[data-set="1"] .remove-set-btn');
                if (firstSetRemoveBtn) {
                    firstSetRemoveBtn.style.display = 'flex';
                }
            }
            
            // Scroll to new set
            newSet.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });

        // Remove manga set
        function removeSet(setId) {
            const setElement = document.querySelector(`[data-set="${setId}"]`);
            if (setElement) {
                setElement.remove();
                
                // Hide remove button on first set if it's the only one left
                const remainingSets = document.querySelectorAll('.manga-set');
                if (remainingSets.length === 1) {
                    const firstSetRemoveBtn = document.querySelector('.manga-set .remove-set-btn');
                    if (firstSetRemoveBtn) {
                        firstSetRemoveBtn.style.display = 'none';
                    }
                }
                
                // Renumber remaining sets
                renumberSets();
            }
        }

        // Renumber sets after removal
        function renumberSets() {
            const sets = document.querySelectorAll('.manga-set');
            sets.forEach((set, index) => {
                const setNumber = index + 1;
                set.setAttribute('data-set', setNumber);
                
                // Update header
                const header = set.querySelector('h4 span');
                if (header) {
                    header.innerHTML = `<i class="fas fa-book"></i> Set #${setNumber}`;
                }
                
                // Update remove button onclick
                const removeBtn = set.querySelector('.remove-set-btn');
                if (removeBtn) {
                    removeBtn.setAttribute('onclick', `removeSet(${setNumber})`);
                }
                
                // Update form field names and IDs
                const inputs = set.querySelectorAll('input, select');
                inputs.forEach(input => {
                    const name = input.getAttribute('name');
                    const id = input.getAttribute('id');
                    
                    if (name) {
                        const newName = name.replace(/\[\d+\]/, `[${setNumber}]`);
                        input.setAttribute('name', newName);
                    }
                    
                    if (id) {
                        const newId = id.replace(/_\d+$/, `_${setNumber}`);
                        input.setAttribute('id', newId);
                        
                        // Update corresponding label
                        const label = set.querySelector(`label[for="${id}"]`);
                        if (label) {
                            label.setAttribute('for', newId);
                        }
                    }
                });
            });
            
            setCounter = sets.length;
        }

        // Mobile-friendly touch interactions
        document.addEventListener('touchstart', function() {}, {passive: true});
        
        // Prevent zoom on input focus for iOS
        document.addEventListener('touchend', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName === 'TEXTAREA') {
                e.target.style.fontSize = '16px';
            }
        });

        // Photo Upload Functionality
        let uploadedPhotos = [];
        let photoIdCounter = 0;

        const photoInput = document.getElementById('collection_photos');
        const uploadArea = document.querySelector('.upload-area');
        const previewContainer = document.getElementById('photoPreviewContainer');
        const addMoreBtn = document.getElementById('addMorePhotos');
        const photoCount = document.getElementById('photoCount');

        // Handle file input change
        photoInput.addEventListener('change', function(e) {
            handleFileSelection(e.target.files);
        });

        // Handle add more photos button
        addMoreBtn.addEventListener('click', function() {
            photoInput.click();
        });

        // Drag and drop functionality
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            handleFileSelection(files);
        });

        // Handle file selection (cumulative)
        function handleFileSelection(files) {
            Array.from(files).forEach(file => {
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    alert(`"${file.name}" is not an image file. Only images are allowed.`);
                    return;
                }

                // Validate file size (10MB max)
                if (file.size > 10 * 1024 * 1024) {
                    alert(`"${file.name}" is too large. Maximum file size is 10MB.`);
                    return;
                }

                // Check for duplicates
                const isDuplicate = uploadedPhotos.some(photo => 
                    photo.name === file.name && photo.size === file.size
                );

                if (isDuplicate) {
                    alert(`"${file.name}" is already uploaded.`);
                    return;
                }

                // Add to uploaded photos array
                const photoId = ++photoIdCounter;
                uploadedPhotos.push({
                    id: photoId,
                    file: file,
                    name: file.name,
                    size: file.size
                });

                // Create preview
                createPhotoPreview(file, photoId);
            });

            updatePhotoCount();
            updateFileInput();
            
            // Show "Add More" button if photos exist
            if (uploadedPhotos.length > 0) {
                addMoreBtn.style.display = 'flex';
            }
        }

        // Create photo preview with thumbnail
        function createPhotoPreview(file, photoId) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const previewDiv = document.createElement('div');
                previewDiv.className = 'photo-preview';
                previewDiv.setAttribute('data-photo-id', photoId);
                
                previewDiv.innerHTML = `
                    <img src="${e.target.result}" alt="${file.name}" loading="lazy">
                    <button type="button" class="photo-remove" onclick="removePhoto(${photoId})" title="Remove photo">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="photo-name">${file.name}</div>
                `;
                
                previewContainer.appendChild(previewDiv);
            };
            
            reader.readAsDataURL(file);
        }

        // Remove photo function
        function removePhoto(photoId) {
            // Remove from array
            uploadedPhotos = uploadedPhotos.filter(photo => photo.id !== photoId);
            
            // Remove preview element
            const previewElement = document.querySelector(`[data-photo-id="${photoId}"]`);
            if (previewElement) {
                previewElement.remove();
            }
            
            updatePhotoCount();
            updateFileInput();
            
            // Hide "Add More" button if no photos
            if (uploadedPhotos.length === 0) {
                addMoreBtn.style.display = 'none';
            }
        }

        // Update photo count display
        function updatePhotoCount() {
            const count = uploadedPhotos.length;
            photoCount.textContent = `${count} photo${count !== 1 ? 's' : ''} selected`;
        }

        // Update file input with current photos (for form submission)
        function updateFileInput() {
            const dataTransfer = new DataTransfer();
            uploadedPhotos.forEach(photo => {
                dataTransfer.items.add(photo.file);
            });
            photoInput.files = dataTransfer.files;
        }

        // Enhanced form submission with photo validation
        document.getElementById('sellForm').addEventListener('submit', function(e) {
            // Basic front-end validation: ensure at least one photo
            if (uploadedPhotos.length === 0) {
                e.preventDefault();
                let err = document.getElementById('errorMessage');
                if (!err) {
                    err = document.createElement('div');
                    err.id = 'errorMessage';
                    err.className = 'error-message';
                    this.parentNode.insertBefore(err, this);
                }
                err.textContent = 'Please upload at least one photo of your manga collection.';
                err.style.display = 'block';
                err.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            // Ensure the file input actually contains the selected photos
            updateFileInput();

            // --- Fix: Add hidden fields for backend compatibility ---
            // Remove any previous hidden fields
            this.querySelectorAll('.auto-hidden').forEach(el => el.remove());

            // Gather all manga sets
            const sets = document.querySelectorAll('.manga-set');
            let numItems = sets.length;
            let setTitles = [], setVolumes = [], setConditions = [], setExpectedPrices = [];
            let overallCondition = '';
            sets.forEach((set, idx) => {
                const n = idx + 1;
                const title = set.querySelector(`[name="sets[${n}][title]"]`)?.value || '';
                const volumes = set.querySelector(`[name="sets[${n}][volumes]"]`)?.value || '';
                const condition = set.querySelector(`[name="sets[${n}][condition]"]`)?.value || '';
                const price = set.querySelector(`[name="sets[${n}][asking_price]"]`)?.value || '';
                setTitles.push(title);
                setVolumes.push(volumes);
                setConditions.push(condition);
                setExpectedPrices.push(price);
                if (idx === 0) overallCondition = condition;
            });
            // Add num_items
            const numItemsInput = document.createElement('input');
            numItemsInput.type = 'hidden';
            numItemsInput.name = 'num_items';
            numItemsInput.value = numItems;
            numItemsInput.className = 'auto-hidden';
            this.appendChild(numItemsInput);
            // Add overall_condition
            const overallCondInput = document.createElement('input');
            overallCondInput.type = 'hidden';
            overallCondInput.name = 'overall_condition';
            overallCondInput.value = overallCondition;
            overallCondInput.className = 'auto-hidden';
            this.appendChild(overallCondInput);
            // Add set_title[]
            setTitles.forEach(val => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'set_title[]';
                input.value = val;
                input.className = 'auto-hidden';
                this.appendChild(input);
            });
            // Add set_volumes[]
            setVolumes.forEach(val => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'set_volumes[]';
                input.value = val;
                input.className = 'auto-hidden';
                this.appendChild(input);
            });
            // Add set_condition[]
            setConditions.forEach(val => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'set_condition[]';
                input.value = val;
                input.className = 'auto-hidden';
                this.appendChild(input);
            });
            // Add set_expected_price[]
            setExpectedPrices.forEach(val => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'set_expected_price[]';
                input.value = val;
                input.className = 'auto-hidden';
                this.appendChild(input);
            });

            // Show processing message
            let processingMsg = document.getElementById('processingMessage');
            if (!processingMsg) {
                processingMsg = document.createElement('div');
                processingMsg.id = 'processingMessage';
                processingMsg.className = 'success-message';
                processingMsg.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Thank you! Your submission is being processed. This may take a moment if you uploaded photos.';
                this.parentNode.insertBefore(processingMsg, this);
            } else {
                processingMsg.style.display = 'block';
            }
            // Fade/disable the form
            this.style.opacity = '0.5';
            Array.from(this.elements).forEach(el => el.disabled = true);
            // Allow native form submission
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
        });

        // Make removePhoto function global
        window.removePhoto = removePhoto;

        // Mobile menu toggle functionality
        document.getElementById('mobileMenuToggle').addEventListener('click', function() {
            const nav = document.getElementById('mobileNav');
            const icon = this.querySelector('i');
            
            nav.classList.toggle('active');
            
            // Toggle hamburger/close icon
            if (nav.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });

        // Close mobile menu when clicking on a link
        document.querySelectorAll('#mobileNav a').forEach(link => {
            link.addEventListener('click', function() {
                const nav = document.getElementById('mobileNav');
                const toggle = document.getElementById('mobileMenuToggle');
                const icon = toggle.querySelector('i');
                
                nav.classList.remove('active');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            });
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            const nav = document.getElementById('mobileNav');
            const toggle = document.getElementById('mobileMenuToggle');
            const icon = toggle.querySelector('i');
            
            if (!nav.contains(e.target) && !toggle.contains(e.target)) {
                nav.classList.remove('active');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    </script>
    <script src="../assets/js/mobile-nav.js"></script>
</body>
</html> 