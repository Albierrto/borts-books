<?php
// Debug configuration page - remove this after testing
require_once 'includes/config.php';

echo "<h1>Configuration Debug</h1>";
echo "<p><strong>This is a debug page. Delete it after confirming everything works!</strong></p>";

echo "<h2>Server Information</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><td>HTTP_HOST</td><td>" . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "</td></tr>";
echo "<tr><td>HTTPS</td><td>" . ($_SERVER['HTTPS'] ?? 'Not set') . "</td></tr>";
echo "<tr><td>REQUEST_URI</td><td>" . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "</td></tr>";
echo "<tr><td>SERVER_NAME</td><td>" . ($_SERVER['SERVER_NAME'] ?? 'Not set') . "</td></tr>";
echo "</table>";

echo "<h2>Detected URLs</h2>";
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$auto_site_url = $protocol . $host;

echo "<table border='1' cellpadding='5'>";
echo "<tr><td>Protocol</td><td>" . $protocol . "</td></tr>";
echo "<tr><td>Host</td><td>" . $host . "</td></tr>";
echo "<tr><td>Auto-detected URL</td><td>" . $auto_site_url . "</td></tr>";
echo "<tr><td>Final SITE_URL</td><td>" . SITE_URL . "</td></tr>";
echo "</table>";

echo "<h2>Environment Variables</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><td>ENV SITE_URL</td><td>" . ($_ENV['SITE_URL'] ?? 'Not set') . "</td></tr>";
echo "<tr><td>ENV DB_HOST</td><td>" . ($_ENV['DB_HOST'] ?? 'Not set') . "</td></tr>";
echo "<tr><td>ENV DB_NAME</td><td>" . ($_ENV['DB_NAME'] ?? 'Not set') . "</td></tr>";
echo "<tr><td>ENV DB_USER</td><td>" . ($_ENV['DB_USER'] ?? 'Not set') . "</td></tr>";
echo "<tr><td>ENV STRIPE_PUBLISHABLE_KEY</td><td>" . (substr($_ENV['STRIPE_PUBLISHABLE_KEY'] ?? 'Not set', 0, 20) . '...') . "</td></tr>";
echo "</table>";

echo "<h2>Stripe URLs (what will be used)</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><td>Success URL</td><td>" . SITE_URL . "/success.php?session_id={CHECKOUT_SESSION_ID}</td></tr>";
echo "<tr><td>Cancel URL</td><td>" . SITE_URL . "/cart.php</td></tr>";
echo "</table>";

echo "<h2>Test Links</h2>";
echo "<p><a href='" . SITE_URL . "/cart.php'>Test Cart Link</a></p>";
echo "<p><a href='" . SITE_URL . "/success.php?session_id=test123'>Test Success Link</a></p>";

echo "<h2>Current Page URL</h2>";
echo "<p>" . $protocol . $host . $_SERVER['REQUEST_URI'] . "</p>";

echo "<hr>";
echo "<p><strong>Everything looks correct? <a href='debug-config.php?delete=1'>Delete this debug file</a></strong></p>";

// Delete file if requested
if (isset($_GET['delete'])) {
    unlink(__FILE__);
    echo "<p>Debug file deleted! <a href='index.php'>Go to homepage</a></p>";
}
?> 