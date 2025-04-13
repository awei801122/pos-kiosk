<?php
/**
 * 菜單管理頁面
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

// 檢查登入狀態和權限
checkLogin();
checkPermission('menu.manage');

// 設置頁面標題
$pageTitle = '菜單管理';

// 獲取分類列表
$stmt = $db->query("SELECT * FROM categories ORDER BY sort_order");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 獲取菜單列表
$stmt = $db->query("
    SELECT 
        m.*,
        c.name as category_name
    FROM menu_items m
    LEFT JOIN categories c ON m.category_id = c.id
    ORDER BY c.sort_order, m.sort_order
");
$menuItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 引入布局文件
require_once __DIR__ . '/layout.php';
?>

<!-- 操作按鈕 -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h2">菜單管理</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMenuItemModal">
        <i class="fas fa-plus me-2"></i>新增商品
    </button>
</div>

<!-- 菜單列表 -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>商品名稱</th>
                        <th>分類</th>
                        <th>價格</th>
                        <th>狀態</th>
                        <th>排序</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($menuItems as $item): ?>
                    <tr>
                        <td><?php echo $item['id']; ?></td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                        <td><?php echo formatMoney($item['price']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $item['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo $item['status'] === 'active' ? '上架' : '下架'; ?>
                            </span>
                        </td>
                        <td><?php echo $item['sort_order']; ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary edit-item" 
                                    data-id="<?php echo $item['id']; ?>"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editMenuItemModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-item" 
                                    data-id="<?php echo $item['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($item['name']); ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 新增商品 Modal -->
<div class="modal fade" id="addMenuItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新增商品</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addMenuItemForm" action="api/admin/menu.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">商品名稱</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">分類</label>
                        <select class="form-select" name="category_id" required>
                            <option value="">請選擇分類</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">價格</label>
                        <input type="number" class="form-control" name="price" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">描述</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">圖片</label>
                        <input type="file" class="form-control" name="image" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">排序</label>
                        <input type="number" class="form-control" name="sort_order" value="0">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="status" value="active" checked>
                            <label class="form-check-label">上架</label>
                        </div>
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

<!-- 編輯商品 Modal -->
<div class="modal fade" id="editMenuItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">編輯商品</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editMenuItemForm" action="api/admin/menu.php" method="POST">
                <input type="hidden" name="id" id="editItemId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">商品名稱</label>
                        <input type="text" class="form-control" name="name" id="editItemName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">分類</label>
                        <select class="form-select" name="category_id" id="editItemCategory" required>
                            <option value="">請選擇分類</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">價格</label>
                        <input type="number" class="form-control" name="price" id="editItemPrice" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">描述</label>
                        <textarea class="form-control" name="description" id="editItemDescription" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">圖片</label>
                        <input type="file" class="form-control" name="image" accept="image/*">
                        <div id="editItemImagePreview" class="mt-2"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">排序</label>
                        <input type="number" class="form-control" name="sort_order" id="editItemSortOrder">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="status" id="editItemStatus" value="active">
                            <label class="form-check-label">上架</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">儲存</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 刪除確認 Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">確認刪除</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>確定要刪除商品 "<span id="deleteItemName"></span>" 嗎？</p>
                <p class="text-danger">此操作無法復原！</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">刪除</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 編輯商品
    document.querySelectorAll('.edit-item').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            // 獲取商品資料
            fetch(`api/admin/menu.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const item = data.data;
                        document.getElementById('editItemId').value = item.id;
                        document.getElementById('editItemName').value = item.name;
                        document.getElementById('editItemCategory').value = item.category_id;
                        document.getElementById('editItemPrice').value = item.price;
                        document.getElementById('editItemDescription').value = item.description;
                        document.getElementById('editItemSortOrder').value = item.sort_order;
                        document.getElementById('editItemStatus').checked = item.status === 'active';
                        
                        // 顯示圖片預覽
                        const preview = document.getElementById('editItemImagePreview');
                        if (item.image) {
                            preview.innerHTML = `<img src="../uploads/${item.image}" class="img-thumbnail" style="max-height: 100px;">`;
                        } else {
                            preview.innerHTML = '';
                        }
                    }
                });
        });
    });

    // 刪除商品
    document.querySelectorAll('.delete-item').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            document.getElementById('deleteItemName').textContent = name;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            modal.show();
            
            document.getElementById('confirmDelete').onclick = function() {
                fetch(`api/admin/menu.php?id=${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || '刪除失敗');
                    }
                });
            };
        });
    });

    // 表單提交
    document.getElementById('addMenuItemForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || '新增失敗');
            }
        });
    });

    document.getElementById('editMenuItemForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || '更新失敗');
            }
        });
    });
});
</script> 