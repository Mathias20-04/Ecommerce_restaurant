<?php
// logout.php
require_once __DIR__ . '/../includes/config.php';

// Destroy session
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Clear any cart data
setcookie('cart', '', time() - 3600, '/');
if (isset($_COOKIE['cart'])) {
    unset($_COOKIE['cart']);
}

// Redirect to home page
header('Location: index.php');
exit;
?>