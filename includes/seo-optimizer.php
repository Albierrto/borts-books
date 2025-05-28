<?php
/**
 * SEO Optimization System for Bort's Books
 * Comprehensive search engine optimization tools
 */

class SEOOptimizer {
    
    private static $site_name = "Bort's Books";
    private static $site_description = "Premium manga collection and rare Japanese comics. Discover authentic manga books, collectibles, and exclusive editions at Bort's Books.";
    private static $default_keywords = "manga, japanese comics, anime books, manga collection, rare manga, manga store, japanese literature";
    
    public static function generateMetaTags($page_data = []) {
        $title = isset($page_data['title']) ? $page_data['title'] . ' | ' . self::$site_name : self::$site_name . ' - Premium Manga Collection';
        $description = isset($page_data['description']) ? $page_data['description'] : self::$site_description;
        $keywords = isset($page_data['keywords']) ? $page_data['keywords'] . ', ' . self::$default_keywords : self::$default_keywords;
        $image = isset($page_data['image']) ? $page_data['image'] : SITE_URL . '/assets/img/og-default.jpg';
        $url = isset($page_data['url']) ? $page_data['url'] : SITE_URL . $_SERVER['REQUEST_URI'];
        
        // Ensure title is under 60 characters
        if (strlen($title) > 60) {
            $title = substr($title, 0, 57) . '...';
        }
        
        // Ensure description is under 160 characters
        if (strlen($description) > 160) {
            $description = substr($description, 0, 157) . '...';
        }
        
        echo "<!-- SEO Meta Tags -->\n";
        echo "<title>" . htmlspecialchars($title) . "</title>\n";
        echo "<meta name=\"description\" content=\"" . htmlspecialchars($description) . "\">\n";
        echo "<meta name=\"keywords\" content=\"" . htmlspecialchars($keywords) . "\">\n";
        echo "<meta name=\"robots\" content=\"index, follow\">\n";
        echo "<link rel=\"canonical\" href=\"" . htmlspecialchars($url) . "\">\n";
        
        // Open Graph tags
        echo "\n<!-- Open Graph Meta Tags -->\n";
        echo "<meta property=\"og:title\" content=\"" . htmlspecialchars($title) . "\">\n";
        echo "<meta property=\"og:description\" content=\"" . htmlspecialchars($description) . "\">\n";
        echo "<meta property=\"og:image\" content=\"" . htmlspecialchars($image) . "\">\n";
        echo "<meta property=\"og:url\" content=\"" . htmlspecialchars($url) . "\">\n";
        echo "<meta property=\"og:type\" content=\"website\">\n";
        echo "<meta property=\"og:site_name\" content=\"" . self::$site_name . "\">\n";
        
        // Twitter Card tags
        echo "\n<!-- Twitter Card Meta Tags -->\n";
        echo "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
        echo "<meta name=\"twitter:title\" content=\"" . htmlspecialchars($title) . "\">\n";
        echo "<meta name=\"twitter:description\" content=\"" . htmlspecialchars($description) . "\">\n";
        echo "<meta name=\"twitter:image\" content=\"" . htmlspecialchars($image) . "\">\n";
    }
    
    public static function generateProductSchema($product) {
        $schema = [
            "@context" => "https://schema.org/",
            "@type" => "Product",
            "name" => $product['title'],
            "description" => $product['description'] ?? '',
            "image" => isset($product['image_url']) ? SITE_URL . '/' . $product['image_url'] : '',
            "brand" => [
                "@type" => "Brand",
                "name" => "Bort's Books"
            ],
            "offers" => [
                "@type" => "Offer",
                "price" => $product['price'],
                "priceCurrency" => "USD",
                "availability" => "https://schema.org/InStock",
                "seller" => [
                    "@type" => "Organization",
                    "name" => "Bort's Books"
                ]
            ]
        ];
        
        // Add aggregateRating if reviews exist
        if (isset($product['rating']) && isset($product['review_count'])) {
            $schema["aggregateRating"] = [
                "@type" => "AggregateRating",
                "ratingValue" => $product['rating'],
                "reviewCount" => $product['review_count']
            ];
        }
        
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
    }
    
    public static function generateOrganizationSchema() {
        $schema = [
            "@context" => "https://schema.org",
            "@type" => "Organization",
            "name" => self::$site_name,
            "description" => self::$site_description,
            "url" => SITE_URL,
            "logo" => SITE_URL . "/assets/img/logo.png",
            "contactPoint" => [
                "@type" => "ContactPoint",
                "email" => "bort@bortsbooks.com",
                "contactType" => "customer service",
                "availableLanguage" => "English"
            ],
            "sameAs" => [
                "https://facebook.com/bortsbooks",
                "https://twitter.com/bortsbooks",
                "https://instagram.com/bortsbooks"
            ]
        ];
        
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
    }
    
    public static function generateBreadcrumbSchema($breadcrumbs) {
        $listItems = [];
        
        foreach ($breadcrumbs as $index => $crumb) {
            $listItems[] = [
                "@type" => "ListItem",
                "position" => $index + 1,
                "name" => $crumb['name'],
                "item" => $crumb['url']
            ];
        }
        
        $schema = [
            "@context" => "https://schema.org",
            "@type" => "BreadcrumbList",
            "itemListElement" => $listItems
        ];
        
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
    }
    
    public static function generateSitemap($db) {
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Homepage
        $sitemap .= self::addSitemapUrl(SITE_URL, '1.0', 'daily');
        
        // Static pages
        $static_pages = [
            '/pages/shop.php' => ['priority' => '0.9', 'changefreq' => 'daily'],
            '/pages/about.php' => ['priority' => '0.7', 'changefreq' => 'monthly'],
            '/pages/contact.php' => ['priority' => '0.6', 'changefreq' => 'monthly'],
            '/pages/faq.php' => ['priority' => '0.5', 'changefreq' => 'monthly']
        ];
        
        foreach ($static_pages as $page => $settings) {
            $sitemap .= self::addSitemapUrl(SITE_URL . $page, $settings['priority'], $settings['changefreq']);
        }
        
        // Product pages
        $products = $db->query("SELECT id, title, updated_at FROM products ORDER BY updated_at DESC")->fetchAll();
        foreach ($products as $product) {
            $url = SITE_URL . '/pages/product.php?id=' . $product['id'];
            $lastmod = date('Y-m-d', strtotime($product['updated_at']));
            $sitemap .= self::addSitemapUrl($url, '0.8', 'weekly', $lastmod);
        }
        
        $sitemap .= '</urlset>';
        
        file_put_contents('../sitemap.xml', $sitemap);
        return true;
    }
    
    private static function addSitemapUrl($url, $priority, $changefreq, $lastmod = null) {
        $xml = "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($url) . "</loc>\n";
        if ($lastmod) {
            $xml .= "    <lastmod>$lastmod</lastmod>\n";
        }
        $xml .= "    <changefreq>$changefreq</changefreq>\n";
        $xml .= "    <priority>$priority</priority>\n";
        $xml .= "  </url>\n";
        
        return $xml;
    }
    
    public static function optimizeImages($imagePath, $alt_text = '') {
        if (!file_exists($imagePath)) {
            return '';
        }
        
        $optimized_path = PerformanceOptimizer::optimizeImage($imagePath);
        $webp_path = PerformanceOptimizer::generateWebP($imagePath);
        
        $html = '<picture>';
        
        if ($webp_path) {
            $html .= '<source srcset="' . $webp_path . '" type="image/webp">';
        }
        
        $html .= '<img src="' . ($optimized_path ?: $imagePath) . '" alt="' . htmlspecialchars($alt_text) . '" loading="lazy">';
        $html .= '</picture>';
        
        return $html;
    }
    
    public static function generateRobotsTxt() {
        $robots = "User-agent: *\n";
        $robots .= "Allow: /\n";
        $robots .= "Disallow: /admin/\n";
        $robots .= "Disallow: /includes/\n";
        $robots .= "Disallow: /vendor/\n";
        $robots .= "Disallow: /uploads/\n";
        $robots .= "Disallow: /cache/\n";
        $robots .= "\n";
        $robots .= "Sitemap: " . SITE_URL . "/sitemap.xml\n";
        
        file_put_contents('../robots.txt', $robots);
        return true;
    }
    
    public static function trackPageView($page_name, $user_id = null) {
        // Simple analytics tracking
        $log_data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'page' => $page_name,
            'user_id' => $user_id,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'direct'
        ];
        
        $log_file = '../logs/analytics.log';
        if (!file_exists(dirname($log_file))) {
            mkdir(dirname($log_file), 0755, true);
        }
        
        file_put_contents($log_file, json_encode($log_data) . "\n", FILE_APPEND);
    }
}

/**
 * Keyword Optimization Helper
 */
class KeywordOptimizer {
    
    private static $manga_keywords = [
        'manga', 'japanese comics', 'anime books', 'manga collection', 'rare manga',
        'vintage manga', 'collectible manga', 'manga series', 'japanese literature',
        'graphic novels', 'comic books', 'anime merchandise', 'otaku culture'
    ];
    
    public static function optimizeProductTitle($title, $category = '') {
        // Add relevant keywords naturally
        $optimized = $title;
        
        if (stripos($title, 'manga') === false && $category) {
            $optimized = $title . ' - ' . ucfirst($category) . ' Manga';
        }
        
        return $optimized;
    }
    
    public static function generateProductDescription($product) {
        $description = $product['description'] ?? '';
        
        if (strlen($description) < 150) {
            $keywords = self::getRelevantKeywords($product['title']);
            $description .= " This authentic Japanese manga features premium quality and is perfect for collectors and manga enthusiasts.";
            
            if (!empty($keywords)) {
                $description .= " Keywords: " . implode(', ', array_slice($keywords, 0, 3));
            }
        }
        
        return $description;
    }
    
    private static function getRelevantKeywords($title) {
        $relevant = [];
        $title_lower = strtolower($title);
        
        foreach (self::$manga_keywords as $keyword) {
            if (stripos($title_lower, $keyword) !== false || 
                similar_text($title_lower, $keyword) > 5) {
                $relevant[] = $keyword;
            }
        }
        
        return $relevant;
    }
    
    public static function generateAltText($product_title, $image_type = 'main') {
        $alt_text = $product_title;
        
        switch ($image_type) {
            case 'main':
                $alt_text .= ' - Front Cover';
                break;
            case 'back':
                $alt_text .= ' - Back Cover';
                break;
            case 'detail':
                $alt_text .= ' - Detail View';
                break;
        }
        
        $alt_text .= ' | Manga Book at Bort\'s Books';
        
        return $alt_text;
    }
}
?> 