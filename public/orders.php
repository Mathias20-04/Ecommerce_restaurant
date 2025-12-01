<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/middleware.php';

// Check authentication but don't output JSON
$auth = requireAuth();
$currentUser = $auth->getCurrentUser();

// Check for order confirmation from query parameters
$order_confirmed = isset($_GET['order_confirmed']) && $_GET['order_confirmed'] == 'true';
$confirmed_order_id = $_GET['order_id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Aunt Joy's Restaurant</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #e74c3c;
            --primary-dark: #2b91c0ff;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --dark-color: #2c3e50;
            --gray-dark: #7f8c8d;
            --gray-light: #ecf0f1;
            --white: #ffffff;
            --border-radius: 12px;
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            box-shadow: 0 4px 20px rgba(231, 76, 60, 0.3);
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
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--white);
            text-decoration: none;
        }

        .logo img {
            height: 60px;
            width: auto;
            border-radius: 60px;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-links a {
            color: var(--white);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: var(--transition);
            font-weight: 500;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .nav-links a.active {
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .user-dropdown {
            position: relative;
            cursor: pointer;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: var(--transition);
            color:blue;
        }

        .user-info:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: blue;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .user-name {
            font-weight: 600;
            color: blue;
        }

        .dropdown-arrow {
            color: blue;
            font-size: 0.8rem;
            transition: transform 0.3s ease;
        }

        .user-dropdown:hover .dropdown-arrow {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: blue;
            color:blue;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            min-width: 220px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: var(--transition);
            z-index: 1000;
            overflow: hidden;
         
        }

        .user-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
              color:blue;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            color: blue;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .dropdown-item:hover {
            background: var(--gray-light);
        }

        .dropdown-divider {
            height: 1px;
            background: var(--gray-light);
            margin: 0.5rem 0;
        }

        .cart-count {
            background: #ff4444;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
            font-weight: bold;
            min-width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            margin-left: 0.25rem;
        }

        /* Orders Container */
        .orders-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
            animation: slideInUp 0.6s ease-out;
        }

        .orders-header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }

        .orders-header::after {
            content: '';
            position: absolute;
            bottom: -1rem;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 2px;
        }

        .orders-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .orders-header p {
            font-size: 1.1rem;
            color: var(--gray-dark);
            max-width: 500px;
            margin: 0 auto;
        }

        /* Order Confirmation */
        .order-confirmation {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: 2px solid #28a745;
            border-radius: var(--border-radius);
            padding: 2.5rem;
            margin-bottom: 3rem;
            text-align: center;
            animation: slideInUp 0.8s ease-out;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .order-confirmation::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--success-color), #28a745);
        }

        .confirmation-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            color: var(--success-color);
        }

        .confirmation-title {
            color: #155724;
            margin-bottom: 1rem;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .confirmation-message {
            color: #155724;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        /* Orders Grid */
        .orders-grid {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        /* Order Card */
        .order-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            transition: var(--transition);
            border-left: 4px solid var(--primary-color);
            position: relative;
            overflow: hidden;
        }

        .order-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.15);
        }

        .order-card:hover::before {
            transform: scaleX(1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .order-info {
            flex: 1;
        }

        .order-id {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .order-id i {
            color: var(--primary-color);
        }

        .order-date {
            color: var(--gray-dark);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .order-status {
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .status-preparing {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-out-for-delivery {
            background: linear-gradient(135deg, #cce7ff, #b3d7ff);
            color: #004085;
            border: 1px solid #b3d7ff;
        }

        .status-delivered {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-cancelled {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-pending {
            background: linear-gradient(135deg, #e2e3e5, #d6d8db);
            color: #383d41;
            border: 1px solid #d6d8db;
        }

        /* Order Details */
        .order-details {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .order-details {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        /* Order Summary */
        .order-summary {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border: 1px solid var(--gray-light);
        }

        .summary-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .summary-title i {
            color: var(--primary-color);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .summary-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .summary-row.total {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid var(--gray-light);
        }

        .summary-label {
            color: var(--gray-dark);
            font-weight: 500;
        }

        .summary-value {
            color: var(--dark-color);
            font-weight: 600;
        }

        /* Delivery Info */
        .delivery-info {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-top: 1.5rem;
            border: 1px solid #bbdefb;
        }

        .delivery-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .delivery-title i {
            color: var(--secondary-color);
        }

        .info-row {
            display: flex;
            margin-bottom: 0.75rem;
        }

        .info-label {
            font-weight: 600;
            min-width: 120px;
            color: var(--dark-color);
        }

        .info-value {
            color: var(--gray-dark);
            flex: 1;
        }

        /* Order Actions */
        .order-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            flex-wrap: wrap;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-light);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4);
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: var(--white);
            transform: translateY(-3px);
        }

        /* Empty State */
        .empty-orders {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray-dark);
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
            color: var(--primary-color);
        }

        .empty-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--dark-color);
        }

        .empty-message {
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }

        /* Loading State */
        .loading {
            text-align: center;
            padding: 3rem;
            color: var(--gray-dark);
        }

        .loading-spinner {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }

            .orders-header h1 {
                font-size: 2rem;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-actions {
                justify-content: flex-start;
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
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="index.php#categories"><i class="fas fa-utensils"></i> Menu</a></li>
                    <li class="auth-required">
                        <a href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart 
                            <span class="cart-count">0</span>
                        </a>
                    </li>
                    <li class="auth-required">
                        <a href="orders.php" class="active"><i class="fas fa-box"></i> Orders</a>
                    </li>
                    <li class="auth-required user-profile">
                        <div class="user-dropdown">
                            <div class="user-info">
                                <span class="user-avatar">
                                    <?php 
                                        $name = $currentUser['full_name'] ?? $currentUser['username'];
                                        echo strtoupper(substr($name, 0, 1)); 
                                    ?>
                                </span>
                                <span class="user-name"><?php echo htmlspecialchars($name); ?></span>
                                <span class="dropdown-arrow"><i class="fas fa-chevron-down"></i></span>
                            </div>
                            <div class="dropdown-menu">
                                <a href="profile.php" class="dropdown-item"><i class="fas fa-user"></i> My Profile</a>
                                <a href="orders.php" class="dropdown-item active"><i class="fas fa-box"></i> My Orders</a>
                                <div class="dropdown-divider"></div>
                                <a href="logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            </div>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="orders-container">
        <div class="orders-header">
            <h1>My Orders</h1>
            <p>Track your orders and delivery status</p>
        </div>

        <?php if ($order_confirmed): ?>
        <div class="order-confirmation" id="order-confirmation">
            <div class="confirmation-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2 class="confirmation-title">Order Confirmed!</h2>
            <p class="confirmation-message">
                Thank you for your order! Your order <strong>#<?php echo $confirmed_order_id; ?></strong> has been successfully placed and is being prepared.
            </p>
            <p>You'll receive updates about your order status. Expected delivery time: <strong>30-45 minutes</strong></p>
        </div>
        <?php endif; ?>

        <div class="orders-grid" id="orders-list">
            <div class="loading">
                <div class="loading-spinner">
                    <i class="fas fa-spinner"></i>
                </div>
                <p>Loading your orders...</p>
            </div>
        </div>
    </div>

    <script>
        // Function to fetch orders from API
        async function fetchOrders() {
            try {
                const response = await fetch('../api/orders/list.php');
                const result = await response.json();

                if (result.success) {
                    displayOrders(result.data.orders);
                } else {
                    showError(result.message || 'Failed to load orders');
                }
            } catch (error) {
                console.error('Error fetching orders:', error);
                showError('Network error: Please check your internet connection');
            }
        }

        // Function to display orders
        function displayOrders(orders) {
            const ordersList = document.getElementById('orders-list');
            
            if (!orders || orders.length === 0) {
                ordersList.innerHTML = `
                    <div class="empty-orders">
                        <div class="empty-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <h2 class="empty-title">No Orders Yet</h2>
                        <p class="empty-message">You haven't placed any orders yet. Start exploring our menu!</p>
                        <a href="index.php#categories" class="btn btn-primary">
                            <i class="fas fa-utensils"></i> Browse Menu
                        </a>
                    </div>
                `;
                return;
            }

            let html = '';
            orders.forEach(order => {
                const orderDate = new Date(order.order_date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                const statusUpdated = new Date(order.status_updated_at).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                html += `
                    <div class="order-card" id="order-${order.order_id}">
                        <div class="order-header">
                            <div class="order-info">
                                <div class="order-id">
                                    <i class="fas fa-receipt"></i>
                                    Order #${order.order_id}
                                </div>
                                <div class="order-date">
                                    <i class="fas fa-calendar"></i>
                                    ${orderDate}
                                </div>
                            </div>
                            <div class="order-status status-${order.current_status ? order.current_status.toLowerCase().replace(' ', '-') : 'pending'}">
                                <i class="fas ${getStatusIcon(order.current_status)}"></i>
                                ${order.current_status || 'Pending'}
                            </div>
                        </div>

                        <div class="order-details">
                            <div class="order-summary">
                                <h3 class="summary-title">
                                    <i class="fas fa-receipt"></i> Order Summary
                                </h3>
                                <div class="summary-row">
                                    <span class="summary-label">Total Amount</span>
                                    <span class="summary-value">MK ${parseFloat(order.total_amount).toFixed(2)}</span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-label">Payment Status</span>
                                    <span class="summary-value ${order.payment_status === 'paid' ? 'status-delivered' : 'status-preparing'}">
                                        ${order.payment_status || 'Pending'}
                                    </span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-label">Order Status</span>
                                    <span class="summary-value">${order.current_status || 'Pending'}</span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-label">Last Updated</span>
                                    <span class="summary-value">${statusUpdated}</span>
                                </div>
                            </div>
                        </div>

                        ${order.delivery_address ? `
                        <div class="delivery-info">
                            <h3 class="delivery-title">
                                <i class="fas fa-truck"></i> Delivery Information
                            </h3>
                            <div class="info-row">
                                <span class="info-label">Delivery Address:</span>
                                <span class="info-value">${order.delivery_address}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Phone:</span>
                                <span class="info-value">${order.customer_phone}</span>
                            </div>
                            ${order.special_instructions ? `
                            <div class="info-row">
                                <span class="info-label">Special Instructions:</span>
                                <span class="info-value">${order.special_instructions}</span>
                            </div>
                            ` : ''}
                        </div>
                        ` : ''}

                        <div class="order-actions">
                            <button class="btn btn-outline" onclick="reorder(${order.order_id})">
                                <i class="fas fa-redo"></i> Reorder
                            </button>
                            <a href="contact.php" class="btn btn-primary">
                                <i class="fas fa-headset"></i> Support
                            </a>
                            ${order.current_status === 'delivered' ? `
                                <button class="btn btn-primary" onclick="rateOrder(${order.order_id})">
                                    <i class="fas fa-star"></i> Rate Order
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            });

            ordersList.innerHTML = html;
        }

        // Helper function to get status icon
        function getStatusIcon(status) {
            if (!status) return 'fa-clock';
            
            const statusIcons = {
                'preparing': 'fa-utensils',
                'out for delivery': 'fa-motorcycle',
                'delivered': 'fa-check-circle',
                'cancelled': 'fa-times-circle',
                'pending': 'fa-clock'
            };
            
            return statusIcons[status.toLowerCase()] || 'fa-clock';
        }

        // Function to show error
        function showError(message) {
            const ordersList = document.getElementById('orders-list');
            ordersList.innerHTML = `
                <div class="empty-orders">
                    <div class="empty-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h2 class="empty-title">Error Loading Orders</h2>
                    <p class="empty-message">${message}</p>
                    <button class="btn btn-primary" onclick="fetchOrders()">
                        <i class="fas fa-redo"></i> Try Again
                    </button>
                </div>
            `;
        }

        // Function to handle reorder
        function reorder(orderId) {
            if (confirm('Would you like to reorder these items?')) {
                const btn = event.target;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding to cart...';
                btn.disabled = true;

                // Simulate API call (you'll need to implement this)
                setTimeout(() => {
                    alert('Items from order #' + orderId + ' have been added to your cart!');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    
                    // Redirect to cart page
                    window.location.href = 'cart.php';
                }, 1500);
            }
        }

        // Function to rate order
        function rateOrder(orderId) {
            alert('Rating feature for order #' + orderId + ' would open here');
            // Implement rating functionality
        }

        // Auto-hide confirmation message
        document.addEventListener('DOMContentLoaded', function() {
            // Fetch orders when page loads
            fetchOrders();

            // Auto-hide confirmation after 10 seconds
            const orderConfirmation = document.getElementById('order-confirmation');
            if (orderConfirmation) {
                setTimeout(() => {
                    orderConfirmation.style.opacity = '0';
                    setTimeout(() => {
                        orderConfirmation.style.display = 'none';
                    }, 500);
                }, 10000);
            }

            // Add interactive animations to order cards
            document.addEventListener('mouseover', function(e) {
                if (e.target.closest('.order-card')) {
                    const card = e.target.closest('.order-card');
                    card.style.transform = 'translateY(-8px)';
                }
            });

            document.addEventListener('mouseout', function(e) {
                if (e.target.closest('.order-card')) {
                    const card = e.target.closest('.order-card');
                    card.style.transform = 'translateY(0)';
                }
            });
        });
    </script>
</body>
</html>