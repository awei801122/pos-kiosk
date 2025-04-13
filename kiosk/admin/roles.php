<?php
/**
 * 角色管理頁面
 */
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

// 檢查權限
checkLogin();
checkPermission('roles.manage');

// 設定頁面標題
$title = '角色管理';

// 包含頁面佈局
require_once __DIR__ . '/../includes/layout.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 mb-0">角色管理</h1>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                <i class="fas fa-plus"></i> 新增角色
            </button>
        </div>
    </div>

    <!-- 搜尋和篩選 -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="searchForm" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="search" name="search" placeholder="搜尋角色名稱...">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> 搜尋
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 角色列表 -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>名稱</th>
                            <th>權限組</th>
                            <th>用戶數量</th>
                            <th>描述</th>
                            <th>創建時間</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="rolesTableBody">
                        <!-- 角色列表將通過 JavaScript 動態載入 -->
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

<!-- 新增角色 Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新增角色</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addRoleForm">
                    <div class="mb-3">
                        <label for="name" class="form-label">角色名稱</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="permissionGroup" class="form-label">權限組</label>
                        <select class="form-select" id="permissionGroup" name="permission_group_id" required>
                            <option value="">請選擇權限組</option>
                            <!-- 權限組列表將通過 JavaScript 動態載入 -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">描述</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="saveRoleBtn">儲存</button>
            </div>
        </div>
    </div>
</div>

<!-- 編輯角色 Modal -->
<div class="modal fade" id="editRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">編輯角色</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editRoleForm">
                    <input type="hidden" id="editRoleId" name="id">
                    <div class="mb-3">
                        <label for="editName" class="form-label">角色名稱</label>
                        <input type="text" class="form-control" id="editName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editPermissionGroup" class="form-label">權限組</label>
                        <select class="form-select" id="editPermissionGroup" name="permission_group_id" required>
                            <option value="">請選擇權限組</option>
                            <!-- 權限組列表將通過 JavaScript 動態載入 -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editDescription" class="form-label">描述</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="updateRoleBtn">更新</button>
            </div>
        </div>
    </div>
</div>

<!-- 刪除確認 Modal -->
<div class="modal fade" id="deleteRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">確認刪除</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>確定要刪除此角色嗎？此操作無法復原。</p>
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

// 載入角色列表
function loadRoles(page = 1) {
    const search = $('#search').val();
    
    // 顯示載入中狀態
    $('#rolesTableBody').html('<tr><td colspan="7" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">載入中...</span></div></td></tr>');
    
    $.get('/api/admin/roles/list.php', {
        page: page,
        search: search
    }, function(response) {
        if (response.success) {
            updateRolesTable(response.data.roles);
            updatePagination(response.data);
            currentPage = page;
        } else {
            showAlert('danger', response.message);
        }
    }).fail(function() {
        showAlert('danger', '載入角色列表失敗');
    });
}

// 更新角色表格
function updateRolesTable(roles) {
    const tbody = $('#rolesTableBody');
    tbody.empty();
    
    if (roles.length === 0) {
        tbody.html('<tr><td colspan="7" class="text-center">沒有找到角色</td></tr>');
        return;
    }
    
    roles.forEach(role => {
        tbody.append(`
            <tr>
                <td>${role.id}</td>
                <td>${role.name}</td>
                <td>${role.permission_group_name}</td>
                <td>${role.user_count}</td>
                <td>${role.description || '-'}</td>
                <td>${formatDate(role.created_at)}</td>
                <td>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-primary" onclick="editRole(${role.id})" title="編輯">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteRole(${role.id})" title="刪除">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
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
            <a class="page-link" href="#" onclick="loadRoles(${data.current_page - 1})">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `);
    
    // 頁碼
    for (let i = 1; i <= data.last_page; i++) {
        pagination.append(`
            <li class="page-item ${data.current_page === i ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadRoles(${i})">${i}</a>
            </li>
        `);
    }
    
    // 下一頁
    pagination.append(`
        <li class="page-item ${data.current_page === data.last_page ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadRoles(${data.current_page + 1})">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `);
}

// 載入權限組列表
function loadPermissionGroups(selectElement, selectedId = null) {
    // 顯示載入中狀態
    selectElement.html('<option value="">載入中...</option>');
    
    $.get('/api/admin/permission_groups/list.php', function(response) {
        if (response.success) {
            selectElement.empty();
            selectElement.append('<option value="">請選擇權限組</option>');
            
            response.data.groups.forEach(group => {
                selectElement.append(`
                    <option value="${group.id}" ${selectedId === group.id ? 'selected' : ''}>
                        ${group.name}
                    </option>
                `);
            });
        } else {
            showAlert('danger', response.message);
        }
    }).fail(function() {
        showAlert('danger', '載入權限組列表失敗');
    });
}

// 編輯角色
function editRole(id) {
    // 顯示載入中狀態
    $('#editRoleModal .modal-body').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">載入中...</span></div></div>');
    $('#editRoleModal').modal('show');
    
    $.get('/api/admin/roles/detail.php', { id: id }, function(response) {
        if (response.success) {
            const role = response.data;
            $('#editRoleId').val(role.id);
            $('#editName').val(role.name);
            $('#editDescription').val(role.description);
            
            loadPermissionGroups($('#editPermissionGroup'), role.permission_group_id);
            
            // 恢復表單
            $('#editRoleModal .modal-body').html($('#editRoleForm').parent().html());
        } else {
            showAlert('danger', response.message);
            $('#editRoleModal').modal('hide');
        }
    }).fail(function() {
        showAlert('danger', '載入角色資料失敗');
        $('#editRoleModal').modal('hide');
    });
}

// 刪除角色
function deleteRole(id) {
    $('#deleteRoleModal').data('id', id).modal('show');
}

// 格式化日期
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('zh-TW');
}

// 顯示提示訊息
function showAlert(type, message) {
    const alertDiv = $(`
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    
    $('.container-fluid').prepend(alertDiv);
    
    // 5秒後自動關閉
    setTimeout(() => {
        alertDiv.alert('close');
    }, 5000);
}

// 初始化頁面
$(function() {
    // 載入角色列表
    loadRoles();
    
    // 搜尋表單提交
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        loadRoles(1);
    });
    
    // 新增角色
    $('#saveRoleBtn').on('click', function() {
        const formData = new FormData($('#addRoleForm')[0]);
        
        // 禁用按鈕
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 儲存中...');
        
        $.ajax({
            url: '/api/admin/roles/create.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#addRoleModal').modal('hide');
                    showAlert('success', '角色創建成功');
                    loadRoles(currentPage);
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                showAlert('danger', '創建角色失敗');
            },
            complete: function() {
                // 恢復按鈕狀態
                $('#saveRoleBtn').prop('disabled', false).text('儲存');
            }
        });
    });
    
    // 更新角色
    $('#updateRoleBtn').on('click', function() {
        const formData = new FormData($('#editRoleForm')[0]);
        
        // 禁用按鈕
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 更新中...');
        
        $.ajax({
            url: '/api/admin/roles/update.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#editRoleModal').modal('hide');
                    showAlert('success', '角色更新成功');
                    loadRoles(currentPage);
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                showAlert('danger', '更新角色失敗');
            },
            complete: function() {
                // 恢復按鈕狀態
                $('#updateRoleBtn').prop('disabled', false).text('更新');
            }
        });
    });
    
    // 刪除角色
    $('#confirmDeleteBtn').on('click', function() {
        const id = $('#deleteRoleModal').data('id');
        
        // 禁用按鈕
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 刪除中...');
        
        $.post('/api/admin/roles/delete.php', { id: id }, function(response) {
            if (response.success) {
                $('#deleteRoleModal').modal('hide');
                showAlert('success', '角色刪除成功');
                loadRoles(currentPage);
            } else {
                showAlert('danger', response.message);
            }
        }).fail(function() {
            showAlert('danger', '刪除角色失敗');
        }).always(function() {
            // 恢復按鈕狀態
            $('#confirmDeleteBtn').prop('disabled', false).text('刪除');
        });
    });
    
    // 初始化新增角色 Modal 的權限組列表
    loadPermissionGroups($('#permissionGroup'));
    
    // 重置表單
    $('#addRoleModal').on('hidden.bs.modal', function() {
        $('#addRoleForm')[0].reset();
    });
});
</script> 