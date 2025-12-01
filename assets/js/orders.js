// assets/js/orders.js - FIXED API PATHS
class OrdersManager {
    constructor() {
        this.orders = [];
        this.init();
    }

    init() {
        this.loadOrders();
        this.setupEventListeners();
    }

    async loadOrders() {
        try {
            const data = await app.apiCall('/orders/list.php');
            this.orders = data.data.orders;
            this.renderOrders();
        } catch (error) {
            console.error('Failed to load orders:', error);
            this.showError('Failed to load orders. Please try again later.');
        }
    }

    renderOrders() {
        const ordersList = document.getElementById('orders-list');

        if (this.orders.length === 0) {
            this.showNoOrdersState();
            return;
        }

        ordersList.innerHTML = this.orders.map(order => `
            <div class="order-card" data-order-id="${order.order_id}">
                <div class="order-header">
                    <div class="order-info">
                        <div class="order-id">Order #${order.order_id}</div>
                        <div class="order-date">
                            Placed on ${this.formatDate(order.order_date)}
                            ${order.status_updated_at ? ` • Last updated: ${this.formatDate(order.status_updated_at)}` : ''}
                        </div>
                    </div>
                    <div class="order-status ${this.getStatusClass(order.current_status)}">
                        ${this.getStatusText(order.current_status)}
                    </div>
                </div>

                <div class="order-details">
                    <div class="order-content">
                        <div class="delivery-info">
                            <div class="info-row">
                                <span class="info-label">Delivery Address:</span>
                                <span class="info-value">${order.delivery_address}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Phone:</span>
                                <span class="info-value">${order.customer_phone}</span>
                            </div>
                            ${order.special_instructions ? `
                            <div class="info-row">
                                <span class="info-label">Instructions:</span>
                                <span class="info-value">${order.special_instructions}</span>
                            </div>
                            ` : ''}
                        </div>
                    </div>

                    <div class="order-summary">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>MK ${(parseFloat(order.total_amount) - 1500).toFixed(2)}</span>
                        </div>
                        <div class="summary-row">
                            <span>Delivery Fee:</span>
                            <span>MK 1,500.00</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span>MK ${parseFloat(order.total_amount).toFixed(2)}</span>
                        </div>
                    </div>
                </div>

                <div class="order-actions">
                    <button class="btn-outline view-order-details" data-order-id="${order.order_id}">
                        View Details
                    </button>
                    ${order.current_status === 'preparing' ? `
                    <button class="btn-outline cancel-order" data-order-id="${order.order_id}">
                        Cancel Order
                    </button>
                    ` : ''}
                    ${order.current_status === 'delivered' ? `
                    <button class="btn-outline reorder" data-order-id="${order.order_id}">
                        Order Again
                    </button>
                    ` : ''}
                </div>
            </div>
        `).join('');

        // Load order items for each order
        this.orders.forEach(order => {
            this.loadOrderItems(order.order_id);
        });
    }

    async loadOrderItems(orderId) {
        try {
            const data = await app.apiCall(`/orders/details.php?id=${orderId}`);
            const items = data.data.items;
            this.renderOrderItems(orderId, items);
        } catch (error) {
            console.error(`Failed to load items for order ${orderId}:`, error);
            // Don't show error to user, just skip loading items
        }
    }

    renderOrderItems(orderId, items) {
        const orderCard = document.querySelector(`.order-card[data-order-id="${orderId}"]`);
        if (!orderCard || !items) return;

        const orderContent = orderCard.querySelector('.order-content');
        const existingItems = orderContent.querySelector('.order-items');
        
        if (existingItems) {
            existingItems.remove();
        }

        const itemsHTML = `
            <div class="order-items">
                <h4 style="margin-bottom: 1rem; color: var(--dark-color);">Order Items:</h4>
                ${items.map(item => `
                    <div class="order-item">
                        <div class="item-info">
                            <div class="item-name">${item.meal_name}</div>
                            <div class="item-quantity">Qty: ${item.quantity}</div>
                        </div>
                        <div class="item-price">
                            MK ${parseFloat(item.item_total).toFixed(2)}
                        </div>
                    </div>
                `).join('')}
            </div>
        `;

        orderContent.insertAdjacentHTML('afterbegin', itemsHTML);
    }

    showNoOrdersState() {
        const ordersList = document.getElementById('orders-list');
        ordersList.innerHTML = `
            <div class="empty-orders">
                <div class="empty-icon">📦</div>
                <h3>No orders yet</h3>
                <p>You haven't placed any orders yet. Start by exploring our menu!</p>
                <a href="index.php#categories" class="btn btn-primary" style="margin-top: 1rem;">Browse Menu</a>
            </div>
        `;
    }

    showError(message) {
        const ordersList = document.getElementById('orders-list');
        ordersList.innerHTML = `
            <div class="empty-orders">
                <div class="empty-icon">⚠️</div>
                <h3>Error Loading Orders</h3>
                <p>${message}</p>
                <button class="btn btn-primary" onclick="ordersManager.loadOrders()" style="margin-top: 1rem;">
                    Try Again
                </button>
            </div>
        `;
    }

    getStatusClass(status) {
        const statusMap = {
            'preparing': 'status-preparing',
            'out_for_delivery': 'status-out-for-delivery',
            'delivered': 'status-delivered',
            'cancelled': 'status-cancelled'
        };
        return statusMap[status] || 'status-preparing';
    }

    getStatusText(status) {
        const statusMap = {
            'preparing': 'Preparing',
            'out_for_delivery': 'Out for Delivery',
            'delivered': 'Delivered',
            'cancelled': 'Cancelled'
        };
        return statusMap[status] || 'Preparing';
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    setupEventListeners() {
        document.addEventListener('click', (e) => {
            // View order details
            if (e.target.classList.contains('view-order-details')) {
                const orderId = e.target.getAttribute('data-order-id');
                this.viewOrderDetails(orderId);
            }

            // Cancel order
            if (e.target.classList.contains('cancel-order')) {
                const orderId = e.target.getAttribute('data-order-id');
                this.cancelOrder(orderId);
            }

            // Reorder
            if (e.target.classList.contains('reorder')) {
                const orderId = e.target.getAttribute('data-order-id');
                this.reorder(orderId);
            }
        });
    }

    viewOrderDetails(orderId) {
        // For now, show a simple modal with order info
        const order = this.orders.find(o => o.order_id == orderId);
        if (order) {
            const details = `
Order #${order.order_id}
Status: ${this.getStatusText(order.current_status)}
Date: ${this.formatDate(order.order_date)}
Total: MK ${parseFloat(order.total_amount).toFixed(2)}
Address: ${order.delivery_address}
Phone: ${order.customer_phone}
${order.special_instructions ? `Instructions: ${order.special_instructions}` : ''}
            `.trim();
            
            alert(details);
        }
    }

    async cancelOrder(orderId) {
        if (!confirm('Are you sure you want to cancel this order?')) {
            return;
        }

        try {
            // Check if cancel API exists, if not show message
            const response = await app.apiCall(`/orders/cancel.php`, {
                method: 'POST',
                body: JSON.stringify({ order_id: orderId })
            });

            if (response.success) {
                app.showNotification('Order cancelled successfully', 'success');
                this.loadOrders(); // Reload orders
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            console.error('Failed to cancel order:', error);
            app.showNotification('Cancel feature not available yet', 'info');
        }
    }

    async reorder(orderId) {
        try {
            const data = await app.apiCall(`/orders/details.php?id=${orderId}`);
            const items = data.data.items;
            
            // Add items to cart
            for (const item of items) {
                const meal = {
                    meal_id: item.meal_id,
                    meal_name: item.meal_name,
                    price: parseFloat(item.unit_price),
                    image_url: item.image_url
                };
                
                for (let i = 0; i < item.quantity; i++) {
                    app.addToCart(meal);
                }
            }
            
            app.showNotification('Items added to cart!', 'success');
            setTimeout(() => {
                window.location.href = 'cart.php';
            }, 1500);
            
        } catch (error) {
            console.error('Failed to reorder:', error);
            app.showNotification('Reorder feature not available yet', 'info');
        }
    }
}

// Initialize orders manager
document.addEventListener('DOMContentLoaded', () => {
    window.ordersManager = new OrdersManager();
});