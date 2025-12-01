<?php
// public/manager/dashboard.php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$auth = new Auth();
$currentUser = $auth->getCurrentUser();

// Only managers and admin can access
if (!$auth->isLoggedIn() || (!$auth->hasRole('manager') && !$auth->hasRole('admin'))) {
    header('Location: ../login.php');
    exit;
}

$pageTitle = "Manager Dashboard - Aunt Joy's Restaurant";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #e74c3c;
            --primary-dark: #c0392b;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --dark-color: #2c3e50;
            --gray-dark: #7f8c8d;
            --gray-light: #ecf0f1;
            --white: #ffffff;
            --sidebar-width: 280px;
            --header-height: 70px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        /* Layout */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--dark-color) 0%, #cbd5dfff 100%);
            color: var(--white);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .sidebar-header .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--white);
            text-decoration: none;
            font-size: 1.25rem;
            font-weight: 700;
            margin-left: 90px;
        }

        .sidebar-header .logo img {
            height: 60px;
            width: auto;
            border-radius: 40px;
        }

        .sidebar-nav {
            padding: 1.5rem 0;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: rgba(115, 114, 184, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: var(--white);
            border-left-color: var(--primary-color);
        }

        .nav-link.active {
            background: rgba(231, 76, 60, 0.2);
            color: var(--white);
            border-left-color: var(--primary-color);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 2rem;
        }

        /* Header */
        .content-header {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .header-title h1 {
            font-size: 2rem;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .header-title p {
            color: var(--gray-dark);
            font-size: 1.1rem;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        /* Cards */
        .card {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
            height: 100%;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 40px rgba(0,0,0,0.15);
        }

        .card-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .card-title {
            font-size: 1.25rem;
            color: var(--dark-color);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-title i {
            color: var(--primary-color);
        }

        /* Stats Grid */

        .stat-card.average .stat-icon {
          background: linear-gradient(135deg, #f39c12, #e67e22);
}

        .stat-card.highest .stat-icon {
        background: linear-gradient(135deg, #9b59b6, #8e44ad);
}
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--white) 0%, #f8f9fa 100%);
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: var(--white);
        }

        .stat-card.revenue .stat-icon {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }

        .stat-card.orders .stat-icon {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .stat-card.customers .stat-icon {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }

        .stat-card.meals .stat-icon {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stat-label {
            color: var(--gray-dark);
            font-weight: 500;
            font-size: 1rem;
        }

        .stat-change {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .stat-change.positive {
            background: rgba(39, 174, 96, 0.1);
            color: #27ae60;
        }

        .stat-change.negative {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        /* Filter Section */
        .filter-card {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-light);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
            font-family: 'Poppins', sans-serif;
        }

        .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }

        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
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
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: var(--white);
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        /* Charts Section */
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .chart-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .chart-title {
            font-size: 1.25rem;
            color: var(--dark-color);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
        }

        .data-table th,
        .data-table td {
            padding: 1rem 1.25rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-light);
        }

        .data-table th {
            background: linear-gradient(135deg, #34495e, #2c3e50);
            color: var(--white);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table tr {
            transition: all 0.3s ease;
        }

        .data-table tr:hover {
            background: #f8f9fa;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        /* Loading */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 80px;
            }
            
            .sidebar-header span,
            .nav-link span {
                display: none;
            }
            
            .main-content {
                margin-left: 80px;
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-form {
                grid-template-columns: 1fr;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="../index.php" class="logo">
                    <img src="../../assets/images/kitchen_logo1.png" alt="Aunt Joy's Restaurant Logo" />
                    <span>Aunt Joy's</span>
                </a>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="reports.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Sales Reports</span>
                    </a>
                </div>
                <?php if ($auth->hasRole('admin')): ?>
                <div class="nav-item">
                    <a href="../admin/dashboard.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        <span>Admin Panel</span>
                    </a>
                </div>
                <?php endif; ?>
                <div class="nav-item">
                    <a href="../index.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Back to Site</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="../logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="content-header fade-in-up">
                <div class="header-title">
                    <h1>📊 Manager Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']); ?>! Here's your restaurant overview.</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-outline" onclick="exportReport('csv')">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </button>
                    <button class="btn btn-primary" onclick="exportReport('excel')">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="stats-grid fade-in-up">
                <div class="stat-card revenue">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-value" id="todayRevenue">MK 0.00</div>
                    <div class="stat-label">Today's Revenue</div>
                    <div class="stat-change positive" id="revenueChange">+12% from yesterday</div>
                </div>
                
                <div class="stat-card orders">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-value" id="todayOrders">0</div>
                    <div class="stat-label">Today's Orders</div>
                    <div class="stat-change positive" id="ordersChange">+8% from yesterday</div>
                </div>

             <div class="stat-card average">
                    <div class="stat-icon">
                <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="stat-value">MK 0.00</div>
                    <div class="stat-label">Average Order</div>
                </div>

                <div class="stat-card highest">
                    <div class="stat-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-value">MK 0.00</div>
                    <div class="stat-label">Highest Order</div>
                </div>
                
                <div class="stat-card customers">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value" id="totalCustomers">0</div>
                    <div class="stat-label">Total Customers</div>
                    <div class="stat-change positive">+5 this week</div>
                </div>
                
                <div class="stat-card meals">
                    <div class="stat-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="stat-value" id="availableMeals">0</div>
                    <div class="stat-label">Available Meals</div>
                    <small>Out of <span id="totalMeals">0</span> total</small>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-card fade-in-up">
                <form id="reportFilterForm" class="filter-form">
                    <div class="form-group">
                        <label class="form-label">📅 Month</label>
                        <select class="form-select" id="month" name="month">
                            <?php
                            $months = [
                                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                            ];
                            $currentMonth = date('n');
                            foreach ($months as $num => $name) {
                                $selected = ($num == $currentMonth) ? 'selected' : '';
                                echo "<option value='$num' $selected>$name</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">📅 Year</label>
                        <select class="form-select" id="year" name="year">
                            <?php
                            $currentYear = date('Y');
                            for ($year = $currentYear; $year >= 2020; $year--) {
                                $selected = ($year == $currentYear) ? 'selected' : '';
                                echo "<option value='$year' $selected>$year</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%">
                            <i class="fas fa-chart-line"></i> Generate Report
                        </button>
                    </div>
                </form>
            </div>

            <!-- Charts Section -->
            <div class="charts-grid fade-in-up">
                <!-- Sales Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">
                            <i class="fas fa-chart-line"></i> Sales Trend
                        </h3>
                    </div>
                    <canvas id="salesChart" width="400" height="250"></canvas>
                </div>

                <!-- Order Status Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">
                            <i class="fas fa-chart-pie"></i> Order Status
                        </h3>
                    </div>
                    <canvas id="statusChart" width="300" height="250"></canvas>
                </div>
            </div>

            <!-- Best Sellers Table -->
            <div class="card fade-in-up">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-trophy"></i> Best Selling Items
                    </h3>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Meal Name</th>
                                <th>Category</th>
                                <th>Quantity Sold</th>
                                <th>Total Revenue</th>
                                <th>Popularity</th>
                            </tr>
                        </thead>
                        <tbody id="bestSellersTable">
                            <tr>
                                <td colspan="5" class="text-center" style="padding: 2rem; color: var(--gray-dark);">
                                    <i class="fas fa-spinner fa-spin"></i> Loading data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   <script>
    let salesChart = null;
    let statusChart = null;

    // Initialize dashboard
    document.addEventListener('DOMContentLoaded', function() {
        loadQuickStats();
        generateReport();
        
        document.getElementById('reportFilterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            generateReport();
        });
    });

    async function loadQuickStats() {
        try {
            const response = await fetch('../../api/manager/dashboard/stats.php');
            const data = await response.json();
            
            if (data.success) {
                displayQuickStats(data.data);
            } else {
                console.error('Failed to load quick stats:', data.message);
                // Fallback to zeros if API fails
                displayQuickStats(null);
            }
        } catch (error) {
            console.error('Error loading quick stats:', error);
            displayQuickStats(null);
        }
    }

    function displayQuickStats(stats) {
        if (!stats) {
            // Set zeros if no data
            document.getElementById('todayRevenue').textContent = 'MK 0.00';
            document.getElementById('todayOrders').textContent = '0';
            document.getElementById('totalCustomers').textContent = '0';
            document.getElementById('availableMeals').textContent = '0';
            document.getElementById('totalMeals').textContent = '0';
            return;
        }

        // Format today's revenue
        const todayRevenue = Number(stats.today?.today_revenue || 0);
        document.getElementById('todayRevenue').textContent = 
            'MK ' + todayRevenue.toLocaleString('en-US', {minimumFractionDigits: 2});
        
        // Today's orders
        document.getElementById('todayOrders').textContent = 
            Number(stats.today?.today_orders || 0).toLocaleString();
        
        // Total customers
        document.getElementById('totalCustomers').textContent = 
            Number(stats.customers?.total_customers || 0).toLocaleString();
        
        // Available meals
        document.getElementById('availableMeals').textContent = 
            Number(stats.meals?.available_meals || 0).toLocaleString();
        document.getElementById('totalMeals').textContent = 
            Number(stats.meals?.total_meals || 0).toLocaleString();

        // Calculate trends (you can enhance this with historical data)
        updateTrendIndicators(stats);
    }

    function updateTrendIndicators(stats) {
        // This is a simplified trend calculation
        // In a real app, you'd compare with previous period data
        const monthlyRevenue = Number(stats.monthly?.monthly_revenue || 0);
        const todayRevenue = Number(stats.today?.today_revenue || 0);
        
        // Simple trend logic - you can enhance this
        const revenueElements = document.querySelectorAll('#revenueChange');
        const ordersElements = document.querySelectorAll('#ordersChange');
        
        revenueElements.forEach(el => {
            if (todayRevenue > 10000) {
                el.textContent = '🔥 Excellent day!';
                el.className = 'stat-change positive';
            } else if (todayRevenue > 5000) {
                el.textContent = '📈 Good performance';
                el.className = 'stat-change positive';
            } else if (todayRevenue > 0) {
                el.textContent = '↔️ Steady';
                el.className = 'stat-change';
            } else {
                el.textContent = '😴 No sales yet';
                el.className = 'stat-change';
            }
        });

        ordersElements.forEach(el => {
            const todayOrders = Number(stats.today?.today_orders || 0);
            if (todayOrders > 20) {
                el.textContent = '🚀 Very busy!';
                el.className = 'stat-change positive';
            } else if (todayOrders > 10) {
                el.textContent = '📈 Busy day';
                el.className = 'stat-change positive';
            } else if (todayOrders > 0) {
                el.textContent = '↔️ Normal traffic';
                el.className = 'stat-change';
            } else {
                el.textContent = '🕒 Waiting for orders';
                el.className = 'stat-change';
            }
        });
    }

    async function generateReport() {
        const month = document.getElementById('month').value;
        const year = document.getElementById('year').value;
        
        showLoading(true);
        
        try {
            const response = await fetch(`../../api/manager/reports/sales.php?month=${month}&year=${year}`);
            const data = await response.json();
            
            if (data.success) {
                updateCharts(data.data);
                updateBestSellers(data.data);
                updateSummaryCards(data.data);
            } else {
                console.error('Error generating report:', data.message);
                alert('Failed to generate report: ' + data.message);
            }
        } catch (error) {
            console.error('Error fetching report:', error);
            alert('Failed to load report data. Please check your connection.');
        } finally {
            showLoading(false);
        }
    }

    function updateCharts(reportData) {
        // Sales Trend Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        if (salesChart) salesChart.destroy();
        
        const dailyTrend = reportData.daily_trend || [];
        const dates = dailyTrend.map(d => {
            const date = new Date(d.sale_date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });
        const revenues = dailyTrend.map(d => Number(d.daily_revenue || 0));
        
        salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: dates.length > 0 ? dates : ['No data'],
                datasets: [{
                    label: 'Daily Revenue (MK)',
                    data: revenues.length > 0 ? revenues : [0],
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return 'MK ' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        if (statusChart) statusChart.destroy();
        
        const statusDistribution = reportData.status_distribution || [];
        const statusLabels = statusDistribution.map(s => s.status || 'Unknown');
        const statusData = statusDistribution.map(s => Number(s.order_count || 0));
        
        statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusLabels.length > 0 ? statusLabels : ['No data'],
                datasets: [{
                    data: statusData.length > 0 ? statusData : [1],
                    backgroundColor: [
                        '#27ae60', // Completed - Green
                        '#3498db', // Preparing - Blue
                        '#f39c12', // Pending - Orange
                        '#e74c3c', // Cancelled - Red
                        '#95a5a6', // Other - Gray
                        '#9b59b6'  // Other - Purple
                    ].slice(0, statusLabels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    function updateBestSellers(reportData) {
        const bestSellers = reportData.best_sellers || [];
        const tbody = document.getElementById('bestSellersTable');
        tbody.innerHTML = '';

        if (bestSellers.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center" style="padding: 2rem; color: var(--gray-dark);">
                        <i class="fas fa-info-circle"></i> No sales data available for this period
                    </td>
                </tr>
            `;
            return;
        }

        bestSellers.forEach((item, index) => {
            const row = document.createElement('tr');
            const revenue = Number(item.total_revenue || 0);
            const quantity = Number(item.total_quantity || 0);
            
            // Determine popularity indicator
            let popularity = '⭐ Normal';
            if (quantity > 40) popularity = '🔥 Hot';
            else if (quantity > 20) popularity = '🚀 Popular';
            else if (quantity > 10) popularity = '⭐ Good';
            
            row.innerHTML = `
                <td><strong>${item.meal_name || 'Unknown Meal'}</strong></td>
                <td><span class="badge" style="background: #e74c3c; color: white; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.8rem;">${item.category_name || 'Uncategorized'}</span></td>
                <td>${quantity.toLocaleString()}</td>
                <td><strong>MK ${revenue.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></td>
                <td><span style="color: #e74c3c; font-weight: 600;">${popularity}</span></td>
            `;
            tbody.appendChild(row);
        });
    }

    function updateSummaryCards(reportData) {
        const summary = reportData.summary || {};
        const totalRevenue = Number(summary.total_revenue || 0);
        const totalOrders = Number(summary.total_orders || 0);
        const avgOrder = Number(summary.average_order_value || 0);
        const highestOrder = Number(summary.highest_order || 0);

        // Update the main stats cards with summary data
        document.querySelector('.stat-card.revenue .stat-value').textContent = 
            'MK ' + totalRevenue.toLocaleString('en-US', {minimumFractionDigits: 2});
        document.querySelector('.stat-card.orders .stat-value').textContent = 
            totalOrders.toLocaleString();
        document.querySelector('.stat-card.average .stat-value').textContent = 
            'MK ' + avgOrder.toLocaleString('en-US', {minimumFractionDigits: 2});
        document.querySelector('.stat-card.highest .stat-value').textContent = 
            'MK ' + highestOrder.toLocaleString('en-US', {minimumFractionDigits: 2});
    }

    function exportReport(format) {
        const month = document.getElementById('month').value;
        const year = document.getElementById('year').value;
        
        // Use your actual export endpoint
        const url = `../../api/manager/reports/export.php?format=${format}&month=${month}&year=${year}`;
        window.open(url, '_blank');
        
        // Show confirmation
        showExportNotification(format);
    }

    function showExportNotification(format) {
        // Create a temporary notification
        const notification = document.createElement('div');
        notification.innerHTML = `
            <div style="position: fixed; top: 20px; right: 20px; background: #27ae60; color: white; padding: 1rem 1.5rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10000; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-check-circle"></i>
                ${format.toUpperCase()} export started for ${getMonthName(document.getElementById('month').value)} ${year}
            </div>
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    function getMonthName(monthNumber) {
        const months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        return months[parseInt(monthNumber) - 1] || 'Unknown';
    }

    function showLoading(show) {
        // You can add a loading indicator here if needed
        if (show) {
            // Show loading state
            document.querySelectorAll('.chart-card, .card').forEach(card => {
                card.style.opacity = '0.6';
            });
        } else {
            // Hide loading state
            document.querySelectorAll('.chart-card, .card').forEach(card => {
                card.style.opacity = '1';
            });
        }
    }

    // Add some interactivity
    document.querySelectorAll('.stat-card').forEach(card => {
        card.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });

    // Auto-refresh every 5 minutes for real-time updates
    setInterval(() => {
        loadQuickStats();
    }, 300000); // 5 minutes
</script>
</body>
</html>