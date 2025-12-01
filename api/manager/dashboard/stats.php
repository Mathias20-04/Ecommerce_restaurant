<?php
require_once '../../../includes/config.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/middleware.php';

setCORSHeaders();
handlePreflight();
header('Content-Type: application/json');

// Only managers and admin can access
$auth = requireAuth();
if (!$auth->hasRole('manager') && !$auth->hasRole('admin')) {
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
    $today = date('Y-m-d');
    $current_month = date('Y-m');
    
    // Today's stats
    $today_query = "SELECT 
                      COUNT(*) as today_orders,
                      COALESCE(SUM(total_amount), 0) as today_revenue
                    FROM orders 
                    WHERE DATE(order_date) = ?
                    AND payment_status = 'paid'";
    
    $stmt = $conn->prepare($today_query);
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $today_stats = $stmt->get_result()->fetch_assoc();
    
    // Monthly stats
    $monthly_query = "SELECT 
                        COUNT(*) as monthly_orders,
                        COALESCE(SUM(total_amount), 0) as monthly_revenue
                      FROM orders 
                      WHERE DATE_FORMAT(order_date, '%Y-%m') = ?
                      AND payment_status = 'paid'";
    
    $stmt2 = $conn->prepare($monthly_query);
    $stmt2->bind_param("s", $current_month);
    $stmt2->execute();
    $monthly_stats = $stmt2->get_result()->fetch_assoc();
    
    // Total customers
    $customers_query = "SELECT COUNT(*) as total_customers FROM users WHERE role = 'customer'";
    $customers_result = $conn->query($customers_query);
    $customer_stats = $customers_result->fetch_assoc();
    
    // Available meals
    $meals_query = "SELECT COUNT(*) as total_meals, 
                           SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) as available_meals
                    FROM meals";
    $meals_result = $conn->query($meals_query);
    $meal_stats = $meals_result->fetch_assoc();
    
    // Recent orders (last 5)
    $recent_orders_query = "SELECT o.order_id, o.order_date, o.total_amount, u.full_name, os.status
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
                            ORDER BY o.order_date DESC
                            LIMIT 5";
    
    $recent_orders_result = $conn->query($recent_orders_query);
    $recent_orders = $recent_orders_result->fetch_all(MYSQLI_ASSOC);
    
    jsonResponse(true, 'Dashboard stats retrieved successfully', [
        'today' => $today_stats,
        'monthly' => $monthly_stats,
        'customers' => $customer_stats,
        'meals' => $meal_stats,
        'recent_orders' => $recent_orders
    ]);
    
} catch (Exception $e) {
    jsonResponse(false, 'Failed to retrieve dashboard stats: ' . $e->getMessage(), [], 500);
} finally {
    closeDBConnection($conn);
}
?>