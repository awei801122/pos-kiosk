<?php
/**
 * 用戶權限管理 API
 */

// 設置響應頭
header('Content-Type: application/json');

// 引入必要的文件
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// 檢查權限
if (!checkPermission('permissions.manage')) {
    http_response_code(403);
    echo json_encode(['error' => '沒有權限訪問此功能']);
    exit;
}

// 獲取請求方法
$method = $_SERVER['REQUEST_METHOD'];

// 從 URL 中獲取用戶 ID
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));
$userId = $segments[count($segments) - 2];

try {
    switch ($method) {
        case 'GET':
            // 獲取用戶權限
            $stmt = $db->prepare("
                SELECT p.id, p.code, p.name, p.description
                FROM user_permissions up
                JOIN permissions p ON up.permission_id = p.id
                WHERE up.user_id = ?
            ");
            $stmt->execute([$userId]);
            $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($permissions);
            break;

        case 'POST':
            // 更新用戶權限
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['permissions'])) {
                $data['permissions'] = [];
            }

            // 開始事務
            $db->beginTransaction();

            try {
                // 刪除現有權限
                $stmt = $db->prepare("DELETE FROM user_permissions WHERE user_id = ?");
                $stmt->execute([$userId]);

                // 添加新權限
                if (!empty($data['permissions'])) {
                    $stmt = $db->prepare("
                        INSERT INTO user_permissions (user_id, permission_id)
                        VALUES (?, ?)
                    ");
                    foreach ($data['permissions'] as $permissionId) {
                        $stmt->execute([$userId, $permissionId]);
                    }
                }

                $db->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => '不支援的請求方法']);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 