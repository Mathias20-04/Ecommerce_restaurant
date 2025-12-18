<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/projects/aunt-joy-restaurant';



$auth = new Auth();
$currentUser = $auth->getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aunt Joy's Restaurant - Premium Food Delivery in Mzuzu</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Your existing styles remain intact */
        /* Modern Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* Enhanced Body Styles */
        body {
            font-family: 'Poppins', sans-serif;
            animation: fadeInUp 0.8s ease-out;
        }

        /* Enhanced Hero Section with Modern Animations */
        .hero-content h1 {
            animation: slideInLeft 1s ease-out 0.3s both;
        }

        .hero-content p {
            animation: slideInRight 1s ease-out 0.6s both;
        }

        .btn-hero {
            animation: fadeInUp 1s ease-out 0.9s both;
        }

        /* UPDATED: Meal Cards with Image Support */
        .meal-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
            animation: fadeInScale 0.6s ease-out;
        }

        .meal-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .meal-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 20px 40px rgba(231, 76, 60, 0.15);
        }

        .meal-card:hover::before {
            transform: scaleX(1);
        }

        .meal-image-container {
            width: 100%;
            height: 220px;
            position: relative;
            overflow: hidden;
        }

        .meal-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .meal-card:hover .meal-image {
            transform: scale(1.1);
        }

        .image-fallback {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }

        .category-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: rgba(231, 76, 60, 0.9);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 2;
        }

        .prep-time {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 0.5rem;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
            z-index: 2;
        }

        .meal-content {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            background: white;
            position: relative;
            z-index: 2;
        }

        .meal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .meal-name {
            font-size: 1.25rem;
            color: #2d3748;
            margin: 0;
            font-weight: 600;
            line-height: 1.3;
            flex: 1;
        }

        .meal-price {
            font-size: 1.5rem;
            color: #e74c3c;
            font-weight: 700;
            white-space: nowrap;
            margin-left: 1rem;
        }

        .meal-description {
            color: #718096;
            margin-bottom: 1.5rem;
            line-height: 1.5;
            font-size: 0.95rem;
            flex-grow: 1;
        }

        .add-to-cart {
            background: linear-gradient(135deg, var(--primary-color), #e74c3c);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
            width: 100%;
            justify-content: center;
        }

        .add-to-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
            background: linear-gradient(135deg, #e74c3c, var(--primary-color));
        }

        /* NEW: Category Filters for Meals */
        .category-filters {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .category-filter {
            padding: 0.75rem 1.5rem;
            border: 2px solid #e2e8f0;
            background: white;
            color: #4a5568;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .category-filter.active,
        .category-filter:hover {
            background: #e74c3c;
            color: white;
            border-color: #e74c3c;
            transform: translateY(-2px);
        }

        /* NEW: Search Bar */
        .search-container {
            max-width: 500px;
            margin: 0 auto 2rem auto;
        }

        .search-box {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e2e8f0;
            border-radius: 25px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
        }

        /* NEW: Meals Grid */
        .meals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        /* NEW: Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #718096;
            grid-column: 1 / -1;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        /* Your existing footer, navigation, and other styles remain unchanged */
        /* Amazon-style Professional Footer */
        .footer {
            background: linear-gradient(135deg, #232f3e 0%, #131a22 100%);
            color: #ffffff;
            padding: 4rem 0 2rem;
            margin-top: 6rem;
            position: relative;
            overflow: hidden;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-section h3 {
            color: var(--white);
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .footer-section h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 2px;
            background: var(--primary-color);
        }

        .footer-section h4 {
            color: var(--white);
            font-size: 1.2rem;
            margin-bottom: 1.2rem;
            font-weight: 600;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 0.8rem;
        }

        .footer-links a {
            color: #dddddd;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .footer-links a:hover {
            color: var(--primary-color);
            transform: translateX(5px);
        }

        .footer-contact p {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1rem;
            color: #dddddd;
        }

        .footer-contact i {
            color: var(--primary-color);
            width: 20px;
        }

        .footer-social {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-icon:hover {
            background: var(--primary-color);
            transform: translateY(-3px);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 2rem;
            text-align: center;
        }

        .footer-bottom-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer-copyright {
            color: #aaaaaa;
            font-size: 0.9rem;
        }

        .footer-legal {
            display: flex;
            gap: 2rem;
        }

        .footer-legal a {
            color: #aaaaaa;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .footer-legal a:hover {
            color: var(--primary-color);
        }

        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
            transition: all 0.3s ease;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
        }

        .back-to-top.visible {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            background: #c0392b;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.6);
        }

        /* Enhanced Category Cards */
        .category-card {
            animation: fadeInUp 0.6s ease-out;
            animation-fill-mode: both;
        }

        .category-card:nth-child(1) { animation-delay: 0.1s; }
        .category-card:nth-child(2) { animation-delay: 0.2s; }
        .category-card:nth-child(3) { animation-delay: 0.3s; }
        .category-card:nth-child(4) { animation-delay: 0.4s; }
        .category-card:nth-child(5) { animation-delay: 0.5s; }
        .category-card:nth-child(6) { animation-delay: 0.6s; }

        /* Floating Animation for Featured Items */
        .featured-item {
            animation: float 3s ease-in-out infinite;
        }

        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Enhanced Navigation Styles */
        .user-dropdown {
            position: relative;
            cursor: pointer;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }

        .user-info:hover {
            background: rgba(255,255,255,0.1);
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
        }

        .user-name {
            font-weight: bold;
            color: blue;
        }

        .dropdown-arrow {
            color: blue;
            font-size: 0.7rem;
            transition: transform 0.3s ease;
        }

        .user-dropdown:hover .dropdown-arrow {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
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
            padding: 0.75rem 1rem;
            color: #333;
            text-decoration: none;
            transition: background-color 0.3s ease;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
        }

        .dropdown-item:hover {
            background: #f8f9fa;
        }

        .dropdown-divider {
            height: 1px;
            background: #e9ecef;
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

        /* Navigation Search Styles */
        .nav-search-container {
            display: flex;
            align-items: center;
            margin-left: auto;
            margin-right: 2rem;
        }

        .nav-search {
            position: relative;
            width: 400px;
        }

        .nav-search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 2px solid #dfe1e5;
            border-radius: 24px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
            box-shadow: 0 1px 6px rgba(32, 33, 36, 0.08);
        }

        .nav-search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 1px 6px rgba(32, 33, 36, 0.28);
        }

        .nav-search-input:hover {
            box-shadow: 0 1px 6px rgba(32, 33, 36, 0.28);
        }

        .nav-search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9aa0a6;
            z-index: 1;
        }

        /* Google-style Search Results */
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #dfe1e5;
            border-radius: 0 0 24px 24px;
            box-shadow: 0 4px 6px rgba(32, 33, 36, 0.28);
            margin-top: -1px;
            max-height: 400px;
            overflow-y: auto;
            display: none;
            z-index: 1000;
        }

        .search-results.active {
            display: block;
        }

        .search-result-item {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            transition: background-color 0.1s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f8f9fa;
        }

        .search-result-item:hover {
            background-color: #f8f9fa;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-content {
            flex: 1;
        }

        .search-result-name {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }

        .search-result-category {
            font-size: 0.8rem;
            color: #5f6368;
        }

        .search-result-price {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.9rem;
            margin-left: 1rem;
        }

        .no-results {
            padding: 1.5rem;
            text-align: center;
            color: #5f6368;
            font-style: italic;
        }

        /* Ensure nav links have proper spacing */
        .nav-links {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links > li > a {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: background-color 0.3s ease;
            color: blue;
            text-decoration: none;
        }

        .nav-links > li > a:hover {
            background: rgba(255,255,255,0.1);
        }

        /* NEW STYLES FOR ADMIN DASHBOARD SECTION */
        .admin-dashboard-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 4rem 0;
            border-bottom: 1px solid #dee2e6;
            display: none;
        }

        .admin-dashboard-section.visible {
            display: block;
            animation: fadeInUp 0.8s ease-out;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .dashboard-header h2 {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .dashboard-header p {
            font-size: 1.2rem;
            color: #7f8c8d;
            max-width: 600px;
            margin: 0 auto;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .dashboard-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .dashboard-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.15);
        }

        .dashboard-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            font-size: 2rem;
            color: white;
        }

        .dashboard-card.orders .dashboard-icon {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .dashboard-card.products .dashboard-icon {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }

        .dashboard-card.users .dashboard-icon {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }

        .dashboard-card.analytics .dashboard-icon {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .dashboard-card h3 {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .dashboard-card p {
            color: #7f8c8d;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .dashboard-stats {
            display: flex;
            justify-content: space-between;
            width: 100%;
            margin-top: auto;
        }

        .stat {
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-top: 0.5rem;
        }

        .dashboard-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-dashboard {
            background: linear-gradient(135deg, var(--primary-color), #e74c3c);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
            text-decoration: none;
        }

        .btn-dashboard:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
            background: linear-gradient(135deg, #e74c3c, var(--primary-color));
        }

        .btn-dashboard.secondary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-dashboard.secondary:hover {
            background: linear-gradient(135deg, #2980b9, #3498db);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }

        @media (max-width: 768px) {
            .footer-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .footer-bottom-content {
                flex-direction: column;
                text-align: center;
            }

            .footer-legal {
                justify-content: center;
            }

            .meal-card {
                margin-bottom: 1.5rem;
            }

            .back-to-top {
                bottom: 20px;
                right: 20px;
                width: 45px;
                height: 45px;
            }
            
            .nav-search-container {
                display: none;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-dashboard {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 1024px) {
            .nav-search {
                width: 300px;
            }
        }
    </style>
</head>
<body>
    <!-- Header - Keeping your original navigation -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <img src="../assets/images/kitchen_logo1.png" alt="Aunt Joy's Restaurant Logo" /> 
                    Aunt Joy's
                </div>
                
               

               <ul class="nav-links">
    <li><a href="<?php echo $base_url; ?>/public/index.php"><i class="fas fa-home"></i> Home</a></li>
    <li><a href="#meals-section"><i class="fas fa-utensils"></i> Menu</a></li>
    
    <!-- Guest Links -->
    <?php if(!$auth->isLoggedIn()): ?>
    <li>
        <a href="<?php echo $base_url; ?>/public/login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
    </li>
    <li>
        <a href="<?php echo $base_url; ?>/public/register.php"><i class="fas fa-user-plus"></i> Register</a>
    </li>
    <?php else: ?>
    <!-- Authenticated User Links -->
    <li>
        <a href="<?php echo $base_url; ?>/public/cart.php">
            <i class="fas fa-shopping-cart"></i> Cart 
            <span class="cart-count">0</span>
        </a>
    </li>
    <li>
        <a href="<?php echo $base_url; ?>/public/orders.php"><i class="fas fa-box"></i> Orders</a>
    </li>
    
    <!-- User Profile Dropdown -->
    <li class="user-dropdown">
        <div class="user-info">
            <span class="user-avatar">
                <i class="fas fa-user"></i>
            </span>
            <span class="user-name"><?php echo htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']); ?></span>
            <span class="dropdown-arrow">
                <i class="fas fa-chevron-down"></i>
            </span>
        </div>
        <div class="dropdown-menu">
            <!-- Admin Dashboard Link for Admin Users -->
            <?php if($auth->hasRole('admin')): ?>
           
            <div class="dropdown-divider"></div>
            <?php endif; ?>
            <a href="<?php echo $base_url; ?>/public/profile.php" class="dropdown-item">
                <i class="fas fa-user"></i> My Profile
            </a>
            <a href="<?php echo $base_url; ?>/public/orders.php" class="dropdown-item">
                <i class="fas fa-box"></i> My Orders
            </a>
            <div class="dropdown-divider"></div>
            <a href="<?php echo $base_url; ?>/public/logout.php" class="dropdown-item logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
      </li>
      <?php endif; ?>
    </ul>
        </div>
    </header>

   

    <!-- Hero Section - Keeping your original hero section -->
    <section class="hero">
        <div class="hero-slider">
            <div class="slide slide-1 active"></div>
            <div class="slide slide-2"></div>
            <div class="slide slide-3"></div>
        </div>

        <div class="slider-controls">
            <div class="slider-dot active" data-slide="0"></div>
            <div class="slider-dot" data-slide="1"></div>
            <div class="slider-dot" data-slide="2"></div>
        </div>

        <div class="container">
            <div class="hero-content">
                <h1>Welcome to Aunt Joy<span>'s Restaurant</span></h1>
                <p>Experience the finest cuisine in Mzuzu, delivered straight to your doorstep. Fresh ingredients, authentic flavors, and exceptional service.</p>
                <a href="#meals-section" class="btn btn-hero">
                    <i class="fas fa-utensils"></i> Discover Our Menu
                </a>
            </div>
        </div>
    </section>

    <!-- UPDATED: Meals Section with Image Support -->
    <section id="meals-section" style="padding: 4rem 0; background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);">
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            
            <!-- Section Header -->
            <div style="text-align: center; margin-bottom: 3rem;">
                <h2 style="font-size: 2.5rem; color: #2d3748; margin-bottom: 1rem; font-weight: 700;">
                    🍽️ Our Delicious Meals
                </h2>
                <p style="font-size: 1.1rem; color: #718096; max-width: 600px; margin: 0 auto;">
                    Discover our carefully crafted meals made with the finest ingredients and traditional recipes
                </p>
            </div>

            <!-- Category Filters -->
            <div class="category-filters">
                <button class="category-filter active" data-category="all">
                    All Meals
                  </button>
                <?php
                $categories = getMealCategories();
                foreach ($categories as $category):
                    if ($category['meal_count'] > 0):
                ?>
                    <button class="category-filter" data-category="<?php echo $category['category_id']; ?>">
                        <?php echo htmlspecialchars($category['category_name']); ?>
                        <span style="background: #e74c3c; color: white; border-radius: 50%; padding: 0.25rem 0.5rem; font-size: 0.8rem; margin-left: 0.5rem;">
                            <?php echo $category['meal_count']; ?>
                        </span>
                    </button>
                <?php
                    endif;
                endforeach;
                ?>
            </div>

            <!-- Search Bar -->
            <div class="search-container">
                <div class="search-box">
                    <div class="search-icon">🔍</div>
                    <input type="text" id="meal-search" class="search-input" placeholder="Search for meals...">
                </div>
            </div>

            <!-- Meals Grid -->
            <div id="meals-container">
                <?php
                $meals = getAvailableMeals();
                if (empty($meals)):
                ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🍽️</div>
                        <h3 style="color: #4a5568; margin-bottom: 1rem;">No Meals Available</h3>
                        <p>We're currently preparing new delicious meals. Please check back later!</p>
                    </div>
                <?php else: ?>
                    <div class="meals-grid">
                        <?php foreach ($meals as $meal): ?>
                            <div class="meal-card" 
                                 data-category="<?php echo $meal['category_id']; ?>" 
                                 data-meal-name="<?php echo htmlspecialchars(strtolower($meal['meal_name'])); ?>"
                                 data-meal-description="<?php echo htmlspecialchars(strtolower($meal['meal_description'])); ?>">
                                
                                <!-- Meal Image -->
                                <div class="meal-image-container">
                                    <?php if(!empty($meal['image_url'])): ?>
                                        <img src="../<?php echo htmlspecialchars($meal['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($meal['meal_name']); ?>" 
                                             class="meal-image"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <?php endif; ?>
                                    <div class="image-fallback" style="display: <?php echo empty($meal['image_url']) ? 'flex' : 'none'; ?>;">
                                        🍽️
                                    </div>
                                    
                                    <!-- Category Badge -->
                                    <div class="category-badge">
                                        <?php echo htmlspecialchars($meal['category_name']); ?>
                                    </div>
                                    
                                    <!-- Preparation Time -->
                                    <div class="prep-time">
                                        <?php echo $meal['preparation_time']; ?>m
                                    </div>
                                </div>
                                
                                <!-- Meal Info -->
                                <div class="meal-content">
                                    <div class="meal-header">
                                        <h3 class="meal-name">
                                            <?php echo htmlspecialchars($meal['meal_name']); ?>
                                        </h3>
                                        <div class="meal-price">
                                            MK<?php echo number_format($meal['price'], 2); ?>
                                        </div>
                                    </div>
                                    
                                    <?php if(!empty($meal['meal_description'])): ?>
                                        <p class="meal-description">
                                            <?php echo htmlspecialchars($meal['meal_description']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <!-- Action Button -->
                                    <button class="add-to-cart" 
                                            data-meal-id="<?php echo $meal['meal_id']; ?>"
                                            data-meal-name="<?php echo htmlspecialchars($meal['meal_name']); ?>"
                                            data-meal-price="<?php echo $meal['price']; ?>"
                                            data-meal-image="<?php echo htmlspecialchars($meal['image_url']); ?>">
                                        <span>🛒</span>
                                        Add to Cart
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Professional Amazon-style Footer - Keeping your original footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Aunt Joy's Restaurant</h3>
                    <p>Premium food delivery service in Mzuzu. We bring restaurant-quality meals to your home with fast, reliable delivery.</p>
                    <div class="footer-social">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                        <li><a href="#meals-section"><i class="fas fa-utensils"></i> Menu</a></li>
                        <li><a href="about.php"><i class="fas fa-info-circle"></i> About Us</a></li>
                        <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Customer Service</h4>
                    <ul class="footer-links">
                        <li><a href="help.php"><i class="fas fa-question-circle"></i> Help Center</a></li>
                        <li><a href="track.php"><i class="fas fa-shipping-fast"></i> Track Order</a></li>
                        <li><a href="returns.php"><i class="fas fa-undo"></i> Returns & Refunds</a></li>
                        <li><a href="faq.php"><i class="fas fa-comments"></i> FAQ</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Contact Information</h4>
                    <div class="footer-contact">
                        <p><i class="fas fa-map-marker-alt"></i> Mzuzu City Center, Malawi</p>
                        <p><i class="fas fa-phone"></i> +265 888 123 456</p>
                        <p><i class="fas fa-mobile-alt"></i> +265 999 987 654</p>
                        <p><i class="fas fa-envelope"></i> orders@auntjoys.mw</p>
                    </div>
                    <h4 style="margin-top: 1.5rem;">Business Hours</h4>
                    <div class="footer-contact">
                        <p><i class="fas fa-clock"></i> Monday - Sunday: 8:00 AM - 10:00 PM</p>
                        <p><i class="fas fa-truck"></i> Delivery until 9:30 PM</p>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <div class="footer-copyright">
                        <p>&copy; 2025 Aunt Joy's Restaurant. All rights reserved. Celebrating good food and great moments.</p>
                    </div>
                    <div class="footer-legal">
                        <a href="privacy.php">Privacy Policy</a>
                        <a href="terms.php">Terms of Service</a>
                        <a href="cookies.php">Cookie Policy</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button class="back-to-top" id="backToTop">
        <i class="fas fa-chevron-up"></i>
    </button>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/menu.js"></script>
    
    <script>
           function updateCartCount() {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            
            const cartCountElements = document.querySelectorAll('.cart-count');
            cartCountElements.forEach(element => {
                element.textContent = totalItems;
                element.style.display = totalItems > 0 ? 'inline-flex' : 'none';
            });
        }
        // Enhanced Modern Animations and Interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Back to Top Button
            
         const isAuthenticated = <?php echo $auth->isLoggedIn() ? 'true' : 'false'; ?>;
            const backToTop = document.getElementById('backToTop');
            
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 300) {
                    backToTop.classList.add('visible');
                } else {
                    backToTop.classList.remove('visible');
                }
            });

            backToTop.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });

            // Cart functionality
    function addToCart(mealId, mealName, price, imageUrl) {
    // Check if user is authenticated
    if (!isAuthenticated) {
        // Show authentication required notification
        const notification = document.createElement('div');
        notification.innerHTML = `🔒 Please log in to add items to your cart`;
        notification.style.cssText = 'position:fixed;top:20px;right:20px;background:#e74c3c;color:white;padding:1rem 1.5rem;border-radius:8px;box-shadow:0 10px 25px rgba(0,0,0,0.2);z-index:1000;animation:slideInRight 0.5s ease;display:flex;align-items:center;gap:0.5rem;max-width:300px;';
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideInRight 0.5s ease reverse';
            setTimeout(() => {
                if (notification.parentNode) {
                    document.body.removeChild(notification);
                }
            }, 500);
        }, 3000);
        
        // Redirect to login page after a short delay
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 1000);
        return false; // Stop execution
    }
    
     function updateCartCount() {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        
        // Update all cart count elements
        const cartCountElements = document.querySelectorAll('.cart-count');
        cartCountElements.forEach(element => {
            element.textContent = totalItems;
            if (totalItems === 0) {
                element.style.display = 'none';
            } else {
                element.style.display = 'inline-flex';
                // Add pulse animation
                element.style.animation = 'pulse 0.6s ease';
                setTimeout(() => {
                    element.style.animation = '';
                }, 600);
            }
        });
    }
    // If authenticated, proceed with adding to cart
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    const existingItem = cart.find(item => item.mealId === mealId);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            mealId: mealId,
            name: mealName,
            price: parseFloat(price),
            imageUrl: imageUrl,
            quantity: 1
        });
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    
    // Show success notification
    const notification = document.createElement('div');
    notification.innerHTML = `✅ ${mealName} added to cart!`;
    notification.style.cssText = 'position:fixed;top:20px;right:20px;background:#27ae60;color:white;padding:1rem 1.5rem;border-radius:8px;box-shadow:0 10px 25px rgba(0,0,0,0.2);z-index:1000;animation:slideInRight 0.5s ease;display:flex;align-items:center;gap:0.5rem;max-width:300px;';
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideInRight 0.5s ease reverse';
        setTimeout(() => {
            if (notification.parentNode) {
                document.body.removeChild(notification);
            }
        }, 500);
    }, 3000);
    
    return true;
}

            // Initialize cart count
            updateCartCount();

            // Category Filtering
            const filters = document.querySelectorAll('.category-filter');
            const mealCards = document.querySelectorAll('.meal-card');
            
            filters.forEach(filter => {
                filter.addEventListener('click', function() {
                    filters.forEach(f => f.classList.remove('active'));
                    this.classList.add('active');
                    
                    const category = this.getAttribute('data-category');
                    mealCards.forEach(card => {
                        if (category === 'all' || card.getAttribute('data-category') === category) {
                            card.style.display = 'block';
                            setTimeout(() => {
                                card.style.opacity = '1';
                                card.style.transform = 'translateY(0)';
                            }, 50);
                        } else {
                            card.style.opacity = '0';
                            card.style.transform = 'translateY(20px)';
                            setTimeout(() => {
                                card.style.display = 'none';
                            }, 300);
                        }
                    });
                });
            });

            // Search functionality
            const searchInput = document.getElementById('meal-search');
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase().trim();
                    mealCards.forEach(card => {
                        const mealName = card.getAttribute('data-meal-name');
                        const mealDescription = card.getAttribute('data-meal-description');
                        
                        if (searchTerm === '') {
                            card.style.display = 'block';
                            return;
                        }
                        
                        if (mealName.includes(searchTerm) || mealDescription.includes(searchTerm)) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }

            // Add to cart buttons
         const addToCartButtons = document.querySelectorAll('.add-to-cart');
           addToCartButtons.forEach(button => {
    button.addEventListener('click', function() {
        const mealId = this.getAttribute('data-meal-id');
        const mealName = this.getAttribute('data-meal-name');
        const mealPrice = this.getAttribute('data-meal-price');
        const mealImage = this.getAttribute('data-meal-image');
        
        addToCart(mealId, mealName, mealPrice, mealImage);
          });
      });

            // Your existing JavaScript for animations and interactions
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.animationPlayState = 'running';
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.meal-card').forEach(el => {
                el.style.animationPlayState = 'paused';
                observer.observe(el);
            });

            // Enhanced slider with auto-play and pause on hover
            const hero = document.querySelector('.hero');
            if (hero) {
                hero.addEventListener('mouseenter', function() {
                    if (window.menuManager && window.menuManager.sliderInterval) {
                        clearInterval(window.menuManager.sliderInterval);
                    }
                });

                hero.addEventListener('mouseleave', function() {
                    if (window.menuManager) {
                        window.menuManager.startSlider();
                    }
                });
            }
        });
        
    </script>
</body>
</html> 