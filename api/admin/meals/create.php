<?php
require_once '../../../includes/config.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/middleware.php';

setCORSHeaders();
handlePreflight();
header('Content-Type: application/json');

// Only admins can create meals
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonResponse(false, 'Invalid JSON input', [], 400);
}

// Validate required fields
$required = ['meal_name', 'price', 'category_id'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        jsonResponse(false, "Missing required field: $field", [], 400);
    }
}

$conn = getDBConnection();
if (!$conn) {
    jsonResponse(false, 'Database connection failed', [], 500);
}

try {
    $stmt = $conn->prepare("INSERT INTO meals (meal_name, meal_description, price, image_url, category_id, is_available, preparation_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $meal_name = sanitizeInput($input['meal_name']);
    $meal_description = sanitizeInput($input['meal_description'] ?? '');
    $price = floatval($input['price']);
    $image_url = sanitizeInput($input['image_url'] ?? '');
    $category_id = intval($input['category_id']);
    $is_available = boolval($input['is_available'] ?? true);
    $preparation_time = intval($input['preparation_time'] ?? 20);
    
    $stmt->bind_param("ssdsisi", $meal_name, $meal_description, $price, $image_url, $category_id, $is_available, $preparation_time);
    
    if ($stmt->execute()) {
        $meal_id = $conn->insert_id;
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