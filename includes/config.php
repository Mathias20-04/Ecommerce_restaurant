<?php
// includes/config.php

// Database Configuration
$db_config = [
    'host' => 'localhost',
    'username' => 'root', // Change to your MySQL username
    'password' => '1234@mathias@1234', 
    'database' => 'aunt_joy_restaurant' // Change to your database name
];

// Application Settings
define('SITE_NAME', 'Aunt Joy\'s Restaurant');
define('SITE_URL', 'http://localhost:8000'); // Adjust port as needed
define('BASE_PATH', '/projects/aunt-joy-restaurant/'); // Adjust to your project path

// File Upload Configuration
define('UPLOAD_DIR', '../../assets/images/meals/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// CORS and Security
define('ALLOWED_ORIGIN', 'http://localhost:8000');

// Session Configuration
session_set_cookie_params([
    'lifetime' => 86400, // 24 hours
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
    'secure' => false, // Set to true in production with HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Start session
if (session_status() == PHP_SESSION_NONE) {
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

// Utility function to get base URL
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $project_path = trim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
    return $protocol . '://' . $host . '/' . $project_path;
}
?>