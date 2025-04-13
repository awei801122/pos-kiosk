// 全域變數
let currentPage = 1;
let totalPages = 1;
let orders = [];

// 報表相關變數
let currentReportType = 'daily';
let orderTrendChart = null;
let revenueTrendChart = null;

// DOM 元素
const orderTableBody = document.getElementById('orderTableBody');
const pagination = document.getElementById('pagination');
const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const typeFilter = document.getElementById('typeFilter');
const dateFilter = document.getElementById('dateFilter');
const exportBtn = document.getElementById('exportBtn');

// 圖表實例
let orderTypeChart = null;
let orderStatusChart = null;

// 初始化
document.addEventListener('DOMContentLoaded', () => {
    loadOrders();
    loadStatistics();
    loadReport();
    bindEventListeners();
});

// 綁定事件監聽器
function bindEventListeners() {
    // 搜尋輸入防抖
    let searchTimeout;
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            loadOrders();
        }, 500);
    });

    // 篩選器變更事件
    statusFilter.addEventListener('change', () => {
        currentPage = 1;
        loadOrders();
    });

    typeFilter.addEventListener('change', () => {
        currentPage = 1;
        loadOrders();
    });

    dateFilter.addEventListener('change', () => {
        currentPage = 1;
        loadOrders();
    });

    // 匯出按鈕點擊事件
    exportBtn.addEventListener('click', exportOrders);
}

// 載入訂單列表
async function loadOrders() {
    try {
        const params = new URLSearchParams({
            action: 'list',
            page: currentPage,
            search: searchInput.value,
            status: statusFilter.value,
            type: typeFilter.value,
            date: dateFilter.value
        });

        const response = await fetch(`../api/orders.php?${params}`);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message);
        }

        orders = data.data.orders;
        totalPages = data.data.totalPages;

        renderOrderTable();
        renderPagination();
        
        // 重新載入統計資料
        loadStatistics();
    } catch (error) {
        showError(error.message);
    }
}

// 渲染訂單表格
function renderOrderTable() {
    orderTableBody.innerHTML = '';

    orders.forEach(order => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${order.order_number}</td>
            <td>${order.customer.name}</td>
            <td>${getOrderTypeName(order.type)}</td>
            <td>$${order.total}</td>
            <td>
                <span class="badge bg-${getStatusBadgeClass(order.status)}">
                    ${getStatusName(order.status)}
                </span>
            </td>
            <td>${formatDate(order.created_at)}</td>
            <td>
                <button class="btn btn-sm btn-info me-1" onclick="viewOrder('${order.id}')">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-sm btn-warning me-1" onclick="updateStatus('${order.id}')">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteOrder('${order.id}')">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        orderTableBody.appendChild(row);
    });
}

// 渲染分頁
function renderPagination() {
    pagination.innerHTML = '';

    // 上一頁按鈕
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `
        <a class="page-link" href="#" onclick="changePage(${currentPage - 1})">
            <i class="fas fa-chevron-left"></i>
        </a>
    `;
    pagination.appendChild(prevLi);

    // 頁碼按鈕
    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${currentPage === i ? 'active' : ''}`;
        li.innerHTML = `
            <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
        `;
        pagination.appendChild(li);
    }

    // 下一頁按鈕
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `
        <a class="page-link" href="#" onclick="changePage(${currentPage + 1})">
            <i class="fas fa-chevron-right"></i>
        </a>
    `;
    pagination.appendChild(nextLi);
}

// 切換頁面
function changePage(page) {
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    loadOrders();
}

// 查看訂單詳情
async function viewOrder(orderId) {
    try {
        const response = await fetch(`../api/orders.php?action=get&id=${orderId}`);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message);
        }

        const order = data.data;
        
        // 填充訂單詳情
        document.getElementById('detailOrderNumber').textContent = order.order_number;
        document.getElementById('detailOrderType').textContent = getOrderTypeName(order.type);
        document.getElementById('detailCreatedAt').textContent = formatDate(order.created_at);
        document.getElementById('detailUpdatedAt').textContent = formatDate(order.updated_at);
        
        // 填充客戶資訊
        document.getElementById('detailCustomerName').textContent = order.customer.name;
        document.getElementById('detailCustomerPhone').textContent = order.customer.phone;
        document.getElementById('detailCustomerEmail').textContent = order.customer.email;
        
        // 填充商品明細
        const itemsBody = document.getElementById('detailItems');
        itemsBody.innerHTML = '';
        order.items.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.name}</td>
                <td>$${item.price}</td>
                <td>${item.quantity}</td>
                <td>$${item.price * item.quantity}</td>
            `;
            itemsBody.appendChild(row);
        });
        
        // 填充金額資訊
        document.getElementById('detailSubtotal').textContent = `$${order.subtotal}`;
        document.getElementById('detailDiscount').textContent = `$${order.discount}`;
        document.getElementById('detailTax').textContent = `$${order.tax}`;
        document.getElementById('detailTotal').textContent = `$${order.total}`;
        
        // 填充備註
        document.getElementById('detailNotes').textContent = order.notes;

        // 顯示 Modal
        const modal = new bootstrap.Modal(document.getElementById('orderDetailModal'));
        modal.show();
    } catch (error) {
        showError(error.message);
    }
}

// 更新訂單狀態
function updateStatus(orderId) {
    document.getElementById('updateOrderId').value = orderId;
    const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
    modal.show();
}

// 執行更新訂單狀態
async function updateOrderStatus() {
    try {
        const orderId = document.getElementById('updateOrderId').value;
        const newStatus = document.getElementById('newStatus').value;
        const notes = document.getElementById('statusNotes').value;

        const response = await fetch('../api/orders.php?action=update-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: orderId,
                status: newStatus,
                notes: notes
            })
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message);
        }

        // 關閉 Modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('updateStatusModal'));
        modal.hide();

        // 重新載入訂單列表
        loadOrders();

        showSuccess('訂單狀態已更新');
    } catch (error) {
        showError(error.message);
    }
}

// 刪除訂單
async function deleteOrder(orderId) {
    if (!confirm('確定要刪除這個訂單嗎？')) return;

    try {
        const response = await fetch('../api/orders.php?action=delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: orderId
            })
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message);
        }

        // 重新載入訂單列表
        loadOrders();

        showSuccess('訂單已刪除');
    } catch (error) {
        showError(error.message);
    }
}

// 匯出訂單
function exportOrders() {
    const params = new URLSearchParams({
        action: 'export',
        search: searchInput.value,
        status: statusFilter.value,
        type: typeFilter.value,
        date: dateFilter.value
    });

    window.location.href = `../api/orders.php?${params}`;
}

// 新增商品項目
function addItem() {
    const itemsContainer = document.getElementById('orderItems');
    const itemCount = itemsContainer.children.length;
    
    const itemDiv = document.createElement('div');
    itemDiv.className = 'row mb-2';
    itemDiv.innerHTML = `
        <div class="col-md-5">
            <input type="text" class="form-control" name="items[${itemCount}][name]" placeholder="商品名稱" required>
        </div>
        <div class="col-md-2">
            <input type="number" class="form-control" name="items[${itemCount}][price]" placeholder="價格" required>
        </div>
        <div class="col-md-2">
            <input type="number" class="form-control" name="items[${itemCount}][quantity]" placeholder="數量" required>
        </div>
        <div class="col-md-3">
            <button type="button" class="btn btn-danger" onclick="removeItem(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    
    itemsContainer.appendChild(itemDiv);
}

// 移除商品項目
function removeItem(button) {
    const itemDiv = button.closest('.row');
    itemDiv.remove();
}

// 建立新訂單
async function createOrder() {
    try {
        const form = document.getElementById('newOrderForm');
        const formData = new FormData(form);
        
        // 收集商品明細
        const items = [];
        const itemRows = document.querySelectorAll('#orderItems .row');
        itemRows.forEach(row => {
            const name = row.querySelector('input[name$="[name]"]').value;
            const price = parseFloat(row.querySelector('input[name$="[price]"]').value);
            const quantity = parseInt(row.querySelector('input[name$="[quantity]"]').value);
            
            items.push({
                name,
                price,
                quantity
            });
        });
        
        // 準備訂單資料
        const orderData = {
            customer: {
                name: formData.get('customer_name'),
                phone: formData.get('customer_phone')
            },
            type: formData.get('type'),
            items: items,
            discount: parseFloat(formData.get('discount')) || 0,
            tax: parseFloat(formData.get('tax')) || 0,
            notes: formData.get('notes')
        };
        
        const response = await fetch('../api/orders.php?action=create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(orderData)
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message);
        }
        
        // 關閉 Modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('newOrderModal'));
        modal.hide();
        
        // 重置表單
        form.reset();
        document.getElementById('orderItems').innerHTML = '';
        addItem(); // 添加一個空的商品項目
        
        // 重新載入訂單列表
        loadOrders();
        
        showSuccess('訂單已建立');
    } catch (error) {
        showError(error.message);
    }
}

// 輔助函數：取得訂單類型名稱
function getOrderTypeName(type) {
    const types = {
        'dine-in': '內用',
        'takeout': '外帶',
        'delivery': '外送'
    };
    return types[type] || type;
}

// 輔助函數：取得狀態名稱
function getStatusName(status) {
    const statuses = {
        'pending': '待處理',
        'confirmed': '已確認',
        'preparing': '準備中',
        'ready': '已完成',
        'delivered': '已送達',
        'cancelled': '已取消'
    };
    return statuses[status] || status;
}

// 輔助函數：取得狀態標籤樣式
function getStatusBadgeClass(status) {
    const classes = {
        'pending': 'warning',
        'confirmed': 'info',
        'preparing': 'primary',
        'ready': 'success',
        'delivered': 'secondary',
        'cancelled': 'danger'
    };
    return classes[status] || 'secondary';
}

// 輔助函數：格式化日期
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('zh-TW', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// 輔助函數：顯示成功訊息
function showSuccess(message) {
    const toast = document.createElement('div');
    toast.className = 'toast align-items-center text-white bg-success border-0';
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-check-circle me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    const container = document.querySelector('.toast-container');
    container.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// 輔助函數：顯示錯誤訊息
function showError(message) {
    const toast = document.createElement('div');
    toast.className = 'toast align-items-center text-white bg-danger border-0';
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-exclamation-circle me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    const container = document.querySelector('.toast-container');
    container.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// 載入統計資料
async function loadStatistics() {
    try {
        const response = await fetch('../api/orders.php?action=statistics');
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message);
        }

        const stats = data.data;
        
        // 更新統計卡片
        document.getElementById('todayOrders').textContent = stats.todayOrders;
        document.getElementById('todayRevenue').textContent = `$${stats.todayRevenue}`;
        document.getElementById('avgOrderAmount').textContent = `$${stats.avgOrderAmount}`;
        document.getElementById('pendingOrders').textContent = stats.pendingOrders;
        
        // 更新圖表
        updateOrderTypeChart(stats.orderTypeDistribution);
        updateOrderStatusChart(stats.orderStatusDistribution);
    } catch (error) {
        showError(error.message);
    }
}

// 更新訂單類型分布圖表
function updateOrderTypeChart(data) {
    const ctx = document.getElementById('orderTypeChart').getContext('2d');
    
    if (orderTypeChart) {
        orderTypeChart.destroy();
    }
    
    orderTypeChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['內用', '外帶', '外送'],
            datasets: [{
                data: [
                    data['dine-in'] || 0,
                    data['takeout'] || 0,
                    data['delivery'] || 0
                ],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// 更新訂單狀態分布圖表
function updateOrderStatusChart(data) {
    const ctx = document.getElementById('orderStatusChart').getContext('2d');
    
    if (orderStatusChart) {
        orderStatusChart.destroy();
    }
    
    orderStatusChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['待處理', '已確認', '準備中', '已完成', '已送達', '已取消'],
            datasets: [{
                label: '訂單數量',
                data: [
                    data['pending'] || 0,
                    data['confirmed'] || 0,
                    data['preparing'] || 0,
                    data['ready'] || 0,
                    data['delivered'] || 0,
                    data['cancelled'] || 0
                ],
                backgroundColor: [
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(23, 162, 184, 0.8)',
                    'rgba(13, 110, 253, 0.8)',
                    'rgba(25, 135, 84, 0.8)',
                    'rgba(108, 117, 125, 0.8)',
                    'rgba(220, 53, 69, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// 更新報表數據
function updateReportData(report) {
    // 更新摘要數據
    document.getElementById('reportTotalOrders').textContent = report.summary.totalOrders;
    document.getElementById('reportTotalRevenue').textContent = `$${report.summary.totalRevenue}`;
    document.getElementById('reportAvgOrderAmount').textContent = `$${report.summary.avgOrderAmount}`;
    document.getElementById('reportCancelledOrders').textContent = report.summary.cancelledOrders;
    
    // 更新客戶分析摘要
    document.getElementById('reportTotalCustomers').textContent = report.summary.totalCustomers;
    document.getElementById('reportRepeatCustomers').textContent = report.summary.repeatCustomers;
    document.getElementById('reportAvgOrderPerCustomer').textContent = report.summary.avgOrderPerCustomer.toFixed(2);
    
    // 更新趨勢圖表
    updateOrderTrendChart(report.trends);
    updateRevenueTrendChart(report.trends);
    updateCustomerTrendChart(report.trends);
    
    // 更新熱門商品圖表
    updateTopProductsChart(report.topProducts);
    
    // 更新時段分析圖表
    updateTimeAnalysisChart(report.timeAnalysis);
    
    // 更新客戶分析表格
    updateCustomerAnalysisTable(report.customerAnalysis);
    
    // 新增：更新客單價分布圖表
    updateOrderAmountDistributionChart(report.orderAmountDistribution);
    
    // 新增：更新商品類別分析圖表
    updateProductCategoryChart(report.productCategories);
    
    // 新增：更新雷達圖
    updateTimeAnalysisRadarChart(report.timeAnalysis);
    updateOrderTypeRadarChart(report.orderTypeAnalysis);
}

// 更新客戶趨勢圖表
function updateCustomerTrendChart(data) {
    const ctx = document.getElementById('customerTrendChart').getContext('2d');
    
    if (window.customerTrendChart) {
        window.customerTrendChart.destroy();
    }
    
    window.customerTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: '客戶數',
                data: data.customerCounts,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// 更新熱門商品圖表
function updateTopProductsChart(products) {
    const ctx = document.getElementById('topProductsChart').getContext('2d');
    
    if (window.topProductsChart) {
        window.topProductsChart.destroy();
    }
    
    window.topProductsChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: products.map(p => p.name),
            datasets: [{
                label: '銷量',
                data: products.map(p => p.quantity),
                backgroundColor: 'rgba(54, 162, 235, 0.8)'
            }, {
                label: '營業額',
                data: products.map(p => p.revenue),
                backgroundColor: 'rgba(75, 192, 192, 0.8)'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// 更新時段分析圖表
function updateTimeAnalysisChart(data) {
    const ctx = document.getElementById('timeAnalysisChart').getContext('2d');
    
    if (window.timeAnalysisChart) {
        window.timeAnalysisChart.destroy();
    }
    
    const timeLabels = {
        'morning': '早上 (6-11點)',
        'afternoon': '下午 (11-17點)',
        'evening': '晚上 (17-22點)',
        'night': '深夜 (22-6點)'
    };
    
    window.timeAnalysisChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Object.keys(data).map(key => timeLabels[key]),
            datasets: [{
                label: '訂單數',
                data: Object.values(data).map(d => d.orders),
                backgroundColor: 'rgba(255, 99, 132, 0.8)'
            }, {
                label: '營業額',
                data: Object.values(data).map(d => d.revenue),
                backgroundColor: 'rgba(54, 162, 235, 0.8)'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// 更新客戶分析表格
function updateCustomerAnalysisTable(customers) {
    const tbody = document.getElementById('customerAnalysisTable');
    tbody.innerHTML = '';
    
    customers.forEach(customer => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${customer.name}</td>
            <td>${customer.orders}</td>
            <td>$${customer.total.toFixed(2)}</td>
            <td>$${(customer.total / customer.orders).toFixed(2)}</td>
        `;
        tbody.appendChild(row);
    });
}

// 載入報表
async function loadReport(type = currentReportType) {
    try {
        currentReportType = type;
        const date = document.getElementById('reportDate').value;
        const endDate = document.getElementById('reportEndDate')?.value;
        const orderType = document.getElementById('reportType').value;
        
        const params = new URLSearchParams({
            action: 'report',
            type: type,
            date: date,
            order_type: orderType
        });
        
        if (endDate) {
            params.append('end_date', endDate);
        }
        
        const response = await fetch(`../api/orders.php?${params}`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message);
        }
        
        updateReportData(data.data);
    } catch (error) {
        showError(error.message);
    }
}

// 更新訂單趨勢圖表
function updateOrderTrendChart(data) {
    const ctx = document.getElementById('orderTrendChart').getContext('2d');
    
    if (orderTrendChart) {
        orderTrendChart.destroy();
    }
    
    orderTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: '訂單數量',
                data: data.orderCounts,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// 更新營業額趨勢圖表
function updateRevenueTrendChart(data) {
    const ctx = document.getElementById('revenueTrendChart').getContext('2d');
    
    if (revenueTrendChart) {
        revenueTrendChart.destroy();
    }
    
    revenueTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: '營業額',
                data: data.revenues,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value;
                        }
                    }
                }
            }
        }
    });
}

// 新增：更新客單價分布圖表
function updateOrderAmountDistributionChart(data) {
    const ctx = document.getElementById('orderAmountDistributionChart').getContext('2d');
    
    if (window.orderAmountDistributionChart) {
        window.orderAmountDistributionChart.destroy();
    }
    
    window.orderAmountDistributionChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.ranges,
            datasets: [{
                label: '訂單數量',
                data: data.counts,
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `訂單數: ${context.raw}`;
                        }
                    }
                }
            }
        }
    });
}

// 新增：更新商品類別分析圖表
function updateProductCategoryChart(data) {
    const ctx = document.getElementById('productCategoryChart').getContext('2d');
    
    if (window.productCategoryChart) {
        window.productCategoryChart.destroy();
    }
    
    window.productCategoryChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.categories,
            datasets: [{
                data: data.quantities,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// 新增：更新時段分析雷達圖
function updateTimeAnalysisRadarChart(data) {
    const ctx = document.getElementById('timeAnalysisRadarChart').getContext('2d');
    
    if (window.timeAnalysisRadarChart) {
        window.timeAnalysisRadarChart.destroy();
    }
    
    const timeLabels = {
        'morning': '早上 (6-11點)',
        'afternoon': '下午 (11-17點)',
        'evening': '晚上 (17-22點)',
        'night': '深夜 (22-6點)'
    };
    
    window.timeAnalysisRadarChart = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: Object.keys(data).map(key => timeLabels[key]),
            datasets: [{
                label: '訂單數',
                data: Object.values(data).map(d => d.orders),
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255, 99, 132, 1)',
                pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(255, 99, 132, 1)'
            }, {
                label: '營業額',
                data: Object.values(data).map(d => d.revenue),
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(54, 162, 235, 1)'
            }]
        },
        options: {
            responsive: true,
            scales: {
                r: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// 新增：更新訂單類型分析雷達圖
function updateOrderTypeRadarChart(data) {
    const ctx = document.getElementById('orderTypeRadarChart').getContext('2d');
    
    if (window.orderTypeRadarChart) {
        window.orderTypeRadarChart.destroy();
    }
    
    const typeLabels = {
        'dine-in': '內用',
        'takeout': '外帶',
        'delivery': '外送'
    };
    
    window.orderTypeRadarChart = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: Object.keys(data).map(key => typeLabels[key]),
            datasets: [{
                label: '訂單數',
                data: Object.values(data).map(d => d.orders),
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                pointBackgroundColor: 'rgba(75, 192, 192, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(75, 192, 192, 1)'
            }, {
                label: '營業額',
                data: Object.values(data).map(d => d.revenue),
                backgroundColor: 'rgba(255, 206, 86, 0.2)',
                borderColor: 'rgba(255, 206, 86, 1)',
                pointBackgroundColor: 'rgba(255, 206, 86, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(255, 206, 86, 1)'
            }]
        },
        options: {
            responsive: true,
            scales: {
                r: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// 新增：匯出報表
async function exportReport(format = 'csv') {
    try {
        const date = document.getElementById('reportDate').value;
        const endDate = document.getElementById('reportEndDate')?.value;
        const orderType = document.getElementById('reportType').value;
        
        const params = new URLSearchParams({
            action: 'export-report',
            type: currentReportType,
            date: date,
            order_type: orderType,
            format: format
        });
        
        if (endDate) {
            params.append('end_date', endDate);
        }
        
        const response = await fetch(`../api/orders.php?${params}`);
        
        if (!response.ok) {
            throw new Error('匯出報表失敗');
        }
        
        // 根據格式處理下載
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `order_report_${date}_${format}.${format}`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        showSuccess('報表匯出成功');
    } catch (error) {
        showError(error.message);
    }
}

// 新增：匯出圖表
function exportCharts() {
    const charts = [
        { id: 'orderTrendChart', name: '訂單趨勢' },
        { id: 'revenueTrendChart', name: '營業額趨勢' },
        { id: 'orderAmountDistributionChart', name: '客單價分布' },
        { id: 'productCategoryChart', name: '商品類別分析' },
        { id: 'timeAnalysisRadarChart', name: '時段分析' },
        { id: 'orderTypeRadarChart', name: '訂單類型分析' }
    ];
    
    charts.forEach(chart => {
        const canvas = document.getElementById(chart.id);
        if (canvas) {
            const link = document.createElement('a');
            link.download = `${chart.name}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
        }
    });
    
    showSuccess('圖表匯出成功');
} 