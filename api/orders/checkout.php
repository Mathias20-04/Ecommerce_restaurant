<?php
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
if (!$input) {
    jsonResponse(false, 'Invalid JSON input', [], 400);
}

// Validate required fields
$required = ['delivery_address', 'customer_phone'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        jsonResponse(false, "Missing required field: $field", [], 400);
    }
}

// Check if cart has items
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    jsonResponse(false, 'Cart is empty', [], 400);
}

$conn = getDBConnection();
if (!$conn) {
    jsonResponse(false, 'Database connection failed', [], 500);
}

try {
    $conn->begin_transaction();
    
    // Calculate total amount
    $total_amount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }
    
    // Create order
    $delivery_address = sanitizeInput($input['delivery_address']);
    $customer_phone = sanitizeInput($input['customer_phone']);
    $special_instructions = sanitizeInput($input['special_instructions'] ?? '');
    $customer_id = $_SESSION['user_id'];
    
    $order_stmt = $conn->prepare("INSERT INTO orders (customer_id, total_amount, delivery_address, customer_phone, special_instructions) VALUES (?, ?, ?, ?, ?)");
    $order_stmt->bind_param("idsss", $customer_id, $total_amount, $delivery_address, $customer_phone, $special_instructions);
    
    if (!$order_stmt->execute()) {
        throw new Exception("Failed to create order: " . $conn->error);
    }
    
    $order_id = $conn->insert_id;
    
    // Create order items
    $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, meal_id, quantity, unit_price, item_total) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($_SESSION['cart'] as $item) {
        $item_total = $item['price'] * $item['quantity'];
        $item_stmt->bind_param("iiidd", $order_id, $item['meal_id'], $item['quantity'], $item['price'], $item_total);
        
        if (!$item_stmt->execute()) {
            throw new Exception("Failed to add order item: " . $conn->error);
        }
    }
    
    // Create initial order status
    $status_stmt = $conn->prepare("INSERT INTO order_status (order_id, status, updated_by) VALUES (?, 'preparing', ?)");
    $status_stmt->bind_param("ii", $order_id, $customer_id);
    
    if (!$status_stmt->execute()) {
        throw new Exception("Failed to create order status: " . $conn->error);
    }
    
    $conn->commit();
    
    // Clear cart after successful order
    unset($_SESSION['cart']);
    
    jsonResponse(true, 'Order created successfully', [
        'order_id' => $order_id,
        'total_amount' => $total_amount,
        'delivery_address' => $delivery_address
    ], 201);
    
} catch (Exception $e) {
    $conn->rollback();
    jsonResponse(false, 'Checkout failed: ' . $e->getMessage(), [], 500);
} finally {
    closeDBConnection($conn);
}
?>