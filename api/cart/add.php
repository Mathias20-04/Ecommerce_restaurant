<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/middleware.php';

setCORSHeaders();
handlePreflight();
header('Content-Type: application/json');

// User must be logged in to add to cart
$auth = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

if ($quantity < 1) {
    jsonResponse(false, 'Quantity must be at least 1', [], 400);
}

$conn = getDBConnection();
if (!$conn) {
    jsonResponse(false, 'Database connection failed', [], 500);
}

try {
    // Verify meal exists and is available
    $stmt = $conn->prepare("SELECT meal_id, meal_name, price, is_available FROM meals WHERE meal_id = ?");
    $stmt->bind_param("i", $meal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $meal = $result->fetch_assoc();
    
    if (!$meal) {
        jsonResponse(false, 'Meal not found', [], 404);
    }
    
    if (!$meal['is_available']) {
        jsonResponse(false, 'Meal is not available', [], 400);
    }
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Add or update item in cart
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['meal_id'] == $meal_id) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $_SESSION['cart'][] = [
            'meal_id' => $meal_id,
            'meal_name' => $meal['meal_name'],
            'price' => floatval($meal['price']),
            'quantity' => $quantity
        ];
    }
    
    jsonResponse(true, 'Item added to cart', ['cart' => $_SESSION['cart']]);
    
} catch (Exception $e) {
    jsonResponse(false, 'Error adding to cart: ' . $e->getMessage(), [], 500);
} finally {
    closeDBConnection($conn);
}
?>