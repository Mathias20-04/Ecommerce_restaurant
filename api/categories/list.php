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
    $query = "SELECT category_id, category_name, category_description, is_active 
              FROM categories 
              WHERE is_active = 1
              ORDER BY category_name";
    
    $result = $conn->query($query);
    $categories = $result->fetch_all(MYSQLI_ASSOC);
    
    jsonResponse(true, 'Categories retrieved successfully', ['categories' => $categories]);
    
} catch (Exception $e) {
    jsonResponse(false, 'Failed to retrieve categories: ' . $e->getMessage(), [], 500);
} finally {
    closeDBConnection($conn);
}
?>