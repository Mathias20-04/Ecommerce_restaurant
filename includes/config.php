<?php
// includes/config.php
// includes/config.php

// Database Configuration
$db_config = [
    'host' => 'localhost',
    'username' => 'root', 
    'password' => '1234@mathias@1234', 
    'database' => 'aunt_joy_restaurant'
];

// Application Settings
define('SITE_NAME', 'Aunt Joy\'s Restaurant');
define('SITE_URL', 'http://localhost:8000'); 
define('BASE_PATH', '/projects/aunt-joy-restaurant/');

// File Upload Configuration
define('UPLOAD_DIR', '../../assets/images/meals/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// CORS and Security
define('ALLOWED_ORIGIN', 'http://localhost:8000');

// Session Configuration - CRITICAL FIX
// We need to set the session parameters BEFORE starting session
// The session cookie path MUST include the API path too!

// Don't set session name or params if session already started
if (session_status() === PHP_SESSION_NONE) {
    // Set session parameters
    session_name('auntjoy_restaurant_session');
    
    // FIX: Set path to root so cookies work for ALL subdirectories
    session_set_cookie_params([
        'lifetime' => 86400, // 24 hours
        'path' => '/', // FIXED: Set to root so it works for all subdirectories
        'domain' => 'localhost',
        'secure' => false, // Set to true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Now start the session
    session_start();
}

// Error Reporting (Disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Africa/Blantyre'); // Malawi timezone

// Application Constants
define('MAX_LOGIN_ATTEMPTS', 5);
define('SESSION_TIMEOUT', 3600); // 1 hour

// Database connection function
function getDBConnection() {
    global $db_config;
    
    $conn = new mysqli(
        $db_config['host'],
        $db_config['username'],
        $db_config['password'],
        $db_config['database']
    );
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        return false;
    }
    
    return $conn;
}

// Close database connection
function closeDBConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}

// JSON response helper
function jsonResponse($success, $message = '', $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_PRETTY_PRINT);
    exit();
}

// Utility function to get base URL
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $project_path = trim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
    return $protocol . '://' . $host . '/' . $project_path;
}

// Add this helper function to debug sessions
function debugSession($message = '') {
    error_log("=== SESSION DEBUG: $message ===");
    error_log("Session ID: " . session_id());
    error_log("Session Name: " . session_name());
    error_log("Session Status: " . session_status());
    error_log("Cookie: " . ($_COOKIE[session_name()] ?? 'No cookie'));
    error_log("Logged In: " . (isset($_SESSION['logged_in']) ? 'true' : 'false'));
    error_log("User ID: " . ($_SESSION['user_id'] ?? 'Not set'));
    error_log("Full Session: " . print_r($_SESSION, true));
    error_log("============================");
}
?>