<?php
require_once '../../../includes/config.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/middleware.php';

setCORSHeaders();
handlePreflight();
header('Content-Type: application/json');

// Only sales and admin can access
$auth = requireAuth();
if (!$auth->hasRole('sales') && !$auth->hasRole('admin')) {
    jsonResponse(false, 'Insufficient permissions', [], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

$conn = getDBConnection();
if (!$conn) {
    jsonResponse(false, 'Database connection failed', [], 500);
}

try {
    $status_filter = $_GET['status'] ?? '';
    
    $query = "SELECT o.order_id, o.order_date, o.total_amount, o.delivery_address, 
                     o.customer_phone, o.special_instructions, o.payment_status,
                     u.full_name as customer_name, u.phone as customer_contact,
                     os.status as current_status,
                     (SELECT created_at FROM order_status WHERE order_id = o.order_id ORDER BY created_at DESC LIMIT 1) as status_updated_at
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
              ) os ON o.order_id = os.order_id";
    
    $params = [];
    $types = '';
    
    if (!empty($status_filter)) {
        $query .= " WHERE os.status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }
    
    $query .= " ORDER BY o.order_date DESC";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    
    jsonResponse(true, 'Orders retrieved successfully', ['orders' => $orders]);
    
} catch (Exception $e) {
    jsonResponse(false, 'Failed to retrieve orders: ' . $e->getMessage(), [], 500);
} finally {
    closeDBConnection($conn);
}
?>