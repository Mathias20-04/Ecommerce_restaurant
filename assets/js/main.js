// assets/js/main.js
class App {
    constructor() {
        this.currentUser = null;
        this.cart = [];
        this.isAuthenticated = false;
        // Use relative path for API
        this.apiBaseUrl = '/projects/aunt-joy-restaurant/api';
        this.init();
    }

    init() {
        // Check auth status first
        this.checkAuthStatus().then(() => {
            this.updateUI();
            this.setupEventListeners();
            
            // Debug
            setTimeout(() => {
                this.debugCart();
                this.debugSession();
            }, 1000);
        });
    }

    // Debug session
    async debugSession() {
        try {
            const response = await fetch(this.apiBaseUrl + '/auth/session-check.php', {
                credentials: 'include'
            });
            const data = await response.json();
            console.log('🔍 Session Debug:', data);
        } catch (error) {
            console.error('Session debug error:', error);
        }
    }

    // Enhanced API Call with better session handling
    async apiCall(endpoint, method = 'GET', data = null, options = {}) {
        console.log(`📡 API Call: ${endpoint}`);
        
        const url = this.apiBaseUrl + endpoint;
        
        const config = {
            method: method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'include', // IMPORTANT: This sends cookies
            ...options
        };
        
        // Add body for POST/PUT requests
        if (data && (method === 'POST' || method === 'PUT')) {
            config.body = JSON.stringify(data);
        }
        
        try {
            const response = await fetch(url, config);
            console.log(`📊 Response status for ${endpoint}: ${response.status}`);
            
            // Handle 401 specifically
            if (response.status === 401) {
                console.warn('⚠️ 401 Unauthorized - Session may have expired');
                this.currentUser = null;
                localStorage.removeItem('user');
                this.updateUI();
                
                // Don't throw error for auth check endpoints
                if (endpoint.includes('/auth/')) {
                    const result = await response.json();
                    return result;
                }
                
                throw new Error('Session expired. Please login again.');
            }
            
            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || `HTTP ${response.status}`);
            }
            
            return result;
        } catch (error) {
            console.error(`❌ API Call failed for ${endpoint}:`, error);
            throw error;
        }
    }

    // Authentication methods
    async checkAuthStatus() {
    console.log('🔍 Checking auth status...');
    
    try {
        // First check session via PHP
        const sessionResponse = await fetch(this.apiBaseUrl + '/auth/check.php', {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        const sessionResult = await sessionResponse.json();
        console.log('Session check result:', sessionResult);
        
        if (sessionResult.success && sessionResult.data && sessionResult.data.user) {
            this.currentUser = sessionResult.data.user;
            this.isAuthenticated = true;
            localStorage.setItem('user', JSON.stringify(sessionResult.data.user));
            console.log('✅ User authenticated via session:', this.currentUser);
            
            // Add dashboard link based on role
            this.addDashboardLink();
            
            this.updateAuthUI();
            return true;
        } else {
            // Clear everything
            this.currentUser = null;
            this.isAuthenticated = false;
            localStorage.removeItem('user');
            console.log('❌ Not authenticated');
            this.updateAuthUI();
            return false;
        }
    } catch (error) {
        console.error('Auth check failed:', error);
        this.currentUser = null;
        this.isAuthenticated = false;
        this.updateAuthUI();
        return false;
    }
}

   // Update login method:
async login(username, password) {
    console.log('🔐 Attempting login...');
    try {
        const response = await this.apiCall('/auth/login.php', 'POST', { username, password });
        
        if (response.success && response.data && response.data.user) {
            this.currentUser = response.data.user;
            this.isAuthenticated = true;
            console.log('✅ Login successful:', this.currentUser);
            
            // Add dashboard link based on role
            this.addDashboardLink();
            
            this.updateUI();
            return { success: true, user: response.data.user };
        } else {
            throw new Error(response.message || 'Login failed');
        }
    } catch (error) {
        console.error('❌ Login failed:', error);
        return { success: false, message: error.message };
    }
    }  
    
    
    async logout() {
        console.log('🚪 Logging out...');
        try {
            await this.apiCall('/auth/logout.php', 'POST', {});
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            this.currentUser = null;
            this.isAuthenticated = false;
            this.cart = [];
            localStorage.removeItem('cart');
            localStorage.removeItem('user');
            this.updateUI();
            window.location.href = 'index.php';
        }
    }

    // Update addToCart to handle guest users better
    async addToCart(meal) {
        if (!this.isAuthenticated) {
            this.showNotification('Please login to add items to cart', 'warning');
            // Redirect to login after 2 seconds
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 2000);
            return { success: false, message: 'Login required' };
        }
        
        try {
            console.log('🛒 Adding to cart:', meal);
            
            const data = await this.apiCall('/cart/add.php', 'POST', {
                meal_id: meal.meal_id,
                quantity: 1
            });
            
            this.showNotification('Added to cart!', 'success');
            
            // Refresh cart data
            await this.loadCartFromServer();
            
            return { success: true };
        } catch (error) {
            console.error('Failed to add to cart:', error);
            this.showNotification('Failed to add item to cart', 'error');
            return { success: false, message: error.message };
        }
    }

    async removeFromCart(mealId) {
        try {
            await this.apiCall('/cart/update.php', 'PUT', {
                meal_id: mealId,
                quantity: 0
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
            await this.apiCall('/cart/update.php', 'PUT', {
                meal_id: mealId,
                quantity: quantity
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
        if (!this.isAuthenticated) {
            this.cart = [];
            this.updateCartUI();
            return;
        }
        
        try {
            const data = await this.apiCall('/cart/get.php');
            
            if (data.success) {
                this.cart = data.data?.cart || data.data?.items || data.cart || [];
                console.log('🛒 Cart loaded:', this.cart);
                
                // Save to localStorage for persistence
                localStorage.setItem('cart', JSON.stringify(this.cart));
                
                this.updateCartUI();
            } else {
                console.error('Failed to load cart:', data.message);
                this.cart = [];
                this.updateCartUI();
            }
        } catch (error) {
            console.error('Failed to load cart from server:', error);
            this.cart = [];
            this.updateCartUI();
        }
    }

    getCartTotal() {
        if (!Array.isArray(this.cart)) return 0;
        return this.cart.reduce((total, item) => total + ((item.price || 0) * (item.quantity || 1)), 0);
    }

    getCartCount() {
        if (!Array.isArray(this.cart)) return 0;
        return this.cart.reduce((total, item) => total + (item.quantity || 1), 0);
    }

    saveCart() {
        localStorage.setItem('cart', JSON.stringify(this.cart));
    }

    // UI methods
    updateUI() {
        this.updateAuthUI();
        this.updateCartUI();
    }
    
    async debugCart() {
        console.log('=== CART DEBUG INFO ===');
        console.log('Current user:', this.currentUser);
        console.log('Is authenticated:', this.isAuthenticated);
        console.log('Local cart:', this.cart);
        console.log('Cart count:', this.getCartCount());
        
        try {
            const data = await this.apiCall('/cart/get.php');
            console.log('Server cart response:', data);
        } catch (error) {
            console.log('Debug cart error:', error.message);
        }
        console.log('========================');
    }
    
    updateAuthUI() {
        const loginBtn = document.getElementById('login-btn');
        const logoutBtn = document.getElementById('logout-btn');
        const userGreeting = document.getElementById('user-greeting');
        const userSection = document.getElementById('user-section');
        
        if (this.isAuthenticated && this.currentUser) {
            // User is logged in
            if (loginBtn) loginBtn.style.display = 'none';
            if (logoutBtn) logoutBtn.style.display = 'block';
            if (userGreeting) {
                userGreeting.textContent = `Welcome, ${this.currentUser.name || this.currentUser.email}`;
                userGreeting.style.display = 'block';
            }
            if (userSection) userSection.style.display = 'block';
            
            // Update user avatar in dropdown
            const userAvatar = document.querySelector('.user-avatar');
            if (userAvatar && this.currentUser.name) {
                userAvatar.textContent = this.currentUser.name.charAt(0).toUpperCase();
            }
        } else {
            // User is not logged in
            if (loginBtn) loginBtn.style.display = 'block';
            if (logoutBtn) logoutBtn.style.display = 'none';
            if (userGreeting) userGreeting.style.display = 'none';
            if (userSection) userSection.style.display = 'none';
        }
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
        const cartCount = this.getCartCount();
        const cartBadge = document.getElementById('cart-count');
        
        if (cartBadge) {
            cartBadge.textContent = cartCount;
            cartBadge.style.display = cartCount > 0 ? 'inline-block' : 'none';
        }
        
        // Also update any other cart displays
        const cartTotalEl = document.getElementById('cart-total');
        if (cartTotalEl) {
            const total = this.getCartTotal();
            cartTotalEl.textContent = `MK${total.toFixed(2)}`;
        }
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
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }

    setupEventListeners() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('logout-btn') || 
                e.target.closest('.logout-btn')) {
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