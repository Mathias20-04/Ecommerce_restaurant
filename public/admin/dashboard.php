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

// Get dashboard stats for admin
$totalMeals = $conn->query("SELECT COUNT(*) as count FROM meals")->fetch_assoc()['count'];
$availableMeals = $conn->query("SELECT COUNT(*) as count FROM meals WHERE is_available = 1")->fetch_assoc()['count'];
$totalCategories = $conn->query("SELECT COUNT(*) as count FROM categories WHERE is_active = 1")->fetch_assoc()['count'];
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")->fetch_assoc()['count'];
$totalAdmins = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];

// Get low stock alerts
$lowStockMeals = $conn->query("
    SELECT m.*, c.category_name 
    FROM meals m 
    LEFT JOIN categories c ON m.category_id = c.category_id 
    WHERE m.stock_quantity IS NOT NULL 
    AND m.stock_quantity <= m.low_stock_threshold
    AND m.is_available = 1
    ORDER BY m.stock_quantity ASC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$lowStockCount = $conn->query("
    SELECT COUNT(*) as count FROM meals 
    WHERE stock_quantity IS NOT NULL 
    AND stock_quantity <= low_stock_threshold
    AND is_available = 1
")->fetch_assoc()['count'];

// Get recent meals
$recentMeals = $conn->query("
    SELECT m.*, c.category_name 
    FROM meals m 
    LEFT JOIN categories c ON m.category_id = c.category_id 
    ORDER BY m.created_at DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Get recent users
$recentUsers = $conn->query("
    SELECT user_id, username, full_name, email, created_at 
    FROM users 
    WHERE role = 'customer'
    ORDER BY created_at DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Get categories with meal counts
$categoriesWithCounts = $conn->query("
    SELECT c.category_name, COUNT(m.meal_id) as meal_count 
    FROM categories c 
    LEFT JOIN meals m ON c.category_id = m.category_id AND m.is_available = 1
    WHERE c.is_active = 1
    GROUP BY c.category_id, c.category_name 
    ORDER BY meal_count DESC
")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Aunt Joy's Restaurant</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #7c3aed;
            --primary-light: #8b5cf6;
            --primary-dark: #6d28d9;
            --secondary-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --success-color: #10b981;
            --dark-color: #1e293b;
            --gray-dark: #475569;
            --gray-medium: #94a3b8;
            --gray-light: #e2e8f0;
            --white: #ffffff;
            --bg-light: #f8fafc;
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--bg-light);
            color: var(--dark-color);
            line-height: 1.6;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        .header {
            background: var(--white);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
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
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .logo img {
           height: 80px;
            width: 80px;
            margin-right: 10px;
            border-radius: 50%;
            margin-left: 14px;
            background-color: #ddc332;
            object-fit: cover;
            float: left;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 1.5rem;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--gray-dark);
            font-weight: 600;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links a:hover {
            color: var(--primary-color);
        }

        /* Admin Navigation */
        .admin-nav {
            background: var(--white);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .admin-nav-links {
            display: flex;
            gap: 0;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .admin-nav-links a {
            text-decoration: none;
            color: var(--gray-dark);
            font-weight: 600;
            padding: 1rem 1.5rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 3px solid transparent;
        }

        .admin-nav-links a:hover,
        .admin-nav-links a.active {
            color: var(--primary-color);
            background-color: rgba(124, 58, 237, 0.05);
            border-bottom: 3px solid var(--primary-color);
        }

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
            padding: 2.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.8s ease-out;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }

        .welcome-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(-30%, 30%);
        }

        .welcome-section h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2.2rem;
            position: relative;
            z-index: 1;
        }

        .welcome-section p {
            margin: 0;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-top: 4px solid var(--primary-color);
            animation: slideUp 0.5s ease-out;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary-color);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }

        .stat-card.warning {
            border-top-color: var(--warning-color);
        }

        .stat-card.warning::before {
            background: var(--warning-color);
        }

        .stat-card.danger {
            border-top-color: var(--danger-color);
        }

        .stat-card.danger::before {
            background: var(--danger-color);
        }

        .stat-card.success {
            border-top-color: var(--success-color);
        }

        .stat-card.success::before {
            background: var(--success-color);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            opacity: 0.8;
        }

        .stat-card.warning .stat-icon {
            color: var(--warning-color);
        }

        .stat-card.danger .stat-icon {
            color: var(--danger-color);
        }

        .stat-card.success .stat-icon {
            color: var(--success-color);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--gray-dark);
            font-size: 1rem;
            font-weight: 600;
        }

        /* Dashboard Sections */
        .dashboard-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .dashboard-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            animation: fadeIn 0.6s ease-out;
            transition: transform 0.3s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .card-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--dark-color);
            padding-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--gray-light);
        }

        .card-title a {
            font-size: 0.9rem;
            text-decoration: none;
            color: var(--primary-color);
            font-weight: normal;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .card-title a:hover {
            gap: 0.5rem;
        }

        .list-item {
            padding: 1.25rem 0;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.2s ease;
            border-radius: var(--border-radius-sm);
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        .list-item:hover {
            background-color: rgba(124, 58, 237, 0.05);
        }

        .list-item:last-child {
            border-bottom: none;
        }

        .alert-item {
            border-left: 4px solid var(--danger-color);
            background: rgba(239, 68, 68, 0.05);
            margin-left: -0.5rem;
            margin-right: -0.5rem;
            padding-left: 1rem;
        }

        .item-info {
            flex: 1;
        }

        .item-actions {
            display: flex;
            gap: 0.5rem;
        }

        /* Buttons */
        .btn {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-sm);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(124, 58, 237, 0.3);
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

        .btn-danger {
            background: transparent;
            border: 2px solid var(--danger-color);
            color: var(--danger-color);
        }

        .btn-danger:hover {
            background: var(--danger-color);
            color: var(--white);
            transform: translateY(-2px);
        }

        /* Status Indicators */
        .status-available {
            color: var(--success-color);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .status-unavailable {
            color: var(--danger-color);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .stock-low {
            color: var(--danger-color);
            font-weight: 600;
        }

        .stock-warning {
            color: var(--warning-color);
            font-weight: 600;
        }

        /* Quick Actions */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .quick-action-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            border: 1px solid var(--gray-light);
            animation: fadeIn 0.6s ease-out;
            position: relative;
            overflow: hidden;
        }

        .quick-action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 0;
        }

        .quick-action-card:hover::before {
            opacity: 1;
        }

        .quick-action-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            color: var(--white);
        }

        .quick-action-card.alert-action {
            border-color: var(--danger-color);
            background: rgba(239, 68, 68, 0.05);
        }

        .quick-action-card.alert-action:hover {
            background: var(--danger-color);
            color: var(--white);
        }

        .action-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .action-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
            position: relative;
            z-index: 1;
        }

        .quick-action-card:hover .action-title {
            color: var(--white);
        }

        .action-description {
            color: var(--gray-dark);
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
        }

        .quick-action-card:hover .action-description {
            color: rgba(255, 255, 255, 0.9);
        }

        /* Category Chart */
        .category-chart {
            margin-top: 1rem;
        }

        .chart-bar {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .chart-label {
            width: 120px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .chart-bar-inner {
            flex: 1;
            background: var(--gray-light);
            border-radius: 10px;
            overflow: hidden;
            height: 20px;
        }

        .chart-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            border-radius: 10px;
            transition: width 1s ease-out;
            position: relative;
        }

        .chart-bar-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background-image: linear-gradient(
                -45deg,
                rgba(255, 255, 255, 0.2) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255, 255, 255, 0.2) 50%,
                rgba(255, 255, 255, 0.2) 75%,
                transparent 75%,
                transparent
            );
            background-size: 20px 20px;
            animation: move 2s linear infinite;
            border-radius: 10px;
        }

        .chart-count {
            width: 50px;
            text-align: right;
            font-weight: 600;
            color: var(--dark-color);
        }

        /* Alert Badge */
        .alert-badge {
            background: var(--danger-color);
            color: var(--white);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 0.5rem;
            animation: pulse 2s infinite;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(20px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        @keyframes move {
            0% { background-position: 0 0; }
            100% { background-position: 20px 20px; }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .dashboard-sections {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions-grid {
                grid-template-columns: 1fr;
            }
            
            .alert-item {
                margin-left: -1rem;
                margin-right: -1rem;
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            .admin-nav-links {
                flex-wrap: wrap;
            }
            
            .admin-nav-links a {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }
            
            .navbar {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-links {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="dashboard-container">
            <nav class="navbar">
                <div class="logo">
                    <img src="../../assets/images/kitchen_logo1.png" alt="Aunt Joy's Restaurant Logo" /> 
                    Aunt Joy's  Admin
                </div>
                <ul class="nav-links">
                    <li><a href="../index.php"><i class="fas fa-home"></i> View Site</a></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Admin Navigation -->
    <nav class="admin-nav">
        <div class="dashboard-container">
            <ul class="admin-nav-links">
                <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="meals.php"><i class="fas fa-utensils"></i> Meals</a></li>
                <li><a href="categories.php"><i class="fas fa-list"></i> Categories</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users & Roles</a></li>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Welcome back, Admin!</h1>
            <p>Manage your restaurant's meals, categories, and user roles from this dashboard.</p>
        </div>

        <!-- Stats Overview -->
     <!-- Quick Stats for Admin -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?php echo $totalMeals; ?></div>
        <div class="stat-label">Total Meals</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $availableMeals; ?></div>
        <div class="stat-label">Available Meals</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $totalCategories; ?></div>
        <div class="stat-label">Active Categories</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $totalUsers; ?></div>
        <div class="stat-label">Total Users</div>
    </div>
</div>

        <!-- Main Content -->
        <div class="dashboard-sections">
            <!-- Recent Meals -->
            <div class="dashboard-card">
                <h2 class="card-title">
                    Recent Meals
                    <a href="meals.php">View All <i class="fas fa-arrow-right"></i></a>
                </h2>
                <div class="list-container">
                    <?php foreach($recentMeals as $meal): ?>
                        <div class="list-item">
                            <div class="item-info">
                                <strong><?php echo htmlspecialchars($meal['meal_name']); ?></strong>
                                <div style="font-size: 0.9rem; color: var(--gray-dark);">
                                    <?php echo htmlspecialchars($meal['category_name']); ?> • 
                                    $<?php echo number_format($meal['price'], 2); ?>
                                </div>
                                <div>
                                    <?php if($meal['is_available']): ?>
                                        <span class="status-available"><i class="fas fa-check"></i> Available</span>
                                    <?php else: ?>
                                        <span class="status-unavailable"><i class="fas fa-times"></i> Unavailable</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="item-actions">
                                <a href="edit_meal.php?id=<?php echo $meal['meal_id']; ?>" class="btn btn-outline btn-sm">Edit</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="dashboard-card">
                <h2 class="card-title">
                    Recent Customers
                    <a href="users.php">View All <i class="fas fa-arrow-right"></i></a>
                </h2>
                <div class="list-container">
                    <?php foreach($recentUsers as $user): ?>
                        <div class="list-item">
                            <div class="item-info">
                                <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                <div style="font-size: 0.9rem; color: var(--gray-dark);">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--gray-dark);">
                                    <i class="far fa-calendar"></i> Joined: <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Low Stock Alerts -->
        <?php if ($lowStockCount > 0): ?>
        <div class="dashboard-card">
            <h2 class="card-title">
                <i class="fas fa-exclamation-triangle"></i> Low Stock Alerts
                <a href="meals.php?filter=low_stock">View All <i class="fas fa-arrow-right"></i></a>
            </h2>
            <div class="list-container">
                <?php foreach($lowStockMeals as $meal): ?>
                    <div class="list-item alert-item">
                        <div class="item-info">
                            <strong><?php echo htmlspecialchars($meal['meal_name']); ?></strong>
                            <div style="font-size: 0.9rem; color: var(--danger-color); font-weight: 600;">
                                <i class="fas fa-box"></i> Stock: <?php echo $meal['stock_quantity']; ?> remaining
                                (Threshold: <?php echo $meal['low_stock_threshold']; ?>)
                            </div>
                            <div style="font-size: 0.8rem; color: var(--gray-dark);">
                                <?php echo htmlspecialchars($meal['category_name']); ?> • 
                                $<?php echo number_format($meal['price'], 2); ?>
                            </div>
                        </div>
                        <div class="item-actions">
                            <a href="edit_meal.php?id=<?php echo $meal['meal_id']; ?>" class="btn btn-danger btn-sm"><i class="fas fa-boxes"></i> Restock</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Categories Overview -->
        <div class="dashboard-card">
            <h2 class="card-title">
                Categories Overview
                <a href="categories.php">Manage Categories <i class="fas fa-arrow-right"></i></a>
            </h2>
            <div class="category-chart">
                <?php 
                $maxMeals = max(array_column($categoriesWithCounts, 'meal_count'));
                foreach($categoriesWithCounts as $category): 
                    $percentage = $maxMeals > 0 ? ($category['meal_count'] / $maxMeals) * 100 : 0;
                ?>
                    <div class="chart-bar">
                        <div class="chart-label"><?php echo htmlspecialchars($category['category_name']); ?></div>
                        <div class="chart-bar-inner">
                            <div class="chart-bar-fill" data-width="<?php echo $percentage; ?>%"></div>
                        </div>
                        <div class="chart-count"><?php echo $category['meal_count']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-card">
            <h2 class="card-title">Quick Actions</h2>
            <div class="quick-actions-grid">
                <a href="meals.php?action=add" class="quick-action-card">
                    <div class="action-icon"><i class="fas fa-plus-circle"></i></div>
                    <div class="action-title">Add New Meal</div>
                    <div class="action-description">Create a new meal entry</div>
                </a>

                <a href="categories.php?action=add" class="quick-action-card">
                    <div class="action-icon"><i class="fas fa-folder-plus"></i></div>
                    <div class="action-title">Add Category</div>
                    <div class="action-description">Create a new meal category</div>
                </a>

                <a href="users.php?action=add" class="quick-action-card">
                    <div class="action-icon"><i class="fas fa-user-plus"></i></div>
                    <div class="action-title">Add User</div>
                    <div class="action-description">Create a new user account</div>
                </a>

                <?php if ($lowStockCount > 0): ?>
                <a href="meals.php?filter=low_stock" class="quick-action-card alert-action">
                    <div class="action-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="action-title">Low Stock</div>
                    <div class="action-description"><?php echo $lowStockCount; ?> items need restocking</div>
                </a>
                <?php else: ?>
                <a href="meals.php" class="quick-action-card">
                    <div class="action-icon"><i class="fas fa-chart-bar"></i></div>
                    <div class="action-title">Manage Meals</div>
                    <div class="action-description">View all meals</div>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../../assets/js/admin.js"></script>
    <script>
        // Animation for chart bars
        document.addEventListener('DOMContentLoaded', function() {
            const chartBars = document.querySelectorAll('.chart-bar-fill');
            chartBars.forEach(bar => {
                const width = bar.getAttribute('data-width');
                setTimeout(() => {
                    bar.style.width = width;
                }, 300);
            });
            
            // Add staggered animation to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
            
            // Add staggered animation to quick action cards
            const actionCards = document.querySelectorAll('.quick-action-card');
            actionCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>