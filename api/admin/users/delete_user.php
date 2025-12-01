<?php
require_once '../../../includes/config.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/middleware.php';

// Start output buffering to prevent stray output from breaking JSON responses
if (!ob_get_level()) ob_start();

// Convert PHP errors/warnings to exceptions so they are returned as JSON
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Shutdown handler to catch fatal errors
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && ($err['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
        error_log("Fatal error in delete_user API: " . $err['message'] . " in " . $err['file'] . " on line " . $err['line']);
        // Clean any partial output
        if (ob_get_length()) ob_clean();
        // Return generic JSON error (avoid exposing sensitive info)
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Internal server error']);
        exit;
    }
});

// Enable CORS properly
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only admins can delete users
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

// Get user ID from URL or input
$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = $input['user_id'] ?? null;
}

if (!$user_id || !is_numeric($user_id)) {
    jsonResponse(false, 'Valid user ID is required', [], 400);
}

$conn = getDBConnection();
if (!$conn) {
    jsonResponse(false, 'Database connection failed', [], 500);
}

try {
    // Prevent deleting your own account
    if($user_id == $_SESSION['user_id']) {
        jsonResponse(false, 'You cannot delete your own account', [], 400);
    }

    // Check if user exists
    $userCheck = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $userCheck->bind_param("i", $user_id);
    $userCheck->execute();
    $userResult = $userCheck->get_result();
    
    if($userResult->num_rows === 0) {
        jsonResponse(false, 'User not found', [], 404);
    }
    
    $user = $userResult->fetch_assoc();
    
    // Check if this is the last admin
    if($user['role'] === 'admin') {
        $adminCheck = $conn->query("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin' AND user_id != $user_id");
        $adminCount = $adminCheck->fetch_assoc()['admin_count'];
        
        if($adminCount == 0) {
            jsonResponse(false, 'Cannot delete the last administrator account', [], 400);
        }
    }

    // Always delete user, even if they have orders
    $deleteStmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $deleteStmt->bind_param("i", $user_id);
    
    if($deleteStmt->execute()) {
        if ($deleteStmt->affected_rows > 0) {
            jsonResponse(true, 'User deleted successfully');
        } else {
            jsonResponse(false, 'User not found', [], 404);
        }
    } else {
        jsonResponse(false, 'Failed to delete user: ' . $conn->error, [], 500);
    }
    
} catch (Exception $e) {
    jsonResponse(false, 'Error deleting user: ' . $e->getMessage(), [], 500);
} finally {
    closeDBConnection($conn);
}
?>