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
    $_SESSION['error_message'] = "Invalid category ID";
    header("Location: categories.php");
    exit;
}

$category_id = intval($_GET['id']);
$conn = getDBConnection();

try {
    // Check if category has meals
    $mealCheck = $conn->prepare("SELECT COUNT(*) as meal_count FROM meals WHERE category_id = ?");
    $mealCheck->bind_param("i", $category_id);
    $mealCheck->execute();
    $mealResult = $mealCheck->get_result()->fetch_assoc();

    if($mealResult['meal_count'] > 0) {
        // Category has meals, we can't delete but can deactivate
        $updateStmt = $conn->prepare("UPDATE categories SET is_active = 0 WHERE category_id = ?");
        $updateStmt->bind_param("i", $category_id);
        
        if($updateStmt->execute()) {
            $_SESSION['success_message'] = "Category has meals and cannot be deleted. Category has been deactivated instead.";
        } else {
            $_SESSION['error_message'] = "Failed to deactivate category";
        }
    } else {
        // Safe to delete category
        $deleteStmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
        $deleteStmt->bind_param("i", $category_id);
        
        if($deleteStmt->execute()) {
            $_SESSION['success_message'] = "Category deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to delete category: " . $conn->error;
        }
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error deleting category: " . $e->getMessage();
} finally {
    closeDBConnection($conn);
}

header("Location: categories.php");
exit;
?>