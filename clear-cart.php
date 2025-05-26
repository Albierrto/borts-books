<?php
session_start();

// Clear the cart completely
$_SESSION['cart'] = [];

// Provide feedback
echo "Cart has been cleared successfully.";
echo "<br><br>";
echo '<a href="cart.php">Go to Cart</a> | <a href="index.php">Go to Homepage</a>';
?> 