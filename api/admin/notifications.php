<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/middleware.php';

setCORSHeaders();
handlePreflight();
header('Content-Type: application/json');

// Only admins can access notifications
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

$conn = getDBConnection();
if (!$conn) {
    jsonResponse(false, 'Database connection failed', [], 500);
}

try {
    // Get recent pending orders (last 2 hours)
    $pendingOrdersQuery = "
        SELECT COUNT(*) as count 
        FROM orders o 
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
        AND EXISTS (
            SELECT 1 FROM order_status os 
            WHERE os.order_id = o.order_id 
            AND os.status IN ('preparing', 'out_for_delivery')
            ORDER BY os.created_at DESC 
            LIMIT 1
        )
    ";
    $pendingOrdersResult = $conn->query($pendingOrdersQuery);
    $pendingOrdersCount = $pendingOrdersResult->fetch_assoc()['count'];

    // Get low stock meals (less than 10 in stock if you have stock management)
    $lowStockQuery = "
        SELECT COUNT(*) as count 
        FROM meals 
        WHERE is_available = 1 
        AND stock_quantity < 10
    ";
    $lowStockResult = $conn->query($lowStockQuery);
    $lowStockCount = $lowStockResult ? $lowStockResult->fetch_assoc()['count'] : 0;

    // Get today's cancelled orders
    $cancelledOrdersQuery = "
        SELECT COUNT(*) as count 
        FROM orders o 
        WHERE DATE(o.created_at) = CURDATE()
        AND EXISTS (
            SELECT 1 FROM order_status os 
            WHERE os.order_id = o.order_id 
            AND os.status = 'cancelled'
            ORDER BY os.created_at DESC 
            LIMIT 1
        )
    ";
    $cancelledOrdersResult = $conn->query($cancelledOrdersQuery);
    $cancelledOrdersCount = $cancelledOrdersResult->fetch_assoc()['count'];

    $notifications = [];

    // Pending orders notification
    if ($pendingOrdersCount > 0) {
        $notifications[] = [
            'id' => 1,
            'type' => 'pending_orders',
            'title' => 'Pending Orders',
            'message' => "You have {$pendingOrdersCount} orders pending in the last 2 hours",
            'priority' => 'high',
            'action_url' => 'orders.php?status=preparing',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

    // Low stock notification (if you implement stock management)
    if ($lowStockCount > 0) {
        $notifications[] = [
            'id' => 2,
            'type' => 'low_stock',
            'title' => 'Low Stock Alert',
            'message' => "{$lowStockCount} meals are running low on stock",
            'priority' => 'medium',
            'action_url' => 'meals.php?filter=low_stock',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

    // Cancelled orders notification
    if ($cancelledOrdersCount > 0) {
        $notifications[] = [
            'id' => 3,
            'type' => 'cancelled_orders',
            'title' => 'Cancelled Orders',
            'message' => "{$cancelledOrdersCount} orders were cancelled today",
            'priority' => 'low',
            'action_url' => 'orders.php?status=cancelled',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

    // Check for system notifications (you can expand this)
    $systemNotifications = $this->checkSystemNotifications($conn);
    $notifications = array_merge($notifications, $systemNotifications);

    jsonResponse(true, 'Notifications retrieved', [
        'notifications' => $notifications,
        'unread_count' => count($notifications)
    ]);

} catch (Exception $e) {
    jsonResponse(false, 'Failed to retrieve notifications: ' . $e->getMessage(), [], 500);
} finally {
    closeDBConnection($conn);
}

function checkSystemNotifications($conn) {
    $systemNotifications = [];
    
    // Check if there are any meals without categories
    $noCategoryQuery = "SELECT COUNT(*) as count FROM meals WHERE category_id IS NULL OR category_id = 0";
    $noCategoryResult = $conn->query($noCategoryQuery);
    $noCategoryCount = $noCategoryResult->fetch_assoc()['count'];
    
    if ($noCategoryCount > 0) {
        $systemNotifications[] = [
            'id' => 4,
            'type' => 'meals_no_category',
            'title' => 'Uncategorized Meals',
            'message' => "{$noCategoryCount} meals are not assigned to any category",
            'priority' => 'medium',
            'action_url' => 'meals.php?filter=no_category',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    // Check for outdated meals (not updated in last 30 days)
    $outdatedQuery = "SELECT COUNT(*) as count FROM meals WHERE updated_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $outdatedResult = $conn->query($outdatedQuery);
    $outdatedCount = $outdatedResult->fetch_assoc()['count'];
    
    if ($outdatedCount > 0) {
        $systemNotifications[] = [
            'id' => 5,
            'type' => 'outdated_meals',
            'title' => 'Outdated Meals',
            'message' => "{$outdatedCount} meals haven't been updated in over 30 days",
            'priority' => 'low',
            'action_url' => 'meals.php?filter=outdated',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    return $systemNotifications;
}
?>