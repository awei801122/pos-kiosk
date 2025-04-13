<?php
/**
 * 權限組管理頁面
 */
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

// 檢查權限
checkLogin();
checkPermission('roles.manage');

// 設定頁面標題
$title = '權限組管理';

// 包含頁面佈局
require_once __DIR__ . '/../includes/layout.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 mb-0">權限組管理</h1>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGroupModal">
                <i class="fas fa-plus"></i> 新增權限組
            </button>
        </div>
    </div>

    <!-- 搜尋和篩選 -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="searchForm" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="search" name="search" placeholder="搜尋權限組名稱...">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> 搜尋
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 權限組列表 -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>名稱</th>
                            <th>權限</th>
                            <th>描述</th>
                            <th>創建時間</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="groupsTableBody">
                        <!-- 權限組列表將通過 JavaScript 動態載入 -->
                    </tbody>
                </table>
            </div>

            <!-- 分頁 -->
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center" id="pagination">
                    <!-- 分頁將通過 JavaScript 動態載入 -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- 新增權限組 Modal -->
<div class="modal fade" id="addGroupModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新增權限組</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addGroupForm">
                    <div class="mb-3">
                        <label for="name" class="form-label">權限組名稱</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">權限</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">系統管理</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="permission_users" name="permissions[]" value="users.manage">
                                            <label class="form-check-label" for="permission_users">用戶管理</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="permission_roles" name="permissions[]" value="roles.manage">
                                            <label class="form-check-label" for="permission_roles">角色管理</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="permission_settings" name="permissions[]" value="settings.manage">
                                            <label class="form-check-label" for="permission_settings">系統設置</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">菜單管理</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="permission_menu" name="permissions[]" value="menu.manage">
                                            <label class="form-check-label" for="permission_menu">菜單管理</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="permission_categories" name="permissions[]" value="categories.manage">
                                            <label class="form-check-label" for="permission_categories">分類管理</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">訂單管理</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="permission_orders" name="permissions[]" value="orders.manage">
                                            <label class="form-check-label" for="permission_orders">訂單管理</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="permission_payments" name="permissions[]" value="payments.manage">
                                            <label class="form-check-label" for="permission_payments">支付管理</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="permission_reports" name="permissions[]" value="reports.view">
                                            <label class="form-check-label" for="permission_reports">報表查看</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">庫存管理</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="permission_inventory" name="permissions[]" value="inventory.manage">
                                            <label class="form-check-label" for="permission_inventory">庫存管理</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="permission_suppliers" name="permissions[]" value="suppliers.manage">
                                            <label class="form-check-label" for="permission_suppliers">供應商管理</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">描述</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="saveGroupBtn">儲存</button>
            </div>
        </div>
    </div>
</div>

<!-- 編輯權限組 Modal -->
<div class="modal fade" id="editGroupModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">編輯權限組</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editGroupForm">
                    <input type="hidden" id="editGroupId" name="id">
                    <div class="mb-3">
                        <label for="editName" class="form-label">權限組名稱</label>
                        <input type="text" class="form-control" id="editName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">權限</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">系統管理</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="edit_permission_users" name="permissions[]" value="users.manage">
                                            <label class="form-check-label" for="edit_permission_users">用戶管理</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="edit_permission_roles" name="permissions[]" value="roles.manage">
                                            <label class="form-check-label" for="edit_permission_roles">角色管理</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="edit_permission_settings" name="permissions[]" value="settings.manage">
                                            <label class="form-check-label" for="edit_permission_settings">系統設置</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">菜單管理</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="edit_permission_menu" name="permissions[]" value="menu.manage">
                                            <label class="form-check-label" for="edit_permission_menu">菜單管理</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="edit_permission_categories" name="permissions[]" value="categories.manage">
                                            <label class="form-check-label" for="edit_permission_categories">分類管理</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">訂單管理</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="edit_permission_orders" name="permissions[]" value="orders.manage">
                                            <label class="form-check-label" for="edit_permission_orders">訂單管理</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="edit_permission_payments" name="permissions[]" value="payments.manage">
                                            <label class="form-check-label" for="edit_permission_payments">支付管理</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="edit_permission_reports" name="permissions[]" value="reports.view">
                                            <label class="form-check-label" for="edit_permission_reports">報表查看</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">庫存管理</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="edit_permission_inventory" name="permissions[]" value="inventory.manage">
                                            <label class="form-check-label" for="edit_permission_inventory">庫存管理</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="edit_permission_suppliers" name="permissions[]" value="suppliers.manage">
                                            <label class="form-check-label" for="edit_permission_suppliers">供應商管理</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editDescription" class="form-label">描述</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="updateGroupBtn">更新</button>
            </div>
        </div>
    </div>
</div>

<!-- 刪除確認 Modal -->
<div class="modal fade" id="deleteGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">確認刪除</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>確定要刪除此權限組嗎？此操作無法復原。</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">刪除</button>
            </div>
        </div>
    </div>
</div>

<script>
// 當前頁碼
let currentPage = 1;

// 權限映射
const permissionMap = {
    'users.manage': '用戶管理',
    'roles.manage': '角色管理',
    'settings.manage': '系統設置',
    'menu.manage': '菜單管理',
    'categories.manage': '分類管理',
    'orders.manage': '訂單管理',
    'payments.manage': '支付管理',
    'reports.view': '報表查看',
    'inventory.manage': '庫存管理',
    'suppliers.manage': '供應商管理'
};

// 載入權限組列表
function loadGroups(page = 1) {
    const search = $('#search').val();
    
    $.get('/api/admin/permission_groups/list.php', {
        page: page,
        search: search
    }, function(response) {
        if (response.success) {
            updateGroupsTable(response.data.groups);
            updatePagination(response.data);
            currentPage = page;
        } else {
            showAlert('danger', response.message);
        }
    });
}

// 更新權限組表格
function updateGroupsTable(groups) {
    const tbody = $('#groupsTableBody');
    tbody.empty();
    
    if (groups.length === 0) {
        tbody.html('<tr><td colspan="6" class="text-center">沒有找到權限組</td></tr>');
        return;
    }
    
    groups.forEach(group => {
        const permissions = JSON.parse(group.permissions);
        const permissionNames = permissions.map(p => permissionMap[p] || p).join(', ');
        
        tbody.append(`
            <tr>
                <td>${group.id}</td>
                <td>${group.name}</td>
                <td>${permissionNames}</td>
                <td>${group.description || '-'}</td>
                <td>${formatDate(group.created_at)}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-primary" onclick="editGroup(${group.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteGroup(${group.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `);
    });
}

// 更新分頁
function updatePagination(data) {
    const pagination = $('#pagination');
    pagination.empty();
    
    if (data.last_page <= 1) {
        return;
    }
    
    // 上一頁
    pagination.append(`
        <li class="page-item ${data.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadGroups(${data.current_page - 1})">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `);
    
    // 頁碼
    for (let i = 1; i <= data.last_page; i++) {
        pagination.append(`
            <li class="page-item ${data.current_page === i ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadGroups(${i})">${i}</a>
            </li>
        `);
    }
    
    // 下一頁
    pagination.append(`
        <li class="page-item ${data.current_page === data.last_page ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadGroups(${data.current_page + 1})">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `);
}

// 編輯權限組
function editGroup(id) {
    $.get('/api/admin/permission_groups/detail.php', { id: id }, function(response) {
        if (response.success) {
            const group = response.data;
            $('#editGroupId').val(group.id);
            $('#editName').val(group.name);
            $('#editDescription').val(group.description);
            
            // 重置所有權限勾選
            $('input[name="permissions[]"]:checked').prop('checked', false);
            
            // 勾選已有權限
            const permissions = JSON.parse(group.permissions);
            permissions.forEach(permission => {
                $(`#edit_permission_${permission.replace('.', '_')}`).prop('checked', true);
            });
            
            $('#editGroupModal').modal('show');
        } else {
            showAlert('danger', response.message);
        }
    });
}

// 刪除權限組
function deleteGroup(id) {
    $('#deleteGroupModal').data('id', id).modal('show');
}

// 初始化頁面
$(function() {
    // 載入權限組列表
    loadGroups();
    
    // 搜尋表單提交
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        loadGroups(1);
    });
    
    // 新增權限組
    $('#saveGroupBtn').on('click', function() {
        const formData = new FormData($('#addGroupForm')[0]);
        
        // 收集權限
        const permissions = [];
        $('input[name="permissions[]"]:checked').each(function() {
            permissions.push($(this).val());
        });
        
        if (permissions.length === 0) {
            showAlert('danger', '請至少選擇一個權限');
            return;
        }
        
        formData.append('permissions', JSON.stringify(permissions));
        
        $.ajax({
            url: '/api/admin/permission_groups/create.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#addGroupModal').modal('hide');
                    showAlert('success', '權限組創建成功');
                    loadGroups(currentPage);
                } else {
                    showAlert('danger', response.message);
                }
            }
        });
    });
    
    // 更新權限組
    $('#updateGroupBtn').on('click', function() {
        const formData = new FormData($('#editGroupForm')[0]);
        
        // 收集權限
        const permissions = [];
        $('input[name="permissions[]"]:checked').each(function() {
            permissions.push($(this).val());
        });
        
        if (permissions.length === 0) {
            showAlert('danger', '請至少選擇一個權限');
            return;
        }
        
        formData.append('permissions', JSON.stringify(permissions));
        
        $.ajax({
            url: '/api/admin/permission_groups/update.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#editGroupModal').modal('hide');
                    showAlert('success', '權限組更新成功');
                    loadGroups(currentPage);
                } else {
                    showAlert('danger', response.message);
                }
            }
        });
    });
    
    // 刪除權限組
    $('#confirmDeleteBtn').on('click', function() {
        const id = $('#deleteGroupModal').data('id');
        
        $.post('/api/admin/permission_groups/delete.php', { id: id }, function(response) {
            if (response.success) {
                $('#deleteGroupModal').modal('hide');
                showAlert('success', '權限組刪除成功');
                loadGroups(currentPage);
            } else {
                showAlert('danger', response.message);
            }
        });
    });
});
</script> 