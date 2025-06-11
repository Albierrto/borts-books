<?php
// Usage: Run this script ONCE to generate encryption.key and encryption.salt in includes/config/
// If you want to overwrite existing files, use ?force=1 in the URL or pass --force on CLI

$keyFile = __DIR__ . '/config/encryption.key';
$saltFile = __DIR__ . '/config/encryption.salt';
$force = (php_sapi_name() === 'cli') ? in_array('--force', $argv) : (isset($_GET['force']) && $_GET['force'] == '1');

function generateRandomHex($bytes = 32) {
    return bin2hex(random_bytes($bytes));
}

function writeFile($file, $data, $force) {
    if (file_exists($file) && !$force) {
        return false;
    }
    file_put_contents($file, $data);
    return true;
}

$key = generateRandomHex(32);
$salt = generateRandomHex(32);

$keyWritten = writeFile($keyFile, $key, $force);
$saltWritten = writeFile($saltFile, $salt, $force);

header('Content-Type: text/plain');
if ($keyWritten && $saltWritten) {
    echo "Encryption key and salt generated successfully!\n";
    echo "Key file: $keyFile\n";
    echo "Salt file: $saltFile\n";
    echo "\nKeep these files safe and do NOT share them.\n";
} else {
    echo "Key or salt file already exists. To overwrite, run with --force or ?force=1\n";
    echo "Key file: $keyFile\n";
    echo "Salt file: $saltFile\n";
} 