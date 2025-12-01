<?php
require_once 'auth.php';

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
?>