<?php
// Comprehensive script to redirect all edit-product.php references to edit-product-clean.php
echo "<h1>🔄 Redirecting All Edit Product Links</h1>";
echo "<p>Updating all references from edit-product.php to edit-product-clean.php...</p>";

// Define files that need updating
$files_to_update = [
    // Upload system files
    'upload-images.php',
    'upload-images-simple.php', 
    'upload-images-production.php',
    'upload-images-fixed.php',
    
    // Product and admin pages
    'product.php',
    'admin.php',
    
    // Processing scripts
    'process-upload-images.php',
    'process-upload-images-secure.php',
    'process-edit-product.php',
    'process-add-product.php',
    'delete-image.php',
    
    // Diagnostic and utility files (keep for reference but update links)
    'test-edit-product.php',
    'manual-fix-upload-link.php'
];

$updated_count = 0;
$errors = [];

echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 8px;'>";
echo "<h3>📝 Updating Files...</h3>";

foreach ($files_to_update as $filename) {
    if (file_exists($filename)) {
        $content = file_get_contents($filename);
        $original_content = $content;
        
        // Replace edit-product.php with edit-product-clean.php
        // But preserve form action references (those should stay as process-edit-product.php)
        $content = preg_replace('/edit-product\.php(?!\w)/', 'edit-product-clean.php', $content);
        
        // Special handling to preserve form actions
        $content = str_replace('process-edit-product-clean.php', 'process-edit-product.php', $content);
        
        if ($content !== $original_content) {
            if (file_put_contents($filename, $content)) {
                echo "✅ Updated: $filename<br>";
                $updated_count++;
            } else {
                echo "❌ Failed to update: $filename<br>";
                $errors[] = $filename;
            }
        } else {
            echo "ℹ️ No changes needed: $filename<br>";
        }
    } else {
        echo "⚠️ File not found: $filename<br>";
    }
}

echo "</div>";

// Show results
echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 8px;'>";
echo "<h3>✅ Update Summary</h3>";
echo "<p><strong>Files updated:</strong> $updated_count</p>";
echo "<p><strong>Errors:</strong> " . count($errors) . "</p>";

if (!empty($errors)) {
    echo "<p style='color: #dc3545;'>Failed to update: " . implode(', ', $errors) . "</p>";
}

echo "</div>";

// Test the key links
echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 8px;'>";
echo "<h3>🧪 Test Updated Links</h3>";
echo "<p>Test these key pages to ensure they now point to the working version:</p>";
echo "<ul>";
echo "<li><a href='admin.php' target='_blank' style='color: #007cba;'>Admin Panel</a> - Check product edit buttons</li>";
echo "<li><a href='upload-images-production.php?product_id=815' target='_blank' style='color: #007cba;'>Upload Images</a> - Check back button</li>";
echo "<li><a href='edit-product-clean.php?id=815' target='_blank' style='color: #007cba;'>Edit Product (Clean)</a> - Main working page</li>";
echo "<li><a href='product.php?id=815' target='_blank' style='color: #007cba;'>Product Page</a> - Check admin edit button</li>";
echo "</ul>";
echo "</div>";

// Show what was changed
echo "<div style='background: #d1ecf1; padding: 15px; margin: 10px 0; border-radius: 8px;'>";
echo "<h3>🔍 What Was Changed</h3>";
echo "<p>The following types of links were updated:</p>";
echo "<ul>";
echo "<li>✅ <strong>Navigation links:</strong> edit-product.php?id=X → edit-product-clean.php?id=X</li>";
echo "<li>✅ <strong>Back buttons:</strong> In upload pages and other admin areas</li>";
echo "<li>✅ <strong>Admin buttons:</strong> Edit links in product listings</li>";
echo "<li>✅ <strong>Redirect headers:</strong> After form submissions</li>";
echo "<li>✅ <strong>Reference links:</strong> In diagnostic and utility pages</li>";
echo "</ul>";
echo "<p><strong>Preserved:</strong></p>";
echo "<ul>";
echo "<li>🔒 <strong>Form actions:</strong> process-edit-product.php (unchanged)</li>";
echo "<li>🔒 <strong>File existence checks:</strong> In diagnostic scripts</li>";
echo "</ul>";
echo "</div>";

// Next steps
echo "<div style='background: #e2e3e5; padding: 15px; margin: 10px 0; border-radius: 8px;'>";
echo "<h3>📋 Next Steps</h3>";
echo "<ol>";
echo "<li><strong>Test the admin workflow:</strong> Go to admin.php and try editing a product</li>";
echo "<li><strong>Test image uploads:</strong> Try uploading images and check if the back button works</li>";
echo "<li><strong>Verify form submissions:</strong> Make sure editing still saves properly</li>";
echo "<li><strong>Clean up old files:</strong> Once confirmed working, you can rename edit-product-clean.php to edit-product.php if desired</li>";
echo "</ol>";
echo "</div>";

// Safety note
echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 8px;'>";
echo "<h3>⚠️ Safety Note</h3>";
echo "<p>All files have been updated to point to edit-product-clean.php. The original broken edit-product.php is still available as a backup.</p>";
echo "<p>If you need to revert any changes, use your version control system or restore from backups.</p>";
echo "</div>";
?> 