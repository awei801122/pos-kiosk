// 全域變數
let inventoryData = null;
let categories = [];
let suppliers = [];

// 初始化頁面
document.addEventListener('DOMContentLoaded', async () => {
    try {
        // 載入庫存數據
        const response = await fetch('/kiosk/api/inventory.php');
        inventoryData = await response.json();
        
        // 載入分類和供應商
        categories = inventoryData.categories || [];
        suppliers = inventoryData.suppliers || [];
        
        // 更新頁面
        updateDashboard();
        updateInventoryTable();
        updateSelectOptions();
    } catch (error) {
        console.error('載入數據失敗:', error);
        alert('載入數據時發生錯誤');
    }
});

// 更新儀表板數據
function updateDashboard() {
    if (!inventoryData) return;
    
    const items = inventoryData.items || [];
    const totalItems = items.length;
    const lowStockItems = items.filter(item => item.quantity <= item.min_stock).length;
    const totalValue = items.reduce((sum, item) => sum + (item.quantity * item.cost_price), 0);
    const monthlyExpense = calculateMonthlyExpense();
    
    document.getElementById('totalItems').textContent = totalItems;
    document.getElementById('lowStockItems').textContent = lowStockItems;
    document.getElementById('totalValue').textContent = `NT$ ${totalValue.toLocaleString()}`;
    document.getElementById('monthlyExpense').textContent = `NT$ ${monthlyExpense.toLocaleString()}`;
}

// 計算本月支出
function calculateMonthlyExpense() {
    if (!inventoryData) return 0;
    
    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();
    
    return inventoryData.items.reduce((sum, item) => {
        const lastRestock = new Date(item.last_restock);
        if (lastRestock.getMonth() === currentMonth && lastRestock.getFullYear() === currentYear) {
            return sum + (item.quantity * item.cost_price);
        }
        return sum;
    }, 0);
}

// 更新庫存表格
function updateInventoryTable() {
    if (!inventoryData) return;
    
    const tbody = document.getElementById('inventoryTableBody');
    tbody.innerHTML = '';
    
    const items = inventoryData.items || [];
    items.forEach(item => {
        const tr = document.createElement('tr');
        if (item.quantity <= item.min_stock) {
            tr.classList.add('low-stock');
        }
        
        tr.innerHTML = `
            <td>${item.name}</td>
            <td>${getCategoryName(item.category)}</td>
            <td>${item.quantity}</td>
            <td>${item.unit}</td>
            <td>NT$ ${item.cost_price.toLocaleString()}</td>
            <td>NT$ ${item.selling_price.toLocaleString()}</td>
            <td>${getSupplierName(item.supplier)}</td>
            <td>
                <button class="btn btn-sm btn-primary me-1" onclick="showEditModal('${item.id}')">
                    <i class="bi bi-pencil"></i> 編輯
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteItem('${item.id}')">
                    <i class="bi bi-trash"></i> 刪除
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// 更新下拉選單選項
function updateSelectOptions() {
    // 更新分類選單
    const categorySelects = document.querySelectorAll('select[name="category"]');
    categorySelects.forEach(select => {
        select.innerHTML = '<option value="">請選擇分類</option>';
        categories.forEach(category => {
            select.innerHTML += `<option value="${category.id}">${category.name}</option>`;
        });
    });
    
    // 更新供應商選單
    const supplierSelects = document.querySelectorAll('select[name="supplier"]');
    supplierSelects.forEach(select => {
        select.innerHTML = '<option value="">請選擇供應商</option>';
        suppliers.forEach(supplier => {
            select.innerHTML += `<option value="${supplier.id}">${supplier.name}</option>`;
        });
    });
}

// 取得分類名稱
function getCategoryName(categoryId) {
    const category = categories.find(c => c.id === categoryId);
    return category ? category.name : '未知分類';
}

// 取得供應商名稱
function getSupplierName(supplierId) {
    const supplier = suppliers.find(s => s.id === supplierId);
    return supplier ? supplier.name : '未知供應商';
}

// 顯示編輯 Modal
function showEditModal(itemId) {
    const item = inventoryData.items.find(i => i.id === itemId);
    if (!item) return;
    
    const form = document.getElementById('editItemForm');
    form.elements['id'].value = item.id;
    form.elements['name'].value = item.name;
    form.elements['category'].value = item.category;
    form.elements['quantity'].value = item.quantity;
    form.elements['unit'].value = item.unit;
    form.elements['cost_price'].value = item.cost_price;
    form.elements['selling_price'].value = item.selling_price;
    form.elements['min_stock'].value = item.min_stock;
    form.elements['supplier'].value = item.supplier;
    
    const modal = new bootstrap.Modal(document.getElementById('editItemModal'));
    modal.show();
}

// 新增品項
async function addItem() {
    const form = document.getElementById('addItemForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    try {
        const response = await fetch('/kiosk/api/inventory.php/items', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        });
        
        if (response.ok) {
            const result = await response.json();
            inventoryData.items.push(result);
            updateDashboard();
            updateInventoryTable();
            bootstrap.Modal.getInstance(document.getElementById('addItemModal')).hide();
            form.reset();
        } else {
            throw new Error('新增失敗');
        }
    } catch (error) {
        console.error('新增品項失敗:', error);
        alert('新增品項時發生錯誤');
    }
}

// 更新品項
async function updateItem() {
    const form = document.getElementById('editItemForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    const itemId = data.id;
    
    try {
        const response = await fetch(`/kiosk/api/inventory.php/items/${itemId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        });
        
        if (response.ok) {
            const result = await response.json();
            const index = inventoryData.items.findIndex(item => item.id === itemId);
            if (index !== -1) {
                inventoryData.items[index] = result;
            }
            updateDashboard();
            updateInventoryTable();
            bootstrap.Modal.getInstance(document.getElementById('editItemModal')).hide();
        } else {
            throw new Error('更新失敗');
        }
    } catch (error) {
        console.error('更新品項失敗:', error);
        alert('更新品項時發生錯誤');
    }
}

// 刪除品項
async function deleteItem(itemId) {
    if (!confirm('確定要刪除此品項嗎？')) return;
    
    try {
        const response = await fetch(`/kiosk/api/inventory.php/items/${itemId}`, {
            method: 'DELETE',
        });
        
        if (response.ok) {
            inventoryData.items = inventoryData.items.filter(item => item.id !== itemId);
            updateDashboard();
            updateInventoryTable();
        } else {
            throw new Error('刪除失敗');
        }
    } catch (error) {
        console.error('刪除品項失敗:', error);
        alert('刪除品項時發生錯誤');
    }
} 