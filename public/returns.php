<?php
// returns.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Returns & Refunds - Aunt Joy's Restaurant</title>
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
        .returns-hero {
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

        /* Policy Section */
        .policy-section {
            padding: 6rem 0;
            background: white;
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

        .policy-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .policy-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid #e74c3c;
            transition: all 0.3s ease;
        }

        .policy-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(231, 76, 60, 0.1);
        }

        .policy-icon {
            font-size: 2.5rem;
            color: #e74c3c;
            margin-bottom: 1rem;
        }

        .policy-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .policy-content {
            color: #666;
            line-height: 1.7;
        }

        .policy-list {
            list-style: none;
            margin: 1rem 0;
        }

        .policy-list li {
            padding: 0.5rem 0;
            position: relative;
            padding-left: 1.5rem;
        }

        .policy-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #27ae60;
            font-weight: bold;
        }

        /* Process Section */
        .process-section {
            padding: 6rem 0;
            background: #f8f9fa;
        }

        .process-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .process-step {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
        }

        .process-step:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(231, 76, 60, 0.15);
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 auto 1.5rem;
        }

        .step-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .step-description {
            color: #666;
            line-height: 1.6;
        }

        /* Timeline */
        .timeline {
            position: relative;
            max-width: 800px;
            margin: 3rem auto;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e74c3c;
            transform: translateX(-50%);
        }

        .timeline-item {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            width: 45%;
        }

        .timeline-item:nth-child(odd) {
            left: 0;
        }

        .timeline-item:nth-child(even) {
            left: 55%;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            top: 20px;
            width: 20px;
            height: 20px;
            background: #e74c3c;
            border-radius: 50%;
        }

        .timeline-item:nth-child(odd)::before {
            right: -50px;
        }

        .timeline-item:nth-child(even)::before {
            left: -50px;
        }

        /* Contact Section */
        .contact-section {
            padding: 6rem 0;
            background: white;
        }

        .contact-info {
            text-align: center;
            max-width: 600px;
            margin: 0 auto;
        }

        .contact-icon {
            font-size: 3rem;
            color: #e74c3c;
            margin-bottom: 1.5rem;
        }

        .contact-text {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .btn-contact {
            display: inline-block;
            padding: 1rem 2rem;
            background: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-contact:hover {
            background: #c0392b;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(231, 76, 60, 0.3);
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

            .process-steps {
                grid-template-columns: 1fr;
            }

            .timeline::before {
                left: 30px;
            }

            .timeline-item {
                width: calc(100% - 80px);
                left: 80px !important;
            }

            .timeline-item::before {
                left: -50px !important;
                right: auto !important;
            }

            .nav-links {
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .hero-title {
                font-size: 2rem;
            }

            .policy-card {
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
                    <li><a href="help.php">Help Center</a></li>
                    <li><a href="track.php">Track Order</a></li>
                    <li><a href="returns.php" style="background: #e74c3c; color: white;">Returns</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="returns-hero">
        <div class="container">
            <h1 class="hero-title">Returns & Refunds Policy</h1>
            <p class="hero-subtitle">Your satisfaction is our priority. Learn about our return and refund procedures.</p>
        </div>
    </section>

    <!-- Policy Section -->
    <section class="policy-section">
        <div class="container">
            <h2 class="section-title">Our Policy</h2>
            <div class="policy-container">
                <div class="policy-card">
                    <div class="policy-icon">
                        <i class="fas fa-undo"></i>
                    </div>
                    <h3 class="policy-title">Return Policy</h3>
                    <div class="policy-content">
                        <p>We want you to be completely satisfied with your order. If you're not happy with your meal, here's what you need to know:</p>
                        <ul class="policy-list">
                            <li>Returns must be requested within 2 hours of delivery</li>
                            <li>Items must be substantially uneaten (at least 75% remaining)</li>
                            <li>Original packaging should be intact where possible</li>
                            <li>Photographic evidence may be required for quality issues</li>
                        </ul>
                    </div>
                </div>

                <div class="policy-card">
                    <div class="policy-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h3 class="policy-title">Refund Policy</h3>
                    <div class="policy-content">
                        <p>We process refunds quickly and efficiently. Here's how our refund system works:</p>
                        <ul class="policy-list">
                            <li>Full refund for incorrect orders or quality issues</li>
                            <li>Partial refund for minor issues or delays</li>
                            <li>Refunds processed within 3-5 business days</li>
                            <li>Refund method matches original payment method</li>
                        </ul>
                        <p><strong>Note:</strong> Delivery fees are non-refundable unless the error was on our part.</p>
                    </div>
                </div>

                <div class="policy-card">
                    <div class="policy-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h3 class="policy-title">Non-Refundable Items</h3>
                    <div class="policy-content">
                        <p>Some items cannot be returned or refunded due to their nature:</p>
                        <ul class="policy-list">
                            <li>Customized or personalized orders</li>
                            <li>Perishable items that have been consumed</li>
                            <li>Digital products or gift cards</li>
                            <li>Orders cancelled after preparation has begun</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Process Section -->
    <section class="process-section">
        <div class="container">
            <h2 class="section-title">Return Process</h2>
            <div class="process-steps">
                <div class="process-step">
                    <div class="step-number">1</div>
                    <h3 class="step-title">Contact Us</h3>
                    <p class="step-description">Call or email us within 2 hours of delivery to report the issue.</p>
                </div>

                <div class="process-step">
                    <div class="step-number">2</div>
                    <h3 class="step-title">Provide Details</h3>
                    <p class="step-description">Share your order number and describe the issue with photos if possible.</p>
                </div>

                <div class="process-step">
                    <div class="step-number">3</div>
                    <h3 class="step-title">Assessment</h3>
                    <p class="step-description">Our team reviews your case and determines the appropriate solution.</p>
                </div>

                <div class="process-step">
                    <div class="step-number">4</div>
                    <h3 class="step-title">Resolution</h3>
                    <p class="step-description">We process your refund or arrange for a replacement meal.</p>
                </div>
            </div>

            <!-- Timeline -->
            <div class="timeline">
                <div class="timeline-item">
                    <h4>Immediate Action</h4>
                    <p>Contact us within 2 hours of delivery for fastest resolution.</p>
                </div>
                <div class="timeline-item">
                    <h4>Review Period</h4>
                    <p>We assess your case within 24 hours of notification.</p>
                </div>
                <div class="timeline-item">
                    <h4>Refund Processing</h4>
                    <p>Refunds are processed within 3-5 business days after approval.</p>
                </div>
                <div class="timeline-item">
                    <h4>Confirmation</h4>
                    <p>You receive confirmation once the refund is complete.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <div class="contact-info">
                <div class="contact-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h2 class="section-title">Need Help With a Return?</h2>
                <p class="contact-text">Our customer service team is here to help you with any return or refund requests. We're committed to resolving issues quickly and fairly.</p>
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
                        <li><a href="returns.php">Returns & Refunds</a></li>
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
            // Animate process steps on scroll
            const processSteps = document.querySelectorAll('.process-step');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });
            
            processSteps.forEach(step => {
                step.style.opacity = '0';
                step.style.transform = 'translateY(20px)';
                step.style.transition = 'all 0.6s ease';
                observer.observe(step);
            });

            // Animate timeline items
            const timelineItems = document.querySelectorAll('.timeline-item');
            
            timelineItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateX(' + (index % 2 === 0 ? '-50px' : '50px') + ')';
                item.style.transition = 'all 0.6s ease ' + (index * 0.2) + 's';
            });
            
            const timelineObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateX(0)';
                    }
                });
            }, { threshold: 0.1 });
            
            timelineItems.forEach(item => timelineObserver.observe(item));
        });
    </script>
</body>
</html>