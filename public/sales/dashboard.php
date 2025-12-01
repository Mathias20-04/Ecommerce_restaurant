<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$auth = new Auth();
$currentUser = $auth->getCurrentUser();

// Check if user has sales or admin role
if (!$auth->hasRole('sales') && !$auth->hasRole('admin')) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Admin - Aunt Joy's Restaurant</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .sales-admin-dashboard {
            padding: 2rem 0;
            background: #f8f9fa;
            min-height: 100vh;
        }

        .dashboard-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .dashboard-header h1 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            font-size: 2.5rem;
        }

        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .stat-card h3 {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            display: block;
        }

        .orders-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .section-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .section-header h2 {
            color: #2c3e50;
            font-size: 1.8rem;
        }

        .filters {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .filters select, .filters input {
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            background: white;
        }

        .filters select:focus, .filters input:focus {
            outline: none;
            border-color: #667eea;
        }

        .orders-list {
            display: grid;
            gap: 1.5rem;
        }

        .order-card {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            background: white;
            transition: all 0.3s ease;
            position: relative;
        }

        .order-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .order-card.new-order {
            border-left: 4px solid #e74c3c;
            animation: pulse 2s infinite;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .order-info h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
        }

        .order-date {
            color: #718096;
            font-size: 0.9rem;
        }

        .order-status {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: capitalize;
        }

        .status-preparing { background: #fff3cd; color: #856404; }
        .status-out_for_delivery { background: #cce7ff; color: #004085; }
        .status-delivered { background: #d4edda; color: #155724; }

        .status-select {
            padding: 0.5rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            background: white;
            font-size: 0.9rem;
        }

        .customer-info, .order-items {
            margin-bottom: 1.5rem;
        }

        .customer-info h4, .order-items h4 {
            color: #2c3e50;
            margin-bottom: 0.75rem;
            font-size: 1.1rem;
        }

        .customer-info p {
            margin: 0.25rem 0;
            color: #4a5568;
        }

        .items-list {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }

        .items-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
        }

        .items-list li:last-child {
            border-bottom: none;
        }

        .order-total {
            text-align: right;
            font-size: 1.2rem;
            font-weight: 700;
            color: #2c3e50;
            padding-top: 1rem;
            border-top: 2px solid #e2e8f0;
        }

        .order-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #48bb78;
            color: white;
        }

        .btn-success:hover {
            background: #3da56a;
            transform: translateY(-2px);
        }

        .loading-spinner {
            text-align: center;
            padding: 3rem;
            color: #718096;
        }

        .loading-spinner i {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideInRight 0.5s ease;
        }

        .notification.success { background: #48bb78; }
        .notification.error { background: #e53e3e; }
        .notification.warning { background: #ed8936; }

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

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(231, 76, 60, 0); }
            100% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0); }
        }

        .no-orders {
            text-align: center;
            padding: 3rem;
            color: #718096;
        }

        .no-orders i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .section-header {
                flex-direction: column;
                align-items: stretch;
            }

            .filters {
                flex-direction: column;
            }

            .order-header {
                flex-direction: column;
            }

            .order-actions {
                justify-content: stretch;
            }

            .btn {
                flex: 1;
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
                <div class="logo">
                    <img src="../../assets/images/kitchen_logo1.png" alt="Aunt Joy's Restaurant Logo" /> 
                    Aunt Joy's - Sales Admin
                </div>
                
                <ul class="nav-links">
                    <li><a href="../index.php"><i class="fas fa-home"></i> Back to Site</a></li>
                    <li class="user-dropdown">
                        <div class="user-info">
                            <span class="user-avatar">
                                <i class="fas fa-user-tie"></i>
                            </span>
                            <span class="user-name"><?php echo htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']); ?> (Sales)</span>
                        </div>
                        <div class="dropdown-menu">
                            <a href="../logout.php" class="dropdown-item logout-btn">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Sales Admin Dashboard -->
    <section class="sales-admin-dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h1><i class="fas fa-tachometer-alt"></i> Sales Admin Dashboard</h1>
                <div class="stats-overview">
                    <div class="stat-card">
                        <h3>New Orders</h3>
                        <span class="stat-number" id="new-orders-count">0</span>
                    </div>
                    <div class="stat-card">
                        <h3>Preparing</h3>
                        <span class="stat-number" id="preparing-count">0</span>
                    </div>
                    <div class="stat-card">
                        <h3>Out for Delivery</h3>
                        <span class="stat-number" id="delivery-count">0</span>
                    </div>
                    <div class="stat-card">
                        <h3>Delivered</h3>
                        <span class="stat-number" id="delivered-count">0</span>
                    </div>
                </div>
            </div>

            <div class="orders-section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> Order Management</h2>
                    <div class="filters">
                        <select id="status-filter">
                            <option value="">All Orders</option>
                            <option value="preparing">Preparing</option>
                            <option value="out_for_delivery">Out for Delivery</option>
                            <option value="delivered">Delivered</option>
                        </select>
                        <input type="text" id="search-orders" placeholder="Search by order ID, customer name...">
                    </div>
                </div>

                <div class="orders-list" id="orders-container">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Loading orders...</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

<script>
class SalesAdminDashboard {
    constructor() {
        this.orders = [];
        this.filteredOrders = [];
        // Use the correct API endpoint for sales orders
        this.baseUrl = '../../api/sales/orders/'; // Relative to sales directory
        this.init();
    }

    init() {
        this.loadOrders();
        this.setupEventListeners();
        this.startAutoRefresh();
    }

    setupEventListeners() {
        document.getElementById('status-filter').addEventListener('change', (e) => {
            this.filterOrders();
        });

        document.getElementById('search-orders').addEventListener('input', (e) => {
            this.filterOrders();
        });
    }

    startAutoRefresh() {
        setInterval(() => {
            this.loadOrders();
        }, 30000);
    }

    async loadOrders() {
        try {
            const statusFilter = document.getElementById('status-filter').value;
            let url = this.baseUrl + 'list.php';
            if (statusFilter) {
                url += `?status=${encodeURIComponent(statusFilter)}`;
            }

            console.log('🔍 Fetching orders from:', url);

            const response = await fetch(url, {
                headers: {
                    'Authorization': 'Bearer ' + this.getAuthToken()
                }
            });

            console.log('📊 Response status:', response.status);
            
            const responseText = await response.text();
            console.log('📄 Response sample:', responseText.substring(0, 200));

            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('❌ JSON Parse Error:', parseError);
                if (responseText.includes('<!DOCTYPE') || responseText.includes('<html')) {
                    throw new Error('API returned HTML instead of JSON. Check API endpoint path and PHP errors.');
                } else {
                    throw new Error('Invalid JSON response from server: ' + responseText.substring(0, 100));
                }
            }

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${data.message || 'Server error'}`);
            }

            if (data.success) {
                this.orders = data.data.orders || [];
                this.filterOrders();
                this.updateStats();
                console.log(`✅ Loaded ${this.orders.length} orders`);
            } else {
                throw new Error(data.message || 'Unknown API error');
            }
        } catch (error) {
            console.error('💥 Load orders error:', error);
            this.showNotification('Error loading orders: ' + error.message, 'error');
            
            // Show error in UI
            const container = document.getElementById('orders-container');
            container.innerHTML = `
                <div class="no-orders">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Failed to Load Orders</h3>
                    <p>${error.message}</p>
                    <p style="margin-top: 1rem; font-size: 0.9rem; color: #718096;">
                        Check console for details and verify API endpoints are working.
                    </p>
                </div>
            `;
        }
    }

    filterOrders() {
        const statusFilter = document.getElementById('status-filter').value;
        const searchTerm = document.getElementById('search-orders').value.toLowerCase();

        this.filteredOrders = this.orders.filter(order => {
            const matchesStatus = !statusFilter || order.current_status === statusFilter;
            const matchesSearch = !searchTerm || 
                order.order_id.toString().includes(searchTerm) ||
                (order.customer_name && order.customer_name.toLowerCase().includes(searchTerm)) ||
                (order.customer_contact && order.customer_contact.includes(searchTerm));

            return matchesStatus && matchesSearch;
        });

        this.renderOrders();
    }

    renderOrders() {
        const container = document.getElementById('orders-container');
        
        if (this.filteredOrders.length === 0) {
            container.innerHTML = `
                <div class="no-orders">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>No orders found</h3>
                    <p>There are no orders matching your current filters.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.filteredOrders.map(order => this.createOrderCard(order)).join('');
    }

    createOrderCard(order) {
        const statusClass = `status-${order.current_status || 'preparing'}`;
        const statusDisplay = this.formatStatus(order.current_status);
        const orderDate = order.order_date ? new Date(order.order_date).toLocaleString() : 'N/A';
        const statusUpdated = order.status_updated_at ? new Date(order.status_updated_at).toLocaleString() : 'N/A';

        return `
            <div class="order-card" data-order-id="${order.order_id}">
                <div class="order-header">
                    <div class="order-info">
                        <h3>Order #${order.order_id}</h3>
                        <span class="order-date">Placed: ${orderDate}</span>
                        ${order.status_updated_at ? `<br><span class="order-date">Last Updated: ${statusUpdated}</span>` : ''}
                    </div>
                    <div class="order-status">
                        <span class="status-badge ${statusClass}">${statusDisplay}</span>
                        <select class="status-select" onchange="salesAdmin.updateOrderStatus(${order.order_id}, this.value)">
                            <option value="preparing" ${(order.current_status || 'preparing') === 'preparing' ? 'selected' : ''}>Preparing</option>
                            <option value="out_for_delivery" ${order.current_status === 'out_for_delivery' ? 'selected' : ''}>Out for Delivery</option>
                            <option value="delivered" ${order.current_status === 'delivered' ? 'selected' : ''}>Delivered</option>
                        </select>
                    </div>
                </div>
                
                <div class="customer-info">
                    <h4><i class="fas fa-user"></i> Customer Details</h4>
                    <p><strong>Name:</strong> ${order.customer_name || 'N/A'}</p>
                    <p><strong>Phone:</strong> ${order.customer_contact || order.customer_phone || 'N/A'}</p>
                    <p><strong>Address:</strong> ${order.delivery_address || 'N/A'}</p>
                    ${order.special_instructions ? `<p><strong>Special Instructions:</strong> ${order.special_instructions}</p>` : ''}
                    <p><strong>Payment Status:</strong> <span class="status-badge ${order.payment_status === 'paid' ? 'status-delivered' : 'status-preparing'}">${order.payment_status || 'pending'}</span></p>
                </div>

                <div class="order-items">
                    <h4><i class="fas fa-utensils"></i> Order Summary</h4>
                    <div class="order-total">
                        <strong>Total: MK ${parseFloat(order.total_amount || 0).toFixed(2)}</strong>
                    </div>
                </div>

                <div class="order-actions">
                    <button class="btn btn-primary" onclick="salesAdmin.viewOrderDetails(${order.order_id})">
                        <i class="fas fa-eye"></i> View Details
                    </button>
                    <button class="btn btn-success" onclick="salesAdmin.printOrder(${order.order_id})">
                        <i class="fas fa-print"></i> Print Order
                    </button>
                </div>
            </div>
        `;
    }

    formatStatus(status) {
        const statusMap = {
            'preparing': 'Preparing',
            'out_for_delivery': 'Out for Delivery',
            'delivered': 'Delivered',
            'cancelled': 'Cancelled'
        };
        return statusMap[status] || (status || 'Preparing');
    }

    async updateOrderStatus(orderId, newStatus) {
        if (!confirm(`Are you sure you want to update order #${orderId} to "${this.formatStatus(newStatus)}"?`)) {
            this.loadOrders(); // Reload to reset select
            return;
        }

        try {
            const response = await fetch(this.baseUrl + 'update-status.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + this.getAuthToken()
                },
                body: JSON.stringify({
                    order_id: orderId,
                    status: newStatus,
                    status_notes: `Status updated by sales admin`
                })
            });

            const responseText = await response.text();
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                throw new Error('Invalid JSON response from server');
            }

            if (data.success) {
                this.showNotification(`Order #${orderId} status updated to ${this.formatStatus(newStatus)}`, 'success');
                this.loadOrders();
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            this.showNotification('Error updating order: ' + error.message, 'error');
            this.loadOrders();
        }
    }

    async viewOrderDetails(orderId) {
        // Fetch full details (items + status history) from the API
        try {
            const url = '../../api/orders/details.php?id=' + encodeURIComponent(orderId);
            console.log('🔍 Fetching order details from:', url);

            const resp = await fetch(url, {
                headers: {
                    'Authorization': 'Bearer ' + this.getAuthToken(),
                },
                credentials: 'same-origin'
            });

            const text = await resp.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse order details response:', text);
                throw new Error('Invalid JSON response for order details');
            }

            if (!resp.ok || !data.success) {
                throw new Error(data.message || ('Server returned ' + resp.status));
            }

            const payload = data.data || {};
            // Some endpoints return { order, items, status_history } (api/orders/details.php)
            // Normalize into a single `order` object with items and status_history
            const order = payload.order || payload;
            order.items = payload.items || order.items || [];
            order.status_history = payload.status_history || [];

            this.showOrderModal(order);

        } catch (err) {
            console.error('Error fetching order details:', err);
            this.showNotification('Failed to load order details: ' + err.message, 'error');
        }
    }

    showOrderModal(order) {
        // Build items markup
        const items = (order.items || []).map(item => `
            <li>
                <div style="display:flex;gap:1rem;align-items:center;">
                    <div style="flex:1;"><strong>${item.meal_name || item.name || 'Item'}</strong></div>
                    <div style="width:70px;text-align:center;">x${item.quantity}</div>
                    <div style="width:90px;text-align:right;">MK ${parseFloat(item.subtotal || (item.price && item.quantity ? item.price * item.quantity : 0)).toFixed(2)}</div>
                </div>
            </li>
        `).join('');

        // Status history markup
        const statusHistory = (order.status_history || []).map(s => `
            <div style="padding:6px 0;border-bottom:1px solid #eee;">
                <div style="font-weight:600;">${s.status}</div>
                ${s.status_notes ? `<div style="font-size:0.9rem;color:#666;">${s.status_notes}</div>` : ''}
                <div style="font-size:0.8rem;color:#888;margin-top:4px;">${s.created_at ? new Date(s.created_at).toLocaleString() : ''} ${s.updated_by ? ' — ' + (s.updated_by) : ''}</div>
            </div>
        `).join('');

        const modalHTML = `
            <div class="modal-overlay" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:2000;display:flex;align-items:center;justify-content:center;">
                <div class="modal-content" style="background:white;padding:2rem;border-radius:12px;max-width:800px;width:94%;max-height:84vh;overflow-y:auto;">
                    <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                        <div>
                            <h3 style="margin:0;">Order #${order.order_id} Details</h3>
                            <div style="color:#666;font-size:0.9rem;">Placed: ${order.order_date ? new Date(order.order_date).toLocaleString() : 'N/A'}</div>
                        </div>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <button onclick="window.open('print_order.php?order_id=${order.order_id}','_blank')" class="btn btn-primary">Print</button>
                            <button onclick="this.closest('.modal-overlay').remove()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;">×</button>
                        </div>
                    </div>

                    <div style="display:flex;gap:2rem;flex-wrap:wrap;margin-bottom:1rem;">
                        <div style="flex:1;min-width:240px;">
                            <h4 style="margin:0 0 6px;">Customer</h4>
                            <div style="font-size:0.95rem;color:#444;">${order.customer_name || 'N/A'}</div>
                            <div style="font-size:0.88rem;color:#666;">Phone: ${order.customer_contact || order.customer_phone || 'N/A'}</div>
                            <div style="font-size:0.88rem;color:#666;">Address: ${order.delivery_address || 'N/A'}</div>
                        </div>
                        <div style="flex:1;min-width:240px;">
                            <h4 style="margin:0 0 6px;">Status</h4>
                            <div style="font-weight:700;">${this.formatStatus(order.current_status || 'preparing')}</div>
                            ${order.payment_status ? `<div style="color:#666;">Payment: ${order.payment_status}</div>` : ''}
                        </div>
                        <div style="min-width:220px;text-align:right;flex:0 0 220px;">
                            <div style="font-size:0.9rem;color:#666;">Last updated: ${order.status_updated_at ? new Date(order.status_updated_at).toLocaleString() : 'N/A'}</div>
                            <div style="font-weight:700;font-size:1.2rem;margin-top:6px;">Total: MK ${parseFloat(order.total_amount || 0).toFixed(2)}</div>
                        </div>
                    </div>

                    <div style="margin-bottom:1rem;">
                        <h4 style="margin-bottom:6px;">Items</h4>
                        <ul class="items-list" style="list-style:none;padding:0;margin:0 0 10px 0;">${items || '<li><em>No items</em></li>'}</ul>
                    </div>

                    <div style="margin-bottom:1rem;">
                        <h4 style="margin-bottom:6px;">Status History</h4>
                        <div style="border:1px solid #eee;border-radius:8px;padding:8px;background:#fafafa;">${statusHistory || '<div><em>No history</em></div>'}</div>
                    </div>

                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    printOrder(orderId) {
        window.open(`print_order.php?order_id=${orderId}`, '_blank');
    }

    updateStats() {
        const stats = {
            'new-orders-count': this.orders.filter(o => !o.current_status || o.current_status === 'preparing').length,
            'preparing-count': this.orders.filter(o => o.current_status === 'preparing').length,
            'delivery-count': this.orders.filter(o => o.current_status === 'out_for_delivery').length,
            'delivered-count': this.orders.filter(o => o.current_status === 'delivered').length
        };

        Object.keys(stats).forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = stats[id];
            }
        });
    }

    showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i>
            ${message}
        `;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 5000);
    }

    getAuthToken() {
        return '<?php echo session_id(); ?>';
    }
}

const salesAdmin = new SalesAdminDashboard();
</script>
</body>
</html>