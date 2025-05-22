<?php
// Function to create a placeholder image
function createPlaceholder($width, $height, $text = 'No Image') {
    $image = imagecreatetruecolor($width, $height);
    $bg = imagecolorallocate($image, 245, 245, 250); // Light gray background
    $textColor = imagecolorallocate($image, 35, 41, 70); // Dark blue text
    
    imagefill($image, 0, 0, $bg);
    
    // Add text
    $fontSize = 5;
    $textWidth = imagefontwidth($fontSize) * strlen($text);
    $textHeight = imagefontheight($fontSize);
    $x = ($width - $textWidth) / 2;
    $y = ($height - $textHeight) / 2;
    
    imagestring($image, $fontSize, $x, $y, $text, $textColor);
    
    return $image;
}

// Function to create genre image
function createGenreImage($width, $height, $genre, $color) {
    $image = imagecreatetruecolor($width, $height);
    $bg = imagecolorallocate($image, $color[0], $color[1], $color[2]);
    $textColor = imagecolorallocate($image, 255, 255, 255);
    
    imagefill($image, 0, 0, $bg);
    
    // Add genre text
    $fontSize = 5;
    $textWidth = imagefontwidth($fontSize) * strlen($genre);
    $textHeight = imagefontheight($fontSize);
    $x = ($width - $textWidth) / 2;
    $y = ($height - $textHeight) / 2;
    
    imagestring($image, $fontSize, $x, $y, $genre, $textColor);
    
    return $image;
}

// Create placeholder image
$placeholder = createPlaceholder(300, 400);
imagejpeg($placeholder, __DIR__ . '/placeholder.jpg', 90);
imagedestroy($placeholder);

// Create genre images
$genres = [
    'shonen' => [238, 187, 195], // Pink
    'shojo' => [255, 182, 193], // Light pink
    'seinen' => [35, 41, 70],   // Dark blue
    'josei' => [255, 192, 203], // Pink
    'isekai' => [147, 112, 219], // Purple
    'sports' => [70, 130, 180]  // Steel blue
];

foreach ($genres as $genre => $color) {
    $image = createGenreImage(300, 400, ucfirst($genre), $color);
    imagejpeg($image, __DIR__ . "/genre-{$genre}.jpg", 90);
    imagedestroy($image);
}

echo "Images generated successfully!\n";
?> 