// assets/js/main.js
class App {
    constructor() {
        this.currentUser = null;
        this.cart = JSON.parse(localStorage.getItem('cart')) || [];
        this.init();
    }

    init() {
        this.checkAuthStatus();
        this.updateUI();
        this.setupEventListeners();
        // for temporal debugging
         setTimeout(() => {
        this.debugCart();
    }, 1000);
    }

    // API Call helper 
    async apiCall(endpoint, options = {}) {
        const baseUrl = 'http://localhost:8000/projects/aunt-joy-restaurant/api';
        const url = baseUrl + endpoint;
        
        const config = {
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };

        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'API request failed');
            }
            
            return data;
        } catch (error) {
            console.error('API Call failed:', error);
            throw error;
        }
    }

    // Authentication methods
    async checkAuthStatus() {
        try {
            const data = await this.apiCall('/user/profile.php');
            this.currentUser = data.user;
            this.updateUI();
        } catch (error) {
            this.currentUser = null;
            console.log('Not logged in:', error.message);
        }
    }

    async login(username, password) {
        try {
            const data = await this.apiCall('/auth/login.php', {
                method: 'POST',
                body: JSON.stringify({ username, password })
            });
            
            this.currentUser = data.user;
            this.updateUI();
            return { success: true, user: data.user };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    async logout() {
        try {
            await this.apiCall('/auth/logout.php', { method: 'POST' });
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            this.currentUser = null;
            this.cart = [];
            localStorage.removeItem('cart');
            this.updateUI();
            window.location.href = 'index.php';
        }
    }
    

  async addToCart(meal) {
    try {
        console.log('=== ADD TO CART START ===');
        console.log('Meal being added:', meal);
        
        const response = await fetch('/projects/aunt-joy-restaurant/api/cart/add.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({
                meal_id: meal.meal_id,
                quantity: 1
            })
        });

        console.log('Add to cart response status:', response.status);
        
        const data = await response.json();
        console.log('Add to cart response data:', data);
        
        if (data.success) {
            this.showNotification('Added to cart!', 'success');
            
            
            if (data.data.cart) {
                this.cart = data.data.cart;
                this.saveCart();
                this.updateCartUI();
            } else {
                // Fallback: reload cart from server
                await this.loadCartFromServer();
            }
            
            // Refresh cart page if open
            if (window.cartManager) {
                await window.cartManager.refreshCart();
            }
            
            return { success: true };
        } else {
            const errorMsg = data.message || 'Failed to add item to cart';
            console.error('Add to cart failed:', errorMsg);
            this.showNotification(errorMsg, 'error');
            return { success: false, message: errorMsg };
        }
    } catch (error) {
        console.error('Failed to add to cart:', error);
        const errorMsg = 'Failed to add to cart. Please try again.';
        this.showNotification(errorMsg, 'error');
        return { success: false, message: errorMsg };
    }
 }

    async removeFromCart(mealId) {
        try {
            await this.apiCall('/cart/update.php', {
                method: 'PUT',
                body: JSON.stringify({
                    meal_id: mealId,
                    quantity: 0
                })
            });
            
            this.cart = this.cart.filter(item => item.meal_id !== mealId);
            this.saveCart();
            this.updateCartUI();
            
        } catch (error) {
            console.error('Failed to remove from cart:', error);
        }
    }

    async updateCartQuantity(mealId, quantity) {
        try {
            await this.apiCall('/cart/update.php', {
                method: 'PUT',
                body: JSON.stringify({
                    meal_id: mealId,
                    quantity: quantity
                })
            });
            
            const item = this.cart.find(item => item.meal_id === mealId);
            if (item) {
                if (quantity <= 0) {
                    this.removeFromCart(mealId);
                } else {
                    item.quantity = quantity;
                    this.saveCart();
                    this.updateCartUI();
                }
            }
            
        } catch (error) {
            console.error('Failed to update cart quantity:', error);
        }
    }

    async loadCartFromServer() {
    try {
        const response = await fetch('/projects/aunt-joy-restaurant/api/cart/get.php', {
            credentials: 'include'
        });
        const data = await response.json();
        
        console.log('Cart data from server:', data);
        
        if (data.success) {
            // FIX: Changed from data.data.items to data.data.cart
            this.cart = data.data.cart || []; 
            this.saveCart();
            this.updateCartUI();
            console.log('Cart loaded successfully:', this.cart);
        } else {
            console.error('Failed to load cart:', data.message);
            this.cart = [];
            this.saveCart();
            this.updateCartUI();
        }
    } catch (error) {
        console.error('Failed to load cart from server:', error);
        this.cart = [];
        this.saveCart();
        this.updateCartUI();
    }
    }

    getCartTotal() {
        return this.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    }

    getCartCount() {
    if (!this.cart || !Array.isArray(this.cart)) {
        return 0;
    }
    return this.cart.reduce((count, item) => count + (item.quantity || 1), 0);
     }

    saveCart() {
        localStorage.setItem('cart', JSON.stringify(this.cart));
    }

    // UI methods
    updateUI() {
        this.updateAuthUI();
        this.updateCartUI();
    }
    // for cart debugging
    async debugCart() {
    console.log('=== CART DEBUG INFO ===');
    console.log('Current user:', this.currentUser);
    console.log('Local cart:', this.cart);
    console.log('Cart count:', this.getCartCount());
    
    try {
        const response = await fetch('/projects/aunt-joy-restaurant/api/cart/get.php', {
            credentials: 'include'
        });
        const data = await response.json();
        console.log('Server cart response:', data);
    } catch (error) {
        console.error('Debug cart error:', error);
    }
    console.log('========================');
}

  
updateAuthUI() {
    const authElements = document.querySelectorAll('.auth-required');
    const guestElements = document.querySelectorAll('.guest-required');
    const userDisplayName = document.getElementById('user-display-name');

    if (this.currentUser) {
        authElements.forEach(el => el.style.display = 'block');
        guestElements.forEach(el => el.style.display = 'none');
        
        if (userDisplayName) {
            // Display first name or username
            const displayName = this.currentUser.full_name ? 
                this.currentUser.full_name.split(' ')[0] : 
                this.currentUser.username;
            userDisplayName.textContent = displayName;
        }
        
        // Add dashboard link if user is admin, manager, or sales
        this.addDashboardLink();
        
        // Load server cart when user is authenticated
        this.loadCartFromServer();
    } else {
        authElements.forEach(el => el.style.display = 'none');
        guestElements.forEach(el => el.style.display = 'block');
        
        if (userDisplayName) {
            userDisplayName.textContent = '';
        }
    }
    
    this.updateCartUI();
}

    addDashboardLink() {
        // Check user role and add appropriate dashboard link
        const dropdownMenu = document.querySelector('.dropdown-menu');
        if (!dropdownMenu) return;
        
        // Remove existing dashboard link if it exists
        const existingDashboardLink = dropdownMenu.querySelector('.dashboard-link');
        if (existingDashboardLink) {
            existingDashboardLink.remove();
        }
        
        // Determine dashboard URL based on role
        let dashboardUrl = null;
        const role = this.currentUser.role;
        
        if (role === 'admin') {
            dashboardUrl = 'admin/dashboard.php';
        } else if (role === 'manager') {
            dashboardUrl = 'manager/dashboard.php';
        } else if (role === 'sales') {
            dashboardUrl = 'sales/dashboard.php';
        }
        
        // If dashboard URL exists, add link before logout
        if (dashboardUrl) {
            const divider = dropdownMenu.querySelector('.dropdown-divider');
            const dashboardLink = document.createElement('a');
            dashboardLink.href = dashboardUrl;
            dashboardLink.className = 'dropdown-item dashboard-link';
            dashboardLink.innerHTML = '<i class="fas fa-chart-line"></i> Dashboard';
            
            if (divider) {
                divider.parentNode.insertBefore(dashboardLink, divider);
            } else {
                dropdownMenu.insertBefore(dashboardLink, dropdownMenu.lastChild);
            }
        }
    }

    updateCartUI() {
        const cartCountElements = document.querySelectorAll('.cart-count');
        const count = this.getCartCount();
        
        cartCountElements.forEach(element => {
            element.textContent = count;
        });
    }

    showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
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
        }, 3000);
    }

    setupEventListeners() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('logout-btn')) {
                e.preventDefault();
                this.logout();
            }
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.hero-search')) {
                const searchResults = document.getElementById('search-results');
                if (searchResults) {
                    searchResults.classList.remove('active');
                }
            }
        });
    }
}


// Initialize the app
document.addEventListener('DOMContentLoaded', () => {
    window.app = new App();
});