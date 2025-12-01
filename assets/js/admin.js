// assets/js/admin.js - Simplified for Admin role only
class AdminManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupDataTables();
        this.setupFormValidation();
    }

    setupEventListeners() {
        // Search functionality for admin tables
        const searchInputs = document.querySelectorAll('.search-input');
        searchInputs.forEach(input => {
            input.addEventListener('input', (e) => this.handleSearch(e));
        });

        // Bulk actions for meals and users
        const bulkActionSelect = document.querySelector('.bulk-actions');
        if (bulkActionSelect) {
            bulkActionSelect.addEventListener('change', (e) => this.handleBulkAction(e));
        }
    }

    setupDataTables() {
        // Initialize simple sorting for admin tables
        const tables = document.querySelectorAll('.admin-table');
        tables.forEach(table => {
            this.enhanceTable(table);
        });
    }

    enhanceTable(table) {
        const headers = table.querySelectorAll('th[data-sort]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                this.sortTable(table, header);
            });
        });
    }

    sortTable(table, header) {
        const columnIndex = Array.from(header.parentNode.children).indexOf(header);
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        const isAscending = !header.classList.contains('asc');
        
        // Remove existing sort classes
        table.querySelectorAll('th').forEach(th => {
            th.classList.remove('asc', 'desc');
        });

        // Sort rows
        rows.sort((a, b) => {
            const aValue = a.children[columnIndex].textContent.trim();
            const bValue = b.children[columnIndex].textContent.trim();
            
            // Handle numeric values (prices, IDs)
            if (!isNaN(aValue) && !isNaN(bValue)) {
                return isAscending ? aValue - bValue : bValue - aValue;
            }
            
            // Handle text values
            return isAscending 
                ? aValue.localeCompare(bValue)
                : bValue.localeCompare(aValue);
        });

        // Update table
        const tbody = table.querySelector('tbody');
        tbody.innerHTML = '';
        rows.forEach(row => tbody.appendChild(row));

        // Update header class
        header.classList.add(isAscending ? 'asc' : 'desc');
    }

    handleSearch(event) {
        const searchTerm = event.target.value.toLowerCase();
        const table = event.target.closest('.admin-card').querySelector('table');
        
        if (!table) return;
        
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    }

    handleBulkAction(event) {
        const action = event.target.value;
        const selectedItems = this.getSelectedItems();
        
        if (selectedItems.length === 0) {
            alert('Please select items to perform this action.');
            event.target.value = '';
            return;
        }

        if (confirm(`Are you sure you want to ${action} ${selectedItems.length} item(s)?`)) {
            this.performBulkAction(action, selectedItems);
        }
        
        event.target.value = '';
    }

    getSelectedItems() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]:checked');
        return Array.from(checkboxes).map(cb => cb.value);
    }

    async performBulkAction(action, items) {
        try {
            let endpoint = '';
            
            // Determine which bulk action to perform
            if (window.location.pathname.includes('meals.php')) {
                endpoint = '../api/admin/meals/bulk_action.php';
            } else if (window.location.pathname.includes('users.php')) {
                endpoint = '../api/admin/users/bulk_action.php';
            } else {
                this.showNotification('Bulk actions not supported on this page', 'error');
                return;
            }

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: action,
                    items: items
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showNotification(`Successfully ${action}ed ${items.length} item(s)`, 'success');
                location.reload();
            } else {
                this.showNotification(`Failed to ${action} items: ${data.message}`, 'error');
            }
        } catch (error) {
            this.showNotification('An error occurred while performing bulk action', 'error');
        }
    }

    setupFormValidation() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => this.validateForm(e));
        });

        // Real-time validation for price fields in meal forms
        const priceInputs = document.querySelectorAll('input[name="price"]');
        priceInputs.forEach(input => {
            input.addEventListener('blur', () => {
                this.validatePrice(input);
            });
        });

        // Real-time validation for email fields in user forms
        const emailInputs = document.querySelectorAll('input[type="email"]');
        emailInputs.forEach(input => {
            input.addEventListener('blur', () => {
                this.validateEmail(input);
            });
        });
    }

    validateForm(event) {
        const form = event.target;
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'This field is required');
                isValid = false;
            } else {
                this.clearFieldError(field);
            }
        });

        if (!isValid) {
            event.preventDefault();
            this.showNotification('Please fill in all required fields', 'error');
        }
    }

    validatePrice(input) {
        const value = parseFloat(input.value);
        if (value < 0) {
            this.showFieldError(input, 'Price cannot be negative');
            input.value = '0';
        } else if (value > 1000) {
            this.showFieldError(input, 'Price seems too high. Please verify.');
        } else {
            this.clearFieldError(input);
        }
    }

    validateEmail(input) {
        const email = input.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            this.showFieldError(input, 'Please enter a valid email address');
        } else {
            this.clearFieldError(input);
        }
    }

    showFieldError(field, message) {
        this.clearFieldError(field);
        
        field.style.borderColor = 'var(--danger-color)';
        
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

    clearFieldError(field) {
        field.style.borderColor = '';
        
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }

    showNotification(message, type = 'info') {
        // Remove existing notification
        const existingNotification = document.querySelector('.admin-notification');
        if (existingNotification) {
            existingNotification.remove();
        }

        // Create new notification
        const notification = document.createElement('div');
        notification.className = `admin-notification notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            color: white;
            font-weight: 600;
            z-index: 10000;
            box-shadow: var(--shadow);
            max-width: 300px;
            animation: slideInRight 0.3s ease;
        `;

        // Set background color based on type
        const colors = {
            success: 'var(--success-color)',
            error: 'var(--danger-color)',
            warning: 'var(--warning-color)',
            info: 'var(--info-color)'
        };

        notification.style.backgroundColor = colors[type] || colors.info;
        notification.textContent = message;

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    }

    // Utility function for admin API calls
    async apiCall(endpoint, options = {}) {
        try {
            const response = await fetch(endpoint, {
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });

            return await response.json();
        } catch (error) {
            console.error('API call failed:', error);
            this.showNotification('Network error occurred', 'error');
            throw error;
        }
    }
}

// Initialize admin manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.adminManager = new AdminManager();
});

// Add CSS animations for notifications and table sorting
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .admin-table th[data-sort]:hover {
        background-color: rgba(0,0,0,0.05);
    }
    
    .admin-table th.asc::after {
        content: ' ▲';
        font-size: 0.8em;
        opacity: 0.7;
    }
    
    .admin-table th.desc::after {
        content: ' ▼';
        font-size: 0.8em;
        opacity: 0.7;
    }
    
    .search-input {
        padding: 0.5rem;
        border: 2px solid var(--gray-light);
        border-radius: var(--border-radius);
        margin-bottom: 1rem;
        width: 250px;
    }
    
    .bulk-actions {
        padding: 0.5rem;
        border: 2px solid var(--gray-light);
        border-radius: var(--border-radius);
        margin-bottom: 1rem;
    }
`;
document.head.appendChild(style);