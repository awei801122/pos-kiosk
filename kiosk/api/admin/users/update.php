<?php
/**
 * 更新用戶 API
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
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$roleId = isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
$confirmPassword = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

// 驗證參數
if (!$userId || !$name || !$roleId || !$status) {
    echo json_encode(['success' => false, 'message' => '請填寫所有必填欄位']);
    exit;
}

if ($password && $password !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => '兩次輸入的密碼不一致']);
    exit;
}

try {
    // 檢查用戶是否存在
    $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    if (!$stmt->fetch()) {
        throw new Exception('用戶不存在');
    }
    
    // 檢查角色是否存在
    $stmt = $db->prepare("SELECT id FROM roles WHERE id = ?");
    $stmt->execute([$roleId]);
    if (!$stmt->fetch()) {
        throw new Exception('角色不存在');
    }
    
    // 開始事務
    $db->beginTransaction();
    
    // 更新用戶資訊
    if ($password) {
        $stmt = $db->prepare("
            UPDATE users SET
                name = ?,
                role_id = ?,
                status = ?,
                password = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $name,
            $roleId,
            $status,
            password_hash($password, PASSWORD_DEFAULT),
            $userId
        ]);
    } else {
        $stmt = $db->prepare("
            UPDATE users SET
                name = ?,
                role_id = ?,
                status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $name,
            $roleId,
            $status,
            $userId
        ]);
    }
    
    // 記錄操作日誌
    $stmt = $db->prepare("
        INSERT INTO system_logs (
            user_id,
            action,
            details,
            created_at
        ) VALUES (?, 'update_user', ?, NOW())
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        json_encode([
            'user_id' => $userId,
            'name' => $name,
            'role_id' => $roleId,
            'status' => $status,
            'password_changed' => $password ? true : false
        ])
    ]);
    
    // 提交事務
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => '用戶更新成功'
    ]);
    
} catch (Exception $e) {
    // 回滾事務
    $db->rollBack();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 