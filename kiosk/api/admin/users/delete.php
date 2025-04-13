<?php
/**
 * 刪除用戶 API
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/session.php';

// 檢查權限
checkLogin();
checkPermission('users.manage');

// 檢查請求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '不支援的請求方法']);
    exit;
}

// 獲取參數
$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

// 驗證參數
if (!$userId) {
    echo json_encode(['success' => false, 'message' => '請提供用戶ID']);
    exit;
}

try {
    // 檢查用戶是否存在
    $stmt = $db->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('用戶不存在');
    }
    
    // 檢查是否為當前登入用戶
    if ($userId == $_SESSION['user_id']) {
        throw new Exception('不能刪除當前登入的用戶');
    }
    
    // 開始事務
    $db->beginTransaction();
    
    // 刪除用戶
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    
    // 記錄操作日誌
    $stmt = $db->prepare("
        INSERT INTO system_logs (
            user_id,
            action,
            details,
            created_at
        ) VALUES (?, 'delete_user', ?, NOW())
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        json_encode([
            'deleted_user_id' => $userId,
            'deleted_username' => $user['username']
        ])
    ]);
    
    // 提交事務
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => '用戶刪除成功'
    ]);
    
} catch (Exception $e) {
    // 回滾事務
    $db->rollBack();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 