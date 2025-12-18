<?php
// reports.php - Sales Reporting Dashboard for Managers and Admins
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php'; // ADD THIS LINE

$auth = new Auth();

// Redirect if not logged in or not admin/manager
if(!$auth->isLoggedIn() || (!$auth->hasRole('admin') && !$auth->hasRole('manager'))) {
    header("Location: ../login.php");
    exit;
}

$current_user = $auth->getCurrentUser();
$current_month = date('m');
$current_year = date('Y');

if(isset($_GET['month'])) {
    $current_month = sanitizeInput($_GET['month']);
}
if(isset($_GET['year'])) {
    $current_year = sanitizeInput($_GET['year']);
}

// Define base URL for navigation
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/projects/aunt-joy-restaurant';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports - Aunt Joy's Restaurant</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 2rem 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
        }

        .header h1 {
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            color:#e1e5e9;
        }

        .header-nav {
            display: flex;
            gap: 1rem;
        }

        .header-nav a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: background 0.3s ease;
        }

        .header-nav a:hover {
            background: rgba(255,255,255,0.2);
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .filter-group {
            display: flex;
            gap: 1.5rem;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .filter-item {
            flex: 1;
            min-width: 150px;
        }

        .filter-item label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .filter-item select,
        .filter-item input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .filter-item select:focus,
        .filter-item input:focus {
            outline: none;
            border-color: #e74c3c;
        }

        .filter-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: #e74c3c;
            color: white;
        }

        .btn-primary:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #3498db;
            color: white;
        }

        .btn-secondary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #229954;
            transform: translateY(-2px);
        }

        /* Metrics Cards */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .metric-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #e74c3c;
        }

        .metric-card.revenue {
            border-left-color: #27ae60;
        }

        .metric-card.orders {
            border-left-color: #3498db;
        }

        .metric-card.average {
            border-left-color: #f39c12;
        }

        .metric-label {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-bottom: 0.5rem;
        }

        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .metric-icon {
            font-size: 3rem;
            color: #e74c3c;
            opacity: 0.1;
            float: right;
        }

        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .chart-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #2c3e50;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        /* Tables */
        .table-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .table-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #2c3e50;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
        }

        th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #e1e5e9;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Loading */
        .loading {
            text-align: center;
            padding: 2rem;
            color: #7f8c8d;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #e74c3c;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .filter-group {
                flex-direction: column;
            }

            .filter-item {
                min-width: 100%;
            }

            .charts-section {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <h1>
                    <i class="fas fa-chart-line"></i> Sales Reports
                </h1>
                <div class="header-nav">
                    <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                    <a href="<?php echo $base_url; ?>/public/index.php"><i class="fas fa-home"></i> Home</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-group">
                <div class="filter-item">
                    <label>Month</label>
                    <select id="filterMonth">
                        <option value="01" <?php echo $current_month == '01' ? 'selected' : ''; ?>>January</option>
                        <option value="02" <?php echo $current_month == '02' ? 'selected' : ''; ?>>February</option>
                        <option value="03" <?php echo $current_month == '03' ? 'selected' : ''; ?>>March</option>
                        <option value="04" <?php echo $current_month == '04' ? 'selected' : ''; ?>>April</option>
                        <option value="05" <?php echo $current_month == '05' ? 'selected' : ''; ?>>May</option>
                        <option value="06" <?php echo $current_month == '06' ? 'selected' : ''; ?>>June</option>
                        <option value="07" <?php echo $current_month == '07' ? 'selected' : ''; ?>>July</option>
                        <option value="08" <?php echo $current_month == '08' ? 'selected' : ''; ?>>August</option>
                        <option value="09" <?php echo $current_month == '09' ? 'selected' : ''; ?>>September</option>
                        <option value="10" <?php echo $current_month == '10' ? 'selected' : ''; ?>>October</option>
                        <option value="11" <?php echo $current_month == '11' ? 'selected' : ''; ?>>November</option>
                        <option value="12" <?php echo $current_month == '12' ? 'selected' : ''; ?>>December</option>
                    </select>
                </div>

                <div class="filter-item">
                    <label>Year</label>
                    <select id="filterYear">
                        <?php 
                        $current_y = date('Y');
                        for($i = $current_y; $i >= 2020; $i--) {
                            echo "<option value=\"$i\" " . ($current_year == $i ? 'selected' : '') . ">$i</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="filter-buttons">
                    <button class="btn btn-primary" onclick="generateReport()">
                        <i class="fas fa-sync"></i> Generate Report
                    </button>
                    <button class="btn btn-secondary" onclick="exportPDF()">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                    <button class="btn btn-success" onclick="exportCSV()">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </button>
                    <button class="btn btn-success" onclick="exportExcel()">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                </div>
            </div>
        </div>

        <!-- Metrics Cards -->
        <div class="metrics-grid">
            <div class="metric-card revenue">
                <div class="metric-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="metric-label">Total Revenue</div>
                <div class="metric-value">MK <span id="totalRevenue">0</span></div>
            </div>
            <div class="metric-card orders">
                <div class="metric-icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="metric-label">Total Orders</div>
                <div class="metric-value"><span id="totalOrders">0</span></div>
            </div>
            <div class="metric-card average">
                <div class="metric-icon"><i class="fas fa-chart-bar"></i></div>
                <div class="metric-label">Average Order Value</div>
                <div class="metric-value">MK <span id="avgValue">0</span></div>
            </div>
            <div class="metric-card">
                <div class="metric-icon"><i class="fas fa-star"></i></div>
                <div class="metric-label">Best Seller Revenue</div>
                <div class="metric-value">MK <span id="bestSellerRevenue">0</span></div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-section">
            <div class="chart-card">
                <div class="chart-title">Daily Sales Trend</div>
                <div class="chart-container">
                    <canvas id="dailyTrendChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-title">Order Status Distribution</div>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Best Sellers Table -->
        <div class="table-section">
            <div class="table-title">Top 10 Best Selling Items</div>
            <div id="bestSellersLoading" class="loading">
                <div class="spinner"></div>
                Loading data...
            </div>
            <table id="bestSellersTable" style="display: none;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item Name</th>
                        <th class="text-right">Quantity Sold</th>
                        <th class="text-right">Total Revenue</th>
                        <th class="text-right">Times Ordered</th>
                    </tr>
                </thead>
                <tbody id="bestSellersBody">
                </tbody>
            </table>
        </div>
    </div>

    <script>
        let reportData = null;
        let dailyTrendChart = null;
        let statusChart = null;

        async function generateReport() {
            const month = document.getElementById('filterMonth').value;
            const year = document.getElementById('filterYear').value;

            try {
                // Show loading state
                document.getElementById('bestSellersLoading').style.display = 'block';
                document.getElementById('bestSellersTable').style.display = 'none';

                const response = await fetch(`../../api/manager/reports/sales.php?month=${month}&year=${year}`);
                const data = await response.json();

                if(!data.success) {
                    alert('Error: ' + data.message);
                    return;
                }

                reportData = data.data;
                displayMetrics();
                displayCharts();
                displayBestSellers();
            } catch (error) {
                console.error('Error fetching report:', error);
                alert('Failed to load report data. Please check your connection.');
            }
        }

        function displayMetrics() {
            const summary = reportData.summary || {};
            const totalRevenue = Number(summary.total_revenue || 0);
            const totalOrders = Number(summary.total_orders || 0);
            const avgValue = totalOrders > 0 ? totalRevenue / totalOrders : 0;

            document.getElementById('totalRevenue').textContent = totalRevenue.toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('totalOrders').textContent = totalOrders.toLocaleString();
            document.getElementById('avgValue').textContent = avgValue.toLocaleString('en-US', {minimumFractionDigits: 2});

            const bestSeller = (reportData.best_sellers && reportData.best_sellers[0]) || null;
            const bestSellerRevenue = Number(bestSeller?.total_revenue || 0);
            document.getElementById('bestSellerRevenue').textContent = bestSellerRevenue.toLocaleString('en-US', {minimumFractionDigits: 2});
        }

        function displayCharts() {
            // Daily Trend Chart
            const dailyData = reportData.daily_trend || [];
            const dates = dailyData.map(d => {
                const date = new Date(d.sale_date);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });
            const revenues = dailyData.map(d => parseFloat(d.daily_revenue || 0));

            if(dailyTrendChart) dailyTrendChart.destroy();
            
            const ctx1 = document.getElementById('dailyTrendChart').getContext('2d');
            dailyTrendChart = new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: dates.length > 0 ? dates : ['No data'],
                    datasets: [{
                        label: 'Daily Revenue (MK)',
                        data: revenues.length > 0 ? revenues : [0],
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'MK ' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });

            // Status Distribution Chart - FIXED: Use status_distribution instead of order_status_distribution
            const statusData = reportData.status_distribution || [];
            const statuses = statusData.map(s => s.status || 'Unknown');
            const counts = statusData.map(s => parseInt(s.order_count || 0));
            const colors = ['#27ae60', '#3498db', '#f39c12', '#e74c3c', '#95a5a6'];

            if(statusChart) statusChart.destroy();
            const ctx2 = document.getElementById('statusChart').getContext('2d');
            statusChart = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: statuses.length > 0 ? statuses : ['No data'],
                    datasets: [{
                        data: counts.length > 0 ? counts : [1],
                        backgroundColor: colors.slice(0, Math.max(statuses.length, 1))
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }

        function displayBestSellers() {
            const tbody = document.getElementById('bestSellersBody');
            tbody.innerHTML = '';

            const bestSellers = reportData.best_sellers || [];
            
            if (bestSellers.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center" style="padding: 2rem; color: #7f8c8d;">
                            <i class="fas fa-info-circle"></i> No sales data available for this period
                        </td>
                    </tr>
                `;
            } else {
                bestSellers.forEach((item, index) => {
                    const row = tbody.insertRow();
                    row.innerHTML = `
                        <td>${index + 1}</td>
                        <td>${item.meal_name || 'Unknown Meal'}</td>
                        <td class="text-right">${Number(item.total_quantity || 0).toLocaleString()}</td>
                        <td class="text-right">MK ${Number(item.total_revenue || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                        <td class="text-right">${Number(item.times_ordered || 0).toLocaleString()}</td>
                    `;
                });
            }

            document.getElementById('bestSellersLoading').style.display = 'none';
            document.getElementById('bestSellersTable').style.display = 'table';
        }

        function exportPDF() {
            if(!reportData) {
                alert('Please generate a report first');
                return;
            }

            const element = document.querySelector('.container');
            const opt = {
                margin: 10,
                filename: `aunt_joys_sales_report_${new Date().toISOString().split('T')[0]}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { orientation: 'landscape', unit: 'mm', format: 'a4' }
            };

            html2pdf().set(opt).from(element).save();
        }

        function exportCSV() {
            if(!reportData) {
                alert('Please generate a report first');
                return;
            }

            const month = document.getElementById('filterMonth').value;
            const year = document.getElementById('filterYear').value;
            
            // Use your export API endpoint
            const url = `../../api/manager/reports/export.php?format=csv&month=${month}&year=${year}`;
            window.open(url, '_blank');
        }

        function exportExcel() {
            if(!reportData) {
                alert('Please generate a report first');
                return;
            }

            const month = document.getElementById('filterMonth').value;
            const year = document.getElementById('filterYear').value;
            
            // Use your export API endpoint
            const url = `../../api/manager/reports/export.php?format=excel&month=${month}&year=${year}`;
            window.open(url, '_blank');
        }

        // Load report on page load
        window.addEventListener('load', generateReport);
    </script>
</body>
</html>