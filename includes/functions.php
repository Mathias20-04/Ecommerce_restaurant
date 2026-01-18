<?php
// includes/functions.php

// Include config file to get DB connection function and constants
require_once __DIR__ . '/config.php';

// Note: We removed getDBConnection() and closeDBConnection() since they're in config.php
// Also removed jsonResponse() since it's in config.php

// Utility functions only

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
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

// Frontend redirect
function redirect($url) {
    header("Location: " . $url);
    exit;
}

// Display alert messages 
function displayAlert() {
    if(isset($_SESSION['alert'])) {
        echo '<div class="alert alert-' . $_SESSION['alert']['type'] . '">' . $_SESSION['alert']['message'] . '</div>';
        unset($_SESSION['alert']);
    }
}

// Set alert message 
function setAlert($message, $type = 'info') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

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

function getAvailableMeals($category_id = null, $limit = null) {
    $conn = getDBConnection(); // This function is now from config.php
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
        closeDBConnection($conn); // This function is now from config.php
    }
}

function getMealCategories() {
    $conn = getDBConnection(); // This function is now from config.php
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
        closeDBConnection($conn); // This function is now from config.php
    }
}
?>