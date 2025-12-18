<?php
// test_db_fixed.php
echo "<h2>🧪 Aunt Joy's Restaurant - Database Connection Test</h2>";
echo "<div style='font-family: Arial, sans-serif; padding: 20px;'>";

// Include all required files
require_once 'includes/config.php';
require_once 'includes/functions.php'; // ADD THIS LINE

// Test if functions are loaded
if (!function_exists('getDBConnection')) {
    die("<div style='color: red; font-weight: bold;'>❌ CRITICAL: getDBConnection function not found!</div>");
}

$conn = getDBConnection();

if ($conn) {
    echo "<div style='color: green; font-weight: bold; font-size: 18px;'>✅ SUCCESS: Connected to database successfully!</div>";
    
    // Test if tables exist
    $tables = ['users', 'categories', 'meals', 'orders', 'order_items', 'order_status'];
    $all_tables_exist = true;
    
    foreach($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if($result->num_rows > 0) {
            echo "<div style='color: green; margin-left: 20px;'>✅ Table '$table' exists</div>";
            
            // Count records
            $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
            $count = $count_result->fetch_assoc();
            echo "<div style='margin-left: 40px; color: #666;'>Records: " . $count['count'] . "</div>";
            
        } else {
            echo "<div style='color: red; margin-left: 20px;'>❌ Table '$table' is MISSING</div>";
            $all_tables_exist = false;
        }
    }
    
    if($all_tables_exist) {
        echo "<div style='background: #d4edda; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
        echo "🎉 <strong>ALL SYSTEMS GO!</strong> Database is properly configured!";
        echo "</div>";
        
        // Show quick stats
        echo "<h3>📊 Quick Stats:</h3>";
        
        // Count users by role
        $user_stats = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
        echo "<div>User Roles:</div>";
        while($row = $user_stats->fetch_assoc()) {
            echo "<div style='margin-left: 20px;'>👤 {$row['role']}: {$row['count']} users</div>";
        }
        
        // Count categories and meals
        $category_count = $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc();
        $meal_count = $conn->query("SELECT COUNT(*) as count FROM meals")->fetch_assoc();
        
        echo "<div style='margin-top: 10px;'>📁 Categories: {$category_count['count']}</div>";
        echo "<div>🍽️ Meals: {$meal_count['count']}</div>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
        echo "⚠️ <strong>Some tables are missing.</strong> You need to create the missing tables.";
        echo "</div>";
    }
    
    closeDBConnection($conn);
    
} else {
    echo "<div style='color: red; font-weight: bold;'>❌ FAILED: Cannot connect to database</div>";
    echo "<div style='background: #f8d7da; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
    echo "Please check your database credentials in includes/config.php";
    echo "</div>";
}

echo "</div>";
?>