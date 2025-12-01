// api/admin/meals/create_with_image.php
<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/middleware.php';

setCORSHeaders();
handlePreflight();
header('Content-Type: application/json');

// Only admins can create meals
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

$conn = getDBConnection();
if (!$conn) {
    jsonResponse(false, 'Database connection failed', [], 500);
}

try {
    // Get input data
    $input = $_POST;
    
    // Validate required fields
    $required = ['meal_name', 'price', 'category_id'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            jsonResponse(false, "Missing required field: $field", [], 400);
        }
    }

    // Prepare meal data
    $meal_name = sanitizeInput($input['meal_name']);
    $meal_description = sanitizeInput($input['meal_description'] ?? '');
    $price = floatval($input['price']);
    $category_id = intval($input['category_id']);
    $is_available = isset($input['is_available']) ? 1 : 0;
    $preparation_time = intval($input['preparation_time'] ?? 20);
    $image_url = '';

    // Insert meal first
    $stmt = $conn->prepare("INSERT INTO meals (meal_name, meal_description, price, image_url, category_id, is_available, preparation_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdsisi", $meal_name, $meal_description, $price, $image_url, $category_id, $is_available, $preparation_time);
    
    if ($stmt->execute()) {
        $meal_id = $conn->insert_id;
        
        // Handle image upload if provided
        if(isset($_FILES['meal_image']) && $_FILES['meal_image']['error'] === UPLOAD_ERR_OK) {
            try {
                $image_url = handleImageUpload($_FILES['meal_image'], $meal_id);
                
                // Update meal with image path
                $update_stmt = $conn->prepare("UPDATE meals SET image_url = ? WHERE meal_id = ?");
                $update_stmt->bind_param("si", $image_url, $meal_id);
                $update_stmt->execute();
                
            } catch (Exception $e) {
                // Meal was created but image failed - we can still return success
                error_log('Image upload failed for meal ' . $meal_id . ': ' . $e->getMessage());
            }
        }
        
        jsonResponse(true, 'Meal created successfully', ['meal_id' => $meal_id], 201);
    } else {
        jsonResponse(false, 'Failed to create meal: ' . $conn->error, [], 500);
    }
    
} catch (Exception $e) {
    jsonResponse(false, 'Error creating meal: ' . $e->getMessage(), [], 500);
} finally {
    closeDBConnection($conn);
}
?>