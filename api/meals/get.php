<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

setCORSHeaders();
handlePreflight();
header('Content-Type: application/json');

$meal_id = $_GET['id'] ?? null;
if (!$meal_id || !is_numeric($meal_id)) {
    jsonResponse(false, 'Valid meal ID is required', [], 400);
}

$conn = getDBConnection();
if (!$conn) {
    jsonResponse(false, 'Database connection failed', [], 500);
}

try {
    $stmt = $conn->prepare("SELECT m.meal_id, m.meal_name, m.meal_description, m.price, 
                                   m.image_url, m.is_available, m.preparation_time,
                                   c.category_id, c.category_name 
                            FROM meals m 
                            LEFT JOIN categories c ON m.category_id = c.category_id 
                            WHERE m.meal_id = ?");
    $stmt->bind_param("i", $meal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $meal = $result->fetch_assoc();
    
    if ($meal) {
        jsonResponse(true, 'Meal retrieved successfully', ['meal' => $meal]);
    } else {
        jsonResponse(false, 'Meal not found', [], 404);
    }
    
} catch (Exception $e) {
    jsonResponse(false, 'Failed to retrieve meal: ' . $e->getMessage(), [], 500);
} finally {
    closeDBConnection($conn);
}
?>