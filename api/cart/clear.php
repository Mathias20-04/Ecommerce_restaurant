<?php
// api/cart/clear.php - Clear cart manually
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/middleware.php';
require_once '../../includes/cart_cache.php';

setCORSHeaders();
handlePreflight();
header('Content-Type: application/json');

$auth = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

$user_id = $_SESSION['user_id'];

$conn = getDBConnection();
if (!$conn) {
    jsonResponse(false, 'Database connection failed', [], 500);
}

try {
    // Clear from database
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Clear cache
    clearCachedCart($user_id);
    
    jsonResponse(true, 'Cart cleared successfully', [
        'item_count' => 0,
        'total_quantity' => 0
    ]);
    
} catch (Exception $e) {
    jsonResponse(false, 'Error clearing cart: ' . $e->getMessage(), [], 500);
} finally {
    closeDBConnection($conn);
}
?>