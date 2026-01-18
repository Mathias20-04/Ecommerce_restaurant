<?php
// api/auth/logout.php
require_once __DIR__ . '/../includes/functions.php';
require_once '../../includes/auth.php';

header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Create auth instance
$auth = new Auth();

// Clear user's cart from database before logout
if (isset($_SESSION['user_id'])) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
        closeDBConnection($conn);
    } catch (Exception $e) {
        // Log error but continue with logout
        error_log("Cart cleanup on logout failed: " . $e->getMessage());
    }
}

// Perform logout
$username = $auth->logout();

jsonResponse(true, "Goodbye, $username! You have been logged out.");
?>