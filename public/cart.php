<?php
// cart.php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();

// Redirect to login if not authenticated
if(!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$currentUser = $auth->getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Aunt Joy's Restaurant</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --amazon-orange: #ff9900;
            --amazon-dark: #232f3e;
            --amazon-light: #fafafa;
            --amazon-border: #ddd;
            --amazon-success: #007600;
        }

        .cart-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
        }

        .cart-header {
            grid-column: 1 / -1;
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }

        .cart-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--amazon-dark);
            margin: 0;
        }
        .user-name {

            color:#232f3e;

        }

        .price {
            color: #b12704;
            font-weight: 600;
        }

        /* Cart Items Section */
        .cart-items-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .cart-section-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--amazon-border);
            background: var(--amazon-light);
            font-weight: 600;
            color: var(--amazon-dark);
        }

        .cart-item {
            display: flex;
            padding: 1.5rem;
            border-bottom: 1px solid var(--amazon-border);
            gap: 1.5rem;
            transition: background-color 0.2s ease;
        }

        .cart-item:hover {
            background: #f9f9f9;
        }

        .item-image {
            width: 120px;
            height: 120px;
            border-radius: 8px;
            object-fit: cover;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #666;
            flex-shrink: 0;
        }

        .item-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .item-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--amazon-dark);
            line-height: 1.4;
        }

        .item-name a {
            color: inherit;
            text-decoration: none;
        }

        .item-name a:hover {
            color: var(--amazon-orange);
            text-decoration: underline;
        }

        .item-availability {
            color: var(--amazon-success);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .item-actions {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .action-btn {
            background: none;
            border: none;
            color: #0066c0;
            cursor: pointer;
            font-size: 0.9rem;
            padding: 0.25rem 0;
        }

        .action-btn:hover {
            color: #c45500;
            text-decoration: underline;
        }

        .delete-btn {
            color: #cc0000;
        }

        .quantity-section {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }

        .quantity-label {
            font-size: 0.9rem;
            color: #666;
        }

        .quantity-select {
            padding: 0.4rem 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f0f2f2;
            cursor: pointer;
        }

        .quantity-select:focus {
            outline: none;
            border-color: var(--amazon-orange);
            box-shadow: 0 0 0 2px rgba(255,153,0,0.2);
        }

        .item-price-section {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            min-width: 120px;
        }

        .item-price {
            font-size: 1.2rem;
            font-weight: 600;
            color: #b12704;
        }

        .item-subtotal {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.25rem;
        }

        /* Order Summary */
        .order-summary {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 2rem;
        }

        .summary-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--amazon-border);
            background: var(--amazon-light);
        }

        .summary-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--amazon-dark);
            margin: 0;
        }

        .summary-content {
            padding: 1.5rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .summary-row.total {
            border-top: 1px solid var(--amazon-border);
            padding-top: 1rem;
            font-size: 1.2rem;
            font-weight: 600;
            color: #b12704;
            margin-bottom: 1.5rem;
        }

        .checkout-btn {
            width: 100%;
            padding: 0.75rem;
            background: var(--amazon-orange);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-bottom: 1rem;
        }

        .checkout-btn:hover {
            background: #e68900;
        }

        .checkout-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .secure-checkout {
            text-align: center;
            font-size: 0.8rem;
            color: #666;
        }

        .secure-checkout i {
            color: var(--amazon-success);
            margin-right: 0.25rem;
        }

        /* Empty Cart */
        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }

        .empty-cart-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            color: #ddd;
        }

        .empty-cart h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--amazon-dark);
        }

        .continue-shopping {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--amazon-orange);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

            .continue-shopping:hover {
                background: #e68900;
                color: white;
            }

            /* Responsive */
            @media (max-width: 768px) {
                .cart-container {
                    grid-template-columns: 1fr;
                    gap: 1rem;
                }

                .cart-item {
                    flex-direction: column;
                    text-align: center;
                }

                .item-price-section {
                    align-items: center;
                }

                .quantity-section {
                    justify-content: center;
                }
            }

        .cart-count {
        background: #ff4444;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 0.7rem;
        font-weight: bold;
        position: relative;
        top: -8px;
        left: -5px;
        min-width: 18px;
        height: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        }

        .nav-links .auth-required:has(.cart-count) {
            position: relative;
        }

    /* Ensuring the cart link has proper spacing */
    .nav-links a[href="cart.php"] {
        position: relative;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    
     /* Save for later styles */
        .save-later-section {
            margin-top: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .saved-item {
            display: flex;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--amazon-border);
            gap: 1rem;
            align-items: center;
        }

        .saved-item:last-child {
            border-bottom: none;
        }

        .move-to-cart-btn {
            background: var(--amazon-orange);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }

        .move-to-cart-btn:hover {
            background: #e68900;
        }

        .saved-actions .delete-btn {
            background: none;
            border: none;
            color: #cc0000;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .saved-actions .delete-btn:hover {
            background: rgba(204, 0, 0, 0.1);
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
               <li class="auth-required">
               <a href="cart.php" style="position: relative; display: flex; align-items: center; gap: 0.5rem;">
               <i class="fas fa-shopping-cart"></i> 
                  Cart
                <span class="cart-count" style="
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
                 ">0</span>
               </a>
             </li>
                    <li class="auth-required">
                        <a href="orders.php"><i class="fas fa-box"></i> Orders</a>
                    </li>
                    <li class="auth-required user-profile">
                        <div class="user-dropdown">
                            <div class="user-info">
                                <span class="user-avatar"><i class="fas fa-user"></i></span>
                                <span class="user-name"><?php echo htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']); ?></span>
                                <span class="dropdown-arrow"><i class="fas fa-chevron-down"></i></span>
                            </div>
                            <div class="dropdown-menu">
                                <a href="profile.php" class="dropdown-item"><i class="fas fa-user"></i> My Profile</a>
                                <a href="orders.php" class="dropdown-item"><i class="fas fa-box"></i> My Orders</a>
                                <div class="dropdown-divider"></div>
                                <a href="logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            </div>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="cart-container">
        <div class="cart-header">
            <h1 class="cart-title">Shopping Cart</h1>
        </div>

        <!-- Main Cart Items -->
        <div class="cart-items-section">
            <div class="cart-section-header">
                <span id="cart-items-count">0 items</span>
            </div>
            
            <div id="cart-items-container">
                <div class="empty-cart" id="empty-cart">
                    <div class="empty-cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>Your Aunt Joy's cart is empty</h3>
                    <p>Add some delicious meals from our menu to get started!</p>
                    <a href="index.php#categories" class="continue-shopping">
                        <i class="fas fa-utensils"></i>
                        Continue Shopping
                    </a>
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="order-summary">
            <div class="summary-header">
                <h3 class="summary-title">Order Summary</h3>
            </div>
            <div class="summary-content">
                <div class="summary-row">
                    <span>Items (<span id="summary-items-count">0</span>):</span>
                    <span id="summary-subtotal">MK 0.00</span>
                </div>
                <div class="summary-row">
                    <span>Delivery Fee:</span>
                    <span id="summary-delivery">MK 1,500.00</span>
                </div>
                <div class="summary-row">
                    <span>Tax:</span>
                    <span id="summary-tax">MK 0.00</span>
                </div>
                <div class="summary-row total">
                    <span>Order Total:</span>
                    <span id="summary-total">MK 0.00</span>
                </div>
              <button id="checkout-btn" class="checkout-btn" onclick="window.location.href='checkout.php'">
                <i class="fas fa-lock"></i>
                   Proceed to Checkout
                 </button>

                
                <div class="secure-checkout">
                    <i class="fas fa-lock"></i>
                    Secure checkout
                </div>
            </div>
        </div>

       <!-- Save for Later Section (Hidden by default) -->
         <div class="save-later-section" id="save-later-section" style="display: none;">
         <div class="cart-section-header">
              Saved for Later (0 items)
           </div>
             <div id="saved-items-container">
            <!-- Saved items will appear here -->
           </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/cart.js"></script>
</body>
</html>