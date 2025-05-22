<?php
session_start();

// Simple hardcoded credentials (change these for production)
$ADMIN_USER = 'admin';
$ADMIN_PASS = 'bortbooks123';

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header('Location: admin-login.php');
    exit;
}

if (isset($_POST['username'], $_POST['password'])) {
    if ($_POST['username'] === $ADMIN_USER && $_POST['password'] === $ADMIN_PASS) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
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
        .admin-container {
            max-width: 400px;
            margin: 4rem auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 2rem;
        }
        .admin-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .admin-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .admin-form input {
            width: 100%;
            padding: 0.7rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 1.2rem;
            font-size: 1rem;
        }
        .admin-form button {
            width: 100%;
            padding: 0.8rem;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
        }
        .admin-form button:hover {
            background: var(--primary-dark);
        }
        .admin-error {
            color: #b71c1c;
            margin-bottom: 1rem;
            text-align: center;
            padding: 0.8rem;
            background: #fdecea;
            border-radius: 4px;
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
                    <li><a href="/pages/sell.php">Sell Manga</a></li>
                    <li><a href="/pages/about.php">About</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="admin-container">
        <div class="admin-title">Admin Login</div>
        <?php if (isset($error)): ?>
            <div class="admin-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form class="admin-form" method="POST">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html> 