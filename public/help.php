<?php
// help.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center - Aunt Joy's Restaurant</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        /* Header Styles */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
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
            font-size: 1.8rem;
            font-weight: 700;
            color: #e74c3c;
            text-decoration: none;
        }

        .logo img {
            height: 50px;
            width: 50px;
            margin-right: 10px;
            border-radius: 50%;
            background-color: #ddc332;
            object-fit: cover;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background: #e74c3c;
            color: white;
            transform: translateY(-2px);
        }

        /* Hero Section */
        .help-hero {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.9), rgba(192, 57, 43, 0.9));
            color: white;
            padding: 6rem 0;
            text-align: center;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            animation: slideDown 1s ease-out;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            opacity: 0.9;
            animation: slideUp 1s ease-out 0.3s both;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Search Section */
        .search-section {
            padding: 3rem 0;
            background: white;
        }

        .search-container {
            max-width: 600px;
            margin: 0 auto;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 1.2rem 2rem 1.2rem 3rem;
            border: 2px solid #e1e5e9;
            border-radius: 50px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .search-input:focus {
            outline: none;
            border-color: #e74c3c;
            background: white;
            box-shadow: 0 5px 20px rgba(231, 76, 60, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        /* Help Categories */
        .categories-section {
            padding: 4rem 0;
            background: #f8f9fa;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 3rem;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            border-radius: 2px;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .category-card {
            background: white;
            border-radius: 15px;
            padding: 2.5rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(231, 76, 60, 0.15);
        }

        .category-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2rem;
        }

        .category-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .category-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .category-link {
            color: #e74c3c;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .category-link:hover {
            gap: 1rem;
        }

        /* Quick Help Section */
        .quick-help {
            padding: 4rem 0;
            background: white;
        }

        .help-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .help-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .help-card:hover {
            background: white;
            box-shadow: 0 10px 30px rgba(231, 76, 60, 0.1);
            transform: translateY(-5px);
        }

        .help-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .help-title i {
            color: #e74c3c;
        }

        .help-links {
            list-style: none;
        }

        .help-links li {
            margin-bottom: 0.8rem;
        }

        .help-links a {
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
            display: block;
            padding: 0.5rem 0;
        }

        .help-links a:hover {
            color: #e74c3c;
            transform: translateX(5px);
        }

        /* Contact Section */
        .contact-section {
            padding: 6rem 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
        }

        .contact-content {
            max-width: 600px;
            margin: 0 auto;
        }

        .contact-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            color: #ffd700;
        }

        .contact-text {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .contact-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .contact-method {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .method-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #ffd700;
        }

        .method-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .method-info {
            opacity: 0.9;
        }

        .btn-contact {
            display: inline-block;
            padding: 1rem 2rem;
            background: white;
            color: #e74c3c;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 2rem;
        }

        .btn-contact:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 255, 255, 0.3);
        }

        /* Footer */
        .footer {
            background: #2c3e50;
            color: white;
            padding: 4rem 0 2rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-section h3 {
            color: white;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }

        .footer-section p {
            opacity: 0.8;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.8rem;
        }

        .footer-links a {
            color: #ddd;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #e74c3c;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .categories-grid {
                grid-template-columns: 1fr;
            }

            .contact-methods {
                grid-template-columns: 1fr;
            }

            .nav-links {
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .hero-title {
                font-size: 2rem;
            }

            .category-card {
                padding: 1.5rem;
            }

            .section-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="logo">
                    <img src="../assets/images/kitchen_logo1.png" alt="Aunt Joy's Restaurant Logo" /> 
                    Aunt Joy's
                </a>
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="help.php" style="background: #e74c3c; color: white;">Help Center</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="help-hero">
        <div class="container">
            <h1 class="hero-title">How Can We Help You?</h1>
            <p class="hero-subtitle">Find answers to common questions or get in touch with our support team</p>
        </div>
    </section>

    <!-- Search Section -->
    <section class="search-section">
        <div class="container">
            <div class="search-container">
                <div class="search-icon">
                    <i class="fas fa-search"></i>
                </div>
                <input type="text" class="search-input" placeholder="Search for help articles...">
            </div>
        </div>
    </section>

    <!-- Help Categories -->
    <section class="categories-section">
        <div class="container">
            <h2 class="section-title">Browse Help Categories</h2>
            <div class="categories-grid">
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3 class="category-title">Ordering & Payment</h3>
                    <p class="category-description">Learn how to place orders, payment methods, and billing questions</p>
                    <a href="faq.php#ordering" class="category-link">
                        Browse Articles <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <h3 class="category-title">Delivery & Tracking</h3>
                    <p class="category-description">Information about delivery areas, times, and order tracking</p>
                    <a href="track.php" class="category-link">
                        Track Order <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-undo"></i>
                    </div>
                    <h3 class="category-title">Returns & Refunds</h3>
                    <p class="category-description">Our return policy and how to request refunds for orders</p>
                    <a href="returns.php" class="category-link">
                        View Policy <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h3 class="category-title">Account & Profile</h3>
                    <p class="category-description">Managing your account, profile settings, and preferences</p>
                    <a href="faq.php#account" class="category-link">
                        Get Help <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Help Section -->
    <section class="quick-help">
        <div class="container">
            <h2 class="section-title">Quick Help Guides</h2>
            <div class="help-grid">
                <div class="help-card">
                    <h3 class="help-title">
                        <i class="fas fa-question-circle"></i>
                        Getting Started
                    </h3>
                    <ul class="help-links">
                        <li><a href="#">How to create an account</a></li>
                        <li><a href="#">Placing your first order</a></li>
                        <li><a href="#">Understanding our menu</a></li>
                        <li><a href="#">Setting delivery preferences</a></li>
                    </ul>
                </div>

                <div class="help-card">
                    <h3 class="help-title">
                        <i class="fas fa-credit-card"></i>
                        Payment & Billing
                    </h3>
                    <ul class="help-links">
                        <li><a href="#">Accepted payment methods</a></li>
                        <li><a href="#">Understanding delivery fees</a></li>
                        <li><a href="#">Applying promo codes</a></li>
                        <li><a href="#">Billing and receipt issues</a></li>
                    </ul>
                </div>

                <div class="help-card">
                    <h3 class="help-title">
                        <i class="fas fa-truck"></i>
                        Delivery Support
                    </h3>
                    <ul class="help-links">
                        <li><a href="#">Delivery areas and times</a></li>
                        <li><a href="#">Tracking your order</a></li>
                        <li><a href="#">Contacting your delivery driver</a></li>
                        <li><a href="#">Delivery instructions</a></li>
                    </ul>
                </div>

                <div class="help-card">
                    <h3 class="help-title">
                        <i class="fas fa-utensils"></i>
                        Food & Menu
                    </h3>
                    <ul class="help-links">
                        <li><a href="#">Customizing your order</a></li>
                        <li><a href="#">Allergy information</a></li>
                        <li><a href="#">Nutritional information</a></li>
                        <li><a href="#">Special dietary requests</a></li>
                    </ul>
                </div>

                <div class="help-card">
                    <h3 class="help-title">
                        <i class="fas fa-mobile-alt"></i>
                        Technical Support
                    </h3>
                    <ul class="help-links">
                        <li><a href="#">Website troubleshooting</a></li>
                        <li><a href="#">Mobile app issues</a></li>
                        <li><a href="#">Login and password help</a></li>
                        <li><a href="#">Browser compatibility</a></li>
                    </ul>
                </div>

                <div class="help-card">
                    <h3 class="help-title">
                        <i class="fas fa-shield-alt"></i>
                        Safety & Security
                    </h3>
                    <ul class="help-links">
                        <li><a href="#">Food safety standards</a></li>
                        <li><a href="#">Contactless delivery</a></li>
                        <li><a href="#">Privacy policy</a></li>
                        <li><a href="#">Account security</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <div class="contact-content">
                <div class="contact-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h2 class="section-title" style="color: white;">Still Need Help?</h2>
                <p class="contact-text">
                    Our dedicated customer support team is available to assist you with any questions or concerns. 
                    We're committed to providing you with the best possible service.
                </p>

                <div class="contact-methods">
                    <div class="contact-method">
                        <div class="method-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h3 class="method-title">Call Us</h3>
                        <p class="method-info">+265 888 123 456</p>
                        <p class="method-info">Available 8AM - 10PM</p>
                    </div>

                    <div class="contact-method">
                        <div class="method-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h3 class="method-title">Email Us</h3>
                        <p class="method-info">support@auntjoys.mw</p>
                        <p class="method-info">Response within 24 hours</p>
                    </div>

                    <div class="contact-method">
                        <div class="method-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3 class="method-title">Live Chat</h3>
                        <p class="method-info">Available on website</p>
                        <p class="method-info">Instant support</p>
                    </div>
                </div>

                <a href="contact.php" class="btn-contact">Contact Support Team</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Aunt Joy's Restaurant</h3>
                    <p>Premium food delivery service in Mzuzu. We bring restaurant-quality meals to your home with fast, reliable delivery.</p>
                </div>
                
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <p><i class="fas fa-map-marker-alt"></i> Mzuzu City Center</p>
                    <p><i class="fas fa-phone"></i> +265 888 123 456</p>
                    <p><i class="fas fa-envelope"></i> info@auntjoys.mw</p>
                </div>
                
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="help.php">Help Center</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 Aunt Joy's Restaurant. Celebrating good food and great moments.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-input');
            const helpCards = document.querySelectorAll('.help-card');
            const categoryCards = document.querySelectorAll('.category-card');

            // Search functionality
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                // Search in help cards
                helpCards.forEach(card => {
                    const title = card.querySelector('.help-title').textContent.toLowerCase();
                    const links = card.querySelectorAll('.help-links a');
                    let hasMatch = title.includes(searchTerm);
                    
                    links.forEach(link => {
                        if (link.textContent.toLowerCase().includes(searchTerm)) {
                            hasMatch = true;
                            link.style.backgroundColor = 'rgba(231, 76, 60, 0.1)';
                        } else {
                            link.style.backgroundColor = 'transparent';
                        }
                    });
                    
                    card.style.display = hasMatch ? 'block' : 'none';
                });

                // Search in category cards
                categoryCards.forEach(card => {
                    const title = card.querySelector('.category-title').textContent.toLowerCase();
                    const description = card.querySelector('.category-description').textContent.toLowerCase();
                    const hasMatch = title.includes(searchTerm) || description.includes(searchTerm);
                    
                    card.style.display = hasMatch ? 'block' : 'none';
                });
            });

            // Add hover animations
            categoryCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            helpCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Smooth scrolling for internal links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>