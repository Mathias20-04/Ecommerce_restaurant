<?php
// api/auth/login.php - ADD EMAIL TO SESSION
require_once __DIR__ . '/../includes/functions.php';
require_once '../../includes/auth.php';

// Enable CORS properly
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonResponse(false, 'Invalid JSON input', [], 400);
}

$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

if (empty($username) || empty($password)) {
    jsonResponse(false, 'Username and password are required', [], 400);
}

$auth = new Auth();

// In your login.php, add this:
if ($auth->login($username, $password)) {
    $user = $auth->getCurrentUser();
    
    // IMPORTANT: Set all session variables
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['last_activity'] = time();
    
    jsonResponse(true, 'Login successful', [
        'user' => $user
    ]);
} else {
    jsonResponse(false, 'Invalid username or password', [], 401);
}
?>