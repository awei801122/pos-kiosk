<?php
/**
 * 角色刪除 API
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

// 獲取角色 ID
$roleId = intval($_POST['id'] ?? 0);

// 驗證輸入
if ($roleId <= 0) {
    echo json_encode(['success' => false, 'message' => '無效的角色 ID']);
    exit;
}

try {
    // 開始事務
    $pdo->beginTransaction();
    
    // 檢查角色是否存在
    $stmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
    $stmt->execute([$roleId]);
    $role = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$role) {
        throw new Exception('角色不存在');
    }
    
    // 檢查是否有用戶使用此角色
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
    $stmt->execute([$roleId]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('無法刪除，有用戶正在使用此角色');
    }
    
    // 刪除角色
    $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
    $stmt->execute([$roleId]);
    
    // 記錄日誌
    logAction('delete_role', "刪除角色：{$role['name']}");
    
    // 提交事務
    $pdo->commit();
    
    // 清除角色快取
    clearRoleCache();
    clearRoleCache($roleId);
    
    echo json_encode([
        'success' => true,
        'message' => '角色刪除成功'
    ]);
} catch (Exception $e) {
    // 回滾事務
    $pdo->rollBack();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 