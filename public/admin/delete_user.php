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

if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid user ID";
    header("Location: users.php");
    exit;
}

$user_id = intval($_GET['id']);
$conn = getDBConnection();

try {
    // Prevent deleting your own admin account
    if($user_id == $_SESSION['user_id']) {
        $_SESSION['error_message'] = "You cannot delete your own account!";
        header("Location: users.php");
        exit;
    }

    // Check if this is the last admin
    $adminCheck = $conn->query("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin' AND user_id != $user_id");
    $adminCount = $adminCheck->fetch_assoc()['admin_count'];
    
    $userCheck = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $userCheck->bind_param("i", $user_id);
    $userCheck->execute();
    $userResult = $userCheck->get_result();
    
    if($userResult->num_rows === 0) {
        $_SESSION['error_message'] = "User not found";
        header("Location: users.php");
        exit;
    }
    
    $user = $userResult->fetch_assoc();
    
    // Prevent deleting the last admin
    if($user['role'] === 'admin' && $adminCount == 0) {
        $_SESSION['error_message'] = "Cannot delete the last administrator account!";
        header("Location: users.php");
        exit;
    }

    // Check if user has orders before deleting
    $orderCheck = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?");
    $orderCheck->bind_param("i", $user_id);
    $orderCheck->execute();
    $orderResult = $orderCheck->get_result()->fetch_assoc();

    if($orderResult['order_count'] > 0) {
        // User has orders, we can't delete but can deactivate
        $updateStmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE user_id = ?");
        $updateStmt->bind_param("i", $user_id);
        
        if($updateStmt->execute()) {
            $_SESSION['success_message'] = "User has orders and cannot be deleted. Account has been deactivated instead.";
        } else {
            $_SESSION['error_message'] = "Failed to deactivate user account";
        }
    } else {
        // Safe to delete user
        $deleteStmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $deleteStmt->bind_param("i", $user_id);
        
        if($deleteStmt->execute()) {
            $_SESSION['success_message'] = "User deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to delete user: " . $conn->error;
        }
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error deleting user: " . $e->getMessage();
} finally {
    closeDBConnection($conn);
}

header("Location: users.php");
exit;
?>
<script>
    alert('API Error: ' + (data.message || 'Unknown error'));
</script>