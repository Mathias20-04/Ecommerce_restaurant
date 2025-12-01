<?php
require_once '../../../includes/config.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/middleware.php';

setCORSHeaders();
handlePreflight();
header('Content-Type: application/json');

// Only admins can delete meals
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

// Get meal ID from URL or input
$meal_id = $_GET['id'] ?? null;
if (!$meal_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $meal_id = $input['meal_id'] ?? null;
}

if (!$meal_id || !is_numeric($meal_id)) {
    jsonResponse(false, 'Valid meal ID is required', [], 400);
}

// Initialize DB connection before using it
$conn = getDBConnection();
if (!$conn) {
    jsonResponse(false, 'Database connection failed', [], 500);
}

// Get the image path before deletion (if any)
$image_stmt = $conn->prepare("SELECT image_url FROM meals WHERE meal_id = ?");
$image_stmt->bind_param("i", $meal_id);
$image_stmt->execute();
$image_result = $image_stmt->get_result()->fetch_assoc();

if ($image_result && !empty($image_result['image_url'])) {
    // Attempt to delete image file, but don't stop deletion if unlink fails
    try {
        deleteImageFile($image_result['image_url']);
    } catch (Exception $e) {
        // Log and continue; we still want to handle the DB deletion/update
        error_log('Failed to delete meal image: ' . $e->getMessage());
    }
}

try {
    // Check if meal exists in any orders before deleting
    $check_stmt = $conn->prepare("SELECT COUNT(*) as order_count FROM order_items WHERE meal_id = ?");
    $check_stmt->bind_param("i", $meal_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();
    
    if ($result['order_count'] > 0) {
        // Instead of deleting, mark as unavailable
        $update_stmt = $conn->prepare("UPDATE meals SET is_available = 0 WHERE meal_id = ?");
        $update_stmt->bind_param("i", $meal_id);
        
        if ($update_stmt->execute()) {
            jsonResponse(true, 'Meal cannot be deleted as it appears in orders. Marked as unavailable instead.');
        } else {
            jsonResponse(false, 'Failed to update meal availability', [], 500);
        }
    } else {
        // Safe to delete
        $delete_stmt = $conn->prepare("DELETE FROM meals WHERE meal_id = ?");
        $delete_stmt->bind_param("i", $meal_id);
        
        if ($delete_stmt->execute()) {
            if ($delete_stmt->affected_rows > 0) {
                jsonResponse(true, 'Meal deleted successfully');
            } else {
                jsonResponse(false, 'Meal not found', [], 404);
            }
        } else {
            jsonResponse(false, 'Failed to delete meal: ' . $conn->error, [], 500);
        }
    }
    
} catch (Exception $e) {
    jsonResponse(false, 'Error deleting meal: ' . $e->getMessage(), [], 500);
} finally {
    closeDBConnection($conn);
}
?>