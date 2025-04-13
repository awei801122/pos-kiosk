<?php
/**
 * 報表統計頁面
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

// 檢查登入狀態和權限
checkLogin();
checkPermission('reports.view');

// 設置頁面標題
$pageTitle = '報表統計';

// 獲取報表類型
$reportType = $_GET['type'] ?? 'sales';

// 獲取日期範圍
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// 引入布局文件
require_once __DIR__ . '/layout.php';
?>

<!-- 操作按鈕 -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h2">報表統計</h1>
    <div>
        <button type="button" class="btn btn-primary" id="exportReport">
            <i class="fas fa-download me-2"></i>匯出報表
        </button>
    </div>
</div>

<!-- 報表選項 -->
<div class="card mb-4">
    <div class="card-body">
        <form id="reportForm" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">報表類型</label>
                <select class="form-select" name="type" id="reportType">
                    <option value="sales" <?php echo $reportType === 'sales' ? 'selected' : ''; ?>>銷售報表</option>
                    <option value="inventory" <?php echo $reportType === 'inventory' ? 'selected' : ''; ?>>庫存報表</option>
                    <option value="logs" <?php echo $reportType === 'logs' ? 'selected' : ''; ?>>操作日誌</option>
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
            <div class="col-md-3 align-self-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>查詢
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 報表內容 -->
<div class="card">
    <div class="card-body">
        <!-- 銷售報表 -->
        <div id="salesReport" class="report-section" <?php echo $reportType !== 'sales' ? 'style="display:none"' : ''; ?>>
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">總銷售額</h5>
                            <h3 class="card-text" id="totalSales">計算中...</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">訂單數量</h5>
                            <h3 class="card-text" id="totalOrders">計算中...</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">平均訂單金額</h5>
                            <h3 class="card-text" id="avgOrderAmount">計算中...</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5 class="card-title">銷售商品數量</h5>
                            <h3 class="card-text" id="totalItems">計算中...</h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <h5>熱銷商品排行</h5>
                    <div class="table-responsive">
                        <table class="table table-hover" id="topProductsTable">
                            <thead>
                                <tr>
                                    <th>商品名稱</th>
                                    <th>銷售數量</th>
                                    <th>銷售金額</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="3" class="text-center">載入中...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5>每日銷售趨勢</h5>
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- 庫存報表 -->
        <div id="inventoryReport" class="report-section" <?php echo $reportType !== 'inventory' ? 'style="display:none"' : ''; ?>>
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h5 class="card-title">低庫存商品</h5>
                            <h3 class="card-text" id="lowStockCount">計算中...</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">庫存總值</h5>
                            <h3 class="card-text" id="totalStockValue">計算中...</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">庫存商品數</h5>
                            <h3 class="card-text" id="totalStockItems">計算中...</h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="inventoryTable">
                    <thead>
                        <tr>
                            <th>商品名稱</th>
                            <th>當前庫存</th>
                            <th>最低庫存</th>
                            <th>單位</th>
                            <th>狀態</th>
                            <th>最後更新</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="text-center">載入中...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 操作日誌 -->
        <div id="logsReport" class="report-section" <?php echo $reportType !== 'logs' ? 'style="display:none"' : ''; ?>>
            <div class="table-responsive">
                <table class="table table-hover" id="logsTable">
                    <thead>
                        <tr>
                            <th>時間</th>
                            <th>用戶</th>
                            <th>操作</th>
                            <th>IP地址</th>
                            <th>詳細信息</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="text-center">載入中...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 載入 Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 報表類型切換
    document.getElementById('reportType').addEventListener('change', function() {
        document.querySelectorAll('.report-section').forEach(section => {
            section.style.display = 'none';
        });
        document.getElementById(this.value + 'Report').style.display = 'block';
        loadReport();
    });

    // 表單提交
    document.getElementById('reportForm').addEventListener('submit', function(e) {
        e.preventDefault();
        loadReport();
    });

    // 匯出報表
    document.getElementById('exportReport').addEventListener('click', function() {
        const type = document.getElementById('reportType').value;
        const startDate = document.querySelector('input[name="start_date"]').value;
        const endDate = document.querySelector('input[name="end_date"]').value;
        
        window.location.href = `api/admin/reports.php?action=export&type=${type}&start_date=${startDate}&end_date=${endDate}`;
    });

    // 載入報表數據
    function loadReport() {
        const type = document.getElementById('reportType').value;
        const startDate = document.querySelector('input[name="start_date"]').value;
        const endDate = document.querySelector('input[name="end_date"]').value;
        
        fetch(`api/admin/reports.php?type=${type}&start_date=${startDate}&end_date=${endDate}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    switch (type) {
                        case 'sales':
                            updateSalesReport(data.data);
                            break;
                        case 'inventory':
                            updateInventoryReport(data.data);
                            break;
                        case 'logs':
                            updateLogsReport(data.data);
                            break;
                    }
                } else {
                    alert(data.message || '載入報表失敗');
                }
            });
    }

    // 更新銷售報表
    function updateSalesReport(data) {
        // 更新統計數據
        document.getElementById('totalSales').textContent = formatMoney(data.summary.total_sales);
        document.getElementById('totalOrders').textContent = data.summary.total_orders;
        document.getElementById('avgOrderAmount').textContent = formatMoney(data.summary.avg_order_amount);
        document.getElementById('totalItems').textContent = data.summary.total_items;

        // 更新熱銷商品表格
        const topProductsHtml = data.top_products.map(product => `
            <tr>
                <td>${escapeHtml(product.name)}</td>
                <td>${product.quantity}</td>
                <td>${formatMoney(product.total_amount)}</td>
            </tr>
        `).join('');
        document.querySelector('#topProductsTable tbody').innerHTML = topProductsHtml;

        // 更新銷售趨勢圖表
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.daily_sales.map(item => item.date),
                datasets: [{
                    label: '日銷售額',
                    data: data.daily_sales.map(item => item.amount),
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
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

    // 更新庫存報表
    function updateInventoryReport(data) {
        // 更新統計數據
        document.getElementById('lowStockCount').textContent = data.summary.low_stock_count;
        document.getElementById('totalStockValue').textContent = formatMoney(data.summary.total_stock_value);
        document.getElementById('totalStockItems').textContent = data.summary.total_items;

        // 更新庫存表格
        const inventoryHtml = data.items.map(item => `
            <tr>
                <td>${escapeHtml(item.name)}</td>
                <td>${item.quantity}</td>
                <td>${item.min_quantity}</td>
                <td>${escapeHtml(item.unit)}</td>
                <td>
                    <span class="badge bg-${item.quantity <= item.min_quantity ? 'danger' : 'success'}">
                        ${item.quantity <= item.min_quantity ? '低庫存' : '正常'}
                    </span>
                </td>
                <td>${formatDateTime(item.updated_at)}</td>
            </tr>
        `).join('');
        document.querySelector('#inventoryTable tbody').innerHTML = inventoryHtml;
    }

    // 更新操作日誌
    function updateLogsReport(data) {
        const logsHtml = data.logs.map(log => `
            <tr>
                <td>${formatDateTime(log.created_at)}</td>
                <td>${escapeHtml(log.username)}</td>
                <td>${escapeHtml(log.action)}</td>
                <td>${log.ip_address}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-info" 
                            onclick="showLogDetails('${escapeHtml(JSON.stringify(log.details))}')">
                        <i class="fas fa-info-circle"></i>
                    </button>
                </td>
            </tr>
        `).join('');
        document.querySelector('#logsTable tbody').innerHTML = logsHtml;
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

    // HTML 轉義
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // 顯示日誌詳細信息
    window.showLogDetails = function(details) {
        alert(JSON.stringify(JSON.parse(details), null, 2));
    };

    // 初始載入報表
    loadReport();
});
</script> 