 <?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$auth = new Auth();
if(!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header("Location: ../login.php");
    exit;
}

if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: meals.php");
    exit;
}

$meal_id = intval($_GET['id']);
$conn = getDBConnection();

// Get meal details
$meal_stmt = $conn->prepare("SELECT * FROM meals WHERE meal_id = ?");
$meal_stmt->bind_param("i", $meal_id);
$meal_stmt->execute();
$meal = $meal_stmt->get_result()->fetch_assoc();

if(!$meal) {
    header("Location: meals.php");
    exit;
}

// Get categories
$categories = $conn->query("SELECT category_id, category_name FROM categories WHERE is_active = 1")->fetch_all(MYSQLI_ASSOC);

$success_message = '';
$error_message = '';



// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_meal'])) {
    $meal_name = sanitizeInput($_POST['meal_name']);
    $meal_description = sanitizeInput($_POST['meal_description']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $preparation_time = intval($_POST['preparation_time']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $stock_quantity = !empty($_POST['stock_quantity']) ? intval($_POST['stock_quantity']) : NULL;
    $low_stock_threshold = intval($_POST['low_stock_threshold']);
    $allow_quantity = isset($_POST['allow_quantity']) ? 1 : 0;
    
    // Start with current image URL
    $image_url = $_POST['current_image'] ?? '';

    if(empty($meal_name) || empty($price) || empty($category_id)) {
        $error_message = "Meal name, price, and category are required!";
    } else {
        // Handle image removal
        $remove_image = isset($_POST['remove_image']) ? intval($_POST['remove_image']) : 0;
        if ($remove_image === 1) {
            // Delete the current image file
            if (!empty($image_url)) {
                deleteImageFile($image_url);
            }
            $image_url = '';
        }

        // Handle new image upload
        if(isset($_FILES['meal_image']) && $_FILES['meal_image']['error'] === UPLOAD_ERR_OK) {
            try {
                // Delete old image if exists
                if (!empty($image_url)) {
                    deleteImageFile($image_url);
                }
                
                // Upload new image
                $image_url = handleImageUpload($_FILES['meal_image'], $meal_id);
                
            } catch (Exception $e) {
                $error_message = "Meal updated but image upload failed: " . $e->getMessage();
            }
        }

        $update_stmt = $conn->prepare("UPDATE meals SET meal_name = ?, meal_description = ?, price = ?, category_id = ?, preparation_time = ?, is_available = ?, image_url = ?, stock_quantity = ?, low_stock_threshold = ?, allow_quantity = ? WHERE meal_id = ?");
        $update_stmt->bind_param("ssdiiisiiii", $meal_name, $meal_description, $price, $category_id, $preparation_time, $is_available, $image_url, $stock_quantity, $low_stock_threshold, $allow_quantity, $meal_id);
        
        if($update_stmt->execute()) {
            $success_message = "Meal updated successfully!";
            // Refresh meal data
            $meal = array_merge($meal, [
                'meal_name' => $meal_name,
                'meal_description' => $meal_description,
                'price' => $price,
                'category_id' => $category_id,
                'preparation_time' => $preparation_time,
                'is_available' => $is_available,
                'image_url' => $image_url,
                'stock_quantity' => $stock_quantity,
                'low_stock_threshold' => $low_stock_threshold,
                'allow_quantity' => $allow_quantity
            ]);
        } else {
            $error_message = "Failed to update meal: " . $conn->error;
        }
    }
}
closeDBConnection($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Meal - Aunt Joy's Restaurant</title>
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
            background-color: var(--gray-50);
            color: var(--gray-800);
            line-height: 1.6;
        }

        .admin-container {
            max-width: 1200px;
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
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 0.75rem;
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

        .admin-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            padding: 2rem;
            animation: fadeInUp 0.5s ease 0.1s both;
            margin-bottom: 2rem;
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


        /* Add to your existing CSS */
.image-preview {
    margin: 1rem 0;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: var(--border-radius);
    border: 2px dashed var(--gray-300);
    transition: var(--transition);
}

.image-preview:hover {
    border-color: var(--primary);
}

.form-text {
    font-size: 0.875rem;
    color: var(--gray-500);
    margin-top: 0.5rem;
}

/* File input styling */
input[type="file"] {
    padding: 0.75rem;
}

input[type="file"]::file-selector-button {
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius);
    border: 1px solid var(--gray-300);
    background: var(--gray-100);
    color: var(--gray-700);
    cursor: pointer;
    transition: var(--transition);
    margin-right: 1rem;
}

input[type="file"]::file-selector-button:hover {
    background: var(--gray-200);
    border-color: var(--gray-400);
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

        .image-preview {
            margin-top: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .image-preview img {
            max-width: 200px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .image-preview img:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-lg);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-200);
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
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="page-header">
            <h1>Edit Meal: <?php echo htmlspecialchars($meal['meal_name']); ?></h1>
            <a href="meals.php" class="btn btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Meals
            </a>
        </div>

        <?php if($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="admin-card">
           
         <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Meal Name</label>
                        <input type="text" name="meal_name" class="form-control" value="<?php echo htmlspecialchars($meal['meal_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Price (MKW)</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?php echo $meal['price']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>" <?php echo $category['category_id'] == $meal['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Preparation Time (minutes)</label>
                        <input type="number" name="preparation_time" class="form-control" value="<?php echo $meal['preparation_time']; ?>" min="1">
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Description</label>
                        <textarea name="meal_description" class="form-control" rows="3"><?php echo htmlspecialchars($meal['meal_description']); ?></textarea>
                    </div>

                   <!-- In edit_meal.php - Replace the image URL section with this: -->
                <div class="form-group full-width">
                    <label class="form-label">Meal Image</label>
                    
                    <!-- Current Image Preview -->
                    <?php if(!empty($meal['image_url'])): ?>
                        <div class="image-preview" style="margin-bottom: 1rem;">
                            <label class="form-label">Current Image</label>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <img src="../../<?php echo htmlspecialchars($meal['image_url']); ?>" 
                                    alt="Current meal image" 
                                    style="max-width: 200px; border-radius: var(--border-radius); box-shadow: var(--shadow);"
                                    onerror="this.style.display='none';">
                                <div>
                                    <button type="button" onclick="removeCurrentImage()" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Remove Image
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- File Upload Input -->
                    <input type="file" name="meal_image" class="form-control" accept="image/*">
                    <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($meal['image_url']); ?>">
                    <input type="hidden" name="remove_image" id="remove_image" value="0">
                    <small class="form-text" style="color: var(--gray-500);">
                        Choose a new image to replace the current one. Supported formats: JPG, PNG, GIF, WebP (Max: 5MB)
                    </small>
                </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="is_available" id="is_available" <?php echo $meal['is_available'] ? 'checked' : ''; ?>>
                            <label class="form-label" for="is_available">Available for ordering</label>
                        </div>
                    </div>
                </div>
                
                <div class="inventory-section">
                    <h3 class="section-title">Inventory Management</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" name="stock_quantity" class="form-control" min="0" value="<?php echo $meal['stock_quantity'] ?? ''; ?>" placeholder="Leave empty for unlimited">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Low Stock Threshold</label>
                            <input type="number" name="low_stock_threshold" class="form-control" value="<?php echo $meal['low_stock_threshold'] ?? 10; ?>" min="1">
                        </div>

                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" name="allow_quantity" id="allow_quantity" <?php echo ($meal['allow_quantity'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-label" for="allow_quantity">Allow customers to select quantity</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="update_meal" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                            <polyline points="17 21 17 13 7 13 7 21"/>
                            <polyline points="7 3 7 8 15 8"/>
                        </svg>
                        Update Meal
                    </button>
                    <a href="meals.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Add some interactive animations
        document.addEventListener('DOMContentLoaded', function() {
            // Add focus animations to form controls
            const formControls = document.querySelectorAll('.form-control');
            formControls.forEach(control => {
                control.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                control.addEventListener('blur', function() {
                    if (this.value === '') {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });

            // Add animation to alerts when they appear
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.animation = 'slideInRight 0.5s ease';
            });

            // Add subtle hover effect to admin card
            const adminCard = document.querySelector('.admin-card');
            adminCard.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = 'var(--shadow-lg)';
            });
            
            adminCard.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'var(--shadow-md)';
            });
        });

       
     function removeCurrentImage() {
    if(confirm('Are you sure you want to remove the current image? This action cannot be undone.')) {
        document.getElementById('remove_image').value = '1';
        
        // Hide the current image preview
        const imagePreview = document.querySelector('.image-preview');
        if (imagePreview) {
            imagePreview.style.opacity = '0.5';
            imagePreview.innerHTML = '<div style="padding: 1rem; text-align: center; color: var(--danger);"><i class="fas fa-exclamation-triangle"></i> Image will be removed on save</div>';
        }
        
        showNotification('Image will be removed when you save changes', 'warning');
    }
}

// Enhanced file input preview
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.querySelector('input[name="meal_image"]');
    
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Check file size
                if (file.size > <?php echo MAX_FILE_SIZE; ?>) {
                    alert('File size exceeds maximum limit of 5MB');
                    this.value = '';
                    return;
                }
                
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Only JPG, JPEG, PNG, GIF, and WebP files are allowed');
                    this.value = '';
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewContainer = document.querySelector('.image-preview') || createPreviewContainer();
                    previewContainer.innerHTML = `
                        <label class="form-label">New Image Preview</label>
                        <img src="${e.target.result}" alt="Preview" style="max-width: 200px; border-radius: var(--border-radius); box-shadow: var(--shadow);">
                    `;
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    function createPreviewContainer() {
        const container = document.createElement('div');
        container.className = 'image-preview';
        container.style.marginBottom = '1rem';
        fileInput.parentNode.insertBefore(container, fileInput.nextSibling);
        return container;
    }
});

// Notification function
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
    </script>
</body>
</html>