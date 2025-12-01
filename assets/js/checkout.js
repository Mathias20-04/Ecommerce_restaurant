// assets/js/checkout.js - FIXED VERSION
class CheckoutManager {
    constructor() {
        this.cart = [];
        this.init();
    }

    init() {
        this.loadCart();
        this.renderOrderSummary();
        this.setupEventListeners();
        this.validateForm();
    }

    loadCart() {
        const savedCart = localStorage.getItem('cart');
        this.cart = savedCart ? JSON.parse(savedCart) : [];
        
        if (this.cart.length === 0) {
            window.location.href = 'cart.php';
            return;
        }
    }

    renderOrderSummary() {
        const orderItemsContainer = document.getElementById('order-items');
        const subtotal = this.cart.reduce((total, item) => total + (parseFloat(item.price) * item.quantity), 0);
        const deliveryFee = 1500;
        const total = subtotal + deliveryFee;

        // Render order items
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
            this.validateForm();
        });

        document.getElementById('mobile-provider').addEventListener('change', () => {
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

    validateForm() {
        const phone = document.getElementById('phone').value.trim();
        const address = document.getElementById('delivery-address').value.trim();
        let isValid = true;

        // Validate phone
        const phoneError = document.getElementById('phone-error');
        if (!phone || !this.isValidPhone(phone)) {
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
            const mobileNumber = document.getElementById('mobile-number').value.trim();
            const provider = document.getElementById('mobile-provider').value;
            
            if (!mobileNumber || !this.isValidPhone(mobileNumber)) {
                isValid = false;
                // Show error for mobile number
                if (!document.getElementById('mobile-error')) {
                    const mobileError = document.createElement('div');
                    mobileError.className = 'error-message';
                    mobileError.id = 'mobile-error';
                    mobileError.textContent = 'Please enter a valid mobile money number';
                    document.getElementById('mobile-number').parentNode.appendChild(mobileError);
                }
                document.getElementById('mobile-error').style.display = 'block';
            } else {
                if (document.getElementById('mobile-error')) {
                    document.getElementById('mobile-error').style.display = 'none';
                }
            }
            
            if (!provider) {
                isValid = false;
                // Show error for provider
                if (!document.getElementById('provider-error')) {
                    const providerError = document.createElement('div');
                    providerError.className = 'error-message';
                    providerError.id = 'provider-error';
                    providerError.textContent = 'Please select a mobile money provider';
                    document.getElementById('mobile-provider').parentNode.appendChild(providerError);
                }
                document.getElementById('provider-error').style.display = 'block';
            } else {
                if (document.getElementById('provider-error')) {
                    document.getElementById('provider-error').style.display = 'none';
                }
            }
        }

        // Enable/disable place order button
        const placeOrderBtn = document.getElementById('place-order-btn');
        placeOrderBtn.disabled = !isValid || this.cart.length === 0;

        return isValid;
    }

    isValidPhone(phone) {
        // Basic phone validation for Malawi numbers
        const phoneRegex = /^(\+265|265|0)?(88|99|98|31)\d{7}$/;
        return phoneRegex.test(phone.replace(/\s/g, ''));
    }

    async placeOrder() {
        if (!this.validateForm()) {
            app.showNotification('Please fix the errors in the form', 'error');
            return;
        }

        const placeOrderBtn = document.getElementById('place-order-btn');
        placeOrderBtn.disabled = true;
        placeOrderBtn.innerHTML = 'Placing Order...';

        try {
            // First, sync cart to session
            await this.syncCartToSession();
            
            // Then place order using session cart
            const orderData = {
                delivery_address: document.getElementById('delivery-address').value.trim(),
                customer_phone: document.getElementById('phone').value.trim(),
                special_instructions: document.getElementById('special-instructions').value.trim()
            };

            console.log('Placing order with data:', orderData);

            // Send order to your checkout endpoint (NOT create.php)
            const response = await app.apiCall('/orders/checkout.php', {
                method: 'POST',
                body: JSON.stringify(orderData)
            });

            console.log('Order response:', response);

            if (response.success) {
                app.showNotification('Order placed successfully!', 'success');
                
                // Clear both local and session cart
                localStorage.removeItem('cart');
                app.cart = [];
                app.updateCartUI();
                
                // Redirect to orders page
                setTimeout(() => {
                    window.location.href = `orders.php?order_id=${response.data.order_id}`;
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
            }
            
            app.showNotification(errorMessage, 'error');
            placeOrderBtn.disabled = false;
            placeOrderBtn.innerHTML = 'Place Order';
        }
    }

    async syncCartToSession() {
        try {
            // Send cart to session sync endpoint
            await app.apiCall('/cart/sync.php', {
                method: 'POST',
                body: JSON.stringify({
                    cart_items: this.cart
                })
            });
            console.log('Cart synced to session successfully');
        } catch (error) {
            console.error('Failed to sync cart to session:', error);
            throw new Error('Failed to prepare cart for checkout');
        }
    }
}

// Initialize checkout manager
document.addEventListener('DOMContentLoaded', () => {
    window.checkoutManager = new CheckoutManager();
});