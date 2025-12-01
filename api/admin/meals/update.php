<?php
require_once '../../../includes/config.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/middleware.php';

setCORSHeaders();
handlePreflight();
header('Content-Type: application/json');

// Only admins can update meals
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
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

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonResponse(false, 'Invalid JSON input', [], 400);
}

$conn = getDBConnection();
if (!$conn) {
    jsonResponse(false, 'Database connection failed', [], 500);
}

try {
    // Build dynamic update query based on provided fields
    $updatableFields = [
        'meal_name', 'meal_description', 'price', 'image_url', 
        'category_id', 'is_available', 'preparation_time'
    ];
    
    $updates = [];
    $params = [];
    $types = '';
    
    foreach ($updatableFields as $field) {
        if (isset($input[$field])) {
            $updates[] = "$field = ?";
            
            if ($field === 'price') {
                $params[] = floatval($input[$field]);
                $types .= 'd';
            } elseif ($field === 'category_id' || $field === 'preparation_time') {
                $params[] = intval($input[$field]);
                $types .= 'i';
            } elseif ($field === 'is_available') {
                $params[] = boolval($input[$field]);
                $types .= 'i';
            } else {
                $params[] = sanitizeInput($input[$field]);
                $types .= 's';
            }
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
            jsonResponse(false, 'No changes made or meal not found', [], 404);
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