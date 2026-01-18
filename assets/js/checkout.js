// assets/js/checkout.js - FIXED API PATHS
class CheckoutManager {
    constructor() {
        this.cart = [];
        this.apiBaseUrl = '/projects/aunt-joy-restaurant/api';
        this.init();
    }

    async init() {
    await this.loadCart(); // Wait for cart to load
    if (this.cart.length > 0) {
        this.renderOrderSummary();
        this.setupEventListeners();
        this.validateForm();
    }
  }

 async loadCart() {
    try {
        console.log('Loading cart for checkout...');
        
        // Fetch cart from server API instead of localStorage
        const response = await fetch('/projects/aunt-joy-restaurant/api/cart/get.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Checkout cart data:', data);
        
        if (data.success && data.data && Array.isArray(data.data.cart)) {
            this.cart = data.data.cart;
            console.log('Cart loaded for checkout:', this.cart.length, 'items');
            
            if (this.cart.length === 0) {
                this.showNotification('Your cart is empty', 'error');
                setTimeout(() => {
                    window.location.href = '/projects/aunt-joy-restaurant/pages/cart.php';
                }, 2000);
                return;
            }
        } else {
            console.error('Invalid cart data structure:', data);
            this.cart = [];
            throw new Error('Failed to load cart');
        }
    } catch (error) {
        console.error('Failed to load cart for checkout:', error);
        this.showNotification('Failed to load cart. Please try again.', 'error');
        this.cart = [];
        
        // Redirect to cart page if empty
        setTimeout(() => {
            window.location.href = '/projects/aunt-joy-restaurant/pages/cart.php';
        }, 2000);
    }
}

  renderOrderSummary() {
    const orderItemsContainer = document.getElementById('order-items');
    const subtotal = this.cart.reduce((total, item) => total + (parseFloat(item.price) * item.quantity), 0);
    const deliveryFee = 1500;
    const total = subtotal + deliveryFee;

    // Render order items - FIX item.name to item.meal_name
    orderItemsContainer.innerHTML = this.cart.map(item => `
        <div class="order-item">
            <div class="item-info">
                <div class="item-name">${item.meal_name}</div>
                <div class="item-quantity">Qty: ${item.quantity}</div>
            </div>
            <div class="item-price">
                MK ${(parseFloat(item.price) * item.quantity).toFixed(2)}
            </div>
        </div>
    `).join('');

    // Update totals
    document.getElementById('order-subtotal').textContent = `MK ${subtotal.toFixed(2)}`;
    document.getElementById('order-delivery').textContent = `MK ${deliveryFee.toFixed(2)}`;
    document.getElementById('order-total').textContent = `MK ${total.toFixed(2)}`;
}

    setupEventListeners() {
        // Payment method selection
        document.getElementById('cash-payment').addEventListener('click', () => {
            this.selectPaymentMethod('cash');
        });

        document.getElementById('mobile-payment').addEventListener('click', () => {
            this.selectPaymentMethod('mobile');
        });

        // Mobile payment details validation
        document.getElementById('mobile-number').addEventListener('input', () => {
            this.validateMobileMoneyNumber();
            this.validateForm();
        });

        document.getElementById('mobile-provider').addEventListener('change', () => {
            this.validateMobileMoneyNumber();
            this.validateForm();
        });

        // Form validation on input
        const formInputs = ['phone', 'delivery-address'];
        formInputs.forEach(inputId => {
            document.getElementById(inputId).addEventListener('input', () => {
                this.validateForm();
            });
        });

        // Place order button
        document.getElementById('place-order-btn').addEventListener('click', () => {
            this.placeOrder();
        });

        // Delivery time change
        document.getElementById('delivery-time').addEventListener('change', (e) => {
            if (e.target.value === 'custom') {
                this.showCustomTimeInput();
            }
        });
    }

    selectPaymentMethod(method) {
        // Update UI
        document.querySelectorAll('.payment-method').forEach(el => {
            el.classList.remove('selected');
        });
        
        if (method === 'cash') {
            document.getElementById('cash-payment').classList.add('selected');
            document.getElementById('mobile-payment-details').style.display = 'none';
        } else {
            document.getElementById('mobile-payment').classList.add('selected');
            document.getElementById('mobile-payment-details').style.display = 'block';
        }
        
        this.validateForm();
    }

    showCustomTimeInput() {
        const currentTime = new Date();
        currentTime.setHours(currentTime.getHours() + 2);
        
        const timeString = currentTime.toTimeString().slice(0, 5);
        const customTime = prompt('Enter delivery time (HH:MM):', timeString);
        
        if (customTime) {
            if (/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/.test(customTime)) {
                document.getElementById('delivery-time').value = 'custom';
            } else {
                alert('Please enter a valid time in HH:MM format');
                document.getElementById('delivery-time').value = 'asap';
            }
        } else {
            document.getElementById('delivery-time').value = 'asap';
        }
    }

   // Replace your validateMobileMoneyNumber method with this:

validateMobileMoneyNumber() {
    const mobileNumber = document.getElementById('mobile-number').value.trim();
    const provider = document.getElementById('mobile-provider').value;
    
    // Clear existing error messages
    this.clearMobileMoneyErrors();
    
    if (!mobileNumber) {
        this.showMobileMoneyError('Please enter a mobile money number');
        return false;
    }
    
    if (!provider) {
        this.showProviderError('Please select a mobile money provider');
        return false;
    }
    
    // Normalize the phone number (remove spaces, dashes, etc.)
    const normalizedNumber = mobileNumber.replace(/[\s\-\(\)]/g, '');
    
    // Basic phone validation
    if (!this.isValidMalawiNumber(normalizedNumber)) {
        this.showMobileMoneyError('Please enter a valid Malawi mobile number (e.g., 0881234567, 0991234567)');
        return false;
    }
    
    // Check if number matches the selected provider
    const isValidForProvider = this.isNumberValidForProvider(normalizedNumber, provider);
    
    if (!isValidForProvider) {
        const errorMessage = this.getProviderErrorMessage(normalizedNumber, provider);
        this.showMobileMoneyError(errorMessage);
        return false;
    }
    
    return true;
}

// Simplify the provider validation
isNumberValidForProvider(number, provider) {
    // Remove country code if present
    let cleanNumber = number.replace(/^\+265/, '').replace(/^265/, '');
    
    // Remove leading 0 if present
    if (cleanNumber.startsWith('0')) {
        cleanNumber = cleanNumber.substring(1);
    }
    
    // Get the first 3 digits
    const prefix = cleanNumber.substring(0, 3);
    
    // Define provider prefixes for Malawi - SIMPLIFIED
    const providerPrefixes = {
        'airtel': ['99', '09'],  // Airtel Money
        'tnm': ['88', '08']      // TNM Mpamba
    };
    
    // Check if prefix matches the selected provider
    const validPrefixes = providerPrefixes[provider] || [];
    return validPrefixes.some(prefix => cleanNumber.startsWith(prefix));
}

getProviderErrorMessage(number, provider) {
    const providerName = provider === 'airtel' ? 'Airtel Money' : 'TNM Mpamba';
    const prefixes = provider === 'airtel' ? '09 or 099' : '08 or 088';
    
    return `This number doesn't appear to be a ${providerName} number. ${providerName} numbers in Malawi typically start with ${prefixes}.`;
}
    getProviderPrefixes(provider) {
        const prefixes = {
            'airtel': '09 or 099',
            'tnm': '08 or 088'
        };
        return prefixes[provider] || '';
    }
    
    clearMobileMoneyErrors() {
        const errorElements = document.querySelectorAll('.mobile-money-error, .provider-error');
        errorElements.forEach(el => el.remove());
    }
    
    showMobileMoneyError(message) {
        const mobileNumberInput = document.getElementById('mobile-number');
        const existingError = mobileNumberInput.parentNode.querySelector('.mobile-money-error');
        
        if (existingError) {
            existingError.textContent = message;
            existingError.style.display = 'block';
        } else {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message mobile-money-error';
            errorDiv.textContent = message;
            errorDiv.style.color = '#e53e3e';
            errorDiv.style.fontSize = '0.875rem';
            errorDiv.style.marginTop = '0.25rem';
            mobileNumberInput.parentNode.appendChild(errorDiv);
        }
    }
    
    showProviderError(message) {
        const providerSelect = document.getElementById('mobile-provider');
        const existingError = providerSelect.parentNode.querySelector('.provider-error');
        
        if (existingError) {
            existingError.textContent = message;
            existingError.style.display = 'block';
        } else {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message provider-error';
            errorDiv.textContent = message;
            errorDiv.style.color = '#e53e3e';
            errorDiv.style.fontSize = '0.875rem';
            errorDiv.style.marginTop = '0.25rem';
            providerSelect.parentNode.appendChild(errorDiv);
        }
    }
    
    isValidMalawiNumber(phone) {
        // Accepts formats: +265XXXXXXXXX, 265XXXXXXXXX, 0XXXXXXXXX, XXXXXXXXX
        const phoneRegex = /^(\+?265|0)?(88|99|98|31)\d{7}$/;
        return phoneRegex.test(phone.replace(/\s/g, ''));
    }

    validateForm() {
        const phone = document.getElementById('phone').value.trim();
        const address = document.getElementById('delivery-address').value.trim();
        let isValid = true;

        // Validate customer phone
        const phoneError = document.getElementById('phone-error');
        if (!phone || !this.isValidMalawiNumber(phone)) {
            phoneError.style.display = 'block';
            isValid = false;
        } else {
            phoneError.style.display = 'none';
        }

        // Validate address
        const addressError = document.getElementById('address-error');
        if (!address) {
            addressError.style.display = 'block';
            isValid = false;
        } else {
            addressError.style.display = 'none';
        }

        // Validate mobile payment details if selected
        const isMobilePayment = document.getElementById('mobile-payment').classList.contains('selected');
        if (isMobilePayment) {
            const mobileValidationResult = this.validateMobileMoneyNumber();
            if (!mobileValidationResult) {
                isValid = false;
            }
        }

        // Enable/disable place order button
        const placeOrderBtn = document.getElementById('place-order-btn');
        placeOrderBtn.disabled = !isValid || this.cart.length === 0;

        // Debug log to see validation status
        console.log('Validation status:', isValid, 'Cart items:', this.cart.length);
        
        return isValid;
    }
async placeOrder() {
    if (!this.validateForm()) {
        this.showNotification('Please fix the errors in the form', 'error');
        return;
    }

    // Calculate totals
    const subtotal = this.cart.reduce((total, item) => total + (parseFloat(item.price) * item.quantity), 0);
    const deliveryFee = 1500;
    const totalAmount = subtotal + deliveryFee;

    // Get payment method
    const isMobilePayment = document.getElementById('mobile-payment').classList.contains('selected');
    const paymentMethod = isMobilePayment ? 'mobile' : 'cash';
    
    // Prepare payment details for database
    let paymentDetails = null;
    if (isMobilePayment) {
        paymentDetails = JSON.stringify({
            provider: document.getElementById('mobile-provider').value,
            number: document.getElementById('mobile-number').value.trim()
        });
    }

    const placeOrderBtn = document.getElementById('place-order-btn');
    placeOrderBtn.disabled = true;
    placeOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Placing Order...';

    try {
        // MAP CART ITEMS TO THE CORRECT STRUCTURE - ADD THIS
        const cartItemsForOrder = this.cart.map(item => ({
            meal_id: item.meal_id,
            quantity: item.quantity || 1,
            price: parseFloat(item.price) || 0
            // Optionally include name for debugging
            // meal_name: item.meal_name
        }));
        
        console.log('Cart items for order:', cartItemsForOrder);

        // Prepare complete order data
        const orderData = {
            delivery_address: document.getElementById('delivery-address').value.trim(),
            customer_phone: document.getElementById('phone').value.trim(),
            special_instructions: document.getElementById('special-instructions').value.trim(),
            payment_method: paymentMethod,
            payment_details: paymentDetails,
            total_amount: totalAmount.toFixed(2),
            subtotal: subtotal.toFixed(2),
            delivery_fee: deliveryFee.toFixed(2),
            cart_items: cartItemsForOrder,  // Now defined
            payment_status: paymentMethod === 'mobile' ? 'paid' : 'pending'
        };

        console.log('Placing order with data:', orderData);

        // Call checkout API
        const response = await this.apiCall('/orders/checkout.php', {
            method: 'POST',
            body: JSON.stringify(orderData)
        });

        console.log('Order response:', response);

        if (response.success) {
            this.showNotification('Order placed successfully!', 'success');
            
            // Note: The server now clears the cart automatically, so you don't need to clear it here
            // Remove this block or keep as backup
            /*
            try {
                // Clear each item from server cart
                for (const item of this.cart) {
                    await this.apiCall('/cart/update.php', {
                        method: 'PUT',
                        body: JSON.stringify({
                            meal_id: item.meal_id,
                            quantity: 0
                        })
                    });
                }
            } catch (clearError) {
                console.warn('Failed to clear cart after order:', clearError);
            }
            */
            
            this.cart = [];
            
            // Redirect to orders page
            setTimeout(() => {
                window.location.href = `/projects/aunt-joy-restaurant/public/orders.php?order_id=${response.data.order_id}`;
            }, 2000);
            
        } else {
            throw new Error(response.message || 'Failed to place order');
        }

    } catch (error) {
        console.error('Order placement failed:', error);
        
        let errorMessage = 'Failed to place order. Please try again.';
        if (error.message.includes('Cart is empty')) {
            errorMessage = 'Your cart is empty. Please add items before ordering.';
        } else if (error.message.includes('Missing required field')) {
            errorMessage = 'Please provide both phone number and delivery address.';
        } else if (error.message.includes('payment')) {
            errorMessage = 'Please check your payment details and try again.';
        } else if (error.message.includes('customer')) {
            errorMessage = 'Please ensure you are logged in to place an order.';
        }
        
        this.showNotification(errorMessage, 'error');
        placeOrderBtn.disabled = false;
        placeOrderBtn.innerHTML = 'Place Order';
    }
     }

     // Helper method for API calls
    async apiCall(endpoint, options = {}) {
    try {
        // Remove ../api from endpoint if present
        const cleanEndpoint = endpoint.replace('../api', '');
        const url = this.apiBaseUrl + cleanEndpoint;
        
        console.log('API call to:', url);
        
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include' // Important for sessions
        };
        
        const response = await fetch(url, { ...defaultOptions, ...options });
        
        // Check if response is JSON
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.includes("application/json")) {
            const data = await response.json();
            
            // Log non-successful responses for debugging
            if (!response.ok || (data && !data.success)) {
                console.error('API error response:', data);
            }
            
            return data;
        } else {
            const text = await response.text();
            console.error('Non-JSON response:', text.substring(0, 200));
            throw new Error('Server returned non-JSON response');
        }
    } catch (error) {
        console.error('API call failed:', error);
        throw error;
    }
 }
    // Helper method to show notifications
    showNotification(message, type = 'success') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `checkout-notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i>
            ${message}
        `;
        
        // Style the notification
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideInRight 0.5s ease;
            background: ${type === 'success' ? '#48bb78' : type === 'error' ? '#e53e3e' : '#ed8936'};
            display: flex;
            align-items: center;
            gap: 0.5rem;
        `;
        
        document.body.appendChild(notification);
        
        // Remove after 5 seconds
        setTimeout(() => {
            notification.style.animation = 'slideInRight 0.5s ease reverse';
            setTimeout(() => {
                if (notification.parentNode) {
                    document.body.removeChild(notification);
                }
            }, 500);
        }, 5000);
    }
    
    // Helper method to update cart UI
    updateCartUI() {
    const cartCountElements = document.querySelectorAll('.cart-count');
    if (cartCountElements.length > 0) {
        const totalItems = this.cart.reduce((sum, item) => sum + item.quantity, 0);
        cartCountElements.forEach(element => {
            element.textContent = totalItems;
        });
    }
}
}

// Initialize checkout manager
document.addEventListener('DOMContentLoaded', () => {
    console.log('=== CHECKOUT INIT ===');
    window.checkoutManager = new CheckoutManager();
    
    // Debug: Check cart after initialization
    setTimeout(() => {
        console.log('Cart after init:', window.checkoutManager.cart);
        console.log('Cart length:', window.checkoutManager.cart.length);
    }, 1000);
    
    // Add CSS animation for notifications
    if (!document.querySelector('#notification-animation')) {
        const style = document.createElement('style');
        style.id = 'notification-animation';
        style.textContent = `
            @keyframes slideInRight {
                from {
                    opacity: 0;
                    transform: translateX(100%);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
        `;
        document.head.appendChild(style);
    }
});