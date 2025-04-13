<?php
/**
 * 訂單管理頁面
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

// 檢查登入狀態和權限
checkLogin();
checkPermission('orders.manage');

// 設置頁面標題
$pageTitle = '訂單管理';

// 獲取訂單狀態
$status = $_GET['status'] ?? 'all';

// 獲取日期範圍
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// 引入布局文件
require_once __DIR__ . '/layout.php';
?>

<!-- 操作按鈕 -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h2">訂單管理</h1>
    <div>
        <button type="button" class="btn btn-primary" id="exportOrders">
            <i class="fas fa-download me-2"></i>匯出訂單
        </button>
    </div>
</div>

<!-- 訂單篩選 -->
<div class="card mb-4">
    <div class="card-body">
        <form id="filterForm" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">訂單狀態</label>
                <select class="form-select" name="status" id="orderStatus">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>全部</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>待付款</option>
                    <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>已付款</option>
                    <option value="preparing" <?php echo $status === 'preparing' ? 'selected' : ''; ?>>準備中</option>
                    <option value="serving" <?php echo $status === 'serving' ? 'selected' : ''; ?>>出餐中</option>
                    <option value="ready" <?php echo $status === 'ready' ? 'selected' : ''; ?>>待取餐</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>已完成</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>已取消</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">開始日期</label>
                <input type="date" class="form-control" name="start_date" 
                       value="<?php echo $startDate; ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">結束日期</label>
                <input type="date" class="form-control" name="end_date" 
                       value="<?php echo $endDate; ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">訂單編號</label>
                <input type="text" class="form-control" name="order_no" 
                       placeholder="輸入訂單編號">
            </div>
            <div class="col-md-2 align-self-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>查詢
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 訂單列表 -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="ordersTable">
                <thead>
                    <tr>
                        <th>訂單編號</th>
                        <th>下單時間</th>
                        <th>訂單金額</th>
                        <th>付款方式</th>
                        <th>訂單狀態</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" class="text-center">載入中...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- 分頁 -->
        <nav aria-label="訂單分頁" class="mt-4">
            <ul class="pagination justify-content-center" id="pagination">
            </ul>
        </nav>
    </div>
</div>

<!-- 訂單詳情模態框 -->
<div class="modal fade" id="orderDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">訂單詳情</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>訂單信息</h6>
                        <table class="table table-sm">
                            <tr>
                                <th>訂單編號：</th>
                                <td id="detailOrderNo"></td>
                            </tr>
                            <tr>
                                <th>下單時間：</th>
                                <td id="detailOrderTime"></td>
                            </tr>
                            <tr>
                                <th>訂單狀態：</th>
                                <td id="detailOrderStatus"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>付款信息</h6>
                        <table class="table table-sm">
                            <tr>
                                <th>付款方式：</th>
                                <td id="detailPaymentMethod"></td>
                            </tr>
                            <tr>
                                <th>付款狀態：</th>
                                <td id="detailPaymentStatus"></td>
                            </tr>
                            <tr>
                                <th>付款時間：</th>
                                <td id="detailPaymentTime"></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <h6>訂單商品</h6>
                <div class="table-responsive">
                    <table class="table table-sm" id="detailOrderItems">
                        <thead>
                            <tr>
                                <th>商品名稱</th>
                                <th>單價</th>
                                <th>數量</th>
                                <th>小計</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6>訂單金額</h6>
                        <table class="table table-sm">
                            <tr>
                                <th>商品總額：</th>
                                <td id="detailSubtotal"></td>
                            </tr>
                            <tr>
                                <th>稅金：</th>
                                <td id="detailTax"></td>
                            </tr>
                            <tr>
                                <th>折扣：</th>
                                <td id="detailDiscount"></td>
                            </tr>
                            <tr>
                                <th>實付金額：</th>
                                <td id="detailTotal"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
                <button type="button" class="btn btn-primary" id="updateOrderStatus">更新狀態</button>
            </div>
        </div>
    </div>
</div>

<!-- 添加音效 -->
<audio id="notificationSound" src="/kiosk/admin/assets/sounds/notification.mp3"></audio>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
    let totalPages = 1;
    
    // 表單提交
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        loadOrders();
    });
    
    // 匯出訂單
    document.getElementById('exportOrders').addEventListener('click', function() {
        const status = document.getElementById('orderStatus').value;
        const startDate = document.querySelector('input[name="start_date"]').value;
        const endDate = document.querySelector('input[name="end_date"]').value;
        const orderNo = document.querySelector('input[name="order_no"]').value;
        
        window.location.href = `api/admin/orders.php?action=export&status=${status}&start_date=${startDate}&end_date=${endDate}&order_no=${orderNo}`;
    });
    
    // 載入訂單列表
    function loadOrders() {
        const status = document.getElementById('orderStatus').value;
        const startDate = document.querySelector('input[name="start_date"]').value;
        const endDate = document.querySelector('input[name="end_date"]').value;
        const orderNo = document.querySelector('input[name="order_no"]').value;
        
        fetch(`api/admin/orders.php?page=${currentPage}&status=${status}&start_date=${startDate}&end_date=${endDate}&order_no=${orderNo}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateOrdersTable(data.data.orders);
                    updatePagination(data.data.total_pages);
                } else {
                    alert(data.message || '載入訂單失敗');
                }
            });
    }
    
    // 更新訂單表格
    function updateOrdersTable(orders) {
        const tbody = document.querySelector('#ordersTable tbody');
        tbody.innerHTML = orders.map(order => `
            <tr>
                <td>${order.order_no}</td>
                <td>${formatDateTime(order.created_at)}</td>
                <td>${formatMoney(order.total_amount)}</td>
                <td>${order.payment_method}</td>
                <td>
                    <span class="badge bg-${getStatusColor(order.status)}">
                        ${getStatusText(order.status)}
                    </span>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-info" 
                            onclick="showOrderDetail(${order.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-warning" 
                            onclick="updateOrderStatus(${order.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }
    
    // 更新分頁
    function updatePagination(totalPages) {
        const pagination = document.getElementById('pagination');
        let html = '';
        
        // 上一頁
        html += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="changePage(${currentPage - 1})">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;
        
        // 頁碼
        for (let i = 1; i <= totalPages; i++) {
            html += `
                <li class="page-item ${currentPage === i ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
                </li>
            `;
        }
        
        // 下一頁
        html += `
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="changePage(${currentPage + 1})">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;
        
        pagination.innerHTML = html;
    }
    
    // 格式化金額
    function formatMoney(amount) {
        return new Intl.NumberFormat('zh-TW', {
            style: 'currency',
            currency: 'TWD'
        }).format(amount);
    }
    
    // 格式化日期時間
    function formatDateTime(datetime) {
        return new Date(datetime).toLocaleString('zh-TW');
    }
    
    // 獲取狀態顏色
    function getStatusColor(status) {
        switch (status) {
            case 'pending': return 'warning';
            case 'paid': return 'info';
            case 'preparing': return 'primary';
            case 'ready': return 'success';
            case 'completed': return 'secondary';
            case 'cancelled': return 'danger';
            default: return 'secondary';
        }
    }
    
    // 獲取狀態文字
    function getStatusText(status) {
        switch (status) {
            case 'pending': return '待付款';
            case 'paid': return '已付款';
            case 'preparing': return '準備中';
            case 'ready': return '待取餐';
            case 'completed': return '已完成';
            case 'cancelled': return '已取消';
            default: return status;
        }
    }
    
    // 切換頁面
    window.changePage = function(page) {
        if (page >= 1 && page <= totalPages) {
            currentPage = page;
            loadOrders();
        }
    };
    
    // 顯示訂單詳情
    window.showOrderDetail = function(orderId) {
        fetch(`api/admin/orders.php?id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const order = data.data;
                    
                    // 更新訂單信息
                    document.getElementById('detailOrderNo').textContent = order.order_no;
                    document.getElementById('detailOrderTime').textContent = formatDateTime(order.created_at);
                    document.getElementById('detailOrderStatus').textContent = getStatusText(order.status);
                    
                    // 更新付款信息
                    document.getElementById('detailPaymentMethod').textContent = order.payment_method;
                    document.getElementById('detailPaymentStatus').textContent = order.payment_status;
                    document.getElementById('detailPaymentTime').textContent = order.payment_time ? formatDateTime(order.payment_time) : '-';
                    
                    // 更新訂單商品
                    const tbody = document.querySelector('#detailOrderItems tbody');
                    tbody.innerHTML = order.items.map(item => `
                        <tr>
                            <td>${item.name}</td>
                            <td>${formatMoney(item.price)}</td>
                            <td>${item.quantity}</td>
                            <td>${formatMoney(item.price * item.quantity)}</td>
                        </tr>
                    `).join('');
                    
                    // 更新訂單金額
                    document.getElementById('detailSubtotal').textContent = formatMoney(order.subtotal);
                    document.getElementById('detailTax').textContent = formatMoney(order.tax_amount);
                    document.getElementById('detailDiscount').textContent = formatMoney(order.discount_amount);
                    document.getElementById('detailTotal').textContent = formatMoney(order.total_amount);
                    
                    // 添加操作按鈕
                    let actionButtons = '';
                    if (order.status === 'preparing') {
                        actionButtons += '<button class="btn btn-warning" onclick="updateOrderStatus(' + order.id + ', \'serving\')">開始出餐</button>';
                    } else if (order.status === 'serving') {
                        actionButtons += '<button class="btn btn-success" onclick="updateOrderStatus(' + order.id + ', \'ready\')">出餐完成</button>';
                    } else if (order.status === 'ready') {
                        actionButtons += '<button class="btn btn-info" onclick="callOrder(' + order.id + ')">呼叫取餐</button>';
                    }
                    
                    $('#orderDetailModal .modal-footer').html(`
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
                        ${actionButtons}
                    `);
                    
                    // 顯示模態框
                    new bootstrap.Modal(document.getElementById('orderDetailModal')).show();
                } else {
                    alert(data.message || '載入訂單詳情失敗');
                }
            });
    };
    
    // 更新訂單狀態
    window.updateOrderStatus = function(orderId) {
        const status = prompt('請輸入新的訂單狀態（pending/paid/preparing/ready/completed/cancelled）：');
        if (status && ['pending', 'paid', 'preparing', 'ready', 'completed', 'cancelled'].includes(status)) {
            fetch('api/admin/orders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'update_status',
                    order_id: orderId,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('訂單狀態已更新');
                    loadOrders();
                } else {
                    alert(data.message || '更新訂單狀態失敗');
                }
            });
        }
    };
    
    // 播放提示音
    function playNotificationSound() {
        const audio = document.getElementById('notificationSound');
        audio.play();
    }
    
    // 叫號功能
    function callOrder(orderId) {
        $.ajax({
            url: '/kiosk/admin/api/call_order.php',
            method: 'POST',
            data: { order_id: orderId },
            success: function(response) {
                if (response.success) {
                    playNotificationSound();
                    alert('已呼叫訂單 ' + response.order_no);
                } else {
                    alert('呼叫失敗：' + response.message);
                }
            }
        });
    }
    
    // 初始載入訂單
    loadOrders();
});
</script> 