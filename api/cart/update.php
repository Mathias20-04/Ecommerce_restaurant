<?php
// api/cart/update.php 
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/middleware.php';
require_once '../../includes/cart_cache.php'; 

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

$user_id = $_SESSION['user_id'];

$conn = getDBConnection();
if (!$conn) {
    jsonResponse(false, 'Database connection failed', [], 500);
}

try {
    if ($quantity == 0) {
        // Remove item from database cart
        $delete_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND meal_id = ?");
        $delete_stmt->bind_param("ii", $user_id, $meal_id);
        $delete_stmt->execute();
        $message = 'Item removed from cart';
    } else {
        // Update quantity in database
        $check_stmt = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ? AND meal_id = ?");
        $check_stmt->bind_param("ii", $user_id, $meal_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Update existing item
            $update_stmt = $conn->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND meal_id = ?");
            $update_stmt->bind_param("iii", $quantity, $user_id, $meal_id);
            $update_stmt->execute();
        } else {
            // Insert new item (if quantity > 0)
            $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, meal_id, quantity) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("iii", $user_id, $meal_id, $quantity);
            $insert_stmt->execute();
        }
        $message = 'Cart updated successfully';
    }
    
    // INVALIDATE CACHE
    clearCachedCart($user_id);
    
    // Get updated count
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
    jsonResponse(false, 'Error updating cart: ' . $e->getMessage(), [], 500);
} finally {
    closeDBConnection($conn);
}
?>