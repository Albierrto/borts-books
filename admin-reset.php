<?php
/**
 * Admin Reset Tool
 * Use this to reset login attempts and set custom admin credentials
 */

session_start();

echo "<h1>Admin Reset Tool</h1>";

// Reset login attempts
if (isset($_GET['reset_attempts'])) {
    unset($_SESSION['login_attempts']);
    unset($_SESSION['last_attempt']);
    echo "<div style='color: green; margin: 20px 0; padding: 10px; border: 1px solid green; background: #e8f5e8;'>";
    echo "✅ Login attempts have been reset! You can now try logging in again.";
    echo "</div>";
}

// Current admin info
echo "<h2>Current Admin Credentials</h2>";
echo "<div style='background: #f4f4f4; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
echo "<strong>Username:</strong> admin<br>";
echo "<strong>Password:</strong> password<br>";
echo "<small>(This is the default password - you should change it for security)</small>";
echo "</div>";

// Reset button
echo "<h2>Reset Login Attempts</h2>";
echo "<p>If you're locked out due to failed login attempts, click below:</p>";
echo "<a href='?reset_attempts=1' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Login Attempts</a>";

// Generate new password section
if (isset($_POST['new_password'])) {
    $newPassword = $_POST['new_password'];
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    echo "<h2>New Password Hash Generated</h2>";
    echo "<div style='background: #fffacd; padding: 15px; margin: 20px 0; border: 2px solid #ddd; border-radius: 5px;'>";
    echo "<p><strong>Your new password:</strong> " . htmlspecialchars($newPassword) . "</p>";
    echo "<p><strong>Hash to use in admin-login.php:</strong></p>";
    echo "<textarea style='width: 100%; height: 60px; font-family: monospace;'>" . $hash . "</textarea>";
    echo "<p><strong>Instructions:</strong></p>";
    echo "<ol>";
    echo "<li>Copy the hash above</li>";
    echo "<li>Open <code>pages/admin-login.php</code></li>";
    echo "<li>Replace the <code>\$ADMIN_PASS_HASH</code> value with the new hash</li>";
    echo "<li>Delete this admin-reset.php file for security</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<h2>Create New Password</h2>";
echo "<form method='POST' style='margin: 20px 0;'>";
echo "<p>Enter a new secure password:</p>";
echo "<input type='password' name='new_password' required style='padding: 8px; width: 250px; margin-right: 10px;'>";
echo "<button type='submit' style='background: #28a745; color: white; padding: 8px 15px; border: none; border-radius: 3px; cursor: pointer;'>Generate Hash</button>";
echo "</form>";

echo "<h2>Test Current Credentials</h2>";
echo "<p>Try logging in with these credentials:</p>";
echo "<a href='pages/admin-login.php' style='background: #eebbc3; color: #232946; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Go to Admin Login</a>";

echo "<div style='margin-top: 40px; padding: 15px; background: #ffeaa7; border-radius: 5px;'>";
echo "<strong>⚠️ Security Note:</strong> Delete this file (admin-reset.php) after you're done using it!";
echo "</div>";
?> 