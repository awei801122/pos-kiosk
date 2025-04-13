<?php
/**
 * 庫存管理頁面
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

// 檢查權限
checkLogin();
checkPermission('inventory.manage');

// 設置頁面標題
$pageTitle = '庫存管理';

// 獲取庫存列表
$stmt = $db->prepare("
    SELECT 
        m.id,
        m.name,
        m.category_id,
        c.name as category_name,
        i.current_stock,
        i.low_stock_threshold,
        i.unit,
        i.status,
        i.updated_at
    FROM menu_items m
    JOIN categories c ON m.category_id = c.id
    JOIN inventory i ON m.id = i.menu_item_id
    ORDER BY c.sort_order, m.sort_order
");
$stmt->execute();
$inventoryItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 獲取分類列表
$stmt = $db->prepare("SELECT id, name FROM categories ORDER BY sort_order");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 包含布局文件
include __DIR__ . '/layout.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><?php echo $pageTitle; ?></h1>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-upload"></i> 批量導入
            </button>
        </div>
    </div>

    <!-- 庫存列表 -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>商品名稱</th>
                            <th>分類</th>
                            <th>當前庫存</th>
                            <th>低庫存閾值</th>
                            <th>單位</th>
                            <th>狀態</th>
                            <th>最後更新</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventoryItems as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                            <td>
                                <?php if ($item['current_stock'] <= $item['low_stock_threshold']): ?>
                                <span class="badge bg-danger"><?php echo $item['current_stock']; ?></span>
                                <?php else: ?>
                                <?php echo $item['current_stock']; ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $item['low_stock_threshold']; ?></td>
                            <td><?php echo htmlspecialchars($item['unit']); ?></td>
                            <td>
                                <?php if ($item['status'] === 'active'): ?>
                                <span class="badge bg-success">正常</span>
                                <?php else: ?>
                                <span class="badge bg-danger">停用</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($item['updated_at'])); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#adjustModal"
                                        data-id="<?php echo $item['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                        data-current="<?php echo $item['current_stock']; ?>"
                                        data-threshold="<?php echo $item['low_stock_threshold']; ?>"
                                        data-unit="<?php echo htmlspecialchars($item['unit']); ?>">
                                    <i class="bi bi-pencil"></i> 調整
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 調整庫存對話框 -->
<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">調整庫存</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="adjustForm">
                    <input type="hidden" name="id" id="adjustId">
                    <div class="mb-3">
                        <label class="form-label">商品名稱</label>
                        <input type="text" class="form-control" id="adjustName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">當前庫存</label>
                        <input type="number" class="form-control" name="current_stock" id="adjustCurrent" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">低庫存閾值</label>
                        <input type="number" class="form-control" name="low_stock_threshold" id="adjustThreshold" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">單位</label>
                        <input type="text" class="form-control" name="unit" id="adjustUnit" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">備註</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="saveAdjust">保存</button>
            </div>
        </div>
    </div>
</div>

<!-- 批量導入對話框 -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">批量導入庫存</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="importForm">
                    <div class="mb-3">
                        <label class="form-label">選擇文件</label>
                        <input type="file" class="form-control" name="file" accept=".csv,.xlsx" required>
                        <div class="form-text">支持 CSV 和 Excel 格式</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">文件模板</label>
                        <a href="/templates/inventory_import_template.csv" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-download"></i> 下載模板
                        </a>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="saveImport">導入</button>
            </div>
        </div>
    </div>
</div>

<script>
// 調整庫存
document.querySelectorAll('[data-bs-toggle="modal"]').forEach(button => {
    button.addEventListener('click', function() {
        const modal = document.getElementById('adjustModal');
        const id = this.dataset.id;
        const name = this.dataset.name;
        const current = this.dataset.current;
        const threshold = this.dataset.threshold;
        const unit = this.dataset.unit;
        
        modal.querySelector('#adjustId').value = id;
        modal.querySelector('#adjustName').value = name;
        modal.querySelector('#adjustCurrent').value = current;
        modal.querySelector('#adjustThreshold').value = threshold;
        modal.querySelector('#adjustUnit').value = unit;
    });
});

document.getElementById('saveAdjust').addEventListener('click', function() {
    const form = document.getElementById('adjustForm');
    const formData = new FormData(form);
    
    fetch('/api/admin/inventory/adjust.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('庫存調整成功');
            window.location.reload();
        } else {
            alert(data.message || '庫存調整失敗');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('庫存調整時發生錯誤');
    });
});

// 批量導入
document.getElementById('saveImport').addEventListener('click', function() {
    const form = document.getElementById('importForm');
    const formData = new FormData(form);
    
    fetch('/api/admin/inventory/import.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('庫存導入成功');
            window.location.reload();
        } else {
            alert(data.message || '庫存導入失敗');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('庫存導入時發生錯誤');
    });
});
</script> 