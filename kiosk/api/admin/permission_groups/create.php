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
$name = trim($_POST['name'] ?? '');
$permissions = $_POST['permissions'] ?? [];
$description = trim($_POST['description'] ?? '');

// 驗證輸入
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
    
    // 檢查權限組名稱是否已存在
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM permission_groups WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('權限組名稱已存在');
    }
    
    // 插入權限組
    $stmt = $pdo->prepare("
        INSERT INTO permission_groups (name, permissions, description, created_at, updated_at)
        VALUES (?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $name,
        json_encode($permissions),
        $description
    ]);
    
    $groupId = $pdo->lastInsertId();
    
    // 記錄日誌
    logAction('create_permission_group', "創建權限組：{$name}");
    
    // 提交事務
    $pdo->commit();
    
    // 清除權限組快取
    clearPermissionGroupCache();
    
    echo json_encode([
        'success' => true,
        'message' => '權限組創建成功',
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