<?php
/**
 * 獲取單一用戶詳情 API
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/session.php';

// 檢查權限
checkLogin();
checkPermission('users.manage');

// 檢查請求方法
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => '不支援的請求方法']);
    exit;
}

// 獲取參數
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// 驗證參數
if (!$userId) {
    echo json_encode(['success' => false, 'message' => '請提供用戶ID']);
    exit;
}

try {
    // 獲取用戶詳情
    $stmt = $db->prepare("
        SELECT 
            u.id,
            u.username,
            u.name,
            u.status,
            u.role_id,
            u.created_at,
            u.updated_at,
            u.last_login,
            r.name as role_name,
            r.permissions as role_permissions
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('用戶不存在');
    }
    
    // 獲取用戶的操作日誌
    $stmt = $db->prepare("
        SELECT 
            action,
            details,
            created_at
        FROM system_logs
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 解析角色權限
    $user['role_permissions'] = json_decode($user['role_permissions'], true);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'user' => $user,
            'logs' => $logs
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 