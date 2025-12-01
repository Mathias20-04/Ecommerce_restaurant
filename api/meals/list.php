<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

setCORSHeaders();
handlePreflight();
header('Content-Type: application/json');

$conn = getDBConnection();
if (!$conn) {
    jsonResponse(false, 'Database connection failed', [], 500);
}

try {
    // Get optional filters
    $category_id = $_GET['category_id'] ?? null;
    $search = $_GET['search'] ?? '';
    
    $query = "SELECT m.meal_id, m.meal_name, m.meal_description, m.price, 
                     m.image_url, m.is_available, m.preparation_time,
                     m.category_id,  -- ADD THIS LINE
                     c.category_name 
              FROM meals m 
              LEFT JOIN categories c ON m.category_id = c.category_id 
              WHERE m.is_available = 1";
    
    $params = [];
    $types = '';
    
    if ($category_id && is_numeric($category_id)) {
        $query .= " AND m.category_id = ?";
        $params[] = $category_id;
        $types .= 'i';
    }
    
    if (!empty($search)) {
        $query .= " AND (m.meal_name LIKE ? OR m.meal_description LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'ss';
    }
    
    $query .= " ORDER BY m.meal_name";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $meals = $result->fetch_all(MYSQLI_ASSOC);
    
    jsonResponse(true, 'Meals retrieved successfully', ['meals' => $meals]);
    
} catch (Exception $e) {
    jsonResponse(false, 'Failed to retrieve meals: ' . $e->getMessage(), [], 500);
} finally {
    closeDBConnection($conn);
}
?>