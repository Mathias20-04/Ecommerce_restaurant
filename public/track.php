<?php
// track.php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/middleware.php';

$error = '';
$orderData = null;
$orderItems = [];
$statusHistory = [];

$auth = new Auth();

// Redirect if not logged in
if(!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Process order tracking
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_id = sanitizeInput($_POST['order_id']);
    
    if(empty($order_id)) {
        $error = "Please enter an order ID!";
    } else {
        // Fetch order details directly
        $orderData = fetchOrderDetailsDirect($order_id);
        
        if($orderData && $orderData['success']) {
            $orderData = $orderData['data'];
            $orderItems = $orderData['items'] ?? [];
            $statusHistory = $orderData['status_history'] ?? [];
        } else {
            $error = $orderData['message'] ?? "Order not found or access denied!";
        }
    }
}

// Direct function to fetch order details using MySQLi
function fetchOrderDetailsDirect($order_id) {
    $conn = getDBConnection();
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'Authentication required'];
        }
        
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['role'] ?? 'customer';
        
        if (!$order_id || !is_numeric($order_id)) {
            return ['success' => false, 'message' => 'Valid order ID is required'];
        }
        
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
        
        if (!in_array($user_role, ['admin', 'sales'])) {
            $query .= " AND o.customer_id = ?";
            $params[] = $user_id;
        }
        
        $stmt = $conn->prepare($query);
        
        // Bind parameters dynamically
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found or access denied'];
        }
        
        // Get order items
        $items_query = "
            SELECT oi.meal_id, oi.quantity, oi.unit_price, oi.item_total, m.meal_name, m.image_url
            FROM order_items oi
            JOIN meals m ON oi.meal_id = m.meal_id
            WHERE oi.order_id = ?
        ";
        $items_stmt = $conn->prepare($items_query);
        $items_stmt->bind_param('s', $order_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        $items = [];
        while ($row = $items_result->fetch_assoc()) {
            $items[] = $row;
        }
        
        // Get status history
        $status_query = "
            SELECT os.status, os.status_notes, os.created_at, u.username as updated_by
            FROM order_status os
            LEFT JOIN users u ON os.updated_by = u.user_id
            WHERE os.order_id = ?
            ORDER BY os.created_at ASC
        ";
        $status_stmt = $conn->prepare($status_query);
        $status_stmt->bind_param('s', $order_id);
        $status_stmt->execute();
        $status_result = $status_stmt->get_result();
        $status_history = [];
        while ($row = $status_result->fetch_assoc()) {
            $status_history[] = $row;
        }
        
        // Close statements and connection
        $stmt->close();
        $items_stmt->close();
        $status_stmt->close();
        closeDBConnection($conn);
        
        return [
            'success' => true, 
            'message' => 'Order details retrieved successfully',
            'data' => [
                'order' => $order,
                'items' => $items,
                'status_history' => $status_history
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Failed to retrieve order details: " . $e->getMessage());
        if (isset($conn)) {
            closeDBConnection($conn);
        }
        return ['success' => false, 'message' => 'Failed to retrieve order details: ' . $e->getMessage()];
    }
}

// Direct function to fetch recent orders
function fetchRecentOrdersDirect() {
    $conn = getDBConnection();
    
    if (!$conn) {
        error_log("Database connection failed in fetchRecentOrdersDirect");
        return [];
    }
    
    try {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            closeDBConnection($conn);
            return [];
        }
        
        $customer_id = (int)$_SESSION['user_id'];
        
        $query = "
            SELECT o.order_id, o.order_date, o.total_amount, o.delivery_address, 
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
            ORDER BY o.order_date DESC
            LIMIT 5
        ";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            closeDBConnection($conn);
            return [];
        }
        
        // bind as integer (safe cast above)
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $orders = $result->fetch_all(MYSQLI_ASSOC);
        
        $stmt->close();
        closeDBConnection($conn);
        
        return $orders ?? [];
        
    } catch (Exception $e) {
        error_log("Failed to retrieve recent orders: " . $e->getMessage());
        if (isset($conn)) {
            closeDBConnection($conn);
        }
        return [];
    }
}

// Function to get status progress percentage
function getStatusProgress($currentStatus) {
    $statusSteps = [
        'pending' => 20,
        'confirmed' => 40,
        'preparing' => 60,
        'ready' => 80,
        'out_for_delivery' => 90,
        'delivered' => 100,
        'cancelled' => 0
    ];
    
    return $statusSteps[$currentStatus] ?? 20;
}

// Function to get status display text
function getStatusDisplay($status) {
    $statusMap = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'preparing' => 'Preparing',
        'ready' => 'Ready for Pickup',
        'out_for_delivery' => 'Out for Delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled'
    ];
    
    return $statusMap[$status] ?? ucfirst($status);
}

// Function to get status class for styling
function getStatusClass($status) {
    $statusClasses = [
        'pending' => 'status-preparing',
        'confirmed' => 'status-preparing',
        'preparing' => 'status-preparing',
        'ready' => 'status-ready',
        'out_for_delivery' => 'status-out',
        'delivered' => 'status-delivered',
        'cancelled' => 'status-cancelled'
    ];
    
    return $statusClasses[$status] ?? 'status-preparing';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Order - Aunt Joy's Restaurant</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        /* Header Styles */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 1.8rem;
            font-weight: 700;
            color: #e74c3c;
            text-decoration: none;
        }

        .logo img {
            height: 50px;
            width: 50px;
            margin-right: 10px;
            border-radius: 50%;
            background-color: #ddc332;
            object-fit: cover;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background: #e74c3c;
            color: white;
            transform: translateY(-2px);
        }

        /* Hero Section */
        .track-hero {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.9), rgba(192, 57, 43, 0.9));
            color: white;
            padding: 6rem 0;
            text-align: center;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            animation: slideDown 1s ease-out;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            opacity: 0.9;
            animation: slideUp 1s ease-out 0.3s both;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Search Section */
        .search-section {
            padding: 4rem 0;
            background: white;
        }

        .search-container {
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
        }

        .search-title {
            font-size: 2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 2rem;
        }

        .search-form {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .search-input {
            flex: 1;
            padding: 1rem 1.5rem;
            border: 2px solid #e1e5e9;
            border-radius: 50px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }

        .btn-search {
            padding: 1rem 2rem;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-search:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .search-help {
            color: #666;
            font-size: 0.9rem;
        }

        .auth-error {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 500;
            animation: shake 0.5s ease-in-out;
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Tracking Section */
        .tracking-section {
            padding: 4rem 0;
            background: #f8f9fa;
        }

        .tracking-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .order-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            animation: slideUp 0.6s ease-out;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f8f9fa;
        }

        .order-info h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-preparing {
            background: #fff3cd;
            color: #856404;
        }

        .status-ready {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-out {
            background: #d4edda;
            color: #155724;
        }

        .status-delivered {
            background: #28a745;
            color: white;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .detail-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .detail-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .detail-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
        }

        /* Progress Tracker */
        .progress-tracker {
            position: relative;
            margin: 3rem 0;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
        }

        .progress-bar {
            position: absolute;
            top: 25px;
            left: 0;
            right: 0;
            height: 4px;
            background: #e1e5e9;
            z-index: 1;
        }

        .progress-fill {
            position: absolute;
            top: 25px;
            left: 0;
            height: 4px;
            background: #e74c3c;
            z-index: 2;
            transition: width 0.5s ease;
        }

        .progress-step {
            text-align: center;
            position: relative;
            z-index: 3;
            flex: 1;
        }

        .step-icon {
            width: 50px;
            height: 50px;
            background: white;
            border: 3px solid #e1e5e9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            transition: all 0.3s ease;
        }

        .step-icon.active {
            background: #e74c3c;
            border-color: #e74c3c;
            color: white;
        }

        .step-label {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        .step-label.active {
            color: #e74c3c;
            font-weight: 600;
        }

        /* Order Items */
        .order-items {
            margin-top: 2rem;
        }

        .items-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .item-list {
            list-style: none;
        }

        .item-list li {
            padding: 1rem;
            border-bottom: 1px solid #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .item-list li:last-child {
            border-bottom: none;
        }

        .item-name {
            font-weight: 500;
        }

        .item-price {
            color: #e74c3c;
            font-weight: 600;
        }

        /* Status History */
        .status-history {
            margin-top: 2rem;
        }

        .history-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .history-list {
            list-style: none;
        }

        .history-item {
            padding: 1rem;
            border-left: 3px solid #e74c3c;
            background: #f8f9fa;
            margin-bottom: 0.5rem;
            border-radius: 0 8px 8px 0;
        }

        .history-status {
            font-weight: 600;
            color: #2c3e50;
        }

        .history-date {
            font-size: 0.9rem;
            color: #666;
        }

        .history-notes {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.25rem;
        }

        /* Support Section */
        .support-section {
            padding: 4rem 0;
            background: white;
            text-align: center;
        }

        .support-content {
            max-width: 600px;
            margin: 0 auto;
        }

        .support-icon {
            font-size: 3rem;
            color: #e74c3c;
            margin-bottom: 1.5rem;
        }

        .support-text {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .btn-support {
            display: inline-block;
            padding: 1rem 2rem;
            background: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-support:hover {
            background: #c0392b;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(231, 76, 60, 0.3);
        }

        /* Footer */
        .footer {
            background: #2c3e50;
            color: white;
            padding: 4rem 0 2rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-section h3 {
            color: white;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }

        .footer-section p {
            opacity: 0.8;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.8rem;
        }

        .footer-links a {
            color: #ddd;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #e74c3c;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .search-form {
                flex-direction: column;
            }

            .order-details {
                grid-template-columns: 1fr;
            }

            .progress-steps {
                flex-wrap: wrap;
                gap: 1rem;
            }

            .progress-step {
                flex: 0 0 calc(50% - 0.5rem);
            }

            .nav-links {
                gap: 1rem;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .hero-title {
                font-size: 2rem;
            }

            .order-card {
                padding: 1.5rem;
            }

            .step-icon {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="logo">
                    <img src="../assets/images/kitchen_logo1.png" alt="Aunt Joy's Restaurant Logo" /> 
                    Aunt Joy's
                </a>
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="help.php">Help Center</a></li>
                    <li><a href="track.php" style="background: #e74c3c; color: white;">Track Order</a></li>
                    <li><a href="returns.php">Returns</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="track-hero">
        <div class="container">
            <h1 class="hero-title">Track Your Order</h1>
            <p class="hero-subtitle">Get real-time updates on your food delivery status</p>
        </div>
    </section>

    <!-- Search Section -->
    <section class="search-section">
        <div class="container">
            <div class="search-container">
                <h2 class="search-title">Enter Your Order Details</h2>
                
                <?php if($error): ?>
                    <div class="auth-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="search-form" id="trackForm">
                    <input type="text" name="order_id" class="search-input" placeholder="Enter Order ID (e.g., 1, 2, 3...)" required
                           value="<?php echo isset($_POST['order_id']) ? htmlspecialchars($_POST['order_id']) : ''; ?>">
                    <button type="submit" class="btn-search">
                        <i class="fas fa-search"></i> Track Order
                    </button>
                </form>
                
                <!-- Recent Orders Quick Access -->
                <?php
                // Fetch recent orders using direct database query
                $recent_orders = fetchRecentOrdersDirect();
                
                if ($recent_orders) {
                    echo '<div class="recent-orders" style="margin-top: 2rem;">';
                    echo '<h3 style="font-size: 1.2rem; margin-bottom: 1rem; color: #2c3e50;">Your Recent Orders</h3>';
                    echo '<div class="recent-orders-grid" style="display: grid; gap: 1rem;">';
                    
                    foreach ($recent_orders as $order) {
                        echo '<div class="recent-order-card" style="background: #f8f9fa; padding: 1rem; border-radius: 10px; cursor: pointer; border: 2px solid transparent; transition: all 0.3s ease;" '
                              . 'onmouseover="this.style.borderColor=\'#e74c3c\'; this.style.transform=\'translateY(-2px)\'" '
                              . 'onmouseout="this.style.borderColor=\'transparent\'; this.style.transform=\'translateY(0)\'" '
                              . 'onclick="document.querySelector(\'[name=order_id]\').value = ' . json_encode($order['order_id']) . '">';
                        echo '<div style="display: flex; justify-content: space-between; align-items: center;">';
                        echo '<div>';
                        echo '<strong>Order #' . htmlspecialchars($order['order_id']) . '</strong>';
                        echo '<div style="font-size: 0.9rem; color: #666;">' . htmlspecialchars(date('M j, Y g:i A', strtotime($order['order_date']))) . '</div>';
                        echo '</div>';
                        echo '<div style="text-align: right;">';
                        echo '<span class="order-status ' . htmlspecialchars(getStatusClass($order['current_status'] ?? 'pending')) . '" style="font-size: 0.8rem; display: block; margin-bottom: 0.25rem;">' . htmlspecialchars(getStatusDisplay($order['current_status'] ?? 'pending')) . '</span>';
                        echo '<div style="font-size: 0.9rem; font-weight: 600; color: #e74c3c;">MK ' . number_format($order['total_amount'] ?? 0, 2) . '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                    
                    echo '</div>';
                    echo '</div>';
                } else {
                    echo '<div class="recent-orders" style="margin-top: 2rem; text-align: center; color: #666;">';
                    echo '<p><i class="fas fa-info-circle"></i> No recent orders found. Place an order to see it here.</p>';
                    echo '</div>';
                }
                ?>
                
                <p class="search-help">
                    <i class="fas fa-info-circle"></i> 
                    You can find your Order ID in your order confirmation email or click on any of your recent orders above.
                </p>
            </div>
        </div>
    </section> 

    <!-- Tracking Section -->
    <?php if($orderData && !$error): ?>
    <section class="tracking-section" id="trackingResults">
        <div class="container">
            <div class="tracking-container">
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-info">
                            <h3>Order #<?php echo htmlspecialchars($orderData['order']['order_id']); ?></h3>
                            <p>Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($orderData['order']['order_date'])); ?></p>
                            <p>Customer: <?php echo htmlspecialchars($orderData['order']['customer_name'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="order-status <?php echo getStatusClass($orderData['order']['current_status']); ?>">
                            <?php echo getStatusDisplay($orderData['order']['current_status']); ?>
                        </div>
                    </div>

                    <div class="order-details">
                        <div class="detail-item">
                            <div class="detail-label">Total Amount</div>
                            <div class="detail-value">MK <?php echo number_format($orderData['order']['total_amount'], 2); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Delivery Address</div>
                            <div class="detail-value"><?php echo htmlspecialchars($orderData['order']['delivery_address'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Contact Phone</div>
                            <div class="detail-value"><?php echo htmlspecialchars($orderData['order']['customer_phone'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Payment Status</div>
                            <div class="detail-value"><?php echo ucfirst($orderData['order']['payment_status'] ?? 'pending'); ?></div>
                        </div>
                    </div>

                    <!-- Progress Tracker -->
                    <div class="progress-tracker">
                        <div class="progress-bar"></div>
                        <div class="progress-fill" style="width: <?php echo getStatusProgress($orderData['order']['current_status']); ?>%;"></div>
                        <div class="progress-steps">
                            <div class="progress-step">
                                <div class="step-icon <?php echo in_array($orderData['order']['current_status'], ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered']) ? 'active' : ''; ?>">
                                    <i class="fas fa-receipt"></i>
                                </div>
                                <div class="step-label <?php echo in_array($orderData['order']['current_status'], ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered']) ? 'active' : ''; ?>">Order Placed</div>
                            </div>
                            <div class="progress-step">
                                <div class="step-icon <?php echo in_array($orderData['order']['current_status'], ['confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered']) ? 'active' : ''; ?>">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="step-label <?php echo in_array($orderData['order']['current_status'], ['confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered']) ? 'active' : ''; ?>">Confirmed</div>
                            </div>
                            <div class="progress-step">
                                <div class="step-icon <?php echo in_array($orderData['order']['current_status'], ['preparing', 'ready', 'out_for_delivery', 'delivered']) ? 'active' : ''; ?>">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <div class="step-label <?php echo in_array($orderData['order']['current_status'], ['preparing', 'ready', 'out_for_delivery', 'delivered']) ? 'active' : ''; ?>">Preparing</div>
                            </div>
                            <div class="progress-step">
                                <div class="step-icon <?php echo in_array($orderData['order']['current_status'], ['ready', 'out_for_delivery', 'delivered']) ? 'active' : ''; ?>">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="step-label <?php echo in_array($orderData['order']['current_status'], ['ready', 'out_for_delivery', 'delivered']) ? 'active' : ''; ?>">Ready</div>
                            </div>
                            <div class="progress-step">
                                <div class="step-icon <?php echo in_array($orderData['order']['current_status'], ['out_for_delivery', 'delivered']) ? 'active' : ''; ?>">
                                    <i class="fas fa-motorcycle"></i>
                                </div>
                                <div class="step-label <?php echo in_array($orderData['order']['current_status'], ['out_for_delivery', 'delivered']) ? 'active' : ''; ?>">Out for Delivery</div>
                            </div>
                            <div class="progress-step">
                                <div class="step-icon <?php echo $orderData['order']['current_status'] === 'delivered' ? 'active' : ''; ?>">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div class="step-label <?php echo $orderData['order']['current_status'] === 'delivered' ? 'active' : ''; ?>">Delivered</div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <?php if(!empty($orderItems)): ?>
                    <div class="order-items">
                        <h4 class="items-title">Order Items</h4>
                        <ul class="item-list">
                            <?php foreach($orderItems as $item): ?>
                            <li>
                                <span class="item-name">
                                    <?php echo htmlspecialchars($item['meal_name']); ?> 
                                    (x<?php echo $item['quantity']; ?>)
                                </span>
                                <span class="item-price">
                                    MK <?php echo number_format($item['item_total'], 2); ?>
                                </span>
                            </li>
                            <?php endforeach; ?>
                            <li style="border-top: 2px solid #e1e5e9; padding-top: 1rem;">
                                <span class="item-name" style="font-weight: 600;">Total Amount</span>
                                <span class="item-price" style="font-weight: 700;">MK <?php echo number_format($orderData['order']['total_amount'], 2); ?></span>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- Special Instructions -->
                    <?php if(!empty($orderData['order']['special_instructions'])): ?>
                    <div class="order-items">
                        <h4 class="items-title">Special Instructions</h4>
                        <p style="color: #666; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                            <?php echo htmlspecialchars($orderData['order']['special_instructions']); ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <!-- Status History -->
                    <?php if(!empty($statusHistory)): ?>
                    <div class="status-history">
                        <h4 class="history-title">Status History</h4>
                        <ul class="history-list">
                            <?php foreach($statusHistory as $history): ?>
                            <li class="history-item">
                                <div class="history-status"><?php echo getStatusDisplay($history['status']); ?></div>
                                <div class="history-date">
                                    <?php echo date('F j, Y \a\t g:i A', strtotime($history['created_at'])); ?>
                                    <?php if(!empty($history['updated_by'])): ?>
                                    <br><small>by <?php echo htmlspecialchars($history['updated_by']); ?></small>
                                    <?php endif; ?>
                                </div>
                                <?php if(!empty($history['status_notes'])): ?>
                                <div class="history-notes"><?php echo htmlspecialchars($history['status_notes']); ?></div>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>


    <!-- Support Section -->
    <section class="support-section">
        <div class="container">
            <div class="support-content">
                <div class="support-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h2>Need Help With Your Order?</h2>
                <p class="support-text">
                    If you're experiencing issues with your order or have questions about the delivery, 
                    our customer support team is available to assist you.
                </p>
                <a href="contact.php" class="btn-support">Contact Support</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Aunt Joy's Restaurant</h3>
                    <p>Premium food delivery service in Mzuzu. We bring restaurant-quality meals to your home with fast, reliable delivery.</p>
                </div>
                
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <p><i class="fas fa-map-marker-alt"></i> Mzuzu City Center</p>
                    <p><i class="fas fa-phone"></i> +265 888 123 456</p>
                    <p><i class="fas fa-envelope"></i> info@auntjoys.mw</p>
                </div>
                
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="track.php">Track Order</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 Aunt Joy's Restaurant. Celebrating good food and great moments.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const trackForm = document.getElementById('trackForm');
            const progressFill = document.querySelector('.progress-fill');

            // Add loading state to form submission
            trackForm.addEventListener('submit', function() {
                const submitBtn = trackForm.querySelector('.btn-search');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Tracking...';
                submitBtn.disabled = true;

                // Re-enable button after 3 seconds in case of error
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            });

            // Auto-refresh order status every 30 seconds if tracking results are shown
            <?php if($orderData && !$error): ?>
            function refreshOrderStatus() {
                const orderId = '<?php echo $orderData["order"]["order_id"]; ?>';
                
                fetch('track.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'order_id=' + orderId
                })
                .then(response => response.text())
                .then(html => {
                    // This is a simplified approach - in a real app, you'd use AJAX to update specific elements
                    console.log('Order status refreshed');
                })
                .catch(error => {
                    console.error('Error refreshing order status:', error);
                });
            }

            // Refresh every 30 seconds
            setInterval(refreshOrderStatus, 30000);
            <?php endif; ?>

            // Add smooth animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observe order card for animation
            const orderCard = document.querySelector('.order-card');
            if (orderCard) {
                orderCard.style.opacity = '0';
                orderCard.style.transform = 'translateY(20px)';
                orderCard.style.transition = 'all 0.6s ease';
                observer.observe(orderCard);
            }
        });
    </script>
</body>
</html>