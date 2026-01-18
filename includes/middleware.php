<?php
// includes/middleware.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

// Your existing functions
function requireAuth() {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        jsonResponse(false, 'Authentication required', [], 401);
    }
    return $auth;
}

function requireAdmin() {
    $auth = requireAuth();
    $auth->requireRole('admin');
    return $auth;
}

function requireSales() {
    $auth = requireAuth();
    $auth->requireRole('sales');
    return $auth;
}

function requireManager() {
    $auth = requireAuth();
    $auth->requireRole('manager');
    return $auth;
}

// NEW functions for API endpoints
function requireLogin() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        $response = [
            'success' => false,
            'message' => 'Authentication required',
            'requires_login' => true
        ];
        
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode($response);
        exit();
    }
}

function getCurrentUserData() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'email' => $_SESSION['email'] ?? null
    ];
}

// Session management functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function checkSessionTimeout() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

function initSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    checkSessionTimeout();
}
?>