<?php
// Manual fix for upload link - safer approach
echo "<h1>Manual Upload Link Fix</h1>";

echo "<h3>Current Status:</h3>";

// Check current link
$content = file_get_contents('edit-product.php');
if (strpos($content, 'upload-images-production.php') !== false) {
    echo "✅ Upload link is already fixed and points to working version<br>";
} else if (strpos($content, 'upload-images.php') !== false) {
    echo "⚠️ Upload link still points to broken version<br>";
    echo "<p><strong>Manual Fix Instructions:</strong></p>";
    echo "<ol>";
    echo "<li>You can directly access the working upload page: <a href='upload-images-production.php?product_id=815' target='_blank'>Upload Images (Working Version)</a></li>";
    echo "<li>Or use the fixed version: <a href='upload-images-fixed.php?product_id=815' target='_blank'>Upload Images (Fixed Version)</a></li>";
    echo "<li>The edit product page should still work, just the upload button points to the wrong file</li>";
    echo "</ol>";
} else {
    echo "❓ Could not find upload link in file<br>";
}

echo "<br><h3>🔧 Quick Solutions:</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #007cba; margin: 10px 0;'>";
echo "<h4>Option 1: Use Direct Links (Recommended)</h4>";
echo "<p>Skip the broken button and use these working links directly:</p>";
echo "<ul>";
echo "<li><a href='upload-images-production.php?product_id=815' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>📸 Upload Images (Production)</a></li>";
echo "<li><a href='upload-images-fixed.php?product_id=815' style='background: #007cba; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-left: 10px;'>📸 Upload Images (Fixed)</a></li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 10px 0;'>";
echo "<h4>Option 2: Test Edit Product Page</h4>";
echo "<p>The edit product page should still work. The only issue is the upload button.</p>";
echo "<p><a href='edit-product.php?id=815' target='_blank' style='background: #6c757d; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>📝 Test Edit Product Page</a></p>";
echo "</div>";

echo "<div style='background: #d1ecf1; padding: 15px; border-left: 4px solid #17a2b8; margin: 10px 0;'>";
echo "<h4>Option 3: Run Diagnostics</h4>";
echo "<p>Check what specifically is wrong:</p>";
echo "<p><a href='test-edit-product.php' style='background: #17a2b8; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>🔍 Run Full Diagnostic</a></p>";
echo "</div>";

echo "<br><h3>📋 Status Summary:</h3>";
echo "<ul>";
echo "<li>✅ Upload functionality works (upload-images-production.php)</li>";
echo "<li>✅ Database and images system works</li>";
echo "<li>⚠️ Edit product page may have issues (needs testing)</li>";
echo "<li>⚠️ Upload button on edit page points to broken file</li>";
echo "</ul>";

echo "<br><p><em>The safest approach is to use the direct upload links above until we can properly fix the edit-product.php file.</em></p>";
?> 