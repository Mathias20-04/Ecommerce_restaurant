<?php
// api/cart/get.php - UPDATED
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cart_cache.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
header('Access-Control-Allow-Credentials: true');

// Debug: Check if session is working
error_log("=== CART GET DEBUG ===");
error_log("Session ID: " . session_id());
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));

// Simple session check - no middleware
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    error_log("ERROR: No user_id in session");
    
    echo json_encode([
        'success' => false,
        'message' => 'Please login to view cart',
        'session_debug' => [
            'session_id' => session_id(),
            'has_user_id' => isset($_SESSION['user_id']),
            'user_id_value' => $_SESSION['user_id'] ?? null
        ]
    ]);
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Check cache first
$cached_cart = getCachedCart($user_id);
if ($cached_cart !== null) {
    echo json_encode($cached_cart, JSON_PRETTY_PRINT);
    exit();
}

// Not in cache or expired, query database
$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed'], JSON_PRETTY_PRINT);
    exit();
}

try {
    // Your existing query...
    $query = "SELECT c.cart_id, c.meal_id, c.quantity, c.created_at,
                     m.meal_name, m.meal_description, m.price, m.image_url, m.is_available
              FROM cart c
              JOIN meals m ON c.meal_id = m.meal_id
              WHERE c.user_id = ? AND m.is_available = 1
              ORDER BY c.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cart_items = [];
    $total_price = 0;
    $total_items = 0;
    
    while ($row = $result->fetch_assoc()) {
        $row['total_price'] = $row['price'] * $row['quantity'];
        $total_price += $row['total_price'];
        $total_items += $row['quantity'];
        $cart_items[] = $row;
    }
    
    $cart_data = [
        'success' => true,
        'message' => 'Cart loaded',
        'data' => [
            'cart' => $cart_items,
            'count' => count($cart_items),
            'total_items' => $total_items,
            'total_price' => $total_price
        ]
    ];
    
    // Cache the result
    setCachedCart($user_id, $cart_data);
    
    echo json_encode($cart_data, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Cart get error: " . $e->getMessage());
    
    $response = [
        'success' => false,
        'message' => 'Failed to load cart',
        'error' => $e->getMessage()
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
} finally {
    closeDBConnection($conn);
}
?>