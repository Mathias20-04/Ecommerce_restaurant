<?php
// api/setup/sample_data.php - Run once to add sample data
require_once '../../includes/config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();
    
    // Sample Categories
    $categories = [
        ['Main Course', 'Hearty and satisfying main dishes'],
        ['Appetizers', 'Perfect starters for your meal'],
        ['Desserts', 'Sweet treats to complete your dining experience'],
        ['Drinks', 'Refreshing beverages and drinks'],
        ['Local Specialties', 'Authentic Mzuzu cuisine']
    ];
    
    foreach($categories as $category) {
        $stmt = $conn->prepare("INSERT IGNORE INTO categories (category_name, category_description) VALUES (?, ?)");
        $stmt->bind_param("ss", $category[0], $category[1]);
        $stmt->execute();
    }
    
    // Sample Meals
    $meals = [
        // Main Course
        ['Chicken Curry', 'Tender chicken in rich curry sauce with rice', 25.00, 1],
        ['Beef Stew', 'Slow-cooked beef with vegetables and nsima', 22.00, 1],
        ['Grilled Fish', 'Fresh fish grilled with local spices', 28.00, 1],
        ['Vegetable Stir Fry', 'Fresh vegetables stir-fried with tofu', 18.00, 1],
        
        // Appetizers
        ['Samosa', 'Crispy pastry filled with spiced potatoes', 8.00, 2],
        ['Spring Rolls', 'Vegetable spring rolls with sweet chili sauce', 10.00, 2],
        ['Garlic Bread', 'Fresh bread with garlic butter', 6.00, 2],
        
        // Desserts
        ['Chocolate Cake', 'Rich chocolate cake with frosting', 12.00, 3],
        ['Fruit Salad', 'Fresh seasonal fruits', 8.00, 3],
        ['Ice Cream', 'Vanilla ice cream with chocolate sauce', 10.00, 3],
        
        // Drinks
        ['Fresh Juice', 'Orange, mango or pineapple juice', 7.00, 4],
        ['Soda', 'Coke, Fanta or Sprite', 5.00, 4],
        ['Bottled Water', '500ml bottled water', 3.00, 4],
        
        // Local Specialties
        ['Chambo Fish', 'Lake Malawi fish with rice', 30.00, 5],
        ['Nsima with Relish', 'Traditional maize porridge with vegetable relish', 15.00, 5]
    ];
    
    foreach($meals as $meal) {
        $stmt = $conn->prepare("INSERT IGNORE INTO meals (meal_name, meal_description, price, category_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssdi", $meal[0], $meal[1], $meal[2], $meal[3]);
        $stmt->execute();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Sample data added successfully!',
        'categories_added' => count($categories),
        'meals_added' => count($meals)
    ]);
    
    closeDBConnection($conn);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add sample data',
        'error' => $e->getMessage()
    ]);
}
?>