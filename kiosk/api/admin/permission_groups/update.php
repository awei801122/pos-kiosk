<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../../includes/session.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../../../includes/cache.php';

// 檢查權限
checkLogin();
checkPermission('roles.manage');

// 檢查請求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '無效的請求方法']);
    exit;
}

// 獲取輸入數據
$groupId = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$permissions = $_POST['permissions'] ?? [];
$description = trim($_POST['description'] ?? '');

// 驗證輸入
if ($groupId <= 0) {
    echo json_encode(['success' => false, 'message' => '無效的權限組 ID']);
    exit;
}

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => '權限組名稱不能為空']);
    exit;
}

if (empty($permissions)) {
    echo json_encode(['success' => false, 'message' => '請至少選擇一個權限']);
    exit;
}

try {
    // 開始事務
    $pdo->beginTransaction();
    
    // 檢查權限組是否存在
    $stmt = $pdo->prepare("SELECT name FROM permission_groups WHERE id = ?");
    $stmt->execute([$groupId]);
    $oldGroup = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$oldGroup) {
        throw new Exception('權限組不存在');
    }
    
    // 檢查權限組名稱是否已存在（排除當前權限組）
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM permission_groups WHERE name = ? AND id != ?");
    $stmt->execute([$name, $groupId]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('權限組名稱已存在');
    }
    
    // 更新權限組
    $stmt = $pdo->prepare("
        UPDATE permission_groups 
        SET name = ?, permissions = ?, description = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([
        $name,
        json_encode($permissions),
        $description,
        $groupId
    ]);
    
    // 記錄日誌
    logAction('update_permission_group', "更新權限組：{$oldGroup['name']} -> {$name}");
    
    // 提交事務
    $pdo->commit();
    
    // 清除權限組快取
    clearPermissionGroupCache();
    clearPermissionGroupCache($groupId);
    
    echo json_encode([
        'success' => true,
        'message' => '權限組更新成功',
        'data' => [
            'id' => $groupId,
            'name' => $name,
            'permissions' => $permissions,
            'description' => $description
        ]
    ]);
} catch (Exception $e) {
    // 回滾事務
    $pdo->rollBack();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 