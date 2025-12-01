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

// Handle form submissions
$success_message = '';
$error_message = '';

// Add new category
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $category_name = sanitizeInput($_POST['category_name']);
    $category_description = sanitizeInput($_POST['category_description']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Basic validation
    if(empty($category_name)) {
        $error_message = "Category name is required!";
    } else {
        // Check if category already exists
        $check_stmt = $conn->prepare("SELECT category_id FROM categories WHERE category_name = ?");
        $check_stmt->bind_param("s", $category_name);
        $check_stmt->execute();
        
        if($check_stmt->get_result()->num_rows > 0) {
            $error_message = "Category name already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (category_name, category_description, is_active) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $category_name, $category_description, $is_active);
            
            if($stmt->execute()) {
                $success_message = "Category added successfully!";
            } else {
                $error_message = "Failed to add category: " . $conn->error;
            }
        }
    }
}

// Update category
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_category'])) {
    $category_id = intval($_POST['category_id']);
    $category_name = sanitizeInput($_POST['category_name']);
    $category_description = sanitizeInput($_POST['category_description']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if(empty($category_name)) {
        $error_message = "Category name is required!";
    } else {
        // Check if category name already exists (excluding current category)
        $check_stmt = $conn->prepare("SELECT category_id FROM categories WHERE category_name = ? AND category_id != ?");
        $check_stmt->bind_param("si", $category_name, $category_id);
        $check_stmt->execute();
        
        if($check_stmt->get_result()->num_rows > 0) {
            $error_message = "Category name already exists!";
        } else {
            $update_stmt = $conn->prepare("UPDATE categories SET category_name = ?, category_description = ?, is_active = ? WHERE category_id = ?");
            $update_stmt->bind_param("ssii", $category_name, $category_description, $is_active, $category_id);
            
            if($update_stmt->execute()) {
                $success_message = "Category updated successfully!";
            } else {
                $error_message = "Failed to update category: " . $conn->error;
            }
        }
    }
}

// Get all categories
$categories_result = $conn->query("
    SELECT c.*, 
           COUNT(m.meal_id) as meal_count 
    FROM categories c 
    LEFT JOIN meals m ON c.category_id = m.category_id 
    GROUP BY c.category_id 
    ORDER BY c.category_name
");

if($categories_result) {
    $categories = $categories_result->fetch_all(MYSQLI_ASSOC);
} else {
    $categories = [];
    $error_message = "Failed to load categories: " . $conn->error;
}

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Aunt Joy's Restaurant</title>
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
           background: linear-gradient(135deg, #667eea 0%, #ab9bbbff 100%);
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

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @keyframes bounce {
            0%, 20%, 53%, 80%, 100% {
                transform: translate3d(0,0,0);
            }
            40%, 43% {
                transform: translate3d(0,-8px,0);
            }
            70% {
                transform: translate3d(0,-4px,0);
            }
            90% {
                transform: translate3d(0,-2px,0);
            }
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #ab9bbbff 100%);
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
            height: 40px;
            width: auto;
            border-radius: 8px;
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
            background: rgba(10, 21, 71, 0.17);
            transform: translateY(-2px);
        }

        .nav-links a.active {
            background: linear-gradient(135deg, #667eea 0%, #ab9bbbff 100%);
            box-shadow: 0 4px 12px rgba(21, 21, 22, 0.15);
        }

        /* Admin Navigation */
        .admin-nav {
          background: linear-gradient(135deg, #667eea 0%, #ab9bbbff 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .admin-nav-links {
            display: flex;
            list-style: none;
            gap: 0;
            overflow-x: auto;
        }

        .admin-nav-links li {
            flex-shrink: 0;
        }

        .admin-nav-links a {
            color: var(--white);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 1.5rem;
            transition: var(--transition);
            font-weight: 500;
            border-bottom: 3px solid transparent;
        }

        .admin-nav-links a:hover {
            background: rgba(11, 18, 61, 0.17);
            border-bottom-color: var(--primary-color);
        }

        .admin-nav-links a.active {
            background: rgba(255, 255, 255, 0.15);
            border-bottom-color: var(--primary-color);
            box-shadow: inset 0 -3px 0 var(--primary-color);
        }

        /* Admin Container */
        .admin-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 20px;
            animation: slideInUp 0.6s ease-out;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }

        .page-header::after {
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

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--gray-light);
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--white), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-header p {
            font-size: 1.1rem;
            color: var(--gray-light);
            max-width: 500px;
            margin: 0 auto;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--gray-dark);
            font-weight: 500;
        }

        /* Admin Cards */
        .admin-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
            transition: var(--transition);
            border: 1px solid transparent;
            animation: slideInUp 0.6s ease-out;
        }

        .admin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            border-color: var(--primary-color);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--gray-light);
        }

        .card-title i {
            color: var(--primary-color);
        }

        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label i {
            color: var(--primary-color);
            width: 16px;
        }

        .form-control {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background: var(--white);
            font-family: 'Poppins', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
            transform: translateY(-2px);
        }

        .form-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-dark);
            transition: var(--transition);
        }

        .form-control:focus + .form-icon {
            color: var(--primary-color);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
            padding-left: 1rem;
        }

        /* Checkbox Group */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: var(--border-radius);
            border: 2px solid transparent;
            transition: var(--transition);
        }

        .checkbox-group:hover {
            border-color: var(--primary-color);
            background: #ffffff;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: var(--primary-color);
            cursor: pointer;
        }

        .checkbox-group label {
            font-weight: 600;
            color: var(--dark-color);
            cursor: pointer;
            margin: 0;
        }

        /* Buttons */
        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
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

        .btn-danger {
            background: transparent;
            border: 2px solid var(--danger-color);
            color: var(--danger-color);
        }

        .btn-danger:hover {
            background: var(--danger-color);
            color: var(--white);
            transform: translateY(-3px);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .categories-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
        }

        .categories-table th,
        .categories-table td {
            padding: 1.25rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-light);
        }

        .categories-table th {
            background: linear-gradient(135deg, #34495e, #2c3e50);
            color: var(--white);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .categories-table tr {
            transition: var(--transition);
        }

        .categories-table tr:hover {
            background: #f8f9fa;
            transform: scale(1.01);
        }

        .categories-table tr:last-child td {
            border-bottom: none;
        }

        /* Status Badges */
        .status-active {
            background: linear-gradient(135deg, var(--success-color), #2ecc71);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 8px rgba(39, 174, 96, 0.3);
        }

        .status-inactive {
            background: linear-gradient(135deg, var(--danger-color), #c0392b);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 8px rgba(231, 76, 60, 0.3);
        }

        /* Meal Count */
        .meal-count {
            background: linear-gradient(135deg, var(--secondary-color), #2980b9);
            color: var(--white);
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
            transition: var(--transition);
        }

        .meal-count:hover {
            transform: scale(1.1) rotate(5deg);
            animation: pulse 0.6s ease;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        /* Alerts */
        .alert {
            padding: 1.25rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideInUp 0.5s ease-out;
            border-left: 4px solid transparent;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left-color: #27ae60;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #e74c3c;
        }

        .alert i {
            font-size: 1.25rem;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background-color: var(--white);
            margin: 10% auto;
            padding: 2rem;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideInUp 0.4s ease;
            position: relative;
        }

        .modal-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h2 {
            color: var(--dark-color);
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray-dark);
            transition: var(--transition);
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close:hover {
            background: var(--gray-light);
            color: var(--danger-color);
            transform: rotate(90deg);
        }

        /* Loading Animation */
        .btn.loading {
            position: relative;
            color: transparent;
        }

        .btn.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid transparent;
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .admin-nav-links {
                gap: 0;
            }
            
            .admin-nav-links a {
                padding: 1rem;
                font-size: 0.9rem;
            }
        }

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

            .page-header h1 {
                font-size: 2rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .categories-table {
                font-size: 0.9rem;
            }

            .categories-table th,
            .categories-table td {
                padding: 0.75rem;
            }

            .admin-nav-links {
                flex-wrap: nowrap;
                overflow-x: auto;
            }
        }

        @media (max-width: 480px) {
            .admin-container {
                padding: 0 10px;
            }

            .admin-card {
                padding: 1.5rem;
            }

            .modal-content {
                margin: 5% auto;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="../index.php" class="logo">
                    <img src="../../assets/images/kitchen_logo1.png" alt="Aunt Joy's Restaurant Logo" /> 
                    Aunt Joy's - Admin
                </a>
                <ul class="nav-links">
                    <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Admin Navigation -->
    <nav class="admin-nav">
        <div class="container">
            <ul class="admin-nav-links">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="meals.php"><i class="fas fa-utensils"></i> Meals</a></li>
                <li><a href="categories.php" class="active"><i class="fas fa-tags"></i> Categories</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
            </ul>
        </div>
    </nav>

    <div class="admin-container">
        <div class="page-header">
            <h1>Manage Categories</h1>
            <p>Organize your menu with categories and manage their settings</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="stat-value"><?php echo count($categories); ?></div>
                <div class="stat-label">Total Categories</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="stat-value"><?php echo array_sum(array_column($categories, 'meal_count')); ?></div>
                <div class="stat-label">Total Meals</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo count(array_filter($categories, function($cat) { return $cat['is_active']; })); ?></div>
                <div class="stat-label">Active Categories</div>
            </div>
        </div>

        <?php if($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div><?php echo $success_message; ?></div>
            </div>
        <?php endif; ?>

        <?php if($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div><?php echo $error_message; ?></div>
            </div>
        <?php endif; ?>
<!-- Add Category Form -->
<div class="admin-card">
    <h2 class="card-title"><i class="fas fa-plus-circle"></i> Add New Category</h2>
    <form method="POST" action="">
        <div class="form-group">
            <label class="form-label"><i class="fas fa-tag"></i> Category Name *</label>
            <input type="text" name="category_name" class="form-control" required
                   placeholder="Enter category name">
        </div>

        <div class="form-group">
            <label class="form-label"><i class="fas fa-align-left"></i> Description</label>
            <textarea name="category_description" class="form-control" rows="3" 
                      placeholder="Enter category description (optional)"></textarea>
        </div>

        <div class="form-group">
            <div class="checkbox-group">
                <input type="checkbox" name="is_active" id="is_active" checked>
                <label for="is_active">Active Category</label>
            </div>
        </div>

        <button type="submit" name="add_category" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Category
        </button>
    </form>
</div>

        <!-- Categories List -->
        <div class="admin-card">
            <h2 class="card-title"><i class="fas fa-list"></i> All Categories (<?php echo count($categories); ?>)</h2>
            <div class="table-responsive">
                <table class="categories-table">
                    <thead>
                        <tr>
                            <th>Category Name</th>
                            <th>Description</th>
                            <th>Meals</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($categories as $index => $category): ?>
                            <tr style="animation-delay: <?php echo $index * 0.1; ?>s;">
                                <td>
                                    <strong><?php echo htmlspecialchars($category['category_name']); ?></strong>
                                </td>
                                <td>
                                    <?php if(!empty($category['category_description'])): ?>
                                        <?php echo htmlspecialchars(substr($category['category_description'], 0, 100)); ?>
                                        <?php if(strlen($category['category_description']) > 100): ?>...<?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: var(--gray-dark); font-style: italic;">No description</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="meal-count" title="<?php echo $category['meal_count']; ?> meals">
                                        <?php echo $category['meal_count']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($category['is_active']): ?>
                                        <span class="status-active">
                                            <i class="fas fa-check"></i> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="status-inactive">
                                            <i class="fas fa-times"></i> Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($category)); ?>)" 
                                                class="btn btn-outline btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button onclick="deleteCategory(<?php echo $category['category_id']; ?>)" 
                                                class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Edit Category</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" action="" id="editForm">
                <input type="hidden" name="category_id" id="edit_category_id">
                <input type="hidden" name="update_category" value="1">
                
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-tag"></i> Category Name *</label>
                    <input type="text" name="category_name" id="edit_category_name" class="form-control" required>
                    <i class="form-icon fas fa-tag"></i>
                </div>

                <div class="form-group">
                    <label class="form-label"><i class="fas fa-align-left"></i> Description</label>
                    <textarea name="category_description" id="edit_category_description" class="form-control" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_active" id="edit_is_active">
                        <label for="edit_is_active">Active Category</label>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Category
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeEditModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

   <script>
    // Enhanced Modal Functions
    function openEditModal(category) {
        document.getElementById('edit_category_id').value = category.category_id;
        document.getElementById('edit_category_name').value = category.category_name;
        document.getElementById('edit_category_description').value = category.category_description || '';
        document.getElementById('edit_is_active').checked = category.is_active;
        
        document.getElementById('editModal').style.display = 'block';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    // Enhanced Delete Function
    async function deleteCategory(categoryId) {
        if(confirm('Are you sure you want to delete this category? Meals in this category will become uncategorized.')) {
            try {
                const btn = event.target;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
                btn.disabled = true;

                const response = await fetch(`delete_category.php?id=${categoryId}`);
                
                if(response.ok) {
                    window.location.href = 'categories.php';
                } else {
                    alert('Error deleting category');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while deleting the category.');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        if (event.target === modal) {
            closeEditModal();
        }
    }

    // Simple form enhancement - only visual, doesn't prevent submission
    document.addEventListener('DOMContentLoaded', function() {
        // Add input focus effects (visual only)
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.style.borderColor = '#e74c3c';
                this.style.boxShadow = '0 0 0 3px rgba(231, 76, 60, 0.1)';
            });
            
            input.addEventListener('blur', function() {
                this.style.borderColor = '';
                this.style.boxShadow = '';
            });
        });

        // Real-time form validation (visual only)
        const categoryNameInput = document.querySelector('input[name="category_name"]');
        if (categoryNameInput) {
            categoryNameInput.addEventListener('input', function() {
                if (this.value.length > 2) {
                    this.style.borderColor = '#27ae60';
                } else {
                    this.style.borderColor = '#ecf0f1';
                }
            });
        }
    });
z
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Escape key to close modal
        if (e.key === 'Escape') {
            closeEditModal();
        }
    });
</script> <script src="../../assets/js/admin.js"></script>
</body>
</html>