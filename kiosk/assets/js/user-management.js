// 全域變數
let currentPage = 1;
let totalPages = 1;
let users = [];
let selectedUserId = null;

// DOM 元素
const userTableBody = document.getElementById('userTableBody');
const pagination = document.getElementById('pagination');
const searchInput = document.getElementById('searchInput');
const roleFilter = document.getElementById('roleFilter');
const statusFilter = document.getElementById('statusFilter');
const userModal = new bootstrap.Modal(document.getElementById('userModal'));
const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
const userForm = document.getElementById('userForm');
const saveButton = document.getElementById('saveButton');
const confirmDelete = document.getElementById('confirmDelete');

// 初始化
document.addEventListener('DOMContentLoaded', () => {
    // 載入使用者列表
    loadUsers();
    
    // 綁定事件監聽器
    bindEventListeners();
});

// 綁定事件監聽器
function bindEventListeners() {
    // 搜尋和篩選
    searchInput.addEventListener('input', debounce(loadUsers, 300));
    roleFilter.addEventListener('change', loadUsers);
    statusFilter.addEventListener('change', loadUsers);
    
    // 表單提交
    saveButton.addEventListener('click', handleSave);
    confirmDelete.addEventListener('click', handleDelete);
    
    // Modal 關閉時重置表單
    document.getElementById('userModal').addEventListener('hidden.bs.modal', resetForm);
}

// 載入使用者列表
async function loadUsers() {
    try {
        // 取得搜尋和篩選條件
        const search = searchInput.value;
        const role = roleFilter.value;
        const status = statusFilter.value;
        
        // 發送請求
        const response = await fetch(`../api/users.php?action=list&page=${currentPage}&search=${search}&role=${role}&status=${status}`);
        const data = await response.json();
        
        if (data.success) {
            users = data.data;
            renderUserTable();
            renderPagination();
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('載入使用者列表失敗');
        console.error(error);
    }
}

// 渲染使用者表格
function renderUserTable() {
    userTableBody.innerHTML = '';
    
    users.forEach(user => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${user.id}</td>
            <td>${user.username}</td>
            <td>${user.name}</td>
            <td>${user.email}</td>
            <td>${user.phone || '-'}</td>
            <td>${getRoleName(user.role)}</td>
            <td>
                <span class="badge ${user.status === 'active' ? 'bg-success' : 'bg-danger'}">
                    ${user.status === 'active' ? '啟用' : '停用'}
                </span>
            </td>
            <td>${user.last_login || '-'}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editUser('${user.id}')">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="confirmDeleteUser('${user.id}')">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        userTableBody.appendChild(row);
    });
}

// 渲染分頁
function renderPagination() {
    pagination.innerHTML = '';
    
    // 上一頁
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `
        <a class="page-link" href="#" onclick="changePage(${currentPage - 1})">
            <i class="fas fa-chevron-left"></i>
        </a>
    `;
    pagination.appendChild(prevLi);
    
    // 頁碼
    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${currentPage === i ? 'active' : ''}`;
        li.innerHTML = `
            <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
        `;
        pagination.appendChild(li);
    }
    
    // 下一頁
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `
        <a class="page-link" href="#" onclick="changePage(${currentPage + 1})">
            <i class="fas fa-chevron-right"></i>
        </a>
    `;
    pagination.appendChild(nextLi);
}

// 切換頁碼
function changePage(page) {
    if (page >= 1 && page <= totalPages) {
        currentPage = page;
        loadUsers();
    }
}

// 編輯使用者
async function editUser(userId) {
    try {
        // 取得使用者資料
        const response = await fetch(`../api/users.php?action=get&id=${userId}`);
        const data = await response.json();
        
        if (data.success) {
            const user = data.data;
            
            // 填充表單
            document.getElementById('userId').value = user.id;
            document.getElementById('username').value = user.username;
            document.getElementById('name').value = user.name;
            document.getElementById('email').value = user.email;
            document.getElementById('phone').value = user.phone || '';
            document.getElementById('role').value = user.role;
            document.getElementById('status').value = user.status;
            
            // 更新標題
            document.getElementById('modalTitle').textContent = '編輯使用者';
            
            // 顯示 Modal
            userModal.show();
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('載入使用者資料失敗');
        console.error(error);
    }
}

// 確認刪除使用者
function confirmDeleteUser(userId) {
    selectedUserId = userId;
    deleteModal.show();
}

// 處理儲存
async function handleSave() {
    try {
        // 取得表單資料
        const formData = {
            id: document.getElementById('userId').value,
            username: document.getElementById('username').value,
            password: document.getElementById('password').value,
            name: document.getElementById('name').value,
            email: document.getElementById('email').value,
            phone: document.getElementById('phone').value,
            role: document.getElementById('role').value,
            status: document.getElementById('status').value
        };
        
        // 決定是新增還是更新
        const action = formData.id ? 'update' : 'create';
        
        // 發送請求
        const response = await fetch(`../api/users.php?action=${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess(action === 'create' ? '使用者建立成功' : '使用者更新成功');
            userModal.hide();
            loadUsers();
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('儲存使用者失敗');
        console.error(error);
    }
}

// 處理刪除
async function handleDelete() {
    try {
        if (!selectedUserId) return;
        
        // 發送請求
        const response = await fetch(`../api/users.php?action=delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: selectedUserId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('使用者刪除成功');
            deleteModal.hide();
            loadUsers();
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('刪除使用者失敗');
        console.error(error);
    }
}

// 重置表單
function resetForm() {
    userForm.reset();
    document.getElementById('userId').value = '';
    document.getElementById('modalTitle').textContent = '新增使用者';
}

// 取得角色名稱
function getRoleName(roleCode) {
    const roles = {
        'admin': '管理員',
        'manager': '經理',
        'staff': '員工',
        'kitchen': '廚房'
    };
    return roles[roleCode] || roleCode;
}

// 顯示成功訊息
function showSuccess(message) {
    // 使用 Bootstrap Toast 或其他通知元件
    alert(message);
}

// 顯示錯誤訊息
function showError(message) {
    // 使用 Bootstrap Toast 或其他通知元件
    alert(message);
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

// 使用者管理相關的 JavaScript 程式碼
document.addEventListener('DOMContentLoaded', function() {
    // 載入使用者列表
    loadUsers();

    // 新增使用者按鈕事件
    document.getElementById('saveUser').addEventListener('click', addUser);

    // 更新使用者按鈕事件
    document.getElementById('updateUser').addEventListener('click', updateUser);
});

// 載入使用者列表
async function loadUsers() {
    try {
        const response = await fetch('api/users.php?action=list');
        const data = await response.json();
        
        if (data.success) {
            displayUsers(data.users);
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('載入使用者列表失敗');
    }
}

// 顯示使用者列表
function displayUsers(users) {
    const tbody = document.getElementById('userList');
    tbody.innerHTML = '';

    users.forEach(user => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${user.id}</td>
            <td>${user.username}</td>
            <td>${user.name}</td>
            <td>${user.email}</td>
            <td>${user.phone}</td>
            <td>${getRoleName(user.role)}</td>
            <td>${getStatusBadge(user.status)}</td>
            <td>${user.last_login || '從未登入'}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editUser('${user.id}')">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteUser('${user.id}')">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// 新增使用者
async function addUser() {
    const form = document.getElementById('addUserForm');
    const formData = new FormData(form);

    try {
        const response = await fetch('api/users.php?action=create', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            // 關閉 Modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('addUserModal'));
            modal.hide();
            
            // 重新載入使用者列表
            loadUsers();
            
            // 顯示成功訊息
            showSuccess('使用者新增成功');
            
            // 清空表單
            form.reset();
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('新增使用者失敗');
    }
}

// 編輯使用者
async function editUser(userId) {
    try {
        const response = await fetch(`api/users.php?action=get&id=${userId}`);
        const data = await response.json();

        if (data.success) {
            const user = data.user;
            const form = document.getElementById('editUserForm');
            
            // 填入表單資料
            form.querySelector('[name="id"]').value = user.id;
            form.querySelector('[name="username"]').value = user.username;
            form.querySelector('[name="name"]').value = user.name;
            form.querySelector('[name="email"]').value = user.email;
            form.querySelector('[name="phone"]').value = user.phone;
            form.querySelector('[name="role"]').value = user.role;
            form.querySelector('[name="status"]').value = user.status;
            
            // 顯示 Modal
            const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
            modal.show();
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('載入使用者資料失敗');
    }
}

// 更新使用者
async function updateUser() {
    const form = document.getElementById('editUserForm');
    const formData = new FormData(form);
    const userId = formData.get('id');

    try {
        const response = await fetch(`api/users.php?action=update&id=${userId}`, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            // 關閉 Modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
            modal.hide();
            
            // 重新載入使用者列表
            loadUsers();
            
            // 顯示成功訊息
            showSuccess('使用者更新成功');
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('更新使用者失敗');
    }
}

// 刪除使用者
async function deleteUser(userId) {
    if (!confirm('確定要刪除這個使用者嗎？')) {
        return;
    }

    try {
        const response = await fetch(`api/users.php?action=delete&id=${userId}`, {
            method: 'POST'
        });
        const data = await response.json();

        if (data.success) {
            // 重新載入使用者列表
            loadUsers();
            
            // 顯示成功訊息
            showSuccess('使用者刪除成功');
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('刪除使用者失敗');
    }
}

// 取得角色名稱
function getRoleName(role) {
    const roles = {
        'admin': '系統管理員',
        'manager': '店長',
        'staff': '員工',
        'kitchen': '廚房'
    };
    return roles[role] || role;
}

// 取得狀態標籤
function getStatusBadge(status) {
    const badges = {
        'active': '<span class="badge bg-success">啟用</span>',
        'inactive': '<span class="badge bg-danger">停用</span>'
    };
    return badges[status] || status;
}

// 顯示成功訊息
function showSuccess(message) {
    // 實作成功訊息的顯示邏輯
    alert(message);
}

// 顯示錯誤訊息
function showError(message) {
    // 實作錯誤訊息的顯示邏輯
} 