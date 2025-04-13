<?php
/**
 * 用戶管理 API
 */

// 設置響應頭
header('Content-Type: application/json');

// 引入必要的文件
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// 檢查權限
if (!checkPermission('users.manage')) {
    http_response_code(403);
    echo json_encode(['error' => '沒有權限執行此操作']);
    exit;
}

// 獲取請求方法
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // 獲取用戶列表或單個用戶
            if (isset($_GET['id'])) {
                // 獲取單個用戶
                $stmt = $db->prepare("
                    SELECT u.*, r.name as role_name
                    FROM users u
                    LEFT JOIN roles r ON u.role_id = r.id
                    WHERE u.id = ?
                ");
                $stmt->execute([$_GET['id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    // 獲取用戶權限
                    $stmt = $db->prepare("
                        SELECT p.code, p.name
                        FROM user_permissions up
                        JOIN permissions p ON up.permission_id = p.id
                        WHERE up.user_id = ?
                    ");
                    $stmt->execute([$_GET['id']]);
                    $user['permissions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                
                echo json_encode($user);
            } else {
                // 獲取用戶列表
                $stmt = $db->prepare("
                    SELECT u.*, r.name as role_name
                    FROM users u
                    LEFT JOIN roles r ON u.role_id = r.id
                    ORDER BY u.name
                ");
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($users);
            }
            break;

        case 'POST':
            // 新增或更新用戶
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['username']) || empty($data['name']) || empty($data['role_id'])) {
                throw new Exception('用戶名、姓名和角色不能為空');
            }

            // 開始事務
            $db->beginTransaction();
            
            try {
                if (empty($data['id'])) {
                    // 新增用戶
                    if (empty($data['password'])) {
                        throw new Exception('密碼不能為空');
                    }
                    
                    $stmt = $db->prepare("
                        INSERT INTO users (username, password, name, email, phone, role_id, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $data['username'],
                        password_hash($data['password'], PASSWORD_DEFAULT),
                        $data['name'],
                        $data['email'] ?? null,
                        $data['phone'] ?? null,
                        $data['role_id'],
                        $data['status'] ?? 'active'
                    ]);
                    
                    $userId = $db->lastInsertId();
                    
                    // 記錄操作日誌
                    logSystem('info', '新增用戶', [
                        'username' => $data['username'],
                        'user_id' => $_SESSION['user_id']
                    ]);
                } else {
                    // 更新用戶
                    $userId = $data['id'];
                    $sql = "UPDATE users SET username = ?, name = ?, email = ?, phone = ?, role_id = ?, status = ?";
                    $params = [
                        $data['username'],
                        $data['name'],
                        $data['email'] ?? null,
                        $data['phone'] ?? null,
                        $data['role_id'],
                        $data['status'] ?? 'active'
                    ];
                    
                    if (!empty($data['password'])) {
                        $sql .= ", password = ?";
                        $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
                    }
                    
                    $sql .= " WHERE id = ?";
                    $params[] = $userId;
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);
                    
                    // 記錄操作日誌
                    logSystem('info', '更新用戶', [
                        'id' => $userId,
                        'username' => $data['username'],
                        'user_id' => $_SESSION['user_id']
                    ]);
                }
                
                // 更新用戶權限
                if (isset($data['permissions'])) {
                    // 刪除現有權限
                    $stmt = $db->prepare("DELETE FROM user_permissions WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    
                    // 添加新權限
                    if (!empty($data['permissions'])) {
                        $stmt = $db->prepare("
                            INSERT INTO user_permissions (user_id, permission_id)
                            SELECT ?, p.id FROM permissions p WHERE p.code = ?
                        ");
                        foreach ($data['permissions'] as $permission) {
                            $stmt->execute([$userId, $permission]);
                        }
                    }
                }
                
                $db->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        case 'DELETE':
            // 刪除用戶
            $id = basename($_SERVER['REQUEST_URI']);
            
            // 檢查是否為當前用戶
            if ($id == $_SESSION['user_id']) {
                throw new Exception('不能刪除當前登入的用戶');
            }
            
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            
            // 記錄操作日誌
            logSystem('info', '刪除用戶', [
                'id' => $id,
                'user_id' => $_SESSION['user_id']
            ]);
            
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => '不支援的請求方法']);
            break;
    }
} catch (Exception $e) {
    logSystem('error', '用戶操作失敗', ['error' => $e->getMessage()]);
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 