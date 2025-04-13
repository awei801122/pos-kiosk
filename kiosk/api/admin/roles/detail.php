<?php
/**
 * 角色詳情 API
 */
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

// 獲取角色 ID
$roleId = intval($_GET['id'] ?? 0);

// 驗證輸入
if ($roleId <= 0) {
    echo json_encode(['success' => false, 'message' => '無效的角色 ID']);
    exit;
}

try {
    // 從快取中獲取角色詳情
    $role = getRoleCache($roleId);
    
    if (!$role) {
        // 如果快取中沒有，則從資料庫中獲取
        $sql = "
            SELECT 
                r.id,
                r.name,
                r.permission_group_id,
                pg.name as permission_group_name,
                pg.permissions,
                r.description,
                r.created_at,
                r.updated_at,
                (SELECT COUNT(*) FROM users WHERE role_id = r.id) as user_count
            FROM roles r
            LEFT JOIN permission_groups pg ON r.permission_group_id = pg.id
            WHERE r.id = ?
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$roleId]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$role) {
            throw new Exception('角色不存在');
        }
        
        // 處理權限數據
        $role['permissions'] = json_decode($role['permissions'], true);
        
        // 將角色存入快取
        setRoleCache($roleId, $role);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $role
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 