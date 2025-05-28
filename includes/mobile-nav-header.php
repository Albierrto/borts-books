<?php
/**
 * Mobile Navigation Header Include
 * Provides consistent header structure across all pages
 */

// Determine the correct path level based on current directory
$level = '';
if (strpos($_SERVER['REQUEST_URI'], '/pages/') !== false) {
    $level = '../';
}

// Ensure cart count is available
if (!isset($cart_count)) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    $cart_count = count($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Bort\'s Books'; ?> - Bort's Books</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Permanent+Marker&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $level; ?>assets/css/styles.css">
    <link rel="stylesheet" href="<?php echo $level; ?>assets/css/mobile-nav.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php if (isset($additionalCSS)) echo $additionalCSS; ?>
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="<?php echo $level; ?>index.php" class="logo">Bort's <span>Books</span></a>
            <nav class="main-nav">
                <a href="<?php echo $level; ?>index.php" <?php echo (isset($currentPage) && $currentPage === 'home') ? 'class="active"' : ''; ?>>Home</a>
                <a href="<?php echo $level; ?>pages/shop.php" <?php echo (isset($currentPage) && $currentPage === 'shop') ? 'class="active"' : ''; ?>>Shop</a>
                <a href="<?php echo $level; ?>pages/track-order.php" <?php echo (isset($currentPage) && $currentPage === 'track') ? 'class="active"' : ''; ?>>Track Order</a>
                <a href="<?php echo $level; ?>pages/sell.php" <?php echo (isset($currentPage) && $currentPage === 'sell') ? 'class="active"' : ''; ?>>Sell Manga</a>
                <a href="<?php echo $level; ?>pages/about.php" <?php echo (isset($currentPage) && $currentPage === 'about') ? 'class="active"' : ''; ?>>About</a>
            </nav>
            <div class="search-cart">
                <a href="<?php echo $level; ?>pages/cart.php" class="cart-icon" title="Shopping Cart">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </a>
            </div>
        </div>
    </header>
</body>
</html> 