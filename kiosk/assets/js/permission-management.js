// 全域變數
let roles = [];
let permissions = [];

// DOM 元素
const roleTableBody = document.getElementById('roleTableBody');
const permissionTableBody = document.getElementById('permissionTableBody');
const roleModal = new bootstrap.Modal(document.getElementById('roleModal'));
const permissionModal = new bootstrap.Modal(document.getElementById('permissionModal'));
const roleForm = document.getElementById('roleForm');
const permissionForm = document.getElementById('permissionForm');
const saveRoleButton = document.getElementById('saveRoleButton');
const savePermissionButton = document.getElementById('savePermissionButton');
const permissionCheckboxes = document.getElementById('permissionCheckboxes');

// 初始化
document.addEventListener('DOMContentLoaded', () => {
    // 載入角色和權限列表
    loadRoles();
    loadPermissions();
    
    // 綁定事件監聽器
    bindEventListeners();
});

// 綁定事件監聽器
function bindEventListeners() {
    // 角色表單提交
    saveRoleButton.addEventListener('click', handleSaveRole);
    
    // 權限表單提交
    savePermissionButton.addEventListener('click', handleSavePermission);
    
    // Modal 關閉時重置表單
    document.getElementById('roleModal').addEventListener('hidden.bs.modal', resetRoleForm);
    document.getElementById('permissionModal').addEventListener('hidden.bs.modal', resetPermissionForm);
}

// 載入角色列表
async function loadRoles() {
    try {
        const response = await fetch('../api/permissions.php?action=roles');
        const data = await response.json();
        
        if (data.success) {
            roles = data.data;
            renderRoleTable();
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('載入角色列表失敗');
        console.error(error);
    }
}

// 載入權限列表
async function loadPermissions() {
    try {
        const response = await fetch('../api/permissions.php?action=list');
        const data = await response.json();
        
        if (data.success) {
            permissions = data.data;
            renderPermissionTable();
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('載入權限列表失敗');
        console.error(error);
    }
}

// 渲染角色表格
function renderRoleTable() {
    roleTableBody.innerHTML = '';
    
    roles.forEach(role => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${role.id}</td>
            <td>${role.code}</td>
            <td>${role.name}</td>
            <td>${role.description || '-'}</td>
            <td>${role.permissions ? role.permissions.length : 0}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editRole('${role.id}')">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteRole('${role.id}')">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        roleTableBody.appendChild(row);
    });
}

// 渲染權限表格
function renderPermissionTable() {
    permissionTableBody.innerHTML = '';
    
    permissions.forEach(permission => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${permission.id}</td>
            <td>${permission.code}</td>
            <td>${permission.name}</td>
            <td>${permission.description || '-'}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editPermission('${permission.id}')">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deletePermission('${permission.id}')">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        permissionTableBody.appendChild(row);
    });
}

// 渲染權限選項
function renderPermissionCheckboxes(selectedPermissions = []) {
    permissionCheckboxes.innerHTML = '';
    
    permissions.forEach(permission => {
        const div = document.createElement('div');
        div.className = 'form-check';
        div.innerHTML = `
            <input class="form-check-input" type="checkbox" 
                   id="permission_${permission.id}" 
                   value="${permission.id}"
                   ${selectedPermissions.includes(permission.id) ? 'checked' : ''}>
            <label class="form-check-label" for="permission_${permission.id}">
                ${permission.name} (${permission.code})
            </label>
        `;
        permissionCheckboxes.appendChild(div);
    });
}

// 編輯角色
async function editRole(roleId) {
    try {
        const response = await fetch(`../api/permissions.php?action=role&id=${roleId}`);
        const data = await response.json();
        
        if (data.success) {
            const role = data.data;
            
            // 填充表單
            document.getElementById('roleId').value = role.id;
            document.getElementById('roleCode').value = role.code;
            document.getElementById('roleName').value = role.name;
            document.getElementById('roleDescription').value = role.description || '';
            
            // 渲染權限選項
            renderPermissionCheckboxes(role.permissions || []);
            
            // 更新標題
            document.getElementById('roleModalTitle').textContent = '編輯角色';
            
            // 顯示 Modal
            roleModal.show();
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('載入角色資料失敗');
        console.error(error);
    }
}

// 編輯權限
async function editPermission(permissionId) {
    try {
        const response = await fetch(`../api/permissions.php?action=get&id=${permissionId}`);
        const data = await response.json();
        
        if (data.success) {
            const permission = data.data;
            
            // 填充表單
            document.getElementById('permissionId').value = permission.id;
            document.getElementById('permissionCode').value = permission.code;
            document.getElementById('permissionName').value = permission.name;
            document.getElementById('permissionDescription').value = permission.description || '';
            
            // 更新標題
            document.getElementById('permissionModalTitle').textContent = '編輯權限';
            
            // 顯示 Modal
            permissionModal.show();
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('載入權限資料失敗');
        console.error(error);
    }
}

// 處理角色儲存
async function handleSaveRole() {
    try {
        // 取得表單資料
        const formData = {
            id: document.getElementById('roleId').value,
            code: document.getElementById('roleCode').value,
            name: document.getElementById('roleName').value,
            description: document.getElementById('roleDescription').value,
            permissions: Array.from(document.querySelectorAll('#permissionCheckboxes input:checked'))
                .map(checkbox => checkbox.value)
        };
        
        // 決定是新增還是更新
        const action = formData.id ? 'update-role' : 'create-role';
        
        // 發送請求
        const response = await fetch(`../api/permissions.php?action=${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess(action === 'create-role' ? '角色建立成功' : '角色更新成功');
            roleModal.hide();
            loadRoles();
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('儲存角色失敗');
        console.error(error);
    }
}

// 處理權限儲存
async function handleSavePermission() {
    try {
        // 取得表單資料
        const formData = {
            id: document.getElementById('permissionId').value,
            code: document.getElementById('permissionCode').value,
            name: document.getElementById('permissionName').value,
            description: document.getElementById('permissionDescription').value
        };
        
        // 決定是新增還是更新
        const action = formData.id ? 'update' : 'create';
        
        // 發送請求
        const response = await fetch(`../api/permissions.php?action=${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess(action === 'create' ? '權限建立成功' : '權限更新成功');
            permissionModal.hide();
            loadPermissions();
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('儲存權限失敗');
        console.error(error);
    }
}

// 刪除角色
async function deleteRole(roleId) {
    if (!confirm('確定要刪除這個角色嗎？此操作無法復原。')) {
        return;
    }
    
    try {
        const response = await fetch('../api/permissions.php?action=delete-role', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: roleId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('角色刪除成功');
            loadRoles();
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('刪除角色失敗');
        console.error(error);
    }
}

// 刪除權限
async function deletePermission(permissionId) {
    if (!confirm('確定要刪除這個權限嗎？此操作無法復原。')) {
        return;
    }
    
    try {
        const response = await fetch('../api/permissions.php?action=delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: permissionId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('權限刪除成功');
            loadPermissions();
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('刪除權限失敗');
        console.error(error);
    }
}

// 重置角色表單
function resetRoleForm() {
    roleForm.reset();
    document.getElementById('roleId').value = '';
    document.getElementById('roleModalTitle').textContent = '新增角色';
    renderPermissionCheckboxes();
}

// 重置權限表單
function resetPermissionForm() {
    permissionForm.reset();
    document.getElementById('permissionId').value = '';
    document.getElementById('permissionModalTitle').textContent = '新增權限';
}

// 顯示成功訊息
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
    
    document.querySelector('.toast-container').appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // 自動移除
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// 顯示錯誤訊息
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
    
    document.querySelector('.toast-container').appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // 自動移除
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
} 
} 