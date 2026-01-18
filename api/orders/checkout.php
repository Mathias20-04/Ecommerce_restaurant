<?php
// api/orders/checkout.php - FIXED TO USE DATABASE CART
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/middleware.php';
require_once '../../includes/cart_cache.php'; 

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
$required = ['delivery_address', 'customer_phone', 'cart_items'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        jsonResponse(false, "Missing required field: $field", [], 400);
    }
}

// Check if cart has items from the request (NOT session)
if (empty($input['cart_items']) || !is_array($input['cart_items'])) {
    jsonResponse(false, 'Cart is empty', [], 400);
}

$conn = getDBConnection();
if (!$conn) {
    jsonResponse(false, 'Database connection failed', [], 500);
}

try {
    $conn->begin_transaction();
    
    // Get cart items from request, not session
    $cart_items = $input['cart_items'];
    
    // Calculate total amount from cart items in request
    $subtotal = 0;
    foreach ($cart_items as $item) {
        // Ensure we have required item properties
        if (!isset($item['price'], $item['quantity'], $item['meal_id'])) {
            throw new Exception('Invalid cart item structure');
        }
        $subtotal += (float)$item['price'] * (int)$item['quantity'];
    }
    
    // Add delivery fee
    $delivery_fee = 1500;
    $total_amount = $subtotal + $delivery_fee;
    
    // Prepare payment details
    $payment_method = $input['payment_method'] ?? 'cash';
    $payment_details = null;
    
    // Set payment status based on payment method
    if ($payment_method === 'mobile') {
        $payment_status = 'paid';
        if (isset($input['payment_details'])) {
            $payment_details = json_encode($input['payment_details']);
        }
    } else {
        $payment_status = 'pending';
    }
    
    // Get user ID from session (this should be set by requireAuth)
    $customer_id = $_SESSION['user_id'];
    
    // Create order
    $delivery_address = sanitizeInput($input['delivery_address']);
    $customer_phone = sanitizeInput($input['customer_phone']);
    $special_instructions = sanitizeInput($input['special_instructions'] ?? '');
    
    $order_stmt = $conn->prepare("INSERT INTO orders (customer_id, total_amount, delivery_address, customer_phone, special_instructions, payment_method, payment_details, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $order_stmt->bind_param("idssssss", $customer_id, $total_amount, $delivery_address, $customer_phone, $special_instructions, $payment_method, $payment_details, $payment_status);
    
    if (!$order_stmt->execute()) {
        throw new Exception("Failed to create order: " . $conn->error);
    }
    
    $order_id = $conn->insert_id;
    
    // Create order items from cart items in request
    $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, meal_id, quantity, unit_price, item_total) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($cart_items as $item) {
        $item_total = (float)$item['price'] * (int)$item['quantity'];
        $item_stmt->bind_param("iiidd", $order_id, $item['meal_id'], $item['quantity'], $item['price'], $item_total);
        
        if (!$item_stmt->execute()) {
            throw new Exception("Failed to add order item: " . $conn->error);
        }
    }
    
    // Clear user's cart from database after successful order
    $clear_cart_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $clear_cart_stmt->bind_param("i", $customer_id);
    $clear_cart_stmt->execute();
    
    // Clear cart cache
    clearCachedCart($customer_id);
    
    // Create initial order status
    $status_stmt = $conn->prepare("INSERT INTO order_status (order_id, status, updated_by) VALUES (?, 'preparing', ?)");
    $status_stmt->bind_param("ii", $order_id, $customer_id);
    
    if (!$status_stmt->execute()) {
        throw new Exception("Failed to create order status: " . $conn->error);
    }
    
    $conn->commit();
    
    // Return success response
    jsonResponse(true, 'Order created successfully', [
        'order_id' => $order_id,
        'total_amount' => $total_amount,
        'subtotal' => $subtotal,
        'delivery_fee' => $delivery_fee,
        'delivery_address' => $delivery_address,
        'payment_method' => $payment_method,
        'payment_status' => $payment_status
    ], 201);
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    error_log("Checkout error: " . $e->getMessage());
    jsonResponse(false, 'Checkout failed: ' . $e->getMessage(), [], 500);
} finally {
    closeDBConnection($conn);
}
?>