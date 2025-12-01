<?php
require_once '../../../includes/config.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/middleware.php';

setCORSHeaders();
handlePreflight();
header('Content-Type: application/json');

// Only sales and admin can update status
$auth = requireAuth();
if (!$auth->hasRole('sales') && !$auth->hasRole('admin')) {
    jsonResponse(false, 'Insufficient permissions', [], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonResponse(false, 'Invalid JSON input', [], 400);
}

$order_id = $input['order_id'] ?? null;
$status = $input['status'] ?? '';
$status_notes = $input['status_notes'] ?? '';

if (!$order_id || !is_numeric($order_id)) {
    jsonResponse(false, 'Valid order ID is required', [], 400);
}

$allowed_statuses = ['preparing', 'out_for_delivery', 'delivered'];
if (!in_array($status, $allowed_statuses)) {
    jsonResponse(false, 'Invalid status. Allowed: ' . implode(', ', $allowed_statuses), [], 400);
}

$conn = getDBConnection();
if (!$conn) {
    jsonResponse(false, 'Database connection failed', [], 500);
}

try {
    // Verify order exists
    $check_stmt = $conn->prepare("SELECT order_id FROM orders WHERE order_id = ?");
    $check_stmt->bind_param("i", $order_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows === 0) {
        jsonResponse(false, 'Order not found', [], 404);
    }
    
    // Update order status
    $user_id = $_SESSION['user_id'];
    $status_stmt = $conn->prepare("INSERT INTO order_status (order_id, status, status_notes, updated_by) VALUES (?, ?, ?, ?)");
    $status_stmt->bind_param("issi", $order_id, $status, $status_notes, $user_id);
    
    if ($status_stmt->execute()) {
        jsonResponse(true, 'Order status updated successfully', [
            'order_id' => $order_id,
            'new_status' => $status,
            'updated_by' => $user_id
        ]);
    } else {
        jsonResponse(false, 'Failed to update order status: ' . $conn->error, [], 500);
    }
    
} catch (Exception $e) {
    jsonResponse(false, 'Error updating order status: ' . $e->getMessage(), [], 500);
} finally {
    closeDBConnection($conn);
}
?>