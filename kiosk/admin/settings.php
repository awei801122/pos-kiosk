<?php
/**
 * 系統設置頁面
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

// 檢查登入狀態和權限
checkLogin();
checkPermission('settings.manage');

// 設置頁面標題
$pageTitle = '系統設置';

// 獲取系統設置
$stmt = $db->query("SELECT * FROM settings");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key']] = $row['value'];
}

// 引入布局文件
require_once __DIR__ . '/layout.php';
?>

<!-- 操作按鈕 -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h2">系統設置</h1>
    <button type="button" class="btn btn-primary" id="saveSettings">
        <i class="fas fa-save me-2"></i>儲存設置
    </button>
</div>

<!-- 設置表單 -->
<div class="row">
    <!-- 基本設置 -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">基本設置</h5>
            </div>
            <div class="card-body">
                <form id="settingsForm">
                    <div class="mb-3">
                        <label class="form-label">系統名稱</label>
                        <input type="text" class="form-control" name="system_name" 
                               value="<?php echo htmlspecialchars($settings['system_name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">營業時間</label>
                        <div class="row">
                            <div class="col">
                                <input type="time" class="form-control" name="business_hours_start" 
                                       value="<?php echo htmlspecialchars($settings['business_hours_start'] ?? '09:00'); ?>">
                            </div>
                            <div class="col-auto align-self-center">至</div>
                            <div class="col">
                                <input type="time" class="form-control" name="business_hours_end" 
                                       value="<?php echo htmlspecialchars($settings['business_hours_end'] ?? '22:00'); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">稅率 (%)</label>
                        <input type="number" class="form-control" name="tax_rate" 
                               value="<?php echo htmlspecialchars($settings['tax_rate'] ?? '5'); ?>" 
                               min="0" max="100" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">小數位數</label>
                        <select class="form-select" name="decimal_places" required>
                            <option value="0" <?php echo ($settings['decimal_places'] ?? '2') === '0' ? 'selected' : ''; ?>>0 位</option>
                            <option value="1" <?php echo ($settings['decimal_places'] ?? '2') === '1' ? 'selected' : ''; ?>>1 位</option>
                            <option value="2" <?php echo ($settings['decimal_places'] ?? '2') === '2' ? 'selected' : ''; ?>>2 位</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 列印設置 -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">列印設置</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">列印機名稱</label>
                    <input type="text" class="form-control" name="printer_name" 
                           value="<?php echo htmlspecialchars($settings['printer_name'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">列印份數</label>
                    <input type="number" class="form-control" name="print_copies" 
                           value="<?php echo htmlspecialchars($settings['print_copies'] ?? '1'); ?>" 
                           min="1" max="5" required>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="print_kitchen" 
                               value="1" <?php echo ($settings['print_kitchen'] ?? '1') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label">列印廚房單</label>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="print_customer" 
                               value="1" <?php echo ($settings['print_customer'] ?? '1') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label">列印顧客單</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 系統信息 -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">系統信息</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">系統版本</label>
                    <input type="text" class="form-control" value="1.0.0" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">PHP版本</label>
                    <input type="text" class="form-control" value="<?php echo PHP_VERSION; ?>" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">MySQL版本</label>
                    <input type="text" class="form-control" value="<?php echo $db->getAttribute(PDO::ATTR_SERVER_VERSION); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">最後備份時間</label>
                    <input type="text" class="form-control" 
                           value="<?php echo $settings['last_backup'] ?? '從未備份'; ?>" readonly>
                </div>
            </div>
        </div>
    </div>

    <!-- 備份與維護 -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">備份與維護</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <button type="button" class="btn btn-primary w-100" id="backupDatabase">
                        <i class="fas fa-database me-2"></i>備份資料庫
                    </button>
                </div>
                <div class="mb-3">
                    <button type="button" class="btn btn-warning w-100" id="clearCache">
                        <i class="fas fa-broom me-2"></i>清除快取
                    </button>
                </div>
                <div class="mb-3">
                    <button type="button" class="btn btn-danger w-100" id="resetSystem">
                        <i class="fas fa-redo me-2"></i>重置系統
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 重置確認 Modal -->
<div class="modal fade" id="resetConfirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">確認重置</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-danger">確定要重置系統嗎？</p>
                <p>此操作將：</p>
                <ul>
                    <li>清除所有訂單記錄</li>
                    <li>重置庫存數量</li>
                    <li>清除系統日誌</li>
                </ul>
                <p class="text-danger">此操作無法復原！</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-danger" id="confirmReset">確認重置</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 儲存設置
    document.getElementById('saveSettings').addEventListener('click', function() {
        const formData = new FormData(document.getElementById('settingsForm'));
        
        fetch('api/admin/settings.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('設置已儲存');
                location.reload();
            } else {
                alert(data.message || '儲存失敗');
            }
        });
    });

    // 備份資料庫
    document.getElementById('backupDatabase').addEventListener('click', function() {
        fetch('api/admin/settings.php?action=backup')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('備份成功');
                    location.reload();
                } else {
                    alert(data.message || '備份失敗');
                }
            });
    });

    // 清除快取
    document.getElementById('clearCache').addEventListener('click', function() {
        fetch('api/admin/settings.php?action=clear_cache')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('快取已清除');
                } else {
                    alert(data.message || '清除失敗');
                }
            });
    });

    // 重置系統
    document.getElementById('resetSystem').addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('resetConfirmModal'));
        modal.show();
        
        document.getElementById('confirmReset').onclick = function() {
            fetch('api/admin/settings.php?action=reset', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('系統已重置');
                    location.reload();
                } else {
                    alert(data.message || '重置失敗');
                }
            });
        };
    });
});
</script> 