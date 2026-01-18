<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Set CORS headers
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Session expired. Please login again.',
        'requires_login' => true
    ]);
    exit();
}

// User is authenticated - get user data
try {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT user_id, email, name, phone, address, created_at FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit();
    }
    
    $user = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'user' => $user,
        'session_id' => session_id()
    ]);
    
    $stmt->close();
    closeDBConnection($conn);
    
} catch (Exception $e) {
    error_log("Profile error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ]);
}
?>