<?php
// api/cart/sync.php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/middleware.php';

setCORSHeaders();
handlePreflight();
header('Content-Type: application/json');

// User must be logged in
$auth = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['cart_items'])) {
    jsonResponse(false, 'Invalid cart data', [], 400);
}

try {
    // Validate cart items structure
    $validated_cart = [];
    foreach ($input['cart_items'] as $item) {
        if (!isset($item['meal_id']) || !isset($item['meal_name']) || !isset($item['price']) || !isset($item['quantity'])) {
            jsonResponse(false, 'Invalid cart item structure', [], 400);
        }
        
        $validated_cart[] = [
            'meal_id' => intval($item['meal_id']),
            'meal_name' => sanitizeInput($item['meal_name']),
            'price' => floatval($item['price']),
            'quantity' => intval($item['quantity']),
            'image_url' => $item['image_url'] ?? null
        ];
    }
    
    // Sync to session
    $_SESSION['cart'] = $validated_cart;

    jsonResponse(true, 'Cart synced successfully', [
        'item_count' => count($_SESSION['cart'])
    ]);
    
} catch (Exception $e) {
    jsonResponse(false, 'Failed to sync cart: ' . $e->getMessage(), [], 500);
}
?>