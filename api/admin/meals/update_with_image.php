// api/admin/meals/update_with_image.php
<?php
require_once '../../../includes/config.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/middleware.php';

setCORSHeaders();
handlePreflight();
header('Content-Type: application/json');

// Only admins can update meals
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

// Get meal ID
$meal_id = $_POST['meal_id'] ?? null;
if (!$meal_id || !is_numeric($meal_id)) {
    jsonResponse(false, 'Valid meal ID is required', [], 400);
}

$conn = getDBConnection();
if (!$conn) {
    jsonResponse(false, 'Database connection failed', [], 500);
}

try {
    // Get current meal data
    $current_stmt = $conn->prepare("SELECT image_url FROM meals WHERE meal_id = ?");
    $current_stmt->bind_param("i", $meal_id);
    $current_stmt->execute();
    $current_meal = $current_stmt->get_result()->fetch_assoc();
    
    if (!$current_meal) {
        jsonResponse(false, 'Meal not found', [], 404);
    }

    // Prepare update data
    $updatableFields = [
        'meal_name', 'meal_description', 'price', 'category_id', 
        'is_available', 'preparation_time'
    ];
    
    $updates = [];
    $params = [];
    $types = '';
    
    foreach ($updatableFields as $field) {
        if (isset($_POST[$field])) {
            $updates[] = "$field = ?";
            
            if ($field === 'price') {
                $params[] = floatval($_POST[$field]);
                $types .= 'd';
            } elseif ($field === 'category_id' || $field === 'preparation_time') {
                $params[] = intval($_POST[$field]);
                $types .= 'i';
            } elseif ($field === 'is_available') {
                $params[] = boolval($_POST[$field]);
                $types .= 'i';
            } else {
                $params[] = sanitizeInput($_POST[$field]);
                $types .= 's';
            }
        }
    }
    
    // Handle image updates
    $current_image = $current_meal['image_url'];
    $remove_image = isset($_POST['remove_image']) ? intval($_POST['remove_image']) : 0;
    
    if ($remove_image === 1 && !empty($current_image)) {
        // Remove current image
        deleteImageFile($current_image);
        $updates[] = "image_url = ?";
        $params[] = '';
        $types .= 's';
    } elseif (isset($_FILES['meal_image']) && $_FILES['meal_image']['error'] === UPLOAD_ERR_OK) {
        // Upload new image
        try {
            // Delete old image if exists
            if (!empty($current_image)) {
                deleteImageFile($current_image);
            }
            
            $new_image_url = handleImageUpload($_FILES['meal_image'], $meal_id);
            $updates[] = "image_url = ?";
            $params[] = $new_image_url;
            $types .= 's';
            
        } catch (Exception $e) {
            jsonResponse(false, 'Meal updated but image upload failed: ' . $e->getMessage(), [], 500);
        }
    }
    
    if (empty($updates)) {
        jsonResponse(false, 'No fields to update', [], 400);
    }
    
    $params[] = $meal_id;
    $types .= 'i';
    
    $query = "UPDATE meals SET " . implode(', ', $updates) . " WHERE meal_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            jsonResponse(true, 'Meal updated successfully');
        } else {
            jsonResponse(false, 'No changes made', [], 200);
        }
    } else {
        jsonResponse(false, 'Failed to update meal: ' . $conn->error, [], 500);
    }
    
} catch (Exception $e) {
    jsonResponse(false, 'Error updating meal: ' . $e->getMessage(), [], 500);
} finally {
    closeDBConnection($conn);
}
?>