<?php
// Fix the upload images link in edit-product.php
echo "Fixing upload images link in edit-product.php...<br>";

$file_path = 'edit-product.php';
$content = file_get_contents($file_path);

if ($content === false) {
    die("Error: Could not read edit-product.php");
}

// Replace the broken link with the working one
$old_link = 'href="upload-images.php';
$new_link = 'href="upload-images-production.php';

$updated_content = str_replace($old_link, $new_link, $content);

// Check if replacement was made
if ($content !== $updated_content) {
    // Write the updated content back to the file
    $result = file_put_contents($file_path, $updated_content);
    
    if ($result !== false) {
        echo "✅ Successfully updated the upload images link!<br>";
        echo "Changed: <code>$old_link</code><br>";
        echo "To: <code>$new_link</code><br>";
        echo "<br>";
        echo "The 'Upload Images' button should now work correctly.<br>";
        echo "<a href='edit-product.php?id=815'>Test the edit product page</a>";
    } else {
        echo "❌ Error: Could not write to edit-product.php";
    }
} else {
    echo "⚠️ No changes needed - link already appears to be correct.";
}
?> 