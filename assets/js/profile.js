// assets/js/profile.js
class ProfileManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupFormValidation();
    }

    setupEventListeners() {
        // Real-time password validation
        const newPassword = document.querySelector('input[name="new_password"]');
        const confirmPassword = document.querySelector('input[name="confirm_password"]');
        
        if (newPassword && confirmPassword) {
            newPassword.addEventListener('input', () => this.validatePasswordMatch());
            confirmPassword.addEventListener('input', () => this.validatePasswordMatch());
        }

        // Form submission handling
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        });
    }

    setupFormValidation() {
        // Add basic form validation
        const requiredFields = document.querySelectorAll('input[required], textarea[required]');
        requiredFields.forEach(field => {
            field.addEventListener('blur', () => {
                this.validateField(field);
            });
        });
    }

    validateField(field) {
        if (!field.value.trim()) {
            field.style.borderColor = 'var(--danger-color)';
        } else {
            field.style.borderColor = 'var(--success-color)';
        }
    }

    validatePasswordMatch() {
        const newPassword = document.querySelector('input[name="new_password"]');
        const confirmPassword = document.querySelector('input[name="confirm_password"]');
        
        if (!newPassword || !confirmPassword) return;

        if (confirmPassword.value && newPassword.value !== confirmPassword.value) {
            confirmPassword.style.borderColor = 'var(--danger-color)';
        } else {
            confirmPassword.style.borderColor = 'var(--success-color)';
        }

        // Validate password length
        if (newPassword.value && newPassword.value.length < 6) {
            newPassword.style.borderColor = 'var(--danger-color)';
        } else if (newPassword.value) {
            newPassword.style.borderColor = 'var(--success-color)';
        }
    }

    handleFormSubmit(e) {
        const form = e.target;
        const submitButton = form.querySelector('button[type="submit"]');
        
        // Add loading state
        if (submitButton) {
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = 'Please wait...';

            // Re-enable after 5 seconds in case of error
            setTimeout(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }, 5000);
        }

        // Additional validation for password form
        if (form.querySelector('input[name="change_password"]')) {
            if (!this.validatePasswordForm()) {
                e.preventDefault();
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = 'Change Password';
                }
            }
        }
    }

    validatePasswordForm() {
        const currentPassword = document.querySelector('input[name="current_password"]');
        const newPassword = document.querySelector('input[name="new_password"]');
        const confirmPassword = document.querySelector('input[name="confirm_password"]');

        let isValid = true;

        // Reset styles
        [currentPassword, newPassword, confirmPassword].forEach(field => {
            field.style.borderColor = '';
        });

        // Validate new password length
        if (newPassword.value.length < 6) {
            newPassword.style.borderColor = 'var(--danger-color)';
            this.showFieldError(newPassword, 'Password must be at least 6 characters');
            isValid = false;
        }

        // Validate password match
        if (newPassword.value !== confirmPassword.value) {
            confirmPassword.style.borderColor = 'var(--danger-color)';
            this.showFieldError(confirmPassword, 'Passwords do not match');
            isValid = false;
        }

        return isValid;
    }

    showFieldError(field, message) {
        // Remove existing error
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }

        // Create error message
        const errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        errorElement.style.cssText = `
            color: var(--danger-color);
            font-size: 0.8rem;
            margin-top: 0.25rem;
        `;
        errorElement.textContent = message;

        field.parentNode.appendChild(errorElement);
    }
}

// Initialize profile manager
document.addEventListener('DOMContentLoaded', () => {
    window.profileManager = new ProfileManager();
});