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
    
    // Add delivery fee
    $delivery_fee = 1500;
    $total_amount += $delivery_fee;
    
    // Prepare payment details
    $payment_method = isset($input['payment_method']) ? $input['payment_method'] : 'cash';
    $payment_details = null;
    
    // SET PAYMENT STATUS BASED ON PAYMENT METHOD
    if ($payment_method === 'mobile') {
        $payment_status = 'paid';
        // Also encode payment details if provided
        if (isset($input['payment_details'])) {
            $payment_details = json_encode($input['payment_details']);
        }
    } else {
        // For cash on delivery, default to pending
        $payment_status = 'pending';
    }
    
    // Create order
    $delivery_address = sanitizeInput($input['delivery_address']);
    $customer_phone = sanitizeInput($input['customer_phone']);
    $special_instructions = sanitizeInput($input['special_instructions'] ?? '');
    $customer_id = $_SESSION['user_id'];
    
    // CORRECTED: Include payment_status in the INSERT statement
    $order_stmt = $conn->prepare("INSERT INTO orders (customer_id, total_amount, delivery_address, customer_phone, special_instructions, payment_method, payment_details, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    // CORRECTED: Bind 8 parameters instead of 7
    $order_stmt->bind_param("idssssss", $customer_id, $total_amount, $delivery_address, $customer_phone, $special_instructions, $payment_method, $payment_details, $payment_status);
    
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
    
    // Include payment_status in the response
    jsonResponse(true, 'Order created successfully', [
        'order_id' => $order_id,
        'total_amount' => $total_amount,
        'delivery_address' => $delivery_address,
        'payment_method' => $payment_method,
        'payment_status' => $payment_status  // Added this
    ], 201);
    
} catch (Exception $e) {
    $conn->rollback();
    jsonResponse(false, 'Checkout failed: ' . $e->getMessage(), [], 500);
} finally {
    closeDBConnection($conn);
}
?>