<?php
// Get the absolute path to your project root
$project_root = dirname(__DIR__, 2); // Goes up two levels from current file
require_once $project_root . '/includes/config.php';
require_once $project_root . '/includes/auth.php';
require_once $project_root . '/includes/functions.php';

$auth = new Auth();
$currentUser = $auth->getCurrentUser();

// Check if user has sales or admin role
if (!$auth->hasRole('sales') && !$auth->hasRole('admin')) {
    header('Location: index.php');
    exit();
}

// Get order ID from query parameter
$order_id = $_GET['order_id'] ?? null;
if (!$order_id || !is_numeric($order_id)) {
    die('Invalid order ID');
}

// Fetch order details directly from the database
$order = null;
$order_items = [];

$conn = getDBConnection();
if (!$conn) {
    die('Database connection failed');
}

try {
    // Main order
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
              ) os ON o.order_id = os.order_id
              WHERE o.order_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        closeDBConnection($conn);
        die('Order not found');
    }

    // Items
    $items_stmt = $conn->prepare("SELECT oi.meal_id, oi.quantity, oi.unit_price AS price, oi.item_total AS subtotal, m.meal_name, m.image_url
                                  FROM order_items oi
                                  JOIN meals m ON oi.meal_id = m.meal_id
                                  WHERE oi.order_id = ?");
    $items_stmt->bind_param('i', $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    $order_items = $items_result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
    $items_stmt->close();
    closeDBConnection($conn);

    // Attach items inside $order for backwards compatibility
    $order['items'] = $order_items;

} catch (Exception $e) {
    if (isset($conn)) closeDBConnection($conn);
    die('Order not found');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Order No.<?php echo $order_id; ?> - Aunt Joy's Restaurant</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #fff;
            padding: 10px;
        }

        .print-container {
            max-width: 80mm;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .restaurant-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .restaurant-info {
            font-size: 10px;
            margin-bottom: 5px;
        }

        .order-title {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
            text-transform: uppercase;
        }

        .section {
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px dashed #ccc;
        }

        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
            border-bottom: 1px solid #000;
        }

        .customer-info p,
        .order-info p {
            margin-bottom: 3px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        .items-table th {
            text-align: left;
            border-bottom: 1px solid #000;
            padding: 3px 0;
            font-weight: bold;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 4px 0;
            border-bottom: 1px dashed #ccc;
        }

        .item-name {
            width: 60%;
        }

        .item-quantity {
            width: 15%;
            text-align: center;
        }

        .item-price {
            width: 25%;
            text-align: right;
        }

        .total-section {
            text-align: right;
            margin-top: 10px;
            border-top: 2px solid #000;
            padding-top: 5px;
        }

        .total-line {
            margin-bottom: 3px;
        }

        .grand-total {
            font-size: 14px;
            font-weight: bold;
            margin-top: 5px;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px dashed #000;
            font-size: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            background: #000;
            color: #fff;
            border-radius: 3px;
            font-weight: bold;
            text-transform: uppercase;
            margin-left: 5px;
        }

        .instructions {
            margin-top: 10px;
            padding: 5px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            font-style: italic;
        }

        .timestamp {
            font-size: 10px;
            color: #666;
            text-align: center;
            margin: 5px 0;
        }

        @media print {
            body {
                padding: 0;
            }
            
            .no-print {
                display: none !important;
            }
            
            .print-container {
                max-width: 100%;
            }
        }

        .button-container {
            text-align: center;
            margin: 20px 0;
        }

        .print-button {
            background: #000;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            cursor: pointer;
            border-radius: 3px;
        }

        .print-button:hover {
            background: #333;
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Print Button (hidden when printing) -->
        <div class="button-container no-print">
            <button class="print-button" onclick="window.print()">
                🖨️ Print Order Ticket
            </button>
            <button class="print-button" onclick="window.close()" style="background: #666; margin-left: 10px;">
                ❌ Close
            </button>
        </div>

        <!-- Restaurant Header -->
        <div class="header">
            <div class="restaurant-name">AUNT JOY'S RESTAURANT</div>
            <div class="restaurant-info">Mzuzu City Center, Malawi</div>
            <div class="restaurant-info">Tel: +265 998588582</div>
            <div class="timestamp">Printed: <?php echo date('Y-m-d H:i:s'); ?></div>
        </div>

        <!-- Order Title -->
        <div class="order-title">
            ORDER #<?php echo $order_id; ?>
            <span class="status-badge"><?php echo strtoupper($order['current_status'] ?? 'PENDING'); ?></span>
        </div>

        <!-- Order Information -->
        <div class="section order-info">
            <div class="section-title">Order Information</div>
            <p><strong>Order Date:</strong> <?php echo date('Y-m-d H:i', strtotime($order['order_date'])); ?></p>
            <p><strong>Payment Status:</strong> <?php echo strtoupper($order['payment_status'] ?? 'PENDING'); ?></p>
            <?php if ($order['status_updated_at']): ?>
                <p><strong>Last Updated:</strong> <?php echo date('Y-m-d H:i', strtotime($order['status_updated_at'])); ?></p>
            <?php endif; ?>
        </div>

        <!-- Customer Information -->
        <div class="section customer-info">
            <div class="section-title">Customer Details</div>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_contact'] ?? $order['customer_phone'] ?? 'N/A'); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($order['delivery_address'] ?? 'N/A'); ?></p>
        </div>

        <!-- Special Instructions -->
        <?php if (!empty($order['special_instructions'])): ?>
        <div class="section instructions">
            <div class="section-title">Special Instructions</div>
            <p><?php echo htmlspecialchars($order['special_instructions']); ?></p>
        </div>
        <?php endif; ?>

        <!-- Order Items -->
        <div class="section">
            <div class="section-title">Order Items</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="item-name">Item</th>
                        <th class="item-quantity">Qty</th>
                        <th class="item-price">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td class="item-name"><?php echo htmlspecialchars($item['meal_name']); ?></td>
                        <td class="item-quantity"><?php echo $item['quantity']; ?></td>
                        <td class="item-price">MK <?php echo number_format($item['subtotal'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Order Total -->
        <div class="total-section">
            <div class="total-line">
                <strong>Subtotal: MK <?php echo number_format($order['total_amount'], 2); ?></strong>
            </div>
            <div class="total-line">
                <strong>Delivery Fee: MK 0.00</strong>
            </div>
            <div class="grand-total">
                TOTAL: MK <?php echo number_format($order['total_amount'], 2); ?>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>Thank you for your order!</div>
            <div>KITCHEN COPY</div>
            <div>Order prepared by: ___________________</div>
            <div>Time completed: ___________________</div>
        </div>
    </div>

    <script>
        // Auto-print when page loads (optional)
        window.onload = function() {
            // Uncomment the line below if you want auto-print
            // window.print();
        };

        // Close window after printing
        window.onafterprint = function() {
            // Optional: close window after printing
            // window.close();
        };
    </script>
</body>
</html>