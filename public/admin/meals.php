<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$auth = new Auth();

// Redirect to login if not authenticated or not admin
if(!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header("Location: ../login.php");
    exit;
}

$conn = getDBConnection();

// Handle form submissions
$success_message = '';
$error_message = '';

// Add new meal
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_meal'])) {
    $meal_name = sanitizeInput($_POST['meal_name']);
    $meal_description = sanitizeInput($_POST['meal_description']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $preparation_time = intval($_POST['preparation_time']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $stock_quantity = !empty($_POST['stock_quantity']) ? intval($_POST['stock_quantity']) : NULL;
    $low_stock_threshold = intval($_POST['low_stock_threshold']);
    $allow_quantity = isset($_POST['allow_quantity']) ? 1 : 0;
    $image_url = ''; // Initialize empty

    // Basic validation
    if(empty($meal_name) || empty($price) || empty($category_id)) {
        $error_message = "Meal name, price, and category are required!";
    } else {
        // First insert the meal to get an ID
        $stmt = $conn->prepare("INSERT INTO meals (meal_name, meal_description, price, category_id, preparation_time, is_available, image_url, stock_quantity, low_stock_threshold, allow_quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdiiisiii", $meal_name, $meal_description, $price, $category_id, $preparation_time, $is_available, $image_url, $stock_quantity, $low_stock_threshold, $allow_quantity);
        
        if($stmt->execute()) {
            $meal_id = $conn->insert_id;
            
            // Handle image upload if provided
            if(isset($_FILES['meal_image']) && $_FILES['meal_image']['error'] === UPLOAD_ERR_OK) {
                try {
                    $image_url = handleImageUpload($_FILES['meal_image'], $meal_id);
                    
                    // Update the meal with the image path
                    $update_stmt = $conn->prepare("UPDATE meals SET image_url = ? WHERE meal_id = ?");
                    $update_stmt->bind_param("si", $image_url, $meal_id);
                    $update_stmt->execute();
                    
                } catch (Exception $e) {
                    $error_message = "Meal created but image upload failed: " . $e->getMessage();
                }
            }
            
            $success_message = "Meal added successfully!";
        } else {
            $error_message = "Failed to add meal: " . $conn->error;
        }
    }
}

// Get all meals with category names
$meals = $conn->query("
    SELECT m.*, c.category_name 
    FROM meals m 
    LEFT JOIN categories c ON m.category_id = c.category_id 
    ORDER BY m.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Get categories for dropdown
$categories = $conn->query("SELECT category_id, category_name FROM categories WHERE is_active = 1")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Meals - Aunt Joy's Restaurant</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #f97316;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --border-radius: 8px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
        background: linear-gradient(135deg, #667eea 0%, #766488ff 100%);
            color: var(--gray-800);
            line-height: 1.6;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            animation: fadeInDown 0.5s ease;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: bold;
        }

        .page-header h1::before {
            content: '';
            display: block;
            width: 4px;
            height: 32px;
            background: var(--primary);
            border-radius: 2px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            font-size: 0.875rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            box-shadow: var(--shadow);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-outline {
            background-color: transparent;
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }

        .btn-outline:hover {
            background-color: var(--gray-50);
            border-color: var(--gray-400);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-danger {
            background-color: transparent;
            color: var(--danger);
            border: 1px solid var(--danger);
        }

        .btn-danger:hover {
            background-color: var(--danger);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
        }

        .admin-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            padding: 2rem;
            animation: fadeInUp 0.5s ease 0.1s both;
            margin-bottom: 2rem;
            transition: var(--transition);
        }

        .admin-card:hover {
            box-shadow: var(--shadow-lg);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-title::before {
            content: '';
            display: block;
            width: 3px;
            height: 20px;
            background: var(--secondary);
            border-radius: 2px;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            animation: slideInRight 0.5s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background-color: #ecfdf5;
            color: #065f46;
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background-color: #fef2f2;
            color: #991b1b;
            border-left: 4px solid var(--danger);
        }

        .alert::before {
            font-size: 1.25rem;
        }

        .alert-success::before {
            content: '✓';
        }

        .alert-error::before {
            content: '⚠';
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.875rem;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            transition: var(--transition);
            background-color: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }

        .inventory-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-200);
            animation: fadeIn 0.5s ease 0.2s both;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title::before {
            content: '';
            display: block;
            width: 3px;
            height: 20px;
            background: var(--secondary);
            border-radius: 2px;
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
        }

        .meals-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .meals-table th,
        .meals-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .meals-table th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .meals-table tr {
            transition: var(--transition);
        }

        .meals-table tr:hover {
            background: var(--gray-50);
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }

        .status-available {
            color: var(--success);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-available::before {
            content: '';
            display: block;
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
        }

        .status-unavailable {
            color: var(--danger);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-unavailable::before {
            content: '';
            display: block;
            width: 8px;
            height: 8px;
            background: var(--danger);
            border-radius: 50%;
        }

        .meal-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .meal-image:hover {
            transform: scale(1.1);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--gray-500);
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        /* Header Styles */
        .header {
             background: linear-gradient(135deg, #667eea 0%, #ab9bbbff 100%);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 700;
            font-size: 1.25rem;
            color: white;
            font-weight: bold;
        }

        .logo img {
            height: 70px;
            border-radius: 60px;
            margin-left: 20px;
            width: auto;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 1.5rem;
        }

        .nav-links a {
            text-decoration: none;
            color: white;
            font-weight: 500;
            transition: var(--transition);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-weight: bold;
        }

        .nav-links a:hover {
            background: var(--gray-100);
            color: var(--primary);
        }

        .admin-nav {
             background: linear-gradient(135deg, #667eea 0%, #ab9bbbff 100%);
        }

        .admin-nav-links {
            display: flex;
            list-style: none;
            gap: 0;
        }

        .admin-nav-links a {
            display: block;
            padding: 1rem 1.5rem;
            color: var(--gray-300);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            border-bottom: 3px solid transparent;
            font-weight: bold;
            
        }

        .admin-nav-links a:hover,
        .admin-nav-links a.active {
            color: white;
           box-shadow: var(--shadow-xl);
            border-bottom-color : var(--primary);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .admin-container {
                padding: 1rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .meals-table {
                font-size: 0.875rem;
            }
            
            .meals-table th,
            .meals-table td {
                padding: 0.75rem 0.5rem;
            }
            
            .nav-links {
                gap: 0.5rem;
            }
            
            .admin-nav-links {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <img src="../../assets/images/kitchen_logo1.png" alt="Aunt Joy's Restaurant Logo" /> 
                    Aunt Joy's - Admin
                </div>
                <ul class="nav-links">
                    <li><a href="../index.php">🏠 View Site</a></li>
                    <li><a href="../logout.php">🚪 Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Admin Navigation -->
    <nav class="admin-nav">
        <div class="container">
            <ul class="admin-nav-links">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="meals.php" class="active">🍽️ Meals</a></li>
                <li><a href="categories.php">📋 Categories</a></li>
                <li><a href="users.php">👥 Users</a></li>
            </ul>
        </div>
    </nav>

    <div class="admin-container">
        <div class="page-header">
            <h1>Manage Meals</h1>
            <div class="stats-badge">
                <span class="badge"><?php echo count($meals); ?> Meals</span>
            </div>
        </div>

        <?php if($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Add Meal Form -->
        <div class="admin-card">
            <h2 class="card-title">Add New Meal</h2>
           <!-- Change the form tag to include enctype -->
            <form method="POST" action="" id="add-meal-form" enctype="multipart/form-data"> 
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Meal Name</label>
                        <input type="text" name="meal_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Price (MKW)</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Preparation Time (minutes)</label>
                        <input type="number" name="preparation_time" class="form-control" value="20" min="1">
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Description</label>
                        <textarea name="meal_description" class="form-control" rows="3" placeholder="Enter meal description"></textarea>
                    </div>

                 
                    <div class="form-group full-width">
                    <label class="form-label">Meal Image</label>
                    <input type="file" name="meal_image" class="form-control" accept="image/*">
                    <small class="form-text" style="color: var(--gray-500);">
                    Supported formats: JPG, PNG, GIF, WebP (Max: 5MB)
                 </small>
                </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="is_available" id="is_available" checked>
                            <label class="form-label" for="is_available">Available for ordering</label>
                        </div>
                    </div>
                </div>

                <!-- Inventory Management Section -->
                <div class="inventory-section">
                    <h3 class="section-title">Inventory Management</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" name="stock_quantity" class="form-control" min="0" placeholder="Leave empty for unlimited">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Low Stock Threshold</label>
                            <input type="number" name="low_stock_threshold" class="form-control" value="10" min="1">
                        </div>

                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" name="allow_quantity" id="allow_quantity">
                                <label class="form-label" for="allow_quantity">Allow customers to select quantity</label>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" name="add_meal" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Add Meal
                </button>
            </form>
        </div>

        <!-- Meals List -->
        <div class="admin-card">
            <h2 class="card-title">All Meals</h2>
            <div class="table-responsive">
                <?php if(empty($meals)): ?>
                    <div class="empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                        <h3>No meals found</h3>
                        <p>Get started by adding your first meal using the form above.</p>
                    </div>
                <?php else: ?>
                    <table class="meals-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Meal Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Prep Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($meals as $index => $meal): ?>
                                <tr style="animation-delay: <?php echo $index * 0.1; ?>s">
                                    <!-- In the meals table - Update the image display -->
                            <td>
                                <?php if(!empty($meal['image_url'])): ?>
                                    <img src="../../<?php echo htmlspecialchars($meal['image_url']); ?>" 
                                        alt="<?php echo htmlspecialchars($meal['meal_name']); ?>" 
                                        class="meal-image"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div style="width: 60px; height: 60px; background: var(--gray-100); border-radius: var(--border-radius); display: none; align-items: center; justify-content: center; color: var(--gray-400);">
                                        🍽️
                                    </div>
                                <?php else: ?>
                                    <div style="width: 60px; height: 60px; background: var(--gray-100); border-radius: var(--border-radius); display: flex; align-items: center; justify-content: center; color: var(--gray-400);">
                                        🍽️
                                    </div>
                                <?php endif; ?>
                            </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($meal['meal_name']); ?></strong>
                                        <?php if(!empty($meal['meal_description'])): ?>
                                            <br><small style="color: var(--gray-500);"><?php echo htmlspecialchars(substr($meal['meal_description'], 0, 50)); ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($meal['category_name']); ?></td>
                                    <td><strong>$<?php echo number_format($meal['price'], 2); ?></strong></td>
                                    <td><?php echo $meal['preparation_time']; ?> min</td>
                                    <td>
                                        <?php if($meal['is_available']): ?>
                                            <span class="status-available">Available</span>
                                        <?php else: ?>
                                            <span class="status-unavailable">Unavailable</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_meal.php?id=<?php echo $meal['meal_id']; ?>" class="btn btn-outline btn-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                                Edit
                                            </a>
                                            <button onclick="deleteMeal(<?php echo $meal['meal_id']; ?>)" class="btn btn-danger btn-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Enhanced delete function with better UX
        function deleteMeal(mealId) {
            if(confirm('Are you sure you want to delete this meal? This action cannot be undone.')) {
                // Show loading state
                const button = event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"></path></svg> Deleting...';
                button.disabled = true;
                
                fetch('../../api/admin/meals/delete.php?id=' + mealId, {
                    method: 'DELETE',
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        // Show success message with animation
                        showNotification(data.message, 'success');
                        
                        // Remove row with animation
                        const row = button.closest('tr');
                        row.style.transition = 'all 0.3s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(-100%)';
                        
                        setTimeout(() => {
                            row.remove();
                            // Update meal count
                            const badge = document.querySelector('.stats-badge .badge');
                            const currentCount = parseInt(badge.textContent);
                            badge.textContent = (currentCount - 1) + ' Meals';
                        }, 300);
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred while deleting the meal.', 'error');
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
            }
        }

        // Notification system
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.innerHTML = message;
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '1000';
            notification.style.maxWidth = '400px';
            notification.style.animation = 'slideInRight 0.5s ease';
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideInRight 0.5s ease reverse';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 500);
            }, 3000);
        }

        // Form validation and enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('add-meal-form');
            const inputs = form.querySelectorAll('input, textarea, select');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    if (this.value === '') {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });

            // Add smooth scrolling to form errors
            if (document.querySelector('.alert-error')) {
                document.querySelector('.admin-card').scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    </script>
</body>
</html>