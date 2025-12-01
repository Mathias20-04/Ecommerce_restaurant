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

$conn = getDBConnection();
if (!$conn) {
    jsonResponse(false, 'Database connection failed', [], 500);
}

try {
    $customer_id = $_SESSION['user_id'];
    
    $query = "SELECT o.order_id, o.order_date, o.total_amount, o.delivery_address, 
                     o.customer_phone, o.special_instructions, o.payment_status,
                     os.status as current_status,
                     (SELECT created_at FROM order_status WHERE order_id = o.order_id ORDER BY created_at DESC LIMIT 1) as status_updated_at
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
              WHERE o.customer_id = ?
              ORDER BY o.order_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customer_id);
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