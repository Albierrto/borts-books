<?php
echo "<h1>Test Access - PHP Files Working</h1>";
echo "<p>If you can see this page, PHP files in the root directory are accessible.</p>";
echo "<p>Current file path: " . __FILE__ . "</p>";
echo "<p>Document root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script name: " . $_SERVER['SCRIPT_NAME'] . "</p>";

echo "<h2>Available Debug Files</h2>";
$debug_files = [
    'simple-debug.php',
    'deep-runtime-debug.php', 
    'admin-login-bypass.php',
    'debug-admin-errors.php',
    'debug-login.php'
];

echo "<ul>";
foreach ($debug_files as $file) {
    if (file_exists($file)) {
        echo "<li>✅ <a href='$file'>$file</a> - File exists</li>";
    } else {
        echo "<li>❌ $file - File missing</li>";
    }
}
echo "</ul>";

echo "<h2>Server Information</h2>";
echo "Server: " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . "<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Current URL: " . ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_SERVER['REQUEST_URI'] . "<br>";

echo "<h2>Try These Direct Links</h2>";
$base_url = ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['REQUEST_URI']);
foreach ($debug_files as $file) {
    if (file_exists($file)) {
        echo "<p><a href='$base_url/$file' target='_blank'>$base_url/$file</a></p>";
    }
}
?> 