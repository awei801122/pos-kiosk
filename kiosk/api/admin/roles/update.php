<?php
/**
 * 角色更新 API
 */
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
$roleId = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$permissionGroupId = intval($_POST['permission_group_id'] ?? 0);
$description = trim($_POST['description'] ?? '');

// 驗證輸入
if ($roleId <= 0) {
    echo json_encode(['success' => false, 'message' => '無效的角色 ID']);
    exit;
}

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => '角色名稱不能為空']);
    exit;
}

if ($permissionGroupId <= 0) {
    echo json_encode(['success' => false, 'message' => '請選擇權限組']);
    exit;
}

try {
    // 開始事務
    $pdo->beginTransaction();
    
    // 檢查角色是否存在
    $stmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
    $stmt->execute([$roleId]);
    $oldRole = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$oldRole) {
        throw new Exception('角色不存在');
    }
    
    // 檢查角色名稱是否已存在（排除當前角色）
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM roles WHERE name = ? AND id != ?");
    $stmt->execute([$name, $roleId]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('角色名稱已存在');
    }
    
    // 檢查權限組是否存在
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM permission_groups WHERE id = ?");
    $stmt->execute([$permissionGroupId]);
    if ($stmt->fetchColumn() === 0) {
        throw new Exception('權限組不存在');
    }
    
    // 更新角色
    $stmt = $pdo->prepare("
        UPDATE roles 
        SET name = ?, permission_group_id = ?, description = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$name, $permissionGroupId, $description, $roleId]);
    
    // 記錄日誌
    logAction('update_role', "更新角色：{$oldRole['name']} -> {$name}");
    
    // 提交事務
    $pdo->commit();
    
    // 清除角色快取
    clearRoleCache();
    clearRoleCache($roleId);
    
    echo json_encode([
        'success' => true,
        'message' => '角色更新成功',
        'data' => [
            'id' => $roleId,
            'name' => $name,
            'permission_group_id' => $permissionGroupId,
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