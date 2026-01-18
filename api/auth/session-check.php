<?php
// api/auth/session-check.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
debugSession('Session check API');

echo json_encode([
    'success' => true,
    'session' => [
        'id' => session_id(),
        'name' => session_name(),
        'status' => session_status(),
        'cookie_exists' => isset($_COOKIE[session_name()]),
        'cookie_value' => $_COOKIE[session_name()] ?? null,
        'logged_in' => isset($_SESSION['logged_in']) ? $_SESSION['logged_in'] : false,
        'user_id' => $_SESSION['user_id'] ?? null,
        'user_email' => $_SESSION['user_email'] ?? null,
        'user_name' => $_SESSION['user_name'] ?? null
    ],
    'cookies' => $_COOKIE,
    'session_data' => $_SESSION
]);
?>