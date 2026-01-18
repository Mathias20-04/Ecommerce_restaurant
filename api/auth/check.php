<?php
// api/auth/check.php - SIMPLIFIED
// Enable error logging but don't display errors
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering
ob_start();

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/middleware.php';

// Clear any output that might have been generated
ob_end_clean();

// Initialize session
initSession();

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
header('Access-Control-Allow-Credentials: true');

// Check if user is logged in
if (isLoggedIn()) {
    echo json_encode([
        'success' => true,
        'message' => 'Authenticated',
        'data' => [
            'user' => [
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['role'],
                'full_name' => $_SESSION['full_name'],
                'email' => $_SESSION['email'] ?? $_SESSION['username'] . '@example.com'
            ]
        ]
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Not authenticated',
        'requires_login' => true
    ]);
}
?>