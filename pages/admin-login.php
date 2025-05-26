<?php
session_start();

// Secure admin credentials with hashed password
$ADMIN_USER = 'admin';
$ADMIN_PASS_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // "password"

// If you want to use your own password, uncomment this line and run it once:
// echo password_hash('your_new_password', PASSWORD_DEFAULT) . "\n"; exit;

if (isset($_GET['logout'])) {
    // Secure logout
    session_destroy();
    session_start();
    header('Location: admin-login.php');
    exit;
}

if (isset($_POST['username'], $_POST['password'])) {
    // Rate limiting (simple version)
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt'] = time();
    }
    
    // Reset attempts after 15 minutes
    if (time() - $_SESSION['last_attempt'] > 900) {
        $_SESSION['login_attempts'] = 0;
    }
    
    if ($_SESSION['login_attempts'] >= 5) {
        $error = 'Too many failed attempts. Please try again in 15 minutes.';
    } else {
        if ($_POST['username'] === $ADMIN_USER && password_verify($_POST['password'], $ADMIN_PASS_HASH)) {
            // Successful login
            session_regenerate_id(true); // Prevent session fixation
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = $ADMIN_USER;
            $_SESSION['login_time'] = time();
            unset($_SESSION['login_attempts']);
            
            header('Location: admin-dashboard.php');
            exit;
        } else {
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt'] = time();
            $error = 'Invalid username or password.';
        }
    }
}

$pageTitle = "Admin Login";
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
    <style>
        body { background: #f7f7fa; }
        .admin-container {
            max-width: 400px;
            margin: 4rem auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(35,41,70,0.08);
            padding: 2.5rem;
        }
        .admin-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            text-align: center;
            color: #232946;
        }
        .admin-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #232946;
        }
        .admin-form input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 1.2rem;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        .admin-form input:focus {
            outline: none;
            border-color: #eebbc3;
        }
        .admin-form button {
            width: 100%;
            padding: 0.9rem;
            background: #eebbc3;
            color: #232946;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .admin-form button:hover {
            background: #232946;
            color: #fff;
        }
        .admin-error {
            color: #e63946;
            margin-bottom: 1rem;
            text-align: center;
            padding: 0.8rem;
            background: #ffe0e0;
            border: 1px solid #f8d7da;
            border-radius: 8px;
        }
        .security-note {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            border-radius: 0 8px 8px 0;
            font-size: 0.9rem;
            color: #1976d2;
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
        </div>
    </header>

    <div class="admin-container">
        <div class="admin-title">Admin Login</div>
        <?php if (isset($_GET['timeout'])): ?>
            <div class="admin-error">Your session has expired for security. Please login again.</div>
        <?php elseif (isset($error)): ?>
            <div class="admin-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form class="admin-form" method="POST">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autocomplete="username">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
            <button type="submit">Login</button>
        </form>
        
        <div class="security-note">
            <strong>Security:</strong> Admin sessions expire after 2 hours. After 5 failed attempts, the account will be locked for 15 minutes.
        </div>
    </div>
</body>
</html> 