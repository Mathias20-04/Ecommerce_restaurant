<?php
// profile.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in - using session directly
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$currentUser = [
    'user_id' => $_SESSION['user_id'],
    'email' => $_SESSION['user_email'] ?? '',
    'name' => $_SESSION['user_name'] ?? 'User',
    'full_name' => $_SESSION['user_name'] ?? 'User',
    'username' => $_SESSION['user_email'] ?? 'user'
];

$conn = getDBConnection();

// Get user details from database
$userStmt = $conn->prepare("SELECT name, email, phone, address, created_at FROM users WHERE user_id = ?");
$userStmt->bind_param("i", $_SESSION['user_id']);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userDetails = $userResult->fetch_assoc();

if (!$userDetails) {
    $userDetails = [
        'name' => $currentUser['name'],
        'email' => $currentUser['email'],
        'phone' => '',
        'address' => '',
        'created_at' => date('Y-m-d H:i:s')
    ];
}

$success_message = '';
$error_message = '';

// Process profile update
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    
    // Basic validation
    if(empty($full_name) || empty($email)) {
        $error_message = "Full name and email are required!";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format!";
    } else {
        // Check if email already exists (excluding current user)
        $emailCheck = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $emailCheck->bind_param("si", $email, $_SESSION['user_id']);
        $emailCheck->execute();
        
        if($emailCheck->get_result()->num_rows > 0) {
            $error_message = "Email already exists!";
        } else {
            // Update user profile
            $updateStmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
            $updateStmt->bind_param("ssssi", $full_name, $email, $phone, $address, $_SESSION['user_id']);
            
            if($updateStmt->execute()) {
                $success_message = "Profile updated successfully!";
                // Update session
                $_SESSION['user_name'] = $full_name;
                $_SESSION['user_email'] = $email;
                // Refresh user details
                $userDetails['name'] = $full_name;
                $userDetails['email'] = $email;
                $userDetails['phone'] = $phone;
                $userDetails['address'] = $address;
            } else {
                $error_message = "Failed to update profile: " . $conn->error;
            }
        }
    }
}

// Process password change
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if(empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All password fields are required!";
    } elseif($new_password !== $confirm_password) {
        $error_message = "New passwords do not match!";
    } elseif(strlen($new_password) < 6) {
        $error_message = "New password must be at least 6 characters long!";
    } else {
        // Verify current password
        $passwordCheck = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $passwordCheck->bind_param("i", $_SESSION['user_id']);
        $passwordCheck->execute();
        $result = $passwordCheck->get_result();
        
        if($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if(password_verify($current_password, $user['password'])) {
                // Update password
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $updatePassword = $conn->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
                $updatePassword->bind_param("si", $new_password_hash, $_SESSION['user_id']);
                
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