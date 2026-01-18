class CartManager {
    constructor() {
        this.cart = [];
        this.savedForLater = [];
        this.apiBaseUrl = '/projects/aunt-joy-restaurant/api';
        this.init();
    }

    async init() {
        await this.loadCart();
        await this.loadSavedForLater();
        this.renderCart();
        this.setupEventListeners();
        this.updateCartCount();
    }


   async loadCart() {
    try {
            console.log('Loading cart data...');
            // Use the apiBaseUrl
            const response = await fetch(`${this.apiBaseUrl}/cart/get.php`, {  
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include'
            });
        
        console.log('Cart API response status:', response.status);
        const data = await response.json();
        console.log('Full API response:', data);  
        
        if (data.success) {
            // Check the actual structure
            if (data.data && Array.isArray(data.data.cart)) {
                this.cart = data.data.cart;
                console.log('Cart items loaded:', this.cart.length);
            } else if (Array.isArray(data.data)) {
                
                this.cart = data.data;
                console.log('Cart items loaded (alternative structure):', this.cart.length);
            } else {
                console.error('Unexpected cart data structure:', data);
                this.cart = [];
            }
        } else {
            console.error('Cart API returned error:', data.message);
            this.cart = [];
        }
    } catch (error) {
        console.error('Failed to load cart:', error);
        this.cart = [];
    }
}

    // Load saved for later items from localStorage
    loadSavedForLater() {
        try {
            const saved = localStorage.getItem('savedForLater');
            this.savedForLater = saved ? JSON.parse(saved) : [];
            console.log('Saved for later loaded:', this.savedForLater);
        } catch (error) {
            console.error('Failed to load saved for later:', error);
            this.savedForLater = [];
        }
    }

    // Save saved for later items to localStorage
    saveSavedForLater() {
        try {
            localStorage.setItem('savedForLater', JSON.stringify(this.savedForLater));
        } catch (error) {
            console.error('Failed to save saved for later:', error);
        }
    }

    renderCart() {
        const container = document.getElementById('cart-items-container');
        const emptyCart = document.getElementById('empty-cart');
        const orderSummary = document.querySelector('.order-summary');
        const itemsCount = document.getElementById('cart-items-count');
        const saveLaterSection = document.getElementById('save-later-section');
        const savedItemsContainer = document.getElementById('saved-items-container');

        console.log('Rendering cart with', this.cart.length, 'items');

        if (!container) {
            console.error('Cart container not found!');
            return;
        }

        if (this.cart.length === 0) {
            container.innerHTML = `
                <div class="empty-cart" id="empty-cart">
                    <div class="empty-cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>Your Aunt Joy's cart is empty</h3>
                    <p>Add some delicious meals from our menu to get started!</p>
                    <a href="index.php#categories" class="continue-shopping">
                        <i class="fas fa-utensils"></i>
                        Continue Shopping
                    </a>
                </div>
            `;
            
            if (orderSummary) orderSummary.style.display = 'block';
            if (saveLaterSection) saveLaterSection.style.display = 'none';
            
            this.updateOrderSummary();
            return;
        }

        // Render cart items
        container.innerHTML = this.cart.map(item => {
            const quantity = item.quantity || 1;
            const price = parseFloat(item.price) || 0;
            const subtotal = price * quantity;
            
            return `
            <div class="cart-item" data-meal-id="${item.meal_id}">
                <div class="item-image">
                    ${item.image_url ? 
                        `<img src="${item.image_url}" alt="${item.meal_name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">` : 
                        '<i class="fas fa-utensils"></i>'
                    }
                </div>
                <div class="item-details">
                    <div class="item-name">
                        <a href="#" onclick="event.preventDefault(); menuManager?.showCategoryMeals(${item.category_id});">
                            ${item.meal_name || 'Unknown Meal'}
                        </a>
                    </div>
                    <div class="item-availability">
                        <i class="fas fa-check-circle"></i> In Stock
                    </div>
                    <div class="item-actions">
                        <button class="action-btn delete-btn" onclick="cartManager.removeItem(${item.meal_id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                        <button class="action-btn save-later-btn" onclick="cartManager.saveForLater(${item.meal_id})">
                            <i class="fas fa-bookmark"></i> Save for later
                        </button>
                    </div>
                    <div class="quantity-section">
                        <span class="quantity-label">Qty:</span>
                        <select class="quantity-select" onchange="cartManager.updateQuantity(${item.meal_id}, this.value)">
                            ${Array.from({length: 10}, (_, i) => 
                                `<option value="${i + 1}" ${i + 1 === quantity ? 'selected' : ''}>${i + 1}</option>`
                            ).join('')}
                        </select>
                    </div>
                </div>
                <div class="item-price-section">
                    <div class="item-price">MK ${price.toFixed(2)}</div>
                    <div class="item-subtotal">
                        Subtotal: MK ${subtotal.toFixed(2)}
                    </div>
                </div>
            </div>
            `;
        }).join('');

        // Update items count display
        const totalItems = this.cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
        if (itemsCount) {
            itemsCount.textContent = `${totalItems} ${totalItems === 1 ? 'item' : 'items'}`;
        }

        // Render saved for later section
        this.renderSavedForLater();

        this.updateOrderSummary();
    }

    renderSavedForLater() {
        const saveLaterSection = document.getElementById('save-later-section');
        const savedItemsContainer = document.getElementById('saved-items-container');
        const savedHeader = saveLaterSection?.querySelector('.cart-section-header');

        if (!saveLaterSection || !savedItemsContainer) return;

        if (this.savedForLater.length === 0) {
            saveLaterSection.style.display = 'none';
            return;
        }

        saveLaterSection.style.display = 'block';
        
        if (savedHeader) {
            savedHeader.textContent = `Saved for Later (${this.savedForLater.length} items)`;
        }

        savedItemsContainer.innerHTML = this.savedForLater.map(item => {
            const price = parseFloat(item.price) || 0;
            
            return `
            <div class="saved-item" data-meal-id="${item.meal_id}">
                <div class="item-image" style="width: 80px; height: 60px;">
                    ${item.image_url ? 
                        `<img src="${item.image_url}" alt="${item.meal_name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">` : 
                        '<i class="fas fa-utensils"></i>'
                    }
                </div>
                <div class="item-details" style="flex: 1;">
                    <div class="item-name" style="font-size: 1rem;">
                        ${item.meal_name || 'Unknown Meal'}
                    </div>
                    <div class="item-price" style="color: #b12704; font-weight: 600;">
                        MK ${price.toFixed(2)}
                    </div>
                </div>
                <div class="saved-actions" style="display: flex; gap: 0.5rem;">
                    <button class="move-to-cart-btn" onclick="cartManager.moveToCart(${item.meal_id})">
                        <i class="fas fa-cart-plus"></i> Move to Cart
                    </button>
                    <button class="action-btn delete-btn" onclick="cartManager.removeFromSaved(${item.meal_id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            `;
        }).join('');
    }

    // Save item for later
    async saveForLater(mealId) {
        try {
            // Find the item in cart
            const itemIndex = this.cart.findIndex(item => item.meal_id == mealId);
            if (itemIndex === -1) {
                this.showNotification('Item not found in cart', 'error');
                return;
            }

            const item = this.cart[itemIndex];
            
            // Remove from cart via API
            const response = await fetch(`${this.apiBaseUrl}/cart/update.php`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    meal_id: parseInt(mealId),
                    quantity: 0
                })
            });

            const data = await response.json();
            
            if (data.success) {
                // Remove from local cart
                this.cart.splice(itemIndex, 1);
                
                // Add to saved for later (avoid duplicates)
                const existingIndex = this.savedForLater.findIndex(savedItem => savedItem.meal_id == mealId);
                if (existingIndex === -1) {
                    this.savedForLater.push({
                        ...item,
                        saved_at: new Date().toISOString()
                    });
                    this.saveSavedForLater();
                }
                
                // Update UI
                this.renderCart();
                this.updateCartCount();
                this.showNotification('Item saved for later', 'success');
            } else {
                this.showNotification('Failed to save item: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Failed to save for later:', error);
            this.showNotification('Failed to save item. Please try again.', 'error');
        }
    }

    // Move item from saved to cart
    async moveToCart(mealId) {
        try {
            // Find the item in saved for later
            const itemIndex = this.savedForLater.findIndex(item => item.meal_id == mealId);
            if (itemIndex === -1) {
                this.showNotification('Item not found in saved items', 'error');
                return;
            }

            const item = this.savedForLater[itemIndex];
            
            // Add to cart via API
            const response = await fetch(`${this.apiBaseUrl}/cart/add.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    meal_id: item.meal_id,
                    quantity: 1
                })
            });

            const data = await response.json();
            
            if (data.success) {
                // Remove from saved for later
                this.savedForLater.splice(itemIndex, 1);
                this.saveSavedForLater();
                
                // Update local cart from response
                if (data.data.cart) {
                    this.cart = data.data.cart;
                } else {
                    await this.loadCart();
                }
                
                // Update UI
                this.renderCart();
                this.updateCartCount();
                this.showNotification('Item moved to cart', 'success');
            } else {
                this.showNotification('Failed to move item: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Failed to move to cart:', error);
            this.showNotification('Failed to move item. Please try again.', 'error');
        }
    }

    // Remove item from saved for later
    removeFromSaved(mealId) {
        if (!confirm('Remove this item from saved for later?')) {
            return;
        }

        const itemIndex = this.savedForLater.findIndex(item => item.meal_id == mealId);
        if (itemIndex === -1) return;

        this.savedForLater.splice(itemIndex, 1);
        this.saveSavedForLater();
        this.renderSavedForLater();
        this.showNotification('Item removed from saved', 'success');
    }

    // Update the existing removeItem method to be more specific
    async removeItem(mealId) {
        if (!confirm('Are you sure you want to remove this item from your cart?')) {
            return;
        }

        try {
            console.log('Removing meal from cart:', mealId);
            
            const response = await fetch(`${this.apiBaseUrl}/cart/update.php`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    meal_id: parseInt(mealId),
                    quantity: 0
                })
            });

            const data = await response.json();
            console.log('Remove item response:', data);
            
            if (data.success) {
                if (data.data.cart) {
                    this.cart = data.data.cart;
                } else {
                    await this.loadCart();
                }
                
                this.renderCart();
                this.updateCartCount();
                this.showNotification('Item removed from cart', 'success');
            } else {
                const errorMsg = data.message || 'Failed to remove item';
                console.error('Remove item failed:', errorMsg);
                this.showNotification(errorMsg, 'error');
                
                await this.loadCart();
                this.renderCart();
            }
        } catch (error) {
            console.error('Failed to remove item:', error);
            this.showNotification('Failed to remove item. Please try again.', 'error');
        }
    }

   

    updateOrderSummary() {
        const subtotal = this.cart.reduce((sum, item) => {
            const quantity = item.quantity || 1;
            const price = parseFloat(item.price) || 0;
            return sum + (price * quantity);
        }, 0);
        
        const deliveryFee = 1500;
        const tax = subtotal * 0.1; 
        const total = subtotal + deliveryFee + tax;
        const totalItems = this.cart.reduce((sum, item) => sum + (item.quantity || 1), 0);

        // Update summary elements
        const itemsCountElem = document.getElementById('summary-items-count');
        const subtotalElem = document.getElementById('summary-subtotal');
        const taxElem = document.getElementById('summary-tax');
        const totalElem = document.getElementById('summary-total');

        if (itemsCountElem) itemsCountElem.textContent = totalItems;
        if (subtotalElem) subtotalElem.textContent = `MK ${subtotal.toFixed(2)}`;
        if (taxElem) taxElem.textContent = `MK ${tax.toFixed(2)}`;
        if (totalElem) totalElem.textContent = `MK ${total.toFixed(2)}`;

        const checkoutBtn = document.getElementById('checkout-btn');
        if (checkoutBtn) {
            checkoutBtn.disabled = this.cart.length === 0;
        }

        console.log('Order summary updated:', { subtotal, tax, total, totalItems });
    }

    async updateQuantity(mealId, quantity) {
        try {
            console.log('Updating quantity for meal', mealId, 'to', quantity);
            
            const response = await fetch(`${this.apiBaseUrl}/cart/update.php`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    meal_id: parseInt(mealId),
                    quantity: parseInt(quantity)
                })
            });

            const data = await response.json();
            console.log('Update quantity response:', data);
            
            if (data.success) {
                if (data.data.cart) {
                    this.cart = data.data.cart;
                } else {
                    await this.loadCart();
                }
                
                this.renderCart();
                this.updateCartCount();
                this.showNotification('Quantity updated successfully', 'success');
            } else {
                const errorMsg = data.message || 'Failed to update quantity';
                console.error('Update quantity failed:', errorMsg);
                this.showNotification(errorMsg, 'error');
                
                await this.loadCart();
                this.renderCart();
            }
        } catch (error) {
            console.error('Failed to update quantity:', error);
            this.showNotification('Failed to update quantity. Please try again.', 'error');
        }
    }

    updateCartCount() {
        const totalItems = this.cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
        const cartCountElements = document.querySelectorAll('.cart-count');
        
        console.log('Updating cart count to:', totalItems);
        
        cartCountElements.forEach(element => {
            element.textContent = totalItems;
            if (totalItems === 0) {
                element.style.display = 'none';
            } else {
                element.style.display = 'inline-flex';
            }
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
            padding: 12px 20px;
            border-radius: 5px;
            z-index: 1000;
            animation: slideIn 0.3s ease;
            font-size: 14px;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }

    setupEventListeners() {
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.loadCart();
            }
        });
    }

    async refreshCart() {
        await this.loadCart();
        await this.loadSavedForLater();
        this.renderCart();
        this.updateCartCount();
    }

     
    
}


// Initialize cart manager when page loads
document.addEventListener('DOMContentLoaded', () => {
    window.cartManager = new CartManager();
    
    window.addEventListener('pageshow', (event) => {
        if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
            window.cartManager.refreshCart();
        }
    });
});