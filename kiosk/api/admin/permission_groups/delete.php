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

// 獲取權限組 ID
$groupId = intval($_POST['id'] ?? 0);

// 驗證輸入
if ($groupId <= 0) {
    echo json_encode(['success' => false, 'message' => '無效的權限組 ID']);
    exit;
}

try {
    // 開始事務
    $pdo->beginTransaction();
    
    // 檢查權限組是否存在
    $stmt = $pdo->prepare("SELECT name FROM permission_groups WHERE id = ?");
    $stmt->execute([$groupId]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$group) {
        throw new Exception('權限組不存在');
    }
    
    // 檢查是否有角色使用此權限組
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM roles WHERE permission_group_id = ?");
    $stmt->execute([$groupId]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('無法刪除，有角色正在使用此權限組');
    }
    
    // 刪除權限組
    $stmt = $pdo->prepare("DELETE FROM permission_groups WHERE id = ?");
    $stmt->execute([$groupId]);
    
    // 記錄日誌
    logAction('delete_permission_group', "刪除權限組：{$group['name']}");
    
    // 提交事務
    $pdo->commit();
    
    // 清除權限組快取
    clearPermissionGroupCache();
    clearPermissionGroupCache($groupId);
    
    echo json_encode([
        'success' => true,
        'message' => '權限組刪除成功'
    ]);
} catch (Exception $e) {
    // 回滾事務
    $pdo->rollBack();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 