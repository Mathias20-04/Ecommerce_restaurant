<?php
require_once '../../../includes/config.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/middleware.php';

setCORSHeaders();
handlePreflight();

// Only managers and admin can access
$auth = requireAuth();
if (!$auth->hasRole('manager') && !$auth->hasRole('admin')) {
    header('Content-Type: application/json');
    jsonResponse(false, 'Insufficient permissions', [], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Content-Type: application/json');
    jsonResponse(false, 'Method not allowed', [], 405);
}

$format = $_GET['format'] ?? 'json';
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

$allowed_formats = ['json', 'csv', 'excel'];
if (!in_array($format, $allowed_formats)) {
    header('Content-Type: application/json');
    jsonResponse(false, 'Invalid format. Allowed: ' . implode(', ', $allowed_formats), [], 400);
}

$conn = getDBConnection();
if (!$conn) {
    header('Content-Type: application/json');
    jsonResponse(false, 'Database connection failed', [], 500);
}

try {
    // Calculate date ranges
    $start_date = "$year-$month-01";
    $end_date = date('Y-m-t', strtotime($start_date));
    
    // Get detailed sales data for export
    $export_query = "SELECT 
                        o.order_id,
                        o.order_date,
                        o.total_amount,
                        o.payment_status,
                        u.full_name as customer_name,
                        u.phone as customer_phone,
                        os.status as current_status,
                        m.meal_name,
                        oi.quantity,
                        oi.unit_price,
                        oi.item_total
                    FROM orders o
                    JOIN users u ON o.customer_id = u.user_id
                    JOIN order_items oi ON o.order_id = oi.order_id
                    JOIN meals m ON oi.meal_id = m.meal_id
                    LEFT JOIN (
                        SELECT order_id, status 
                        FROM order_status 
                        WHERE (order_id, created_at) IN (
                            SELECT order_id, MAX(created_at) 
                            FROM order_status 
                            GROUP BY order_id
                        )
                    ) os ON o.order_id = os.order_id
                    WHERE DATE(o.order_date) BETWEEN ? AND ?
                    ORDER BY o.order_date DESC";
    
    $stmt = $conn->prepare($export_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $export_data = $result->fetch_all(MYSQLI_ASSOC);
    
    // Get summary for export
    $summary_query = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(total_amount) as total_revenue
                      FROM orders 
                      WHERE DATE(order_date) BETWEEN ? AND ?
                      AND payment_status = 'paid'";
    
    $stmt2 = $conn->prepare($summary_query);
    $stmt2->bind_param("ss", $start_date, $end_date);
    $stmt2->execute();
    $summary = $stmt2->get_result()->fetch_assoc();
    
    switch ($format) {
        case 'csv':
            // Disable error reporting for clean CSV output
            error_reporting(0);
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="aunt_joys_sales_' . $year . '_' . $month . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fwrite($output, "\xEF\xBB\xBF");
            
            // Add headers with proper escaping
            fputcsv($output, ['Aunt Joy Restaurant - Sales Report'], ',', '"', '\\');
            fputcsv($output, ['Period: ' . date('F Y', strtotime($start_date))], ',', '"', '\\');
            fputcsv($output, ['Total Orders: ' . ($summary['total_orders'] ?? 0)], ',', '"', '\\');
            fputcsv($output, ['Total Revenue: MK ' . number_format($summary['total_revenue'] ?? 0, 2)], ',', '"', '\\');
            fputcsv($output, [], ',', '"', '\\'); // Empty line
            
            // Data headers
            fputcsv($output, [
                'Order ID', 
                'Date', 
                'Customer Name', 
                'Customer Phone',
                'Meal Name', 
                'Quantity', 
                'Unit Price (MK)', 
                'Item Total (MK)', 
                'Order Status',
                'Payment Status'
            ], ',', '"', '\\');
            
            // Data rows
            foreach ($export_data as $row) {
                fputcsv($output, [
                    $row['order_id'] ?? '',
                    $row['order_date'] ?? '',
                    $row['customer_name'] ?? '',
                    $row['customer_phone'] ?? '',
                    $row['meal_name'] ?? '',
                    $row['quantity'] ?? 0,
                    number_format($row['unit_price'] ?? 0, 2),
                    number_format($row['item_total'] ?? 0, 2),
                    ucfirst($row['current_status'] ?? 'unknown'),
                    ucfirst($row['payment_status'] ?? 'unknown')
                ], ',', '"', '\\');
            }
            
            fclose($output);
            exit;
            
        case 'excel':
            // Disable error reporting for clean Excel output
            error_reporting(0);
            
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="aunt_joys_sales_' . $year . '_' . $month . '.xls"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            echo "<html>";
            echo "<head>";
            echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">";
            echo "<style>";
            echo "table { border-collapse: collapse; width: 100%; }";
            echo "th { background-color: #e74c3c; color: white; padding: 8px; text-align: left; }";
            echo "td { padding: 6px; border: 1px solid #ddd; }";
            echo ".header { background-color: #2c3e50; color: white; font-weight: bold; }";
            echo ".summary { background-color: #f8f9fa; }";
            echo "</style>";
            echo "</head>";
            echo "<body>";
            
            echo "<table>";
            // Header
            echo "<tr class='header'><td colspan='10'>Aunt Joy Restaurant - Sales Report</td></tr>";
            echo "<tr class='summary'><td colspan='10'>Period: " . date('F Y', strtotime($start_date)) . "</td></tr>";
            echo "<tr class='summary'><td colspan='10'>Total Orders: " . ($summary['total_orders'] ?? 0) . "</td></tr>";
            echo "<tr class='summary'><td colspan='10'>Total Revenue: MK " . number_format($summary['total_revenue'] ?? 0, 2) . "</td></tr>";
            echo "<tr><td colspan='10'></td></tr>"; // Empty row
            
            // Headers
            echo "<tr>";
            echo "<th>Order ID</th>";
            echo "<th>Date</th>";
            echo "<th>Customer Name</th>";
            echo "<th>Customer Phone</th>";
            echo "<th>Meal Name</th>";
            echo "<th>Quantity</th>";
            echo "<th>Unit Price (MK)</th>";
            echo "<th>Item Total (MK)</th>";
            echo "<th>Order Status</th>";
            echo "<th>Payment Status</th>";
            echo "</tr>";
            
            // Data rows
            foreach ($export_data as $row) {
                echo "<tr>";
                echo "<td>" . ($row['order_id'] ?? '') . "</td>";
                echo "<td>" . ($row['order_date'] ?? '') . "</td>";
                echo "<td>" . ($row['customer_name'] ?? '') . "</td>";
                echo "<td>" . ($row['customer_phone'] ?? '') . "</td>";
                echo "<td>" . ($row['meal_name'] ?? '') . "</td>";
                echo "<td>" . ($row['quantity'] ?? 0) . "</td>";
                echo "<td>" . number_format($row['unit_price'] ?? 0, 2) . "</td>";
                echo "<td>" . number_format($row['item_total'] ?? 0, 2) . "</td>";
                echo "<td>" . ucfirst($row['current_status'] ?? 'unknown') . "</td>";
                echo "<td>" . ucfirst($row['payment_status'] ?? 'unknown') . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            echo "</body>";
            echo "</html>";
            exit;
            
        default:
            header('Content-Type: application/json');
            jsonResponse(true, 'Export data retrieved', [
                'export_data' => $export_data,
                'summary' => $summary,
                'period' => date('F Y', strtotime($start_date)),
                'record_count' => count($export_data)
            ]);
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    jsonResponse(false, 'Export failed: ' . $e->getMessage(), [], 500);
} finally {
    closeDBConnection($conn);
}
?>