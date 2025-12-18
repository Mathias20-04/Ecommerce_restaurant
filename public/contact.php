<?php
// contact.php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $subject = sanitizeInput($_POST['subject']);
    $message = sanitizeInput($_POST['message']);
    
    // Basic validation
    if(empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "Please fill in all required fields!";
    } else {
        // Here you would typically send the email
        // For now, we'll just show a success message
        $success = "Thank you for your message! We'll get back to you within 24 hours.";
        
        // Clear form
        $_POST = array();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Aunt Joy's Restaurant</title>
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
            color: #2935dfff;
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
            color: #1651bdff;
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
        .contact-hero {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.9), rgba(38, 137, 184, 0.9)), url('../assets/images/contact-hero.jpg');
            background-size: cover;
            background-position: center;
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

        /* Contact Section */
        .contact-section {
            padding: 6rem 0;
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

        .contact-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Contact Info */
        .contact-info {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .info-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 2rem;
            color: #2c3e50;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            border: 1px solid #e1e5e9;
        }

        .info-item:hover {
            border-color: #e74c3c;
            transform: translateX(10px);
        }

        .info-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .info-content h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .info-content p {
            color: #666;
            line-height: 1.6;
        }

        /* Social Links */
        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .social-link {
            width: 45px;
            height: 45px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .social-link:hover {
            background: #e74c3c;
            color: white;
            transform: translateY(-3px);
            border-color: #e74c3c;
        }

        /* Contact Form */
        .contact-form {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .form-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 2rem;
            color: #2c3e50;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .required::after {
            content: " *";
            color: #e74c3c;
        }

        .form-input, .form-textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }

        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: #e74c3c;
            background: white;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }

        /* Messages */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            animation: slideDown 0.5s ease-out;
        }

        .alert-error {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            font-family: 'Poppins', sans-serif;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }

        .btn-submit:hover::before {
            left: 100%;
        }

        /* Map Section */
        .map-section {
            padding: 4rem 0;
            background: white;
        }

        .map-container {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            height: 400px;
        }

        .map-placeholder {
            background: linear-gradient(135deg, #667eea, #764ba2);
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        /* FAQ Section */
        .faq-section {
            padding: 6rem 0;
            background: #f8f9fa;
        }

        .faq-grid {
            display: grid;
            gap: 1.5rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .faq-item {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .faq-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(231, 76, 60, 0.1);
        }

        .faq-question {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .faq-answer {
            color: #666;
            line-height: 1.6;
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
            .contact-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .contact-info, .contact-form {
                padding: 2rem;
            }

            .nav-links {
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .hero-title {
                font-size: 2rem;
            }

            .info-item {
                flex-direction: column;
                text-align: center;
            }

            .social-links {
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
                <a href="index.php" class="logo">
                    <img src="../assets/images/kitchen_logo1.png" alt="Aunt Joy's Restaurant Logo" /> 
                    Aunt Joy's
                </a>
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="contact.php" style="background: #e74c3c; color: white;">Contact</a></li>
                    <li><a href="categories.php">Menu</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="contact-hero">
        <div class="container">
            <h1 class="hero-title">Get In Touch</h1>
            <p class="hero-subtitle">We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <h2 class="section-title">Contact Us</h2>
            <div class="contact-container">
                <!-- Contact Information -->
                <div class="contact-info">
                    <h2 class="info-title">Let's Talk</h2>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="info-content">
                            <h3>Our Location</h3>
                            <p>Mzuzu City Center<br>Northern Region, Malawi</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="info-content">
                            <h3>Phone Number</h3>
                            <p>+265 888 123 456<br>+265 999 987 654</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="info-content">
                            <h3>Email Address</h3>
                            <p>info@auntjoys.mw<br>orders@auntjoys.mw</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="info-content">
                            <h3>Opening Hours</h3>
                            <p>Monday - Sunday: 8:00 AM - 10:00 PM<br>Delivery until 9:30 PM</p>
                        </div>
                    </div>
                    
                    <div class="social-links">
                        <a href="#" class="social-link">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-link">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <div class="contact-form">
                    <h2 class="form-title">Send us a Message</h2>
                    
                    <?php if($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label class="form-label required">Full Name</label>
                            <input type="text" name="name" class="form-input" required 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                   placeholder="Enter your full name">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required">Email Address</label>
                            <input type="email" name="email" class="form-input" required 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                   placeholder="Enter your email">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-input" 
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                   placeholder="Enter your phone number">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required">Subject</label>
                            <input type="text" name="subject" class="form-input" required 
                                   value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>"
                                   placeholder="What is this regarding?">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required">Message</label>
                            <textarea name="message" class="form-textarea" required 
                                      placeholder="Tell us how we can help you..."><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="container">
            <h2 class="section-title">Find Us</h2>
            <div class="map-container">
                <div class="map-placeholder">
                    <div style="text-align: center;">
                        <i class="fas fa-map-marker-alt" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <div>Interactive Map</div>
                        <small>Mzuzu City Center, Malawi</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="container">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <div class="faq-grid">
                <div class="faq-item">
                    <div class="faq-question">
                        What are your delivery hours?
                    </div>
                    <div class="faq-answer">
                        We deliver from 8:00 AM to 9:30 PM daily, including weekends and holidays.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        What areas do you deliver to?
                    </div>
                    <div class="faq-answer">
                        We deliver throughout Mzuzu and surrounding areas. Delivery fees may vary based on distance.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        How can I pay for my order?
                    </div>
                    <div class="faq-answer">
                        We accept cash on delivery, mobile money (Airtel Money, TNM Mpamba), and bank transfers.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        Can I customize my order?
                    </div>
                    <div class="faq-answer">
                        Yes! You can add special instructions when placing your order, and we'll do our best to accommodate your preferences.
                    </div>
                </div>
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
                    <p><i class="fas fa-phone"></i> +265 998588582</p>
                    <p><i class="fas fa-envelope"></i> info@auntjoys.mw</p>
                </div>
                
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="index.php#categories">Menu</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 Aunt Joy's Restaurant. Celebrating good food and great moments.</p>
            </div>
        </div>
    </footer>

    <script>
        // Add some interactive features
        document.addEventListener('DOMContentLoaded', function() {
            // Add focus effects to form inputs
            const inputs = document.querySelectorAll('.form-input, .form-textarea');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });

            // Add character counter for message textarea
            const messageTextarea = document.querySelector('textarea[name="message"]');
            const charCount = document.createElement('div');
            charCount.style.cssText = 'text-align: right; font-size: 0.8rem; color: #666; margin-top: 0.5rem;';
            messageTextarea.parentElement.appendChild(charCount);

            messageTextarea.addEventListener('input', function() {
                const count = this.value.length;
                charCount.textContent = `${count} characters`;
                
                if (count > 500) {
                    charCount.style.color = '#e74c3c';
                } else {
                    charCount.style.color = '#666';
                }
            });

            // Smooth scrolling for FAQ items
            const faqItems = document.querySelectorAll('.faq-item');
            faqItems.forEach(item => {
                item.addEventListener('click', function() {
                    this.classList.toggle('active');
                });
            });
        });
    </script>
</body>
</html>