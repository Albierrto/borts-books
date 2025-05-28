<?php
/**
 * Mobile Navigation Footer Include
 * Provides consistent footer structure and mobile navigation script
 */

// Determine the correct path level based on current directory
$level = '';
if (strpos($_SERVER['REQUEST_URI'], '/pages/') !== false) {
    $level = '../';
}
?>
    <footer>
        <div class="container footer-container">
            <div class="footer-section">
                <h3>Bort's Books</h3>
                <p>Your trusted source for manga collections since 2023.</p>
            </div>

            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="<?php echo $level; ?>index.php">Home</a></li>
                    <li><a href="<?php echo $level; ?>pages/shop.php">Shop</a></li>
                    <li><a href="<?php echo $level; ?>pages/track-order.php">Track Order</a></li>
                    <li><a href="<?php echo $level; ?>pages/sell.php">Sell Manga</a></li>
                    <li><a href="<?php echo $level; ?>pages/about.php">About</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Help</h3>
                <ul>
                    <li><a href="<?php echo $level; ?>pages/faq.php">FAQ</a></li>
                    <li><a href="<?php echo $level; ?>pages/returns.php">Returns</a></li>
                    <li><a href="<?php echo $level; ?>pages/contact.php">Contact Us</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Contact</h3>
                <ul>
                    <li><i class="fas fa-envelope"></i> bort@bortsbooks.com</li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom container">
            <p>&copy; <?php echo date('Y'); ?> Bort's Books. All rights reserved.</p>
        </div>
    </footer>

    <!-- Mobile Navigation Script -->
    <script src="<?php echo $level; ?>assets/js/mobile-nav.js"></script>
    
    <?php if (isset($additionalJS)) echo $additionalJS; ?>
</body>
</html> 