// assets/js/auth.js
class AuthManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.validateForms();
    }

    setupEventListeners() {
        // Real-time password confirmation validation
        const passwordInputs = document.querySelectorAll('input[type="password"]');
        passwordInputs.forEach(input => {
            input.addEventListener('input', this.validatePasswordMatch.bind(this));
        });

        // Form submission enhancements
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', this.handleFormSubmit.bind(this));
        });
    }

    validatePasswordMatch() {
        const password = document.querySelector('input[name="password"]');
        const confirmPassword = document.querySelector('input[name="confirm_password"]');
        
        if (!password || !confirmPassword) return;

        if (confirmPassword.value && password.value !== confirmPassword.value) {
            confirmPassword.style.borderColor = '#e74c3c';
        } else {
            confirmPassword.style.borderColor = '#2ecc71';
        }
    }

    validateForms() {
        // Add basic form validation
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            const inputs = form.querySelectorAll('input[required]');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.style.borderColor = '#e74c3c';
                    } else {
                        this.style.borderColor = '#2ecc71';
                    }
                });
            });
        });
    }

    handleFormSubmit(e) {
        const form = e.target;
        const submitButton = form.querySelector('button[type="submit"]');
        
        // Add loading state
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = 'Please wait...';
            
            // Re-enable after 3 seconds in case of error
            setTimeout(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = submitButton.getAttribute('data-original-text') || 'Submit';
            }, 3000);
        }
    }

    // Utility method to show notifications
    static showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `auth-notification ${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#4CAF50' : '#f44336'};
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 1000;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
}

// Initialize auth manager
document.addEventListener('DOMContentLoaded', () => {
    window.authManager = new AuthManager();
});