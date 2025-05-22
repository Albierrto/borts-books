<?php
// Start session for cart functionality
session_start();

// Set page title
$pageTitle = "Welcome to Bort's Books";
$currentPage = "home";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Manga Store</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="index.php" class="logo">Bort's <span>Books</span></a>
            <nav>
                <ul>
                    <li><a href="/borts-books/index.php" <?php echo $currentPage === 'home' ? 'class="active"' : ''; ?>>Home</a></li>
                    <li><a href="/borts-books/pages/shop.php" <?php echo $currentPage === 'shop' ? 'class="active"' : ''; ?>>Shop</a></li>
                    <li><a href="/borts-books/pages/collections.php" <?php echo $currentPage === 'collections' ? 'class="active"' : ''; ?>>Collections</a></li>
                    <li><a href="/borts-books/pages/sell.php" <?php echo $currentPage === 'sell' ? 'class="active"' : ''; ?>>Sell Manga</a></li>
                    <li><a href="/borts-books/pages/about.php" <?php echo $currentPage === 'about' ? 'class="active"' : ''; ?>>About</a></li>
                </ul>
            </nav>
            <div class="search-cart">
                <a href="search.php" title="Search"><i class="fas fa-search"></i></a>
                <a href="cart.php" title="Shopping Cart" class="cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count">0</span>
                </a>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="container hero-content">
            <h1>Welcome to Bort's Books</h1>
            <p>Your premier destination for manga collections. Browse our extensive catalog or sell your collection for top dollar.</p>
            <div class="hero-buttons">
                <a href="/borts-books/pages/shop.php" class="btn">Shop Now</a>
                <a href="/borts-books/pages/sell.php" class="btn btn-outline">Sell Your Collection</a>
            </div>
        </div>
    </section>

    <section class="section featured">
        <div class="container">
            <div class="section-header">
                <h2>Featured Manga (Live from Kitsu API)</h2>
                <p>These covers are fetched in real-time from the Kitsu API</p>
            </div>
            <div id="kitsu-manga-covers" class="manga-grid"></div>
        </div>
    </section>

    <section class="sell-collection">
        <div class="container">
            <h2>Sell Your Manga Collection</h2>
            <p>Looking to declutter or upgrade your collection? We offer competitive prices for manga collections of all sizes. From single volumes to entire series, we're interested in what you have to offer.</p>
            <a href="/borts-books/pages/sell.php" class="btn">Get Started</a>
        </div>
    </section>

    <footer>
        <div class="container footer-container">
            <div class="footer-section">
                <h3>Bort's Books</h3>
                <p>Your trusted source for manga collections since 2023.</p>
            </div>

            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="/borts-books/index.php" <?php echo $currentPage === 'home' ? 'class="active"' : ''; ?>>Home</a></li>
                    <li><a href="/borts-books/pages/shop.php" <?php echo $currentPage === 'shop' ? 'class="active"' : ''; ?>>Shop</a></li>
                    <li><a href="/borts-books/pages/sell.php" <?php echo $currentPage === 'sell' ? 'class="active"' : ''; ?>>Sell Manga</a></li>
                    <li><a href="/borts-books/pages/about.php" <?php echo $currentPage === 'about' ? 'class="active"' : ''; ?>>About</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Help</h3>
                <ul>
                    <li><a href="/borts-books/pages/faq.php">FAQ</a></li>
                    <li><a href="/borts-books/pages/shipping.php">Shipping</a></li>
                    <li><a href="/borts-books/pages/returns.php">Returns</a></li>
                    <li><a href="/borts-books/pages/contact.php">Contact Us</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Contact</h3>
                <ul>
                    <li><i class="fas fa-envelope"></i> info@bortsbooks.com</li>
                    <li><i class="fas fa-phone"></i> (123) 456-7890</li>
                    <li><i class="fas fa-map-marker-alt"></i> 123 Manga St, Anime City, AC 12345</li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom container">
            <p>&copy; <?php echo date('Y'); ?> Bort's Books. All rights reserved.</p>
        </div>
    </footer>
    <script>
    const mangaTitles = [
      "One Piece",
      "Demon Slayer: Kimetsu no Yaiba",
      "Chainsaw Man",
      "Jujutsu Kaisen"
    ];

    async function fetchKitsuMangaCover(title) {
      const url = `https://kitsu.io/api/edge/manga?filter[text]=${encodeURIComponent(title)}&page[limit]=1`;
      const res = await fetch(url, {
        headers: {
          'Accept': 'application/vnd.api+json',
          'Content-Type': 'application/vnd.api+json'
        }
      });
      const data = await res.json();
      if (data.data && data.data.length > 0) {
        return data.data[0];
      }
      return null;
    }

    async function renderKitsuMangaCovers() {
      const container = document.getElementById('kitsu-manga-covers');
      container.innerHTML = '';
      for (const title of mangaTitles) {
        const manga = await fetchKitsuMangaCover(title);
        if (manga) {
          const attributes = manga.attributes;
          container.innerHTML += `
            <div class="manga-card">
              <div class="manga-img">
                <img src="${attributes.posterImage && attributes.posterImage.medium ? attributes.posterImage.medium : ''}" alt="${attributes.canonicalTitle}">
              </div>
              <div class="manga-info">
                <h3>${attributes.canonicalTitle}</h3>
                <p>${attributes.titles && attributes.titles.en_jp ? attributes.titles.en_jp : ''}</p>
              </div>
            </div>
          `;
        }
      }
    }
    renderKitsuMangaCovers();
    </script>
</body>
</html>