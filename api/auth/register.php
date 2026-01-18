<?php
// api/register.php
require_once __DIR__ . '/../includes/functions.php';
require_once '../../includes/auth.php';

// Enable CORS
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$username = sanitizeInput($input['username'] ?? '');
$email = sanitizeInput($input['email'] ?? '');
$password = $input['password'] ?? '';
$full_name = sanitizeInput($input['full_name'] ?? '');
$phone = sanitizeInput($input['phone'] ?? '');
$address = sanitizeInput($input['address'] ?? '');

// Validation
$errors = [];

if (empty($username)) $errors[] = 'Username is required';
if (empty($email)) $errors[] = 'Email is required';
if (empty($password)) $errors[] = 'Password is required';
if (empty($full_name)) $errors[] = 'Full name is required';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
    exit;
}

// Attempt registration
$auth = new Auth();
$result = $auth->register($username, $email, $password, $full_name, $phone, $address);

if ($result === true) {
    http_response_code(201);
    echo json_encode([
        'success' => true, 
        'message' => 'Registration successful',
        'user' => [
            'username' => $username,
            'email' => $email,
            'full_name' => $full_name
        ]
    ]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $result]);
}
?>