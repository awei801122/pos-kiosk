<?php
/**
 * 用戶管理頁面
 */
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

// 檢查權限
checkLogin();
checkPermission('users.manage');

// 設置頁面標題
$pageTitle = '用戶管理';

// 包含布局文件
require_once __DIR__ . '/../includes/layout.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 mb-0">用戶管理</h1>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-plus"></i> 新增用戶
            </button>
        </div>
    </div>

    <!-- 搜尋和過濾表單 -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="searchForm" class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" id="search" name="search" placeholder="搜尋用戶名或姓名">
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="status" name="status">
                        <option value="">所有狀態</option>
                        <option value="active">啟用</option>
                        <option value="inactive">停用</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="role" name="role_id">
                        <option value="">所有角色</option>
                        <?php
                        $stmt = $db->query("SELECT id, name FROM roles ORDER BY name");
                        while ($role = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value=\"{$role['id']}\">{$role['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">搜尋</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 用戶列表 -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>用戶名</th>
                            <th>姓名</th>
                            <th>角色</th>
                            <th>狀態</th>
                            <th>創建時間</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <!-- 用戶列表將通過 JavaScript 動態載入 -->
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

<!-- 新增用戶 Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新增用戶</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addUserForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">用戶名</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">姓名</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">密碼</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">確認密碼</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">角色</label>
                        <select class="form-select" name="role_id" required>
                            <?php
                            $stmt = $db->query("SELECT id, name FROM roles ORDER BY name");
                            while ($role = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value=\"{$role['id']}\">{$role['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">新增</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 編輯用戶 Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">編輯用戶</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUserForm">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">用戶名</label>
                        <input type="text" class="form-control" name="username" id="edit_username" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">姓名</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">密碼</label>
                        <input type="password" class="form-control" name="password" placeholder="留空表示不修改">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">確認密碼</label>
                        <input type="password" class="form-control" name="confirm_password" placeholder="留空表示不修改">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">角色</label>
                        <select class="form-select" name="role_id" id="edit_role_id" required>
                            <?php
                            $stmt = $db->query("SELECT id, name FROM roles ORDER BY name");
                            while ($role = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value=\"{$role['id']}\">{$role['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">狀態</label>
                        <select class="form-select" name="status" id="edit_status" required>
                            <option value="active">啟用</option>
                            <option value="inactive">停用</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 刪除確認 Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">確認刪除</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>確定要刪除這個用戶嗎？此操作無法恢復。</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">刪除</button>
            </div>
        </div>
    </div>
</div>

<script>
// 全局變量
let currentPage = 1;
let totalPages = 1;
let deleteUserId = null;

// 載入用戶列表
function loadUsers(page = 1) {
    const formData = new FormData(document.getElementById('searchForm'));
    formData.append('page', page);
    formData.append('per_page', 10);
    
    fetch('/api/admin/users/list.php?' + new URLSearchParams(formData))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateUsersTable(data.data.users);
                updatePagination(data.data.pagination);
                currentPage = data.data.pagination.current_page;
                totalPages = data.data.pagination.total_pages;
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('載入用戶列表失敗');
        });
}

// 更新用戶表格
function updateUsersTable(users) {
    const tbody = document.getElementById('usersTableBody');
    tbody.innerHTML = '';
    
    users.forEach(user => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${user.id}</td>
            <td>${user.username}</td>
            <td>${user.name}</td>
            <td>${user.role_name}</td>
            <td>
                <span class="badge ${user.status === 'active' ? 'bg-success' : 'bg-danger'}">
                    ${user.status === 'active' ? '啟用' : '停用'}
                </span>
            </td>
            <td>${new Date(user.created_at).toLocaleString()}</td>
            <td>
                <button class="btn btn-sm btn-primary edit-user" data-id="${user.id}">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger delete-user" data-id="${user.id}">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
    
    // 綁定編輯和刪除按鈕事件
    document.querySelectorAll('.edit-user').forEach(button => {
        button.addEventListener('click', () => editUser(button.dataset.id));
    });
    
    document.querySelectorAll('.delete-user').forEach(button => {
        button.addEventListener('click', () => confirmDelete(button.dataset.id));
    });
}

// 更新分頁
function updatePagination(pagination) {
    const ul = document.getElementById('pagination');
    ul.innerHTML = '';
    
    // 上一頁
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${pagination.current_page === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `
        <a class="page-link" href="#" data-page="${pagination.current_page - 1}">
            <i class="fas fa-chevron-left"></i>
        </a>
    `;
    ul.appendChild(prevLi);
    
    // 頁碼
    for (let i = 1; i <= pagination.total_pages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === pagination.current_page ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
        ul.appendChild(li);
    }
    
    // 下一頁
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}`;
    nextLi.innerHTML = `
        <a class="page-link" href="#" data-page="${pagination.current_page + 1}">
            <i class="fas fa-chevron-right"></i>
        </a>
    `;
    ul.appendChild(nextLi);
    
    // 綁定分頁點擊事件
    document.querySelectorAll('.page-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const page = parseInt(link.dataset.page);
            if (page >= 1 && page <= pagination.total_pages) {
                loadUsers(page);
            }
        });
    });
}

// 新增用戶
document.getElementById('addUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/api/admin/users/create.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('新增用戶成功');
            $('#addUserModal').modal('hide');
            this.reset();
            loadUsers(1);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('新增用戶失敗');
    });
});

// 編輯用戶
function editUser(userId) {
    fetch(`/api/admin/users/detail.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.data.user;
                document.getElementById('edit_user_id').value = user.id;
                document.getElementById('edit_username').value = user.username;
                document.getElementById('edit_name').value = user.name;
                document.getElementById('edit_role_id').value = user.role_id;
                document.getElementById('edit_status').value = user.status;
                $('#editUserModal').modal('show');
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('獲取用戶詳情失敗');
        });
}

// 提交編輯
document.getElementById('editUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/api/admin/users/update.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('更新用戶成功');
            $('#editUserModal').modal('hide');
            this.reset();
            loadUsers(currentPage);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('更新用戶失敗');
    });
});

// 確認刪除
function confirmDelete(userId) {
    deleteUserId = userId;
    $('#deleteUserModal').modal('show');
}

// 執行刪除
document.getElementById('confirmDelete').addEventListener('click', function() {
    if (!deleteUserId) return;
    
    const formData = new FormData();
    formData.append('user_id', deleteUserId);
    
    fetch('/api/admin/users/delete.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('刪除用戶成功');
            $('#deleteUserModal').modal('hide');
            loadUsers(currentPage);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('刪除用戶失敗');
    });
});

// 搜尋表單提交
document.getElementById('searchForm').addEventListener('submit', function(e) {
    e.preventDefault();
    loadUsers(1);
});

// 初始載入
loadUsers(1);
</script> 
</script> 