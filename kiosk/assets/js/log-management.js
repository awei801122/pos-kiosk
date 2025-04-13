// 全局變量
let currentPage = 1;
let totalPages = 1;
let logsPerPage = 20;
let currentFilters = {
    level: 'all',
    date: '',
    search: ''
};

// DOM 元素
const logList = document.getElementById('logList');
const logPagination = document.getElementById('logPagination');
const logLevel = document.getElementById('logLevel');
const logDate = document.getElementById('logDate');
const logSearch = document.getElementById('logSearch');

// 初始化
document.addEventListener('DOMContentLoaded', () => {
    // 設置默認日期為今天
    logDate.value = new Date().toISOString().split('T')[0];
    
    // 綁定事件監聽器
    logLevel.addEventListener('change', () => {
        currentFilters.level = logLevel.value;
        loadLogs();
    });
    
    logDate.addEventListener('change', () => {
        currentFilters.date = logDate.value;
        loadLogs();
    });
    
    logSearch.addEventListener('input', debounce(() => {
        currentFilters.search = logSearch.value;
        loadLogs();
    }, 500));
    
    // 加載日誌
    loadLogs();
});

// 加載日誌
async function loadLogs() {
    try {
        const response = await fetch(`../api/logs.php?action=list&page=${currentPage}&per_page=${logsPerPage}&level=${currentFilters.level}&date=${currentFilters.date}&search=${currentFilters.search}`);
        const data = await response.json();
        
        if (data.success) {
            renderLogs(data.logs);
            renderPagination(data.total_pages);
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('加載日誌失敗：' + error.message);
    }
}

// 渲染日誌列表
function renderLogs(logs) {
    logList.innerHTML = '';
    
    if (logs.length === 0) {
        logList.innerHTML = '<div class="alert alert-info">沒有找到符合條件的日誌</div>';
        return;
    }
    
    logs.forEach(log => {
        const logEntry = document.createElement('div');
        logEntry.className = `log-entry ${log.level}`;
        
        const timestamp = new Date(log.timestamp).toLocaleString();
        
        logEntry.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <span class="log-level ${log.level}">${log.level.toUpperCase()}</span>
                <span class="log-timestamp">${timestamp}</span>
            </div>
            <div class="log-message">${log.message}</div>
            ${log.context ? `<div class="log-context">${JSON.stringify(log.context, null, 2)}</div>` : ''}
        `;
        
        logList.appendChild(logEntry);
    });
}

// 渲染分頁
function renderPagination(totalPages) {
    logPagination.innerHTML = '';
    
    if (totalPages <= 1) return;
    
    // 上一頁
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `
        <a class="page-link" href="#" onclick="changePage(${currentPage - 1})">
            <i class="fa fa-chevron-left"></i>
        </a>
    `;
    logPagination.appendChild(prevLi);
    
    // 頁碼
    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${currentPage === i ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#" onclick="changePage(${i})">${i}</a>`;
        logPagination.appendChild(li);
    }
    
    // 下一頁
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `
        <a class="page-link" href="#" onclick="changePage(${currentPage + 1})">
            <i class="fa fa-chevron-right"></i>
        </a>
    `;
    logPagination.appendChild(nextLi);
}

// 切換頁碼
function changePage(page) {
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    loadLogs();
}

// 導出日誌
async function exportLogs() {
    try {
        const response = await fetch(`../api/logs.php?action=export&level=${currentFilters.level}&date=${currentFilters.date}&search=${currentFilters.search}`);
        
        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `logs_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();
        } else {
            const data = await response.json();
            showError(data.message);
        }
    } catch (error) {
        showError('導出日誌失敗：' + error.message);
    }
}

// 清空日誌
function clearLogs() {
    const modal = new bootstrap.Modal(document.getElementById('clearLogsModal'));
    modal.show();
}

// 確認清空日誌
async function confirmClearLogs() {
    try {
        const response = await fetch('../api/logs.php?action=clear', {
            method: 'POST'
        });
        const data = await response.json();
        
        if (data.success) {
            showSuccess('日誌已清空');
            loadLogs();
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('清空日誌失敗：' + error.message);
    }
}

// 顯示成功消息
function showSuccess(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.container').insertBefore(alert, document.querySelector('.card'));
    setTimeout(() => alert.remove(), 3000);
}

// 顯示錯誤消息
function showError(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.container').insertBefore(alert, document.querySelector('.card'));
    setTimeout(() => alert.remove(), 3000);
}

// 防抖函數
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
} 