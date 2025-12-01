<?php
// api/auth/logout.php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Set CORS headers
setCORSHeaders();
header('Content-Type: application/json');

// Handle preflight request
handlePreflight();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$auth = new Auth();

if ($auth->isLoggedIn()) {
    $username = $auth->logout();
    jsonResponse(true, 'Logout successful', ['username' => $username]);
} else {
    jsonResponse(false, 'No user is logged in', [], 400);
}
?>