<?php
// api/orders/cancel.php
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
if (!$input || !isset($input['order_id'])) {
    jsonResponse(false, 'Order ID is required', [], 400);
}

$order_id = intval($input['order_id']);
$user_id = $_SESSION['user_id'];

$conn = getDBConnection();
if (!$conn) {
    jsonResponse(false, 'Database connection failed', [], 500);
}

try {
    // Verify order belongs to user and can be cancelled
    $check_stmt = $conn->prepare("SELECT o.order_id, os.status 
                                  FROM orders o
                                  LEFT JOIN (
                                      SELECT order_id, status 
                                      FROM order_status 
                                      WHERE (order_id, created_at) IN (
                                          SELECT order_id, MAX(created_at) 
                                          FROM order_status 
                                          GROUP BY order_id
                                      )
                                  ) os ON o.order_id = os.order_id
                                  WHERE o.order_id = ? AND o.customer_id = ?");
    $check_stmt->bind_param("ii", $order_id, $user_id);
    $check_stmt->execute();
    $order = $check_stmt->get_result()->fetch_assoc();
    
    if (!$order) {
        jsonResponse(false, 'Order not found or access denied', [], 404);
    }
    
    // Check if order can be cancelled (only preparing status)
    if ($order['status'] !== 'preparing') {
        jsonResponse(false, 'Order cannot be cancelled at this stage', [], 400);
    }
    
    // Add cancelled status
    $status_stmt = $conn->prepare("INSERT INTO order_status (order_id, status, status_notes, updated_by) 
                                   VALUES (?, 'cancelled', 'Order cancelled by customer', ?)");
    $status_stmt->bind_param("ii", $order_id, $user_id);
    
    if (!$status_stmt->execute()) {
        throw new Exception("Failed to cancel order: " . $conn->error);
    }
    
    jsonResponse(true, 'Order cancelled successfully');
    
} catch (Exception $e) {
    jsonResponse(false, 'Failed to cancel order: ' . $e->getMessage(), [], 500);
} finally {
    closeDBConnection($conn);
}
?>