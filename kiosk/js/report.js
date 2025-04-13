// 全域變數
let categoryChart = null;
let paymentChart = null;

// 初始化頁面
document.addEventListener('DOMContentLoaded', () => {
    // 設置預設日期為今天
    document.getElementById('reportDate').valueAsDate = new Date();
    
    // 綁定事件處理器
    document.getElementById('generateReport').addEventListener('click', loadDailyReport);
    document.querySelectorAll('[data-report-type]').forEach(button => {
        button.addEventListener('click', switchReportType);
    });
    
    // 初始載入今日報表
    loadDailyReport();
});

// 切換報表類型
function switchReportType(e) {
    const type = e.target.dataset.reportType;
    document.querySelectorAll('[data-report-type]').forEach(btn => {
        btn.classList.remove('active');
    });
    e.target.classList.add('active');
    
    if (type === 'daily') {
        document.getElementById('dailyReport').style.display = 'block';
        document.getElementById('monthlyReport').style.display = 'none';
        document.getElementById('dateSelector').style.display = 'block';
    } else {
        document.getElementById('dailyReport').style.display = 'none';
        document.getElementById('monthlyReport').style.display = 'block';
        document.getElementById('dateSelector').style.display = 'none';
    }
}

// 載入每日報表
async function loadDailyReport() {
    const date = document.getElementById('reportDate').value;
    try {
        const response = await fetch(`/api/reports/daily?date=${date}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('載入報表失敗');
        }
        
        const data = await response.json();
        updateDashboard(data);
        updateCharts(data);
    } catch (error) {
        console.error('載入報表錯誤:', error);
        alert('載入報表時發生錯誤');
    }
}

// 更新儀表板數據
function updateDashboard(data) {
    document.getElementById('totalSales').textContent = `NT$ ${data.total_sales.toLocaleString()}`;
    document.getElementById('orderCount').textContent = data.order_count;
    document.getElementById('averageOrderValue').textContent = `NT$ ${data.average_order_value.toLocaleString()}`;
    document.getElementById('totalItems').textContent = data.total_items;
}

// 更新圖表
function updateCharts(data) {
    // 分類銷售圖表
    if (categoryChart) {
        categoryChart.destroy();
    }
    
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    categoryChart = new Chart(categoryCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(data.category_sales),
            datasets: [{
                label: '銷售金額',
                data: Object.values(data.category_sales),
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: '分類銷售統計'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => `NT$ ${value.toLocaleString()}`
                    }
                }
            }
        }
    });
    
    // 支付方式圖表
    if (paymentChart) {
        paymentChart.destroy();
    }
    
    const paymentCtx = document.getElementById('paymentChart').getContext('2d');
    paymentChart = new Chart(paymentCtx, {
        type: 'pie',
        data: {
            labels: Object.keys(data.payment_methods),
            datasets: [{
                data: Object.values(data.payment_methods),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.5)',
                    'rgba(54, 162, 235, 0.5)',
                    'rgba(255, 206, 86, 0.5)',
                    'rgba(75, 192, 192, 0.5)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: '支付方式統計'
                },
                tooltip: {
                    callbacks: {
                        label: context => {
                            const value = context.raw;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${context.label}: NT$ ${value.toLocaleString()} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
} 