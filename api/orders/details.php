<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/middleware.php';

setCORSHeaders();
handlePreflight();
header('Content-Type: application/json');

// User must be logged in
$auth = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

$order_id = $_GET['id'] ?? null;
if (!$order_id || !is_numeric($order_id)) {
    jsonResponse(false, 'Valid order ID is required', [], 400);
}

$conn = getDBConnection();
if (!$conn) {
    jsonResponse(false, 'Database connection failed', [], 500);
}

try {
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];
    
    // Base query - users can only see their own orders unless admin/sales
    $query = "SELECT o.order_id, o.order_date, o.total_amount, o.delivery_address, 
                     o.customer_phone, o.special_instructions, o.payment_status,
                     u.full_name as customer_name, u.email as customer_email,
                     os.status as current_status
              FROM orders o
              JOIN users u ON o.customer_id = u.user_id
              LEFT JOIN (
                  SELECT order_id, status 
                  FROM order_status 
                  WHERE (order_id, created_at) IN (
                      SELECT order_id, MAX(created_at) 
                      FROM order_status 
                      GROUP BY order_id
                  )
              ) os ON o.order_id = os.order_id
              WHERE o.order_id = ?";
    
    $params = [$order_id];
    $types = "i";
    
    if (!in_array($user_role, ['admin', 'sales'])) {
        $query .= " AND o.customer_id = ?";
        $params[] = $user_id;
        $types .= "i";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    if (!$order) {
        jsonResponse(false, 'Order not found or access denied', [], 404);
    }
    
    // Get order items
    $items_stmt = $conn->prepare("SELECT oi.meal_id, oi.quantity,
                                  oi.unit_price AS price,
                                  oi.item_total AS subtotal,
                                  m.meal_name, m.image_url
                                  FROM order_items oi
                                  JOIN meals m ON oi.meal_id = m.meal_id
                                  WHERE oi.order_id = ?");
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get status history
    $status_stmt = $conn->prepare("SELECT os.status, os.status_notes, os.created_at, u.username as updated_by
                                   FROM order_status os
                                   LEFT JOIN users u ON os.updated_by = u.user_id
                                   WHERE os.order_id = ?
                                   ORDER BY os.created_at ASC");
    $status_stmt->bind_param("i", $order_id);
    $status_stmt->execute();
    $status_history = $status_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    jsonResponse(true, 'Order details retrieved successfully', [
        'order' => $order,
        'items' => $items,
        'status_history' => $status_history
    ]);
    
} catch (Exception $e) {
    jsonResponse(false, 'Failed to retrieve order details: ' . $e->getMessage(), [], 500);
} finally {
    closeDBConnection($conn);
}
?>