<?php
// checkout.php
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
$conn = getDBConnection();

// Get user details for pre-filling form
$userStmt = $conn->prepare("SELECT phone, address FROM users WHERE user_id = ?");
$userStmt->bind_param("i", $currentUser['user_id']);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userDetails = $userResult->fetch_assoc();

$userPhone = $userDetails['phone'] ?? '';
$userAddress = $userDetails['address'] ?? '';

closeDBConnection($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Aunt Joy's Restaurant</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 3rem;
        }

        .checkout-header {
            grid-column: 1 / -1;
            text-align: center;
            margin-bottom: 2rem;
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

        .checkout-section {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--dark-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .delivery-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .delivery-option {
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .delivery-option.selected {
            border-color: var(--primary-color);
            background: rgba(231, 76, 60, 0.05);
        }

        .delivery-option input {
            display: none;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .payment-method {
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .payment-method.selected {
            border-color: var(--primary-color);
            background: rgba(231, 76, 60, 0.05);
        }

        .payment-method input {
            display: none;
        }

        
            .mobile-number.valid {
                border-color: #48bb78;
            }

            .mobile-number.invalid {
                border-color: #e53e3e;
            }

            .provider-match {
                color: #48bb78;
                font-size: 0.875rem;
                margin-top: 0.25rem;
            }

            .provider-mismatch {
                color: #e53e3e;
                font-size: 0.875rem;
                margin-top: 0.25rem;
            }
        .order-summary {
            position: sticky;
            top: 2rem;
            height: fit-content;
        }

        .order-items {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 1.5rem;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-light);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .item-quantity {
            color: var(--gray-dark);
            font-size: 0.9rem;
        }

        .item-price {
            font-weight: 600;
            color: var(--primary-color);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .summary-row.total {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
            border-bottom: none;
        }

        .place-order-btn {
            width: 100%;
            padding: 1.2rem;
            background: var(--success-color);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 1.5rem;
        }

        .place-order-btn:hover:not(:disabled) {
            background: #219a52;
        }

        .place-order-btn:disabled {
            background: var(--gray-light);
            cursor: not-allowed;
        }

        .error-message {
            color: var(--danger-color);
            font-size: 0.9rem;
            margin-top: 0.5rem;
            display: none;
        }

        .required {
            color: var(--danger-color);
        }

        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .form-row,
            .delivery-options,
            .payment-methods {
                grid-template-columns: 1fr;
            }

            .order-summary {
                position: static;
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
                    <li><a href="index.php">🏠 Home</a></li>
                    <li><a href="index.php#categories">📋 Menu</a></li>
                    <li class="auth-required">
                <a href="cart.php" style="position: relative; display: flex; align-items: center; gap: 0.2rem;">
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
                    </li>
                    <li class="auth-required">
                        <a href="orders.php">📦 Orders</a>
                    </li>
                    <li class="auth-required user-profile">
                        <div class="user-dropdown">
                            <div class="user-info">
                                <span class="user-avatar">👤</span>
                                <span class="user-name"><?php echo htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']); ?></span>
                                <span class="dropdown-arrow">▼</span>
                            </div>
                            <div class="dropdown-menu">
                                <a href="profile.php" class="dropdown-item">👤 My Profile</a>
                                <a href="orders.php" class="dropdown-item">📦 My Orders</a>
                                <div class="dropdown-divider"></div>
                                <a href="logout.php" class="dropdown-item">🚪 Logout</a>
                            </div>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="checkout-container">
        <div class="checkout-header">
            <h1>Checkout</h1>
            <p>Complete your order with delivery details</p>
        </div>

        <div class="checkout-main">
            <!-- Delivery Information -->
            <div class="checkout-section">
                <h2 class="section-title">Delivery Information</h2>
                
                <div class="form-group">
                    <label class="form-label">Full Name <span class="required">*</span></label>
                    <input type="text" class="form-control" id="full-name" 
                           value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" readonly>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Phone Number <span class="required">*</span></label>
                        <input type="tel" class="form-control" id="phone" 
                               value="<?php echo htmlspecialchars($userPhone); ?>" 
                               placeholder="Enter your phone number" required>
                        <div class="error-message" id="phone-error">Please enter a valid phone number</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Delivery Time</label>
                        <select class="form-control" id="delivery-time">
                            <option value="asap">As soon as possible</option>
                            <option value="30">In 30 minutes</option>
                            <option value="45">In 45 minutes</option>
                            <option value="60">In 1 hour</option>
                            <option value="custom">Custom time</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Delivery Address <span class="required">*</span></label>
                    <textarea class="form-control" id="delivery-address" rows="3" 
                              placeholder="Enter your complete delivery address" 
                              required><?php echo htmlspecialchars($userAddress); ?></textarea>
                    <div class="error-message" id="address-error">Please enter your delivery address</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Special Instructions</label>
                    <textarea class="form-control" id="special-instructions" rows="2" 
                              placeholder="Any special delivery instructions..."></textarea>
                </div>
            </div>

            <!-- Payment Method -->
            <div class="checkout-section">
                <h2 class="section-title">Payment Method</h2>
                
                <div class="payment-methods">
                    <label class="payment-method" id="cash-payment">
                        <input type="radio" name="payment-method" value="cash" checked>
                        <div>💵 Cash on Delivery</div>
                        <small>Pay when you receive your order</small>
                    </label>
                    
                    <label class="payment-method" id="mobile-payment">
                        <input type="radio" name="payment-method" value="mobile">
                        <div>📱 Mobile Money</div>
                       <small>
                    <i class="fas fa-mobile-alt"></i> 
                    Airtel Money: 099 or 09 | TNM Mpamba: 088 or 08
                </small>
                    </label>
                </div>

                <div id="mobile-payment-details" style="display: none; margin-top: 1rem;">

                <div class="form-group">
                        <label class="form-label">Provider</label>
                        <select class="form-control" id="mobile-provider">
                            <option value="">Select provider</option>
                            <option value="airtel">Airtel Money</option>
                            <option value="tnm">TNM Mpamba</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mobile Money Number</label>
                        <input type="tel" class="form-control" id="mobile-number" 
                               placeholder="Enter your mobile money number">
                    </div>
                    
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="checkout-section order-summary">
            <h2 class="section-title">Order Summary</h2>
            
            <div class="order-items" id="order-items">
                <!-- Order items will be loaded by JavaScript -->
            </div>

            <div class="summary-details">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span id="order-subtotal">MK 0.00</span>
                </div>
                <div class="summary-row">
                    <span>Delivery Fee:</span>
                    <span id="order-delivery">MK 1000.00</span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span id="order-total">MK 0.00</span>
                </div>
            </div>

            <button id="place-order-btn" class="place-order-btn" disabled>
                Place Order
            </button>

            <p style="text-align: center; margin-top: 1rem; font-size: 0.9rem; color: var(--gray-dark);">
                By placing your order, you agree to our terms and conditions
            </p>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/checkout.js"></script>
</body>
</html>