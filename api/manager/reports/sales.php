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
    $month = $_GET['month'] ?? date('m');
    $year = $_GET['year'] ?? date('Y');
    
    // Validate month and year
    if (!is_numeric($month) || $month < 1 || $month > 12) {
        jsonResponse(false, 'Invalid month. Must be between 1-12', [], 400);
    }
    
    if (!is_numeric($year) || $year < 2020 || $year > 2030) {
        jsonResponse(false, 'Invalid year', [], 400);
    }
    
    // Calculate date ranges
    $start_date = "$year-$month-01";
    $end_date = date('Y-m-t', strtotime($start_date));
    
    // 1. Total Revenue and Orders
    $summary_query = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(total_amount) as total_revenue,
                        AVG(total_amount) as average_order_value,
                        MAX(total_amount) as highest_order,
                        MIN(total_amount) as lowest_order
                      FROM orders 
                      WHERE DATE(order_date) BETWEEN ? AND ?
                      AND payment_status = 'paid'";
    
    $stmt = $conn->prepare($summary_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $summary = $stmt->get_result()->fetch_assoc();
    
    // 2. Best Selling Items
    $best_sellers_query = "SELECT 
                            m.meal_name,
                            m.meal_id,
                            SUM(oi.quantity) as total_quantity,
                            SUM(oi.item_total) as total_revenue,
                            COUNT(DISTINCT oi.order_id) as times_ordered
                          FROM order_items oi
                          JOIN meals m ON oi.meal_id = m.meal_id
                          JOIN orders o ON oi.order_id = o.order_id
                          WHERE DATE(o.order_date) BETWEEN ? AND ?
                          AND o.payment_status = 'paid'
                          GROUP BY m.meal_id, m.meal_name
                          ORDER BY total_quantity DESC
                          LIMIT 10";
    
    $stmt2 = $conn->prepare($best_sellers_query);
    $stmt2->bind_param("ss", $start_date, $end_date);
    $stmt2->execute();
    $best_sellers = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // 3. Daily Sales Trend
    $daily_trend_query = "SELECT 
                            DATE(order_date) as sale_date,
                            COUNT(*) as orders_count,
                            SUM(total_amount) as daily_revenue
                          FROM orders
                          WHERE DATE(order_date) BETWEEN ? AND ?
                          AND payment_status = 'paid'
                          GROUP BY DATE(order_date)
                          ORDER BY sale_date";
    
    $stmt3 = $conn->prepare($daily_trend_query);
    $stmt3->bind_param("ss", $start_date, $end_date);
    $stmt3->execute();
    $daily_trend = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // 4. Order Status Distribution
    $status_query = "SELECT 
                      os.status,
                      COUNT(*) as order_count
                    FROM (
                      SELECT order_id, status 
                      FROM order_status 
                      WHERE (order_id, created_at) IN (
                        SELECT order_id, MAX(created_at) 
                        FROM order_status 
                        GROUP BY order_id
                      )
                    ) os
                    JOIN orders o ON os.order_id = o.order_id
                    WHERE DATE(o.order_date) BETWEEN ? AND ?
                    GROUP BY os.status";
    
    $stmt4 = $conn->prepare($status_query);
    $stmt4->bind_param("ss", $start_date, $end_date);
    $stmt4->execute();
    $status_distribution = $stmt4->get_result()->fetch_all(MYSQLI_ASSOC);
    
    jsonResponse(true, 'Sales report generated successfully', [
        'report_period' => [
            'month' => intval($month),
            'year' => intval($year),
            'start_date' => $start_date,
            'end_date' => $end_date
        ],
        'summary' => $summary,
        'best_sellers' => $best_sellers,
        'daily_trend' => $daily_trend,
        'status_distribution' => $status_distribution
    ]);
    
} catch (Exception $e) {
    jsonResponse(false, 'Failed to generate sales report: ' . $e->getMessage(), [], 500);
} finally {
    closeDBConnection($conn);
}
?>