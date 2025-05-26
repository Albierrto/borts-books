<?php
/**
 * Admin Password Generator
 * Run this script to generate a secure password hash for your admin account
 */

// Change this to your desired password
$your_password = 'bortbooks2024!'; // Change this to your preferred password

// Generate secure hash
$hash = password_hash($your_password, PASSWORD_DEFAULT);

echo "<h1>Admin Password Generator</h1>";
echo "<p><strong>Your password:</strong> " . htmlspecialchars($your_password) . "</p>";
echo "<p><strong>Generated hash:</strong></p>";
echo "<code style='background: #f4f4f4; padding: 10px; display: block; word-break: break-all;'>" . $hash . "</code>";
echo "<br>";
echo "<h3>Instructions:</h3>";
echo "<ol>";
echo "<li>Copy the hash above</li>";
echo "<li>Open <code>pages/admin-login.php</code></li>";
echo "<li>Replace the <code>\$ADMIN_PASS_HASH</code> value with your new hash</li>";
echo "<li>Optionally change the <code>\$ADMIN_USER</code> to your preferred username</li>";
echo "<li><strong>Delete this file</strong> after you're done for security</li>";
echo "</ol>";

echo "<h3>Security Notes:</h3>";
echo "<ul>";
echo "<li>Your password is hashed using PHP's secure password_hash() function</li>";
echo "<li>Admin sessions expire after 2 hours</li>";
echo "<li>Rate limiting prevents brute force attacks (5 attempts = 15 minute lockout)</li>";
echo "<li>CSRF protection prevents unauthorized actions</li>";
echo "<li>Email subscriber data is properly validated and sanitized</li>";
echo "</ul>";
?> 