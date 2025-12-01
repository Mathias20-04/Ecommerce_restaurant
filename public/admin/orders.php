<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$auth = new Auth();

// Redirect to login if not authenticated or not admin
if(!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header("Location: ../login.php");
    exit;
}

$conn = getDBConnection();

// Handle order status updates
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $status = sanitizeInput($_POST['status']);
    $status_notes = sanitizeInput($_POST['status_notes']);
    
    $stmt = $conn->prepare("INSERT INTO order_status (order_id, status, status_notes, updated_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $order_id, $status, $status_notes, $_SESSION['user_id']);
    
    if($stmt->execute()) {
        $success_message = "Order status updated successfully!";
    } else {
        $error_message = "Failed to update order status: " . $conn->error;
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Build query with filters using the correct column names from your schema
$query = "
    SELECT o.*, u.full_name, u.phone, u.email,
           (SELECT status FROM order_status WHERE order_id = o.order_id ORDER BY created_at DESC LIMIT 1) as current_status,
           (SELECT created_at FROM order_status WHERE order_id = o.order_id ORDER BY created_at DESC LIMIT 1) as status_updated_at
    FROM orders o 
    LEFT JOIN users u ON o.customer_id = u.user_id 
    WHERE 1=1
";

$params = [];
$types = '';

if($status_filter) {
    $query .= " AND (SELECT status FROM order_status WHERE order_id = o.order_id ORDER BY created_at DESC LIMIT 1) = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if($date_filter) {
    $query .= " AND DATE(o.order_date) = ?";
    $params[] = $date_filter;
    $types .= 's';
}

$query .= " ORDER BY o.order_date DESC";

$stmt = $conn->prepare($query);
if($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Aunt Joy's Restaurant</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .admin-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .card-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--dark-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }

        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-label {
            font-weight: 600;
            color: var(--dark-color);
        }

        .form-control {
            padding: 0.5rem;
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
            padding: 0.5rem 1rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            cursor: pointer;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th,
        .orders-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-light);
        }

        .orders-table th {
            background: var(--gray-light);
            font-weight: 600;
            color: var(--dark-color);
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-preparing { background: #fff3cd; color: #856404; }
        .status-out_for_delivery { background: #cce7ff; color: #004085; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .payment-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .payment-pending { background: #fff3cd; color: #856404; }
        .payment-paid { background: #d4edda; color: #155724; }
        .payment-failed { background: #f8d7da; color: #721c24; }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: var(--white);
            margin: 10% auto;
            padding: 2rem;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 500px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray-dark);
        }

        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <img src="../../assets/images/kitchen_logo1.png" alt="Aunt Joy's Restaurant Logo" /> 
                    Aunt Joy's - Admin
                </div>
                <ul class="nav-links">
                    <li><a href="../index.php">🏠 View Site</a></li>
                    <li><a href="../logout.php">🚪 Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Admin Navigation -->
    <nav class="admin-nav">
        <div class="container">
            <ul class="admin-nav-links">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="orders.php" class="active">📦 Orders</a></li>
                <li><a href="meals.php">🍽️ Meals</a></li>
                <li><a href="categories.php">📋 Categories</a></li>
                <li><a href="users.php">👥 Users & Roles</a></li>
            </ul>
        </div>
    </nav>

    <div class="admin-container">
        <div class="page-header">
            <h1>Manage Orders</h1>
        </div>

        <?php if($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="admin-card">
            <h2 class="card-title">Filters</h2>
            <form method="GET" action="" class="filters">
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="preparing" <?php echo $status_filter === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                        <option value="out_for_delivery" <?php echo $status_filter === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                        <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Date</label>
                    <input type="date" name="date" class="form-control" value="<?php echo $date_filter; ?>">
                </div>

                <div class="filter-group" style="align-self: flex-end;">
                    <button type="submit" class="btn-primary">Apply Filters</button>
                    <a href="orders.php" class="btn-outline">Clear</a>
                </div>
            </form>
        </div>

        <!-- Orders List -->
        <div class="admin-card">
            <h2 class="card-title">All Orders (<?php echo count($orders); ?>)</h2>
            <div class="table-responsive">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Order Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($orders) > 0): ?>
                            <?php foreach($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                    <td>
                                        <?php if(!empty($order['full_name'])): ?>
                                            <strong><?php echo htmlspecialchars($order['full_name']); ?></strong><br>
                                            <small>Phone: <?php echo htmlspecialchars($order['customer_phone']); ?></small><br>
                                            <small>Email: <?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?></small>
                                        <?php else: ?>
                                            <strong>Guest Customer</strong><br>
                                            <small>Phone: <?php echo htmlspecialchars($order['customer_phone']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <?php if(!empty($order['current_status'])): ?>
                                            <span class="status-badge status-<?php echo $order['current_status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $order['current_status'])); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-preparing">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="payment-badge payment-<?php echo $order['payment_status']; ?>">
                                            <?php echo ucfirst($order['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="order_detail.php?id=<?php echo $order['order_id']; ?>" class="btn-outline">View</a>
                                            <button onclick="openStatusModal(<?php echo $order['order_id']; ?>, '<?php echo $order['current_status'] ?? 'preparing'; ?>')" class="btn-primary">Update Status</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem;">
                                    <p>No orders found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Order Status</h2>
                <span class="close" onclick="closeStatusModal()">&times;</span>
            </div>
            <form method="POST" action="" id="statusForm">
                <input type="hidden" name="order_id" id="status_order_id">
                <input type="hidden" name="update_status" value="1">
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" id="status_select" class="form-control" required>
                        <option value="preparing">Preparing</option>
                        <option value="out_for_delivery">Out for Delivery</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Status Notes (Optional)</label>
                    <textarea name="status_notes" class="form-control" rows="3" placeholder="Add any notes about this status update..."></textarea>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn-primary">Update Status</button>
                    <button type="button" class="btn-outline" onclick="closeStatusModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openStatusModal(orderId, currentStatus) {
            document.getElementById('status_order_id').value = orderId;
            document.getElementById('status_select').value = currentStatus;
            document.getElementById('statusModal').style.display = 'block';
        }

        function closeStatusModal() {
            document.getElementById('statusModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('statusModal');
            if (event.target === modal) {
                closeStatusModal();
            }
        }
    </script>

    <script src="../../assets/js/admin.js"></script>
</body>
</html>