<?php
// HTTPS Redirect Setup Tool
echo "<h1>HTTPS Redirect Setup</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .warning { color: orange; } pre { background: #f5f5f5; padding: 10px; border-radius: 4px; }</style>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_redirects'])) {
    // Backup current .htaccess
    $htaccess_content = file_get_contents('.htaccess');
    $backup_filename = '.htaccess.backup.' . date('Y-m-d-H-i-s');
    file_put_contents($backup_filename, $htaccess_content);
    echo "<p class='success'>✅ Backed up current .htaccess to $backup_filename</p>";
    
    // HTTPS redirect rules
    $https_rules = "# Force HTTPS redirect
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security headers
<IfModule mod_headers.c>
    # Force HTTPS for 1 year (be careful with this!)
    Header always set Strict-Transport-Security \"max-age=31536000; includeSubDomains; preload\"
    
    # Prevent clickjacking
    Header always set X-Frame-Options \"SAMEORIGIN\"
    
    # Prevent MIME type sniffing
    Header always set X-Content-Type-Options \"nosniff\"
    
    # Enable XSS protection
    Header always set X-XSS-Protection \"1; mode=block\"
</IfModule>

";
    
    // Add HTTPS rules to the beginning of .htaccess
    $new_content = $https_rules . "\n" . $htaccess_content;
    
    if (file_put_contents('.htaccess', $new_content)) {
        echo "<p class='success'>✅ Successfully added HTTPS redirects to .htaccess!</p>";
        echo "<p class='success'>✅ Added security headers for better protection</p>";
        echo "<p><strong>Test it now:</strong> <a href='http://" . $_SERVER['HTTP_HOST'] . "'>http://" . $_SERVER['HTTP_HOST'] . "</a> (should redirect to HTTPS)</p>";
    } else {
        echo "<p class='error'>❌ Error writing to .htaccess file. Check file permissions.</p>";
    }
} else {
    echo "<h2>Current .htaccess Status:</h2>";
    
    $htaccess_content = file_get_contents('.htaccess');
    
    // Check if redirects already exist
    if (strpos($htaccess_content, 'RewriteRule') !== false && strpos($htaccess_content, 'HTTPS') !== false) {
        echo "<p class='warning'>⚠️ It looks like you might already have some redirect rules in .htaccess</p>";
    } else {
        echo "<p class='success'>✅ No conflicting redirects found</p>";
    }
    
    echo "<h3>Current .htaccess content:</h3>";
    echo "<pre>" . htmlspecialchars($htaccess_content) . "</pre>";
    
    echo "<h2>What This Will Add:</h2>";
    echo "<p>This will add the following rules to the <strong>beginning</strong> of your .htaccess file:</p>";
    
    $rules_to_add = "# Force HTTPS redirect
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security headers
<IfModule mod_headers.c>
    Header always set Strict-Transport-Security \"max-age=31536000; includeSubDomains; preload\"
    Header always set X-Frame-Options \"SAMEORIGIN\"
    Header always set X-Content-Type-Options \"nosniff\"
    Header always set X-XSS-Protection \"1; mode=block\"
</IfModule>";
    
    echo "<pre>" . htmlspecialchars($rules_to_add) . "</pre>";
    
    echo "<h3>What These Rules Do:</h3>";
    echo "<ul>";
    echo "<li><strong>HTTPS Redirect:</strong> Automatically redirects all HTTP traffic to HTTPS</li>";
    echo "<li><strong>Strict-Transport-Security:</strong> Tells browsers to always use HTTPS for your site</li>";
    echo "<li><strong>X-Frame-Options:</strong> Prevents your site from being embedded in frames (clickjacking protection)</li>";
    echo "<li><strong>X-Content-Type-Options:</strong> Prevents MIME type confusion attacks</li>";
    echo "<li><strong>X-XSS-Protection:</strong> Enables browser XSS filtering</li>";
    echo "</ul>";
    
    echo "<h3>Safety Features:</h3>";
    echo "<ul>";
    echo "<li>✅ Will backup your current .htaccess first</li>";
    echo "<li>✅ Adds rules to the beginning (won't conflict with existing rules)</li>";
    echo "<li>✅ Uses standard, widely-compatible syntax</li>";
    echo "</ul>";
    
    echo "<form method='POST'>";
    echo "<button type='submit' name='add_redirects' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; font-size: 16px; cursor: pointer;'>Add HTTPS Redirects & Security Headers</button>";
    echo "</form>";
}

echo "<hr>";
echo "<p><a href='ssl-check.php'>Back to SSL Check</a> | <a href='index.php'>Back to Home</a></p>";
?> 