<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="<?php echo $level ?>assets/css/styles.css">
    <?php if (isset($pageCss)) { ?>
    <link rel="stylesheet" href="<?php echo $level ?>assets/css/<?php echo $pageCss; ?>.css">
    <?php } ?>
</head>
<body>
    <header>
        <div class="container header-container">
            <div class="logo">Bort's <span>Books</span></div>
            <nav>
                <ul>
                    <li><a href="/index.php">Home</a></li>
                    <li><a href="/pages/shop.php">Shop</a></li>
                    <li><a href="/pages/sell.php">Sell Manga</a></li>
                    <li><a href="/pages/ebay-import.php">Import from eBay</a></li>
                    <li><a href="/pages/about.php">About</a></li>
                </ul>
            </nav>
            <div class="search-cart">
                <a href="<?php echo $level ?>pages/search.php">🔍</a>
                <a href="<?php echo $level ?>pages/cart.php">🛒 
                    <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) { ?>
                    <span class="cart-count"><?php echo count($_SESSION['cart']); ?></span>
                    <?php } ?>
                </a>
            </div>
        </div>
    </header>