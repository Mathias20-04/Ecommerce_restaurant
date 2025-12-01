<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
require_once 'functions.php'; // Add this to use jsonResponse

class Auth {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    // User registration
    public function register($username, $email, $password, $full_name, $phone = '', $address = '', $role = 'customer') {
        // Check if user already exists
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            return "Username or email already exists!";
        }
        
        // Hash password and insert new user
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->conn->prepare("INSERT INTO users (username, email, password_hash, full_name, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $username, $email, $password_hash, $full_name, $phone, $address, $role);
        
        if($stmt->execute()) {
            // Auto-login after registration
            $user_id = $this->conn->insert_id;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            $_SESSION['full_name'] = $full_name;
            
            return true;
        }
        
        return "Registration failed: " . $this->conn->error;
    }
    
    // User login
    public function login($username, $password) {
        $stmt = $this->conn->prepare("SELECT user_id, username, password_hash, role, full_name, is_active FROM users WHERE username = ? AND is_active = 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            
            if(password_verify($password, $row['password_hash'])) {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['full_name'] = $row['full_name'];
                
                return true;
            }
        }
        
        return false;
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    // Get current user data
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $stmt = $this->conn->prepare("SELECT user_id, username, email, full_name, phone, address, role, created_at FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    // Get user role for redirection (returns URL, doesn't redirect)
    public function getRoleRedirectUrl() {
        if($this->isLoggedIn()) {
            switch($_SESSION['role']) {
                case 'admin':
                    return '../admin/dashboard.php';
                case 'sales':
                    return '../sales/dashboard.php';
                case 'manager':
                    return '../manager/dashboard.php';
                default:
                    return '../index.php';
            }
        }
        return '../index.php';
    }
    
    // Logout (API version - no redirect)
    public function logout() {
        $username = $_SESSION['username'] ?? 'Unknown';
        session_destroy();
        return $username;
    }
    
    // Check if user has specific role
    public function hasRole($role) {
        return $this->isLoggedIn() && $_SESSION['role'] === $role;
    }
    
    // Require specific role (for middleware)
    public function requireRole($role) {
        if (!$this->isLoggedIn()) {
            jsonResponse(false, 'Authentication required', [], 401);
        }
        
        if (!$this->hasRole($role)) {
            jsonResponse(false, 'Insufficient permissions', [], 403);
        }
        
        return true;
    }
}
?>