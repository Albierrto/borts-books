<footer>
        <div class="container footer-container">
            <div class="footer-section">
                <h3>Bort's Books</h3>
                <p>Your trusted source for manga collections since 2023.</p>
            </div>
            
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="<?php echo $level ?>index.php">Home</a></li>
                    <li><a href="<?php echo $level ?>pages/shop.php">Shop</a></li>
                    <li><a href="<?php echo $level ?>pages/sell.php">Sell Manga</a></li>
                    <li><a href="<?php echo $level ?>pages/about.php">About</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Help</h3>
                <ul>
                    <li><a href="<?php echo $level ?>pages/faq.php">FAQ</a></li>
                    <li><a href="<?php echo $level ?>pages/shipping.php">Shipping</a></li>
                    <li><a href="<?php echo $level ?>pages/returns.php">Returns</a></li>
                    <li><a href="<?php echo $level ?>pages/contact.php">Contact Us</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Contact</h3>
                <ul>
                    <li>Email: info@bortsbooks.com</li>
                    <li>Phone: (123) 456-7890</li>
                    <li>Address: 123 Manga St, Anime City, AC 12345</li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom container">
            <p>&copy; <?php echo date('Y'); ?> Bort's Books. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="<?php echo $level ?>assets/js/main.js"></script>
    <?php if (isset($pageJs)) { ?>
    <script src="<?php echo $level ?>assets/js/<?php echo $pageJs; ?>.js"></script>
    <?php } ?>
</body>
</html>