<?php
// 404.php - Place this in your root directory
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Get the current user
$auth = new Auth();
$currentUser = null;
$isLoggedIn = false;

if($auth->isLoggedIn()) {
    $currentUser = $auth->getCurrentUser();
    $isLoggedIn = true;
}

// Set 404 HTTP status
http_response_code(404);

// Log the 404 error (optional)
$logMessage = date('Y-m-d H:i:s') . " | 404 Error | URL: " . ($_SERVER['REQUEST_URI'] ?? 'Unknown') . 
              " | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . 
              " | User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "\n";
@file_put_contents('logs/error.log', $logMessage, FILE_APPEND);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #e74c3c;
            --primary-dark: #2b91c0ff;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --dark-color: #2c3e50;
            --gray-dark: #7f8c8d;
            --gray-light: #ecf0f1;
            --white: #ffffff;
            --border-radius: 12px;
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            box-shadow: 0 4px 20px rgba(231, 76, 60, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
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
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--white);
            text-decoration: none;
        }

        .logo img {
            height: 60px;
            width: auto;
            border-radius: 60px;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-links a {
            color: var(--white);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: var(--transition);
            font-weight: 500;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .user-dropdown {
            position: relative;
            cursor: pointer;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: var(--transition);
        }

        .user-info:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .user-name {
            font-weight: 600;
            color: var(--white);
        }

        .dropdown-arrow {
            color: var(--white);
            font-size: 0.8rem;
            transition: transform 0.3s ease;
        }

        .user-dropdown:hover .dropdown-arrow {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            min-width: 220px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: var(--transition);
            z-index: 1000;
            overflow: hidden;
        }

        .user-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            color: var(--dark-color);
            text-decoration: none;
            transition: var(--transition);
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .dropdown-item:hover {
            background: var(--gray-light);
        }

        .dropdown-divider {
            height: 1px;
            background: var(--gray-light);
            margin: 0.5rem 0;
        }

        .cart-count {
            background: #ff4444;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
            font-weight: bold;
            min-width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            margin-left: 0.25rem;
        }

        /* 404 Container */
        .error-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 20px;
        }

        .error-content {
            text-align: center;
            max-width: 800px;
            width: 100%;
            padding: 3rem;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .error-icon {
            font-size: 8rem;
            color: var(--primary-color);
            margin-bottom: 2rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-30px);}
            60% {transform: translateY(-15px);}
        }

        .error-title {
            font-size: 4rem;
            font-weight: 800;
            color: var(--dark-color);
            margin-bottom: 1rem;
            line-height: 1;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .error-subtitle {
            font-size: 2rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1.5rem;
        }

        .error-message {
            font-size: 1.2rem;
            color: var(--gray-dark);
            margin-bottom: 3rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }

        .error-actions {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        /* Buttons */
        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4);
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: var(--white);
            transform: translateY(-3px);
        }

        /* Search Box */
        .search-box {
            max-width: 500px;
            margin: 2rem auto;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1.5rem;
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: var(--transition);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }

        /* Error Details */
        .error-details {
            margin-top: 2rem;
            padding: 1.5rem;
            background: var(--gray-light);
            border-radius: var(--border-radius);
            text-align: left;
            display: none;
        }

        .error-details h4 {
            color: var(--dark-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .error-details pre {
            background: var(--white);
            padding: 1rem;
            border-radius: var(--border-radius);
            overflow-x: auto;
            font-size: 0.9rem;
            font-family: 'Courier New', monospace;
            line-height: 1.4;
        }

        .toggle-details {
            margin-top: 1.5rem;
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: underline;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .toggle-details:hover {
            color: var(--primary-dark);
        }

        /* Footer */
        .footer {
            background: var(--dark-color);
            color: var(--white);
            padding: 2rem 0;
            margin-top: auto;
        }

        .footer-content {
            text-align: center;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin: 1.5rem 0;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: var(--white);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: var(--primary-color);
        }

        .copyright {
            color: var(--gray-dark);
            font-size: 0.9rem;
            margin-top: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .error-title {
                font-size: 3rem;
            }

            .error-subtitle {
                font-size: 1.5rem;
            }

            .error-icon {
                font-size: 6rem;
            }

            .error-actions {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }

            .error-content {
                padding: 2rem 1.5rem;
            }

            .footer-links {
                flex-direction: column;
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .error-title {
                font-size: 2.5rem;
            }

            .error-subtitle {
                font-size: 1.2rem;
            }

            .error-icon {
                font-size: 5rem;
            }

            .error-message {
                font-size: 1rem;
            }
        }

        /* Breadcrumb Navigation */
        .breadcrumb {
            background: var(--gray-light);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }

        .breadcrumb-content {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-dark);
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .breadcrumb-separator {
            color: var(--gray-dark);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="<?php echo BASE_PATH; ?>index.php" class="logo">
                    <img src="<?php echo BASE_PATH; ?>assets/images/kitchen_logo1.png" alt="<?php echo SITE_NAME; ?> Logo" /> 
                    <?php echo SITE_NAME; ?>
                </a>
                <ul class="nav-links">
                    <li><a href="<?php echo BASE_PATH; ?>index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="<?php echo BASE_PATH; ?>index.php#categories"><i class="fas fa-utensils"></i> Menu</a></li>
                    
                    <?php if($isLoggedIn): ?>
                        <li><a href="<?php echo BASE_PATH; ?>cart.php"><i class="fas fa-shopping-cart"></i> Cart <span class="cart-count">0</span></a></li>
                        <li><a href="<?php echo BASE_PATH; ?>orders.php"><i class="fas fa-box"></i> Orders</a></li>
                        <li class="user-dropdown">
                            <div class="user-info">
                                <span class="user-avatar">
                                    <?php 
                                        $name = $currentUser['full_name'] ?? $currentUser['username'];
                                        echo strtoupper(substr($name, 0, 1)); 
                                    ?>
                                </span>
                                <span class="user-name"><?php echo htmlspecialchars($name); ?></span>
                                <span class="dropdown-arrow"><i class="fas fa-chevron-down"></i></span>
                            </div>
                            <div class="dropdown-menu">
                                <a href="<?php echo BASE_PATH; ?>profile.php" class="dropdown-item"><i class="fas fa-user"></i> My Profile</a>
                                <a href="<?php echo BASE_PATH; ?>orders.php" class="dropdown-item"><i class="fas fa-box"></i> My Orders</a>
                                <div class="dropdown-divider"></div>
                                <a href="<?php echo BASE_PATH; ?>logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            </div>
                        </li>
                    <?php else: ?>
                        <li><a href="<?php echo BASE_PATH; ?>login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <li><a href="<?php echo BASE_PATH; ?>register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Breadcrumb Navigation -->
    <div class="breadcrumb">
        <div class="container">
            <div class="breadcrumb-content">
                <a href="<?php echo BASE_PATH; ?>index.php"><i class="fas fa-home"></i> Home</a>
                <span class="breadcrumb-separator">/</span>
                <span>404 - Page Not Found</span>
            </div>
        </div>
    </div>

    <!-- 404 Content -->
    <div class="error-container">
        <div class="container">
            <div class="error-content">
                <div class="error-icon">
                    <i class="fas fa-map-signs"></i>
                </div>
                
                <h1 class="error-title">404</h1>
                <h2 class="error-subtitle">Oops! Page Not Found</h2>
                
                <p class="error-message">
                    The page you're looking for seems to have wandered off into the digital wilderness. 
                    It might have been moved, deleted, or never existed. Let's help you find your way back!
                </p>

                <!-- Search Box -->
                <div class="search-box">
                    <input type="text" class="search-input" placeholder="What are you looking for?" id="search-page">
                    <div style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--gray-dark);">
                        Try searching for: menu, burgers, desserts, or your favorite meal
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="error-actions">
                    <a href="<?php echo BASE_PATH; ?>index.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Back to Homepage
                    </a>
                    <a href="<?php echo BASE_PATH; ?>index.php#categories" class="btn btn-outline">
                        <i class="fas fa-utensils"></i> Browse Menu
                    </a>
                    <?php if($isLoggedIn): ?>
                        <a href="<?php echo BASE_PATH; ?>orders.php" class="btn btn-outline">
                            <i class="fas fa-history"></i> My Orders
                        </a>
                    <?php else: ?>
                        <a href="<?php echo BASE_PATH; ?>login.php" class="btn btn-outline">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    <?php endif; ?>
                    <button class="toggle-details" onclick="toggleErrorDetails()">
                        <i class="fas fa-info-circle"></i> Show Details
                    </button>
                </div>

                <!-- Error Details (Hidden by default) -->
                <div class="error-details" id="error-details">
                    <h4><i class="fas fa-bug"></i> Technical Information</h4>
                    <pre>
=== 404 Error Details ===
Requested URL: <?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'Unknown'); ?>

HTTP Status: 404 - Not Found
Server Time: <?php echo date('F j, Y, g:i a'); ?>

User Information:
<?php if($isLoggedIn): ?>- Logged in as: <?php echo htmlspecialchars($currentUser['username']); ?>
<?php else: ?>- User: Guest (Not logged in)
<?php endif; ?>
- IP Address: <?php echo htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'Unknown'); ?>

Server Details:
- Server: <?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'); ?>
- Protocol: <?php echo htmlspecialchars($_SERVER['SERVER_PROTOCOL'] ?? 'Unknown'); ?>
- Method: <?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'Unknown'); ?>

Note: This information is for debugging purposes.
If you believe this is an error, please contact support.
                    </pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                <div class="footer-links">
                    <a href="<?php echo BASE_PATH; ?>index.php">Home</a>
                    <a href="<?php echo BASE_PATH; ?>index.php#categories">Menu</a>
                    <a href="<?php echo BASE_PATH; ?>about.php">About Us</a>
                    <a href="<?php echo BASE_PATH; ?>contact.php">Contact</a>
                    <a href="<?php echo BASE_PATH; ?>privacy.php">Privacy Policy</a>
                    <a href="<?php echo BASE_PATH; ?>terms.php">Terms of Service</a>
                </div>
                <p class="copyright">
                    <i class="fas fa-phone"></i> +265 123 456 789 &nbsp;|&nbsp;
                    <i class="fas fa-envelope"></i> info@auntjoys.com &nbsp;|&nbsp;
                    <i class="fas fa-map-marker-alt"></i> Lilongwe, Malawi
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Toggle error details
        function toggleErrorDetails() {
            const details = document.getElementById('error-details');
            const button = document.querySelector('.toggle-details');
            
            if (details.style.display === 'block') {
                details.style.display = 'none';
                button.innerHTML = '<i class="fas fa-info-circle"></i> Show Details';
            } else {
                details.style.display = 'block';
                button.innerHTML = '<i class="fas fa-times-circle"></i> Hide Details';
                // Smooth scroll to details
                details.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        // Search functionality
        document.getElementById('search-page').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.trim();
                if (searchTerm) {
                    // Redirect to search page or perform search
                    window.location.href = `<?php echo BASE_PATH; ?>search.php?q=${encodeURIComponent(searchTerm)}`;
                }
            }
        });

        // Auto-focus search input
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('search-page').focus();
            
            // Update cart count if user is logged in
            <?php if($isLoggedIn): ?>
            updateCartCount();
            <?php endif; ?>
        });

        // Update cart count from localStorage or session
        function updateCartCount() {
            try {
                const cart = JSON.parse(localStorage.getItem('cart')) || [];
                const cartCount = cart.reduce((total, item) => total + item.quantity, 0);
                const cartBadge = document.querySelector('.cart-count');
                if (cartBadge) {
                    cartBadge.textContent = cartCount > 9 ? '9+' : cartCount.toString();
                    cartBadge.style.display = cartCount > 0 ? 'inline-flex' : 'none';
                }
            } catch (error) {
                console.error('Error updating cart count:', error);
            }
        }

        // Suggest popular pages
        const popularPages = ['Burgers', 'Pizza', 'Desserts', 'Drinks', 'Salads', 'Breakfast', 'Lunch', 'Dinner'];
        const searchInput = document.getElementById('search-page');
        
        searchInput.addEventListener('focus', function() {
            if (!this.hasAttribute('data-suggested')) {
                this.setAttribute('placeholder', 'Try: ' + popularPages.slice(0, 3).join(', ') + '...');
                this.setAttribute('data-suggested', 'true');
            }
        });
        
        searchInput.addEventListener('blur', function() {
            if (!this.value) {
                this.setAttribute('placeholder', 'What are you looking for?');
            }
        });

        // Add page load animation
        document.addEventListener('DOMContentLoaded', function() {
            document.body.style.opacity = '0';
            document.body.style.transition = 'opacity 0.5s ease';
            
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 100);
            
            // Add click animation to buttons
            document.querySelectorAll('.btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 200);
                });
            });
        });

        // Handle 404 page interactions
        document.addEventListener('click', function(e) {
            // Close dropdown when clicking outside
            if (!e.target.closest('.user-dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.style.opacity = '0';
                    menu.style.visibility = 'hidden';
                    menu.style.transform = 'translateY(-10px)';
                });
            }
            
            // Smooth scroll for anchor links
            if (e.target.matches('a[href*="#"]')) {
                const href = e.target.getAttribute('href');
                if (href.includes('#')) {
                    e.preventDefault();
                    const targetId = href.split('#')[1];
                    const targetElement = document.getElementById(targetId);
                    if (targetElement) {
                        targetElement.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }
            }
        });
    </script>
</body>
</html>