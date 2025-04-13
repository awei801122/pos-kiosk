<?php
/**
 * 獲取當前用戶的權限列表
 */

// 設置響應頭
header('Content-Type: application/json');

// 引入必要的文件
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

// 檢查是否已登入
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => '未登入']);
    exit;
}

try {
    // 獲取用戶的所有權限（包括角色權限和直接分配的權限）
    $stmt = $db->prepare("
        SELECT DISTINCT p.code
        FROM permissions p
        LEFT JOIN user_permissions up ON p.id = up.permission_id AND up.user_id = ?
        LEFT JOIN role_permissions rp ON p.id = rp.permission_id
        LEFT JOIN users u ON rp.role_id = u.role_id AND u.id = ?
        WHERE up.user_id IS NOT NULL OR u.id IS NOT NULL
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    
    // 獲取權限代碼列表
    $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode($permissions);
} catch (Exception $e) {
    logSystem('error', '獲取用戶權限失敗', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['error' => '獲取權限失敗']);
} 