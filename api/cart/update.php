<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/middleware.php';

setCORSHeaders();
handlePreflight();
header('Content-Type: application/json');

$auth = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonResponse(false, 'Invalid JSON input', [], 400);
}

$meal_id = $input['meal_id'] ?? null;
$quantity = $input['quantity'] ?? 1;

if (!$meal_id || !is_numeric($meal_id)) {
    jsonResponse(false, 'Valid meal ID is required', [], 400);
}

if ($quantity < 0) {
    jsonResponse(false, 'Quantity cannot be negative', [], 400);
}

if (!isset($_SESSION['cart'])) {
    jsonResponse(false, 'Cart is empty', [], 400);
}

// Find and update/remove item
$found = false;
foreach ($_SESSION['cart'] as $index => &$item) {
    if ($item['meal_id'] == $meal_id) {
        if ($quantity == 0) {
            // Remove item if quantity is 0
            array_splice($_SESSION['cart'], $index, 1);
        } else {
            // Update quantity
            $item['quantity'] = $quantity;
        }
        $found = true;
        break;
    }
}

if (!$found) {
    jsonResponse(false, 'Item not found in cart', [], 404);
}

jsonResponse(true, 'Cart updated successfully', ['cart' => $_SESSION['cart']]);
?>