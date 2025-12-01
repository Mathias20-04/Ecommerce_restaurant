<?php
// includes/functions.php

// Define upload constants
if (!defined('MAX_FILE_SIZE')) {
    define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
}
if (!defined('ALLOWED_IMAGE_TYPES')) {
    define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
}
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', '../../assets/images/meals/');
}

// Default CORS origin (override in config.php if you need a stricter origin)
if (!defined('ALLOWED_ORIGIN')) {
    define('ALLOWED_ORIGIN', '*');
}

function getDBConnection() {
    global $db_config;
    
    if (!isset($db_config)) {
        die("Database configuration not found. Please check your config.php file.");
    }
    
    $conn = new mysqli(
        $db_config['host'], 
        $db_config['username'], 
        $db_config['password'], 
        $db_config['database']
    );
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Database connection failed. Please try again later.");
    }
    
    return $conn;
}

function closeDBConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}

// Your existing functions continue below...
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}



// JSON response helper
function jsonResponse($success, $message = '', $data = [], $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Set CORS headers
function setCORSHeaders() {
    header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

// Handle preflight requests
function handlePreflight() {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// Frontend redirect (for non-API pages)
function redirect($url) {
    header("Location: " . $url);
    exit;
}

// Display alert messages (for frontend)
function displayAlert() {
    if(isset($_SESSION['alert'])) {
        echo '<div class="alert alert-' . $_SESSION['alert']['type'] . '">' . $_SESSION['alert']['message'] . '</div>';
        unset($_SESSION['alert']);
    }
}

// Set alert message (for frontend)
function setAlert($message, $type = 'info') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Validate uploaded image// includes/functions.php - Add these functions

function handleImageUpload($file, $meal_id) {
    try {
        // Check if file was uploaded without errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $file['error']);
        }

        // Check file size
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('File size exceeds maximum limit of 5MB');
        }

        // Get file extension
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Check if file type is allowed
        if (!in_array($fileExtension, ALLOWED_IMAGE_TYPES)) {
            throw new Exception('Only JPG, JPEG, PNG, GIF, and WebP files are allowed');
        }

        // Verify image is actual image
        $check = getimagesize($file['tmp_name']);
        if ($check === false) {
            throw new Exception('File is not a valid image');
        }

        // Create upload directory if it doesn't exist
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        // Generate unique filename
        $filename = 'meal_' . $meal_id . '_' . uniqid() . '.' . $fileExtension;
        $targetPath = UPLOAD_DIR . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Failed to move uploaded file');
        }

        // Return relative path for database storage
        return 'assets/images/meals/' . $filename;

    } catch (Exception $e) {
        error_log('Image upload error: ' . $e->getMessage());
        throw $e;
    }
}

function deleteImageFile($image_path) {
    if (!empty($image_path)) {
        $full_path = '../../' . $image_path;
        if (file_exists($full_path) && is_file($full_path)) {
            unlink($full_path);
        }
    }
}

// includes/functions.php - Add this function

function getAvailableMeals($category_id = null, $limit = null) {
    $conn = getDBConnection();
    if (!$conn) return [];
    
    try {
        $query = "SELECT m.*, c.category_name 
                  FROM meals m 
                  LEFT JOIN categories c ON m.category_id = c.category_id 
                  WHERE m.is_available = 1 
                  AND c.is_active = 1";
        
        if ($category_id) {
            $query .= " AND m.category_id = ?";
        }
        
        $query .= " ORDER BY m.created_at DESC";
        
        if ($limit) {
            $query .= " LIMIT ?";
        }
        
        $stmt = $conn->prepare($query);
        
        if ($category_id && $limit) {
            $stmt->bind_param("ii", $category_id, $limit);
        } elseif ($category_id) {
            $stmt->bind_param("i", $category_id);
        } elseif ($limit) {
            $stmt->bind_param("i", $limit);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $meals = $result->fetch_all(MYSQLI_ASSOC);
        
        return $meals;
        
    } catch (Exception $e) {
        error_log('Error fetching meals: ' . $e->getMessage());
        return [];
    } finally {
        closeDBConnection($conn);
    }
}

function getMealCategories() {
    $conn = getDBConnection();
    if (!$conn) return [];
    
    try {
        $query = "SELECT c.*, COUNT(m.meal_id) as meal_count 
                  FROM categories c 
                  LEFT JOIN meals m ON c.category_id = m.category_id AND m.is_available = 1
                  WHERE c.is_active = 1 
                  GROUP BY c.category_id 
                  ORDER BY c.category_name";
        
        $result = $conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
        
    } catch (Exception $e) {
        error_log('Error fetching categories: ' . $e->getMessage());
        return [];
    } finally {
        closeDBConnection($conn);
    }
}
?>