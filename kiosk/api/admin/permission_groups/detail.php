<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../../includes/session.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../../../includes/cache.php';

// 檢查權限
checkLogin();
checkPermission('roles.manage');

// 檢查請求方法
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => '無效的請求方法']);
    exit;
}

// 獲取權限組 ID
$groupId = intval($_GET['id'] ?? 0);

// 驗證輸入
if ($groupId <= 0) {
    echo json_encode(['success' => false, 'message' => '無效的權限組 ID']);
    exit;
}

try {
    // 從快取中獲取權限組詳情
    $group = getPermissionGroupCache($groupId);
    
    if (!$group) {
        // 如果快取中沒有，則從資料庫中獲取
        $sql = "
            SELECT 
                id,
                name,
                permissions,
                description,
                created_at,
                updated_at
            FROM permission_groups
            WHERE id = ?
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$group) {
            throw new Exception('權限組不存在');
        }
        
        // 處理權限數據
        $group['permissions'] = json_decode($group['permissions'], true);
        
        // 將權限組存入快取
        setPermissionGroupCache($groupId, $group);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $group
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 