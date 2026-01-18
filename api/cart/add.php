<?php
// api/cart/add.php - WITH CACHE INVALIDATION
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/middleware.php';
require_once '../../includes/cart_cache.php'; // Add this

setCORSHeaders();
handlePreflight();
header('Content-Type: application/json');

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

$user_id = $_SESSION['user_id'];

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
    
    // Check if item already exists in user's cart (database)
    $check_stmt = $conn->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = ? AND meal_id = ?");
    $check_stmt->bind_param("ii", $user_id, $meal_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing cart item in database
        $row = $check_result->fetch_assoc();
        $new_quantity = $row['quantity'] + $quantity;
        
        $update_stmt = $conn->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE cart_id = ?");
        $update_stmt->bind_param("ii", $new_quantity, $row['cart_id']);
        $update_stmt->execute();
        
        $message = 'Cart updated';
    } else {
        // Insert new cart item in database
        $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, meal_id, quantity) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("iii", $user_id, $meal_id, $quantity);
        $insert_stmt->execute();
        
        $message = 'Item added to cart';
    }
    
    // INVALIDATE CACHE - clear cache so next get will fetch fresh data
    clearCachedCart($user_id);
    
    // Get updated cart count for immediate response
    $count_stmt = $conn->prepare("SELECT COUNT(*) as item_count, SUM(quantity) as total_quantity FROM cart WHERE user_id = ?");
    $count_stmt->bind_param("i", $user_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_data = $count_result->fetch_assoc();
    
    jsonResponse(true, $message, [
        'item_count' => $count_data['item_count'] ?? 0,
        'total_quantity' => $count_data['total_quantity'] ?? 0
    ]);
    
} catch (Exception $e) {
    jsonResponse(false, 'Error adding to cart: ' . $e->getMessage(), [], 500);
} finally {
    closeDBConnection($conn);
}
?>