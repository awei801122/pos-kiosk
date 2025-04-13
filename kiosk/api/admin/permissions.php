<?php
/**
 * 權限管理 API
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

try {
    switch ($method) {
        case 'GET':
            // 獲取權限列表
            $stmt = $db->prepare("SELECT id, code, name, description FROM permissions ORDER BY name");
            $stmt->execute();
            $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($permissions);
            break;

        case 'POST':
            // 新增或更新權限
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['code']) || empty($data['name'])) {
                throw new Exception('權限代碼和名稱不能為空');
            }

            if (empty($data['id'])) {
                // 新增權限
                $stmt = $db->prepare("
                    INSERT INTO permissions (code, name, description)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$data['code'], $data['name'], $data['description'] ?? '']);
            } else {
                // 更新權限
                $stmt = $db->prepare("
                    UPDATE permissions 
                    SET code = ?, name = ?, description = ?
                    WHERE id = ?
                ");
                $stmt->execute([$data['code'], $data['name'], $data['description'] ?? '', $data['id']]);
            }

            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            // 刪除權限
            $id = basename($_SERVER['REQUEST_URI']);
            
            // 檢查權限是否被使用
            $stmt = $db->prepare("SELECT COUNT(*) FROM user_permissions WHERE permission_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('此權限已被分配給用戶，無法刪除');
            }

            $stmt = $db->prepare("DELETE FROM permissions WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
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