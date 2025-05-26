<?php
// Generate secure hash for the password
$password = 'LolaSombra1!';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Generated hash for secure password: " . $hash;
?> 