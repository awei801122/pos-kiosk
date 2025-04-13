<?php
/**
 * 創建用戶 API
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
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
$confirmPassword = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$roleId = isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0;

// 驗證參數
if (!$username || !$password || !$confirmPassword || !$name || !$roleId) {
    echo json_encode(['success' => false, 'message' => '請填寫所有必填欄位']);
    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => '兩次輸入的密碼不一致']);
    exit;
}

try {
    // 檢查用戶名是否已存在
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        throw new Exception('用戶名已存在');
    }
    
    // 檢查角色是否存在
    $stmt = $db->prepare("SELECT id FROM roles WHERE id = ?");
    $stmt->execute([$roleId]);
    if (!$stmt->fetch()) {
        throw new Exception('角色不存在');
    }
    
    // 開始事務
    $db->beginTransaction();
    
    // 創建用戶
    $stmt = $db->prepare("
        INSERT INTO users (
            username,
            password,
            name,
            role_id,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, 'active', NOW())
    ");
    $stmt->execute([
        $username,
        password_hash($password, PASSWORD_DEFAULT),
        $name,
        $roleId
    ]);
    
    $userId = $db->lastInsertId();
    
    // 記錄操作日誌
    $stmt = $db->prepare("
        INSERT INTO system_logs (
            user_id,
            action,
            details,
            created_at
        ) VALUES (?, 'create_user', ?, NOW())
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        json_encode([
            'user_id' => $userId,
            'username' => $username,
            'name' => $name,
            'role_id' => $roleId
        ])
    ]);
    
    // 提交事務
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => '用戶創建成功'
    ]);
    
} catch (Exception $e) {
    // 回滾事務
    $db->rollBack();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 