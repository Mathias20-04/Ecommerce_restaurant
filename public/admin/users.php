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

// Add new user
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $full_name = sanitizeInput($_POST['full_name']);
    $phone = sanitizeInput($_POST['phone']);
    $role = sanitizeInput($_POST['role']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if(empty($username) || empty($email) || empty($full_name) || empty($role)) {
        $error_message = "Username, email, full name, and role are required!";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format!";
    } elseif($password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    } elseif(strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long!";
    } else {
        // Check if username or email already exists
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        
        if($check_stmt->get_result()->num_rows > 0) {
            $error_message = "Username or email already exists!";
        } else {
            // Hash password and create user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, full_name, phone, role, password_hash) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $username, $email, $full_name, $phone, $role, $password_hash);
            
            if($stmt->execute()) {
                $success_message = "User created successfully!";
            } else {
                $error_message = "Failed to create user: " . $conn->error;
            }
        }
    }
}

// Update user role
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_role'])) {
    $user_id = intval($_POST['user_id']);
    $role = sanitizeInput($_POST['role']);

    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
    $stmt->bind_param("si", $role, $user_id);
    
    if($stmt->execute()) {
        $success_message = "User role updated successfully!";
    } else {
        $error_message = "Failed to update user role: " . $conn->error;
    }
}

// Get filter parameters
$role_filter = $_GET['role'] ?? '';
$search_term = $_GET['search'] ?? '';

// Build query with filters
$query = "SELECT user_id, username, email, full_name, phone, role, created_at FROM users WHERE 1=1";
$params = [];
$types = '';

if($role_filter) {
    $query .= " AND role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

if($search_term) {
    $query .= " AND (username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $search_like = "%$search_term%";
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $types .= 'sss';
}

$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
if($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user counts by role
$role_counts = [
    'admin' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'],
    'manager' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'manager'")->fetch_assoc()['count'],
    'customer' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")->fetch_assoc()['count'],
    'sales' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role ='sales' ")-> fetch_assoc()['count'],
    'total' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count']
];

closeDBConnection($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users & Roles - Aunt Joy's Restaurant</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --primary-gradient: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            --primary-gradient-hover: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            --white: #ffffff;
            --white-95: rgba(255, 255, 255, 0.95);
            --white-90: rgba(255, 255, 255, 0.90);
            --white-80: rgba(255, 255, 255, 0.80);
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --border-radius: 16px;
            --border-radius-lg: 20px;
            --border-radius-sm: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: all 0.15s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--gray-800);
            line-height: 1.6;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            animation: fadeInDown 0.6s ease;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--white);
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-header h1::before {
            content: '';
            display: block;
            width: 6px;
            height: 40px;
            background: var(--white);
            border-radius: 3px;
            box-shadow: 0 2px 8px rgba(255, 255, 255, 0.4);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
            animation: fadeInUp 0.6s ease 0.2s both;
        }

        .stat-card {
            background: var(--white-95);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            padding: 2rem;
            text-align: center;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            border: 1px solid var(--white-80);
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
            background: var(--primary-gradient);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
            display: block;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-label {
            color: var(--gray-600);
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .admin-card {
            background: var(--white-95);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            padding: 2.5rem;
            margin-bottom: 2rem;
            animation: fadeInUp 0.6s ease;
            border: 1px solid var(--white-80);
            transition: var(--transition);
        }

        .admin-card:hover {
            box-shadow: var(--shadow-xl);
        }

        .card-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-100);
        }

        .card-title i {
            color: var(--primary);
            font-size: 1.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2rem;
            border-radius: var(--border-radius-sm);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            font-size: 0.95rem;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: var(--white);
            box-shadow: var(--shadow-md);
        }

        .btn-primary:hover {
            background: var(--primary-gradient-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-danger {
            background: transparent;
            color: var(--danger);
            border: 2px solid var(--danger);
        }

        .btn-danger:hover {
            background: var(--danger);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-sm {
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label i {
            color: var(--primary);
            font-size: 1.1rem;
        }

        .form-label::after {
            content: '*';
            color: var(--danger);
            margin-left: 0.25rem;
        }

        .form-label.optional::after {
            content: '';
        }

        .form-control {
            padding: 1rem 1.25rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius-sm);
            font-size: 1rem;
            transition: var(--transition);
            background: var(--white);
            box-shadow: var(--shadow-sm);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            transform: translateY(-1px);
        }

        .filters {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            align-items: flex-end;
            padding: 1.5rem;
            background: var(--gray-50);
            border-radius: var(--border-radius-sm);
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            flex: 1;
            min-width: 200px;
        }

        .filter-label {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.95rem;
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: var(--border-radius-sm);
            box-shadow: var(--shadow-sm);
            background: var(--white);
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
        }

        .users-table th,
        .users-table td {
            padding: 1.25rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-100);
        }

        .users-table th {
            background: var(--gray-50);
            font-weight: 700;
            color: var(--gray-700);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            position: sticky;
            top: 0;
        }

        .users-table tr {
            transition: var(--transition-fast);
        }

        .users-table tr:hover {
            background: var(--gray-50);
            transform: scale(1.01);
            box-shadow: var(--shadow-sm);
        }

        .role-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: var(--shadow-sm);
        }

        .role-admin { 
            background: var(--primary-gradient);
            color: var(--white);
        }
        .role-manager { 
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: var(--white);
        }
        .role-customer { 
            background: linear-gradient(135deg, var(--gray-500), var(--gray-600));
            color: var(--white);
        }
        .role-sales { 
            background: linear-gradient(135deg, var(--warning), #d97706);
            color: var(--white);
        }

        .action-buttons {
            display: flex;
            gap: 0.75rem;
        }

        .alert {
            padding: 1.25rem 1.5rem;
            border-radius: var(--border-radius-sm);
            margin-bottom: 2rem;
            animation: slideInRight 0.5s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow-md);
            border-left: 4px solid;
            background: var(--white-95);
            backdrop-filter: blur(10px);
        }

        .alert-success {
            color: var(--success);
            border-left-color: var(--success);
        }

        .alert-error {
            color: var(--danger);
            border-left-color: var(--danger);
        }

        .alert i {
            font-size: 1.5rem;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: var(--white);
            margin: 10% auto;
            padding: 3rem;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow-xl);
            animation: slideInUp 0.4s ease;
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-100);
        }

        .modal-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .modal-header i {
            color: var(--primary);
        }

        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray-400);
            transition: var(--transition);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close:hover {
            background: var(--gray-100);
            color: var(--gray-700);
        }

        /* Header Styles */
        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
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
            gap: 1rem;
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--white);
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .logo img {
            height: 65px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
            margin-left: 30px;
            width: auto;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--white);
            font-weight: 600;
            transition: var(--transition);
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius-sm);
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .admin-nav {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .admin-nav-links {
            display: flex;
            list-style: none;
            gap: 0;
        }

        .admin-nav-links a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1.25rem 2rem;
            color: var(--white);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            border-bottom: 3px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .admin-nav-links a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .admin-nav-links a:hover::before {
            left: 100%;
        }

        .admin-nav-links a:hover,
        .admin-nav-links a.active {
            background: rgba(255, 255, 255, 0.15);
            border-bottom-color: var(--white);
        }

        /* Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

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

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .admin-container {
                padding: 1rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .filters {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .users-table {
                font-size: 0.875rem;
            }
            
            .users-table th,
            .users-table td {
                padding: 1rem 0.5rem;
            }
            
            .nav-links {
                gap: 0.5rem;
            }
            
            .admin-nav-links {
                overflow-x: auto;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Live notification system */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1.5rem 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-xl);
            z-index: 1001;
            animation: slideInRight 0.5s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
            max-width: 400px;
            backdrop-filter: blur(20px);
            background: var(--white-95);
            border: 1px solid var(--white-80);
        }

        .notification-success {
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .notification-error {
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(37, 99, 235, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(37, 99, 235, 0);
            }
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray-500);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
            color: var(--primary);
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
                    <li><a href="../index.php"><i class='bx bx-home'></i> View Site</a></li>
                    <li><a href="../logout.php"><i class='bx bx-log-out'></i> Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Admin Navigation -->
    <nav class="admin-nav">
        <div class="container">
            <ul class="admin-nav-links">
                <li><a href="dashboard.php"><i class='bx bx-dashboard'></i> Dashboard</a></li>
                <li><a href="meals.php"><i class='bx bx-food-menu'></i> Meals</a></li>
                <li><a href="categories.php"><i class='bx bx-category'></i> Categories</a></li>
                <li><a href="users.php" class="active"><i class='bx bx-group'></i> Users & Roles</a></li>
            </ul>
        </div>
    </nav>

    <div class="admin-container">
        <div class="page-header">
            <h1><i class='bx bx-group'></i> Manage Users & Roles</h1>
            <div class="header-actions">
                <button class="btn btn-primary pulse" onclick="toggleUserForm()">
                    <i class='bx bx-user-plus'></i>
                    Add New User
                </button>
            </div>
        </div>

        <?php if($success_message): ?>
            <div class="alert alert-success">
                <i class='bx bx-check-circle'></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if($error_message): ?>
            <div class="alert alert-error">
                <i class='bx bx-error-circle'></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- User Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?php echo $role_counts['total']; ?></span>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $role_counts['admin']; ?></span>
                <div class="stat-label">Administrators</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $role_counts['manager']; ?></span>
                <div class="stat-label">Managers</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $role_counts['customer']; ?></span>
                <div class="stat-label">Customers</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $role_counts['sales']; ?></span>
                <div class="stat-label">Sales Managers</div>
            </div>
        </div>

        <!-- Add User Form -->
        <div class="admin-card" id="userForm">
            <h2 class="card-title"><i class='bx bx-user-plus'></i> Add New User</h2>
            <form method="POST" action="" id="add-user-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label"><i class='bx bx-user'></i> Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><i class='bx bx-envelope'></i> Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><i class='bx bx-id-card'></i> Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label optional"><i class='bx bx-phone'></i> Phone</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="form-label"><i class='bx bx-cog'></i> Role</label>
                        <select name="role" class="form-control" required>
                            <option value="customer">Customer</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Administrator</option>
                            <option value="sales">Sales Manager</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><i class='bx bx-lock-alt'></i> Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>

                    <div class="form-group">
                        <label class="form-label"><i class='bx bx-lock'></i> Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="add_user" class="btn btn-primary">
                        <i class='bx bx-user-plus'></i>
                        Create User
                    </button>
                </div>
            </form>
        </div>

        <!-- Filters -->
        <div class="admin-card">
            <h2 class="card-title"><i class='bx bx-filter-alt'></i> User Management</h2>
            <form method="GET" action="" class="filters">
                <div class="filter-group">
                    <label class="filter-label">Role Filter</label>
                    <select name="role" class="form-control">
                        <option value="">All Roles</option>
                        <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Administrators</option>
                        <option value="manager" <?php echo $role_filter === 'manager' ? 'selected' : ''; ?>>Managers</option>
                        <option value="customer" <?php echo $role_filter === 'customer' ? 'selected' : ''; ?>>Customers</option>
                        <option value="sales" <?php echo $role_filter === 'sales' ? 'selected' : ''; ?>>Sales Managers</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Search Users</label>
                    <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Search by username, email, or name...">
                </div>

                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="users.php" class="btn btn-outline">Clear Filters</a>
                </div>
            </form>
        </div>

        <!-- Users List -->
        <div class="admin-card">
            <h2 class="card-title"><i class='bx bx-list-ul'></i> All Users</h2>
            <div class="table-responsive">
                <?php if(empty($users)): ?>
                    <div class="empty-state">
                        <i class='bx bx-user-x'></i>
                        <h3 style="margin-bottom: 0.5rem;">No users found</h3>
                        <p>Try adjusting your filters or add new users to get started.</p>
                    </div>
                <?php else: ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $index => $user): ?>
                                <tr style="animation: fadeInUp 0.5s ease <?php echo $index * 0.1; ?>s both;">
                                    <td><strong>#<?php echo $user['user_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="role-badge role-<?php echo $user['role']; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick="openRoleModal(<?php echo $user['user_id']; ?>, '<?php echo $user['role']; ?>', '<?php echo htmlspecialchars($user['username']); ?>')" class="btn btn-outline btn-sm">
                                                <i class='bx bx-edit'></i>
                                                Change Role
                                            </button>
                                            <?php if($user['role'] !== 'admin' || $role_counts['admin'] > 1): ?>
                                                <button onclick="deleteUser(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" class="btn btn-danger btn-sm">
                                                    <i class='bx bx-trash'></i>
                                                    Delete
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Change Role Modal -->
    <div id="roleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class='bx bx-cog'></i> Change User Role</h2>
                <span class="close" onclick="closeRoleModal()"><i class='bx bx-x'></i></span>
            </div>
            <form method="POST" action="" id="roleForm">
                <input type="hidden" name="user_id" id="role_user_id">
                <input type="hidden" name="update_role" value="1">
                
                <div class="form-group">
                    <label class="form-label"><i class='bx bx-user'></i> User</label>
                    <input type="text" id="role_username" class="form-control" readonly style="background: var(--gray-50);">
                </div>

                <div class="form-group">
                    <label class="form-label"><i class='bx bx-cog'></i> New Role</label>
                    <select name="role" id="role_select" class="form-control" required>
                        <option value="customer">Customer</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Administrator</option>
                        <option value="sales">Sales Manager</option>
                    </select>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-check'></i>
                        Update Role
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeRoleModal()">
                        <i class='bx bx-x'></i>
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Enhanced modal functions
        function openRoleModal(userId, currentRole, username) {
            document.getElementById('role_user_id').value = userId;
            document.getElementById('role_select').value = currentRole;
            document.getElementById('role_username').value = username;
            document.getElementById('roleModal').style.display = 'block';
        }

        function closeRoleModal() {
            document.getElementById('roleModal').style.display = 'none';
        }

        // Enhanced delete function with better UX
        async function deleteUser(userId, username) {
            if (!confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) return;

            const button = event.target;
            const originalText = button.innerHTML;
            
            // Show loading state
            button.innerHTML = `
                <i class='bx bx-loader-circle bx-spin'></i>
                Deleting...
            `;
            button.disabled = true;

            try {
                const response = await fetch(`http://localhost:8000/projects/aunt-joy-restaurant/api/admin/users/delete_user.php?id=${userId}`, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });

                let data = null;
                try {
                    data = await response.json();
                } catch (e) {
                    // ignore parse errors
                }

                if (!response.ok) {
                    const msg = (data && data.message) ? data.message : `HTTP error ${response.status}`;
                    throw new Error(msg);
                }

                if (data && data.success) {
                    showNotification(data.message, 'success');
                    
                    // Remove row with animation
                    const row = button.closest('tr');
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(-100%)';
                    
                    setTimeout(() => {
                        row.remove();
                        updateStats();
                    }, 300);
                } else {
                    throw new Error(data && data.message ? data.message : 'Unknown error');
                }
            } catch (error) {
                console.error('API Error:', error);
                showNotification('Error: ' + error.message, 'error');
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }

        // Notification system
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class='bx ${type === 'success' ? 'bx-check-circle' : 'bx-error-circle'}'></i>
                ${message}
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideInRight 0.5s ease reverse';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 500);
            }, 4000);
        }

        // Update stats after user deletion
        function updateStats() {
            const stats = document.querySelectorAll('.stat-number');
            if (stats[0]) {
                const currentTotal = parseInt(stats[0].textContent);
                stats[0].textContent = currentTotal - 1;
                
                // Add animation to updated stat
                stats[0].style.animation = 'pulse 0.5s ease';
                setTimeout(() => {
                    stats[0].style.animation = '';
                }, 500);
            }
        }

        // Toggle user form visibility
        function toggleUserForm() {
            const form = document.getElementById('userForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        // Form validation and enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('add-user-form');
            const inputs = form.querySelectorAll('input, select');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    if (this.value === '') {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });

            // Add real-time password validation
            const password = document.querySelector('input[name="password"]');
            const confirmPassword = document.querySelector('input[name="confirm_password"]');
            
            function validatePasswords() {
                if (password.value && confirmPassword.value) {
                    if (password.value !== confirmPassword.value) {
                        confirmPassword.style.borderColor = 'var(--danger)';
                        confirmPassword.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.1)';
                    } else {
                        confirmPassword.style.borderColor = 'var(--success)';
                        confirmPassword.style.boxShadow = '0 0 0 4px rgba(16, 185, 129, 0.1)';
                    }
                }
            }
            
            password.addEventListener('input', validatePasswords);
            confirmPassword.addEventListener('input', validatePasswords);
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('roleModal');
            if (event.target === modal) {
                closeRoleModal();
            }
        }
    </script>
</body>
</html>