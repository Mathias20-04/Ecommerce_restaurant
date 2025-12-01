<?php
// logout.php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$currentUser = $auth->getCurrentUser();

// If user is not logged in, redirect to login
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Process logout if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth->logout();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - Aunt Joy's Restaurant</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .logout-container {
            max-width: 500px;
            margin: 4rem auto;
            padding: 0 20px;
        }

        .logout-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(12, 11, 11, 0.1);
            padding: 3rem;
            text-align: center;
        }

        .logout-icon {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 1.5rem;
        }

        .logout-title {
            font-size: 2rem;
            font-weight: 600;
            color: #343a40;
            margin-bottom: 1rem;
        }

        .logout-message {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .user-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 80%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: white;
        }

        .user-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #343a40;
            margin-bottom: 0.5rem;
        
        }

        .user-email {
            color: #6c757d;
            font-size: 1rem;
           text-align: center;
        }

        .logout-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: #dc3545;
            color: white;
        }

        .btn-primary:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            color: #6c757d;
            border: 2px solid #6c757d;
        }

        .btn-outline:hover {
            background: #6c757d;
            color: white;
            transform: translateY(-2px);
        }

        .logout-loading {
            display: none;
            margin-top: 1rem;
        }

        .loading-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #dc3545;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .security-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 1rem;
            margin-top: 2rem;
            text-align: left;
        }

        .security-notice h4 {
            color: #856404;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .security-notice p {
            color: #856404;
            margin: 0;
            font-size: 0.9rem;
        }

        @media (max-width: 576px) {
            .logout-card {
                padding: 2rem 1.5rem;
            }
            
            .logout-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
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
                    <img src="../assets/images/kitchen_logo1.png" alt="Aunt Joy's Restaurant Logo" /> 
                    Aunt Joy's
                </div>
                <ul class="nav-links">
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="index.php#categories"><i class="fas fa-utensils"></i> Menu</a></li>
                    <?php if ($auth->isLoggedIn()): ?>
                        <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                        <li><a href="orders.php"><i class="fas fa-box"></i> Orders</a></li>
                        <li class="user-profile">
                            <div class="user-dropdown">
                                <div class="user-info">
                                    <span class="user-avatar">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <span class="user-name">
                                        <?php echo htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']); ?>
                                    </span>
                                    <span class="dropdown-arrow">
                                        <i class="fas fa-chevron-down"></i>
                                    </span>
                                </div>
                                <div class="dropdown-menu">
                                    <a href="profile.php" class="dropdown-item"><i class="fas fa-user"></i> My Profile</a>
                                    <a href="orders.php" class="dropdown-item"><i class="fas fa-box"></i> My Orders</a>
                                    <div class="dropdown-divider"></div>
                
                                </div>
                            </div>
                        </li>
                    <?php else: ?>
                        <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <li><a href="register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <div class="logout-container">
        <div class="logout-card">
            <div class="logout-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            
            <h1 class="logout-title">Logout</h1>
            
            <div class="user-info">
                
                <div class="user-email">
                    <?php echo htmlspecialchars($currentUser['email'] ?? $currentUser['username']); ?>
                </div>
            </div>
            
            <p class="logout-message">
                Are you sure you want to logout?<br>
                You'll need to sign in again to access your account and make orders.
            </p>
            
            <form id="logout-form" method="POST">
                <div class="logout-actions">
                    <button type="submit" class="btn btn-primary" id="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        Yes, Logout
                    </button>
                    <a href="index.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i>
                        Cancel & Return
                    </a>
                </div>
                
                <div class="logout-loading" id="logout-loading">
                    <div class="loading-spinner"></div>
                    <p style="margin-top: 0.5rem; color: #6c757d;">Logging out...</p>
                </div>
            </form>
            
            <div class="security-notice">
                <h4>
                    <i class="fas fa-shield-alt"></i>
                    Security Notice
                </h4>
                <p>
                    Logging out will securely end your session. This helps protect your account 
                    if you're using a shared or public computer.
                </p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logoutForm = document.getElementById('logout-form');
            const logoutBtn = document.getElementById('logout-btn');
            const logoutLoading = document.getElementById('logout-loading');
            
            logoutForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Show loading state
                logoutBtn.disabled = true;
                logoutLoading.style.display = 'block';
                logoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging out...';
                
                // Perform logout via API first, then submit form
                fetch('/projects/aunt-joy-restaurant/api/auth/logout.php', {
                    method: 'POST',
                    credentials: 'include'
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Logout API response:', data);
                    
                    // Submit the form to complete the logout
                    setTimeout(() => {
                        const formData = new FormData(logoutForm);
                        fetch('logout.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(() => {
                            // Redirect to home page
                            window.location.href = 'index.php';
                        })
                        .catch(error => {
                            console.error('Form submission error:', error);
                            window.location.href = 'index.php';
                        });
                    }, 1000);
                })
                .catch(error => {
                    console.error('Logout API error:', error);
                    // Still try to submit the form
                    logoutForm.submit();
                });
            });
            
            // Add some interactive effects
            logoutBtn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            logoutBtn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>