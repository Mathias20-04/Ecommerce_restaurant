<?php
// profile.php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();

// Redirect to login if not authenticated
if(!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$currentUser = $auth->getCurrentUser();
$conn = getDBConnection();

// Get user details from database
$userStmt = $conn->prepare("SELECT username, email, full_name, phone, address, created_at FROM users WHERE user_id = ?");
$userStmt->bind_param("i", $currentUser['user_id']);
$userStmt->execute();
$userDetails = $userStmt->get_result()->fetch_assoc();

$success_message = '';
$error_message = '';

// Process profile update
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    
    // Basic validation
    if(empty($full_name) || empty($email)) {
        $error_message = "Full name and email are required!";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format!";
    } else {
        // Check if email already exists (excluding current user)
        $emailCheck = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $emailCheck->bind_param("si", $email, $currentUser['user_id']);
        $emailCheck->execute();
        
        if($emailCheck->get_result()->num_rows > 0) {
            $error_message = "Email already exists!";
        } else {
            // Update user profile
            $updateStmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
            $updateStmt->bind_param("ssssi", $full_name, $email, $phone, $address, $currentUser['user_id']);
            
            if($updateStmt->execute()) {
                $success_message = "Profile updated successfully!";
                // Update session
                $_SESSION['full_name'] = $full_name;
                // Refresh user details
                $userDetails = ['full_name' => $full_name, 'email' => $email, 'phone' => $phone, 'address' => $address];
            } else {
                $error_message = "Failed to update profile: " . $conn->error;
            }
        }
    }
}

// Process password change
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All password fields are required!";
    } elseif($new_password !== $confirm_password) {
        $error_message = "New passwords do not match!";
    } elseif(strlen($new_password) < 6) {
        $error_message = "New password must be at least 6 characters long!";
    } else {
        // Verify current password
        $passwordCheck = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $passwordCheck->bind_param("i", $currentUser['user_id']);
        $passwordCheck->execute();
        $result = $passwordCheck->get_result();
        
        if($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if(password_verify($current_password, $user['password_hash'])) {
                // Update password
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $updatePassword = $conn->prepare("UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
                $updatePassword->bind_param("si", $new_password_hash, $currentUser['user_id']);
                
                if($updatePassword->execute()) {
                    $success_message = "Password changed successfully!";
                } else {
                    $error_message = "Failed to change password!";
                }
            } else {
                $error_message = "Current password is incorrect!";
            }
        }
    }
}

closeDBConnection($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Aunt Joy's Restaurant</title>
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

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
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
            height: 60px;
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
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .user-name {
            font-weight: 600;
            color: var(--white);
        }

        .dropdown-arrow {
            color: var(--white);
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
            background:blue;
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
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            color: var(--dark-color);
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

        /* Profile Container */
        .profile-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 20px;
            animation: slideInUp 0.6s ease-out;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }

        .profile-header::after {
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

        .profile-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .profile-header p {
            font-size: 1.1rem;
            color: var(--gray-dark);
            max-width: 500px;
            margin: 0 auto;
        }

        /* Profile Grid */
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Sidebar */
        .profile-sidebar {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .user-card {
            text-align: center;
            margin-bottom: 2rem;
        }

        .user-avatar-large {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: 700;
            margin: 0 auto 1rem;
            border: 4px solid var(--white);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .user-card h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .user-card p {
            color: var(--gray-dark);
            font-size: 0.9rem;
        }

        .sidebar-nav {
            list-style: none;
        }

        .sidebar-nav li {
            margin-bottom: 0.5rem;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--dark-color);
            text-decoration: none;
            border-radius: 8px;
            transition: var(--transition);
            font-weight: 500;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
            transform: translateX(5px);
        }

        .sidebar-nav i {
            width: 20px;
            text-align: center;
        }

        /* Profile Content */
        .profile-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .profile-section {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            transition: var(--transition);
            border: 1px solid transparent;
        }

        .profile-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: var(--primary-color);
        }

        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
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

        /* User Info Grid */
        .user-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            border: 1px solid transparent;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }

        .info-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            margin: 0 auto 1rem;
        }

        .info-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            color: var(--gray-dark);
            font-weight: 500;
        }

        /* Password Requirements */
        .password-requirements {
            font-size: 0.85rem;
            color: var(--gray-dark);
            margin-top: 0.5rem;
            padding-left: 1.5rem;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
        }

        .requirement.met {
            color: var(--success-color);
        }

        .requirement i {
            font-size: 0.8rem;
        }

        /* Account Actions */
        .account-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        /* Animations for form validation */
        .form-control.error {
            border-color: var(--danger-color);
            animation: shake 0.5s ease-in-out;
        }

        .form-control.success {
            border-color: var(--success-color);
        }

        /* Loading States */
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

            .profile-header h1 {
                font-size: 2rem;
            }

            .profile-section {
                padding: 1.5rem;
            }

            .user-info-grid {
                grid-template-columns: 1fr;
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
                        <a href="orders.php"><i class="fas fa-box"></i> Orders</a>
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
                                <a href="profile.php" class="dropdown-item active"><i class="fas fa-user"></i> My Profile</a>
                                <a href="orders.php" class="dropdown-item"><i class="fas fa-box"></i> My Orders</a>
                                <div class="dropdown-divider"></div>
                                <a href="logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            </div>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="profile-container">
        <div class="profile-header">
            <h1>My Profile</h1>
            <p>Manage your account information and preferences</p>
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

        <div class="profile-grid">
            <!-- Sidebar -->
            <div class="profile-sidebar">
                <div class="user-card">
                    <div class="user-avatar-large">
                        <?php 
                            $name = $currentUser['full_name'] ?? $currentUser['username'];
                            echo strtoupper(substr($name, 0, 1)); 
                        ?>
                    </div>
                    <h2><?php echo htmlspecialchars($name); ?></h2>
                    <p>Member since <?php echo date('F Y', strtotime($userDetails['created_at'])); ?></p>
                </div>

                <ul class="sidebar-nav">
                    <li><a href="#personal-info" class="active"><i class="fas fa-user-circle"></i> Personal Info</a></li>
                    <li><a href="#security"><i class="fas fa-shield-alt"></i> Security</a></li>
                    <li><a href="#account-actions"><i class="fas fa-history"></i> Order History</a></li>
                    <li><a href="#account-actions"><i class="fas fa-utensils"></i> Browse Menu</a></li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="profile-content">
                <!-- Personal Information Section -->
                <div class="profile-section" id="personal-info">
                    <h2 class="section-title"><i class="fas fa-user-edit"></i> Personal Information</h2>
                    
                    <div class="user-info-grid">
                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="info-label">Username</div>
                            <div class="info-value"><?php echo htmlspecialchars($currentUser['username']); ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="info-label">Member Since</div>
                            <div class="info-value"><?php echo date('F j, Y', strtotime($userDetails['created_at'])); ?></div>
                        </div>
                    </div>

                    <form method="POST" action="" id="profile-form">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-user"></i> Full Name </label>
                                <input type="text" name="full_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($userDetails['full_name'] ?? ''); ?>" 
                                       required
                                       placeholder="Enter your full name">
                                <i class="form-icon fas fa-user"></i>
                            </div>

                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-envelope"></i> Email Address *</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($userDetails['email'] ?? ''); ?>" 
                                       required
                                       placeholder="Enter your email address">
                                <i class="form-icon fas fa-envelope"></i>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-phone"></i> Phone Number</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($userDetails['phone'] ?? ''); ?>" 
                                       placeholder="Enter your phone number">
                                <i class="form-icon fas fa-phone"></i>
                            </div>

                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-map-marker-alt"></i> Delivery Address</label>
                                <textarea name="address" class="form-control" 
                                          placeholder="Enter your default delivery address"><?php echo htmlspecialchars($userDetails['address'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>

                <!-- Security Section -->
                <div class="profile-section" id="security">
                    <h2 class="section-title"><i class="fas fa-shield-alt"></i> Security Settings</h2>
                    
                    <form method="POST" action="" id="password-form">
                        <input type="hidden" name="change_password" value="1">
                        
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-lock"></i> Current Password *</label>
                            <input type="password" name="current_password" class="form-control" 
                                   required
                                   placeholder="Enter your current password">
                            <i class="form-icon fas fa-lock"></i>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-key"></i> New Password *</label>
                                <input type="password" name="new_password" class="form-control" 
                                       required
                                       placeholder="Enter new password"
                                       id="new-password">
                                <i class="form-icon fas fa-key"></i>
                                <div class="password-requirements" id="password-requirements">
                                    <div class="requirement" id="req-length">
                                        <i class="fas fa-circle"></i> At least 6 characters
                                    </div>
                                    <div class="requirement" id="req-match">
                                        <i class="fas fa-circle"></i> Passwords must match
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-key"></i> Confirm New Password *</label>
                                <input type="password" name="confirm_password" class="form-control" 
                                       required
                                       placeholder="Confirm new password"
                                       id="confirm-password">
                                <i class="form-icon fas fa-key"></i>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sync-alt"></i> Change Password
                        </button>
                    </form>
                </div>

                <!-- Account Actions -->
                <div class="profile-section" id ="account-actions">
                    <h2 class="section-title"><i class="fas fa-cog"></i> Account Actions</h2>
                    
                    <div class="account-actions">
                        <a href="orders.php" class="btn btn-outline">
                            <i class="fas fa-history"></i> Order History
                        </a>
                        <a href="index.php#categories" class="btn btn-outline">
                            <i class="fas fa-utensils"></i> Browse Menu
                        </a>
                        <a href="logout.php" class="btn btn-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        // Enhanced Profile Page Interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Form validation with real-time feedback
            const profileForm = document.getElementById('profile-form');
            const passwordForm = document.getElementById('password-form');
            const newPassword = document.getElementById('new-password');
            const confirmPassword = document.getElementById('confirm-password');
            
            // Real-time password validation
            if (newPassword && confirmPassword) {
                newPassword.addEventListener('input', validatePassword);
                confirmPassword.addEventListener('input', validatePassword);
            }
            
            function validatePassword() {
                const password = newPassword.value;
                const confirm = confirmPassword.value;
                
                // Validate length
                const lengthReq = document.getElementById('req-length');
                if (password.length >= 6) {
                    lengthReq.classList.add('met');
                    lengthReq.innerHTML = '<i class="fas fa-check-circle"></i> At least 6 characters';
                    newPassword.classList.remove('error');
                    newPassword.classList.add('success');
                } else {
                    lengthReq.classList.remove('met');
                    lengthReq.innerHTML = '<i class="fas fa-circle"></i> At least 6 characters';
                    newPassword.classList.remove('success');
                }
                
                // Validate match
                const matchReq = document.getElementById('req-match');
                if (password && confirm && password === confirm) {
                    matchReq.classList.add('met');
                    matchReq.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
                    confirmPassword.classList.remove('error');
                    confirmPassword.classList.add('success');
                } else if (confirm) {
                    matchReq.classList.remove('met');
                    matchReq.innerHTML = '<i class="fas fa-times-circle"></i> Passwords must match';
                    confirmPassword.classList.add('error');
                    confirmPassword.classList.remove('success');
                } else {
                    matchReq.classList.remove('met');
                    matchReq.innerHTML = '<i class="fas fa-circle"></i> Passwords must match';
                    confirmPassword.classList.remove('error', 'success');
                }
            }
            
            // Form submission animations
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.classList.add('loading');
                        submitBtn.disabled = true;
                        
                        // Re-enable after 3 seconds (in case of error)
                        setTimeout(() => {
                            submitBtn.classList.remove('loading');
                            submitBtn.disabled = false;
                        }, 3000);
                    }
                });
            });
            
            // Smooth scrolling for sidebar navigation
            const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href').substring(1);
                    const targetSection = document.getElementById(targetId);
                    
                    if (targetSection) {
                        // Update active state
                        sidebarLinks.forEach(l => l.classList.remove('active'));
                        this.classList.add('active');
                        
                        // Smooth scroll to section
                        targetSection.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                        
                        // Add highlight animation
                        targetSection.style.animation = 'none';
                        setTimeout(() => {
                            targetSection.style.animation = 'pulse 0.6s ease';
                        }, 10);
                    }
                });
            });
            
            // Input focus effects
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('focused');
                });
            });
            
            // Real-time form validation
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    if (this.checkValidity()) {
                        this.classList.remove('error');
                        this.classList.add('success');
                    } else {
                        this.classList.remove('success');
                        this.classList.add('error');
                    }
                });
            });
            
            // Auto-save functionality (optional)
            let saveTimeout;
            profileForm.addEventListener('input', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    // Show auto-save indicator
                    const submitBtn = profileForm.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Auto-saving...';
                    
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                    }, 1000);
                }, 2000);
            });
            
            // Initialize password validation
            validatePassword();
        });
    </script>
</body>
</html>