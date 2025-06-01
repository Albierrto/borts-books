<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = "Sell Your Manga Collection - Get Paid Today";
$currentPage = "sell";
require_once '../includes/db.php';

// Same form processing logic as original
$successMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim(strip_tags($_POST['full_name'] ?? ''));
    $email = trim(strip_tags($_POST['email'] ?? ''));
    $phone = trim(strip_tags($_POST['phone'] ?? ''));
    $zip_code = trim(strip_tags($_POST['zip_code'] ?? ''));
    $num_items = intval($_POST['num_items'] ?? 0);
    $overall_condition = trim(strip_tags($_POST['overall_condition'] ?? ''));
    $collection_description = trim(strip_tags($_POST['collection_description'] ?? ''));
    $preferred_payment = trim(strip_tags($_POST['preferred_payment'] ?? ''));
    
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
    
    if (count($photo_paths) > 0) {
        $stmt = $db->prepare('INSERT INTO sell_submissions (full_name, email, phone, zip_code, num_items, overall_condition, collection_description, preferred_payment, photo_paths) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$full_name, $email, $phone, $zip_code, $num_items, $overall_condition, $collection_description, $preferred_payment, $photo_paths_json]);
        $successMsg = '🎉 QUOTE SUBMITTED! Check your email in the next hour for your cash offer!';
    } else {
        $successMsg = '<span style="color:#b71c1c;">📸 Photos required to process your quote request.</span>';
    }
}

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
    <meta name="description" content="URGENT: Limited-time offer! Sell your manga collection today for INSTANT CASH. We're buying NOW at premium prices. Get your quote in 60 minutes!">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='grad' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%23667eea;stop-opacity:1' /%3E%3Cstop offset='100%25' style='stop-color:%23764ba2;stop-opacity:1' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='15' fill='url(%23grad)'/%3E%3Cpath d='M25 20h50c2.5 0 4.5 2 4.5 4.5v51c0 2.5-2 4.5-4.5 4.5H25c-2.5 0-4.5-2-4.5-4.5v-51c0-2.5 2-4.5 4.5-4.5z' fill='white'/%3E%3Cpath d='M30 30h40v5H30z' fill='%23667eea'/%3E%3Cpath d='M30 40h35v3H30z' fill='%23999'/%3E%3Cpath d='M30 47h30v3H30z' fill='%23999'/%3E%3Cpath d='M30 54h25v3H30z' fill='%23999'/%3E%3Cpath d='M30 61h20v3H30z' fill='%23999'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #dc3545;
            --primary-dark: #c82333;
            --secondary: #ffc107;
            --success: #28a745;
            --urgent: #ff4757;
            --gold: #f39c12;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #000;
            overflow-x: hidden;
        }

        /* URGENT BANNER */
        .urgent-banner {
            background: linear-gradient(90deg, var(--urgent) 0%, #ff3742 50%, var(--urgent) 100%);
            color: white;
            padding: 15px 0;
            text-align: center;
            font-weight: 700;
            font-size: 1.1rem;
            animation: pulse 2s infinite;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(255, 71, 87, 0.4);
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        .urgent-banner .blink {
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* HERO SECTION - SCARCITY FOCUSED */
        .hero-section {
            background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);
            color: white;
            padding: 80px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="money" width="30" height="30" patternUnits="userSpaceOnUse"><text x="15" y="20" text-anchor="middle" fill="rgba(255,199,7,0.1)" font-size="20">💰</text></pattern></defs><rect width="100" height="100" fill="url(%23money)"/></svg>');
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 900px;
            margin: 0 auto;
        }

        .countdown-timer {
            background: var(--urgent);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(255, 71, 87, 0.3);
            animation: shake 3s infinite;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .timer-text {
            font-size: 1.5rem;
            font-weight: 900;
            margin-bottom: 15px;
        }

        .timer-display {
            font-size: 3rem;
            font-weight: 900;
            font-family: 'Courier New', monospace;
            letter-spacing: 3px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .hero-headline {
            font-size: clamp(3rem, 6vw, 5rem);
            font-weight: 900;
            margin-bottom: 25px;
            background: linear-gradient(45deg, var(--secondary), var(--gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            line-height: 1;
        }

        .hero-subheadline {
            font-size: clamp(1.3rem, 3vw, 1.8rem);
            margin-bottom: 40px;
            color: #ffc107;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .money-back {
            background: var(--success);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 700;
            margin: 20px 0;
            display: inline-block;
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
        }

        .hero-cta {
            display: inline-flex;
            align-items: center;
            gap: 15px;
            background: linear-gradient(45deg, var(--urgent), #ff6b7a);
            color: white;
            padding: 25px 50px;
            border-radius: 50px;
            font-size: 1.4rem;
            font-weight: 900;
            text-decoration: none;
            box-shadow: 0 15px 40px rgba(255, 71, 87, 0.4);
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: 3px solid var(--secondary);
            animation: glow 2s infinite;
        }

        @keyframes glow {
            0%, 100% { box-shadow: 0 15px 40px rgba(255, 71, 87, 0.4); }
            50% { box-shadow: 0 15px 40px rgba(255, 71, 87, 0.8), 0 0 50px rgba(255, 199, 7, 0.5); }
        }

        .hero-cta:hover {
            transform: translateY(-5px) scale(1.05);
        }

        /* PRICE SHOCK SECTION */
        .price-shock {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--gold) 100%);
            color: #000;
            padding: 60px 20px;
            text-align: center;
            position: relative;
        }

        .price-shock::before {
            content: '🔥';
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 4rem;
            animation: bounce 1s infinite;
        }

        .price-shock::after {
            content: '💸';
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 4rem;
            animation: bounce 1s infinite 0.5s;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .shock-headline {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 900;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            text-transform: uppercase;
        }

        .price-examples {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin: 40px 0;
            max-width: 1000px;
            margin-left: auto;
            margin-right: auto;
        }

        .price-card {
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 30px;
            border-radius: 20px;
            transform: rotateZ(-2deg);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }

        .price-card:nth-child(2) {
            transform: rotateZ(2deg);
        }

        .price-card:nth-child(3) {
            transform: rotateZ(-1deg);
        }

        .price-card:hover {
            transform: rotateZ(0deg) translateY(-10px);
        }

        .price-card h3 {
            font-size: 1.4rem;
            margin-bottom: 15px;
            color: var(--secondary);
        }

        .price-amount {
            font-size: 2.5rem;
            font-weight: 900;
            color: #00ff00;
            text-shadow: 0 0 20px #00ff00;
        }

        /* LIMITED TIME SECTION */
        .limited-time {
            background: #fff;
            padding: 60px 20px;
            text-align: center;
            border-top: 5px solid var(--urgent);
            border-bottom: 5px solid var(--urgent);
        }

        .limited-headline {
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--urgent);
            margin-bottom: 30px;
            text-transform: uppercase;
        }

        .scarcity-boxes {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            max-width: 800px;
            margin: 0 auto;
        }

        .scarcity-box {
            background: linear-gradient(135deg, var(--urgent) 0%, #ff6b7a 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(255, 71, 87, 0.3);
            animation: wiggle 2s infinite;
        }

        @keyframes wiggle {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(1deg); }
            75% { transform: rotate(-1deg); }
        }

        .scarcity-number {
            font-size: 3rem;
            font-weight: 900;
            display: block;
        }

        .scarcity-text {
            font-size: 1.1rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        /* FORM SECTION - URGENCY FOCUSED */
        .form-section {
            background: linear-gradient(135deg, #1a1a1a 0%, #000 100%);
            color: white;
            padding: 80px 20px;
        }

        .form-container {
            max-width: 700px;
            margin: 0 auto;
            background: rgba(255,255,255,0.05);
            padding: 50px;
            border-radius: 25px;
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255, 199, 7, 0.3);
            box-shadow: 0 25px 60px rgba(0,0,0,0.5);
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .form-title {
            font-size: 2.8rem;
            font-weight: 900;
            background: linear-gradient(45deg, var(--secondary), var(--gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .form-subtitle {
            font-size: 1.3rem;
            color: var(--secondary);
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--secondary);
            font-size: 1.2rem;
            text-transform: uppercase;
        }

        .form-control {
            width: 100%;
            padding: 18px 25px;
            border: 3px solid rgba(255, 199, 7, 0.3);
            border-radius: 15px;
            font-size: 1.1rem;
            background: rgba(255,255,255,0.1);
            color: white;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .form-control::placeholder {
            color: rgba(255,255,255,0.7);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 5px rgba(255, 199, 7, 0.2);
            transform: translateY(-3px);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        /* PHOTO UPLOAD - URGENT STYLE */
        .upload-area {
            display: block;
            width: 100%;
            padding: 50px;
            border: 4px dashed var(--secondary);
            border-radius: 20px;
            text-align: center;
            cursor: pointer;
            background: linear-gradient(135deg, rgba(255, 199, 7, 0.1) 0%, rgba(243, 156, 18, 0.1) 100%);
            transition: all 0.3s ease;
            animation: pulse-border 2s infinite;
        }

        @keyframes pulse-border {
            0%, 100% { border-color: var(--secondary); }
            50% { border-color: var(--gold); }
        }

        .upload-area:hover {
            background: linear-gradient(135deg, rgba(255, 199, 7, 0.2) 0%, rgba(243, 156, 18, 0.2) 100%);
            transform: translateY(-5px);
        }

        .upload-icon {
            font-size: 5rem;
            color: var(--secondary);
            margin-bottom: 20px;
            animation: float 3s infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .upload-text {
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--secondary);
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .upload-subtext {
            color: rgba(255,255,255,0.8);
            font-size: 1.1rem;
            font-weight: 600;
        }

        /* SUBMIT BUTTON - HIGH URGENCY */
        .submit-section {
            text-align: center;
            padding: 50px 0;
        }

        .submit-btn {
            display: inline-flex;
            align-items: center;
            gap: 20px;
            background: linear-gradient(45deg, var(--urgent), #ff6b7a, var(--urgent));
            background-size: 200% 200%;
            color: white;
            padding: 25px 60px;
            border: none;
            border-radius: 50px;
            font-size: 1.5rem;
            font-weight: 900;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 2px;
            box-shadow: 0 20px 50px rgba(255, 71, 87, 0.4);
            transition: all 0.3s ease;
            animation: pulse-gradient 2s infinite;
            border: 4px solid var(--secondary);
        }

        @keyframes pulse-gradient {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .submit-btn:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 25px 60px rgba(255, 71, 87, 0.6);
        }

        .submit-btn .btn-icon {
            font-size: 1.8rem;
            animation: rocket 1s infinite alternate;
        }

        @keyframes rocket {
            0% { transform: translateY(0); }
            100% { transform: translateY(-5px); }
        }

        /* SUCCESS MESSAGE */
        .success-message {
            background: linear-gradient(135deg, var(--success) 0%, #20c997 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin: 30px auto;
            font-size: 1.4rem;
            font-weight: 700;
            max-width: 600px;
            box-shadow: 0 15px 40px rgba(40, 167, 69, 0.3);
            display: none;
        }

        .success-message.show {
            display: block;
            animation: success-bounce 0.6s ease-out;
        }

        @keyframes success-bounce {
            0% { transform: scale(0) rotate(360deg); }
            100% { transform: scale(1) rotate(0deg); }
        }

        /* MOBILE RESPONSIVE */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .hero-section,
            .price-shock,
            .limited-time,
            .form-section {
                padding: 40px 15px;
            }
            
            .form-container {
                padding: 30px 20px;
            }
        }

        /* Loading states */
        .loading {
            display: none;
        }

        .submit-btn.loading .loading {
            display: inline;
        }

        .submit-btn.loading .btn-text {
            display: none;
        }
    </style>
</head>
<body>
    <!-- URGENT BANNER -->
    <div class="urgent-banner">
        ⚡ <span class="blink">LIMITED TIME</span> ⚡ We're paying PREMIUM prices this week only! Don't miss out!
    </div>

    <!-- Success Message -->
    <?php if ($successMsg): ?>
    <div class="success-message show">
        <?php echo $successMsg; ?>
    </div>
    <?php endif; ?>

    <!-- HERO SECTION -->
    <section class="hero-section">
        <div class="hero-content">
            <div class="countdown-timer">
                <div class="timer-text">⏰ OFFER EXPIRES IN:</div>
                <div class="timer-display" id="countdown">11:47:32</div>
            </div>
            
            <h1 class="hero-headline">INSTANT CASH FOR MANGA!</h1>
            <p class="hero-subheadline">🔥 We're buying NOW at 90% market value! 🔥</p>
            
            <div class="money-back">
                💰 CASH IN YOUR POCKET WITHIN 24 HOURS! 💰
            </div>
            
            <a href="#urgent-form" class="hero-cta">
                <i class="fas fa-rocket"></i>
                GET PAID NOW!
            </a>
        </div>
    </section>

    <!-- PRICE SHOCK SECTION -->
    <section class="price-shock">
        <h2 class="shock-headline">🚨 REAL PAYOUTS THIS WEEK! 🚨</h2>
        
        <div class="price-examples">
            <div class="price-card">
                <h3>📚 Sarah from Texas</h3>
                <div class="price-amount">$1,847</div>
                <p>Naruto + One Piece sets</p>
            </div>
            <div class="price-card">
                <h3>📚 Mike from California</h3>
                <div class="price-amount">$2,156</div>
                <p>Attack on Titan collection</p>
            </div>
            <div class="price-card">
                <h3>📚 Jessica from Florida</h3>
                <div class="price-amount">$987</div>
                <p>Mixed manga lot (200+ books)</p>
            </div>
        </div>
        
        <h3 style="font-size: 1.8rem; font-weight: 900; margin-top: 30px;">
            ⚡ YOUR COLLECTION COULD BE WORTH THOUSANDS! ⚡
        </h3>
    </section>

    <!-- LIMITED TIME SECTION -->
    <section class="limited-time">
        <h2 class="limited-headline">🚨 THIS WEEK ONLY! 🚨</h2>
        
        <div class="scarcity-boxes">
            <div class="scarcity-box">
                <span class="scarcity-number">90%</span>
                <span class="scarcity-text">Market Value</span>
            </div>
            <div class="scarcity-box">
                <span class="scarcity-number">1HR</span>
                <span class="scarcity-text">Quote Time</span>
            </div>
            <div class="scarcity-box">
                <span class="scarcity-number">24HR</span>
                <span class="scarcity-text">Payment</span>
            </div>
            <div class="scarcity-box">
                <span class="scarcity-number">$0</span>
                <span class="scarcity-text">Shipping Cost</span>
            </div>
        </div>
        
        <p style="font-size: 1.4rem; font-weight: 700; color: var(--urgent); margin-top: 30px;">
            ⏰ PRICES RETURN TO NORMAL MONDAY! DON'T WAIT!
        </p>
    </section>

    <!-- FORM SECTION -->
    <section class="form-section" id="urgent-form">
        <div class="form-container">
            <div class="form-header">
                <h2 class="form-title">🚀 SUBMIT NOW 🚀</h2>
                <p class="form-subtitle">Get your cash offer in 60 minutes!</p>
            </div>

            <form method="POST" enctype="multipart/form-data" id="urgentForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">FULL NAME 👤</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required placeholder="Enter full name">
                    </div>
                    <div class="form-group">
                        <label for="email">EMAIL ADDRESS 📧</label>
                        <input type="email" id="email" name="email" class="form-control" required placeholder="your.email@example.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">PHONE NUMBER 📱</label>
                        <input type="tel" id="phone" name="phone" class="form-control" placeholder="(555) 123-4567">
                    </div>
                    <div class="form-group">
                        <label for="zip_code">ZIP CODE 📍</label>
                        <input type="text" id="zip_code" name="zip_code" class="form-control" required placeholder="12345">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="num_items">NUMBER OF BOOKS 📚</label>
                        <select id="num_items" name="num_items" class="form-control" required>
                            <option value="">How many books?</option>
                            <option value="1-25">1-25 books</option>
                            <option value="26-50">26-50 books</option>
                            <option value="51-100">51-100 books</option>
                            <option value="101-200">101-200 books</option>
                            <option value="200+">200+ books (💰💰💰)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="overall_condition">CONDITION 🔍</label>
                        <select id="overall_condition" name="overall_condition" class="form-control" required>
                            <option value="">Select condition</option>
                            <option value="like_new">Like New (💎 Top Dollar!)</option>
                            <option value="very_good">Very Good (💰 Great Price!)</option>
                            <option value="good">Good (💵 Good Price!)</option>
                            <option value="fair">Fair (💴 Fair Price!)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="collection_description">LIST YOUR MANGA 📝</label>
                    <textarea id="collection_description" name="collection_description" class="form-control" rows="4" required placeholder="Example: Naruto 1-72, One Piece 1-95, Attack on Titan complete, Dragon Ball Z 1-26, etc. BE SPECIFIC for BEST PRICES!"></textarea>
                </div>

                <div class="form-group">
                    <label for="preferred_payment">PAYMENT METHOD 💳</label>
                    <select id="preferred_payment" name="preferred_payment" class="form-control" required>
                        <option value="">How do you want paid?</option>
                        <option value="paypal">💸 PayPal (INSTANT!)</option>
                        <option value="zelle">⚡ Zelle (INSTANT!)</option>
                        <option value="cashapp">🚀 CashApp (INSTANT!)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="collection_photos">UPLOAD PHOTOS 📸</label>
                    <label for="collection_photos" class="upload-area">
                        <div class="upload-icon">📷</div>
                        <div class="upload-text">PHOTOS = HIGHER OFFERS!</div>
                        <div class="upload-subtext">More photos = More money in your pocket!</div>
                    </label>
                    <input type="file" id="collection_photos" name="collection_photos[]" multiple accept="image/*" style="display: none;" required>
                    <div id="photo-preview" style="margin-top: 15px;"></div>
                </div>

                <div class="submit-section">
                    <button type="submit" class="submit-btn" id="submitBtn">
                        <i class="fas fa-rocket btn-icon"></i>
                        <span class="btn-text">💰 GET MY CASH OFFER! 💰</span>
                        <span class="loading">
                            <i class="fas fa-spinner fa-spin"></i>
                            PROCESSING...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </section>

    <script>
        // Countdown timer
        function updateCountdown() {
            let hours = Math.floor(Math.random() * 12) + 6;
            let minutes = Math.floor(Math.random() * 60);
            let seconds = Math.floor(Math.random() * 60);
            
            setInterval(() => {
                seconds--;
                if (seconds < 0) {
                    seconds = 59;
                    minutes--;
                    if (minutes < 0) {
                        minutes = 59;
                        hours--;
                        if (hours < 0) {
                            hours = 23;
                        }
                    }
                }
                
                document.getElementById('countdown').textContent = 
                    `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }, 1000);
        }
        
        updateCountdown();

        // Photo preview
        document.getElementById('collection_photos').addEventListener('change', function(e) {
            const preview = document.getElementById('photo-preview');
            preview.innerHTML = '';
            
            for (let i = 0; i < e.target.files.length; i++) {
                const file = e.target.files[i];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.cssText = 'width: 100px; height: 100px; object-fit: cover; border-radius: 12px; margin: 8px; border: 3px solid #ffc107; box-shadow: 0 5px 15px rgba(0,0,0,0.3);';
                    preview.appendChild(img);
                };
                
                reader.readAsDataURL(file);
            }
            
            if (e.target.files.length > 0) {
                preview.innerHTML = `<p style="color: #ffc107; font-weight: 900; margin-top: 15px; font-size: 1.2rem;">🔥 ${e.target.files.length} PHOTOS UPLOADED! Higher offer incoming! 🔥</p>` + preview.innerHTML;
            }
        });

        // Form submission
        document.getElementById('urgentForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        });

        // Smooth scroll
        document.querySelector('.hero-cta').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('urgent-form').scrollIntoView({
                behavior: 'smooth'
            });
        });

        // Auto-hide success message
        setTimeout(function() {
            const successMsg = document.querySelector('.success-message.show');
            if (successMsg) {
                successMsg.style.opacity = '0';
                setTimeout(() => successMsg.remove(), 500);
            }
        }, 8000);
    </script>
</body>
</html> 