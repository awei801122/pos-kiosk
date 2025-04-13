<?php
/**
 * 菜單管理 API
 */

// 設置響應頭
header('Content-Type: application/json');

// 引入必要的文件
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// 檢查權限
if (!checkPermission('menu.manage')) {
    http_response_code(403);
    echo json_encode(['error' => '沒有權限執行此操作']);
    exit;
}

// 獲取請求方法
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // 獲取菜單列表或單個菜單項
            if (isset($_GET['id'])) {
                // 獲取單個菜單項
                $stmt = $db->prepare("
                    SELECT * FROM menu_items 
                    WHERE id = ?
                ");
                $stmt->execute([$_GET['id']]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($item);
            } else {
                // 獲取菜單列表
                $stmt = $db->prepare("
                    SELECT * FROM menu_items 
                    ORDER BY category, name
                ");
                $stmt->execute();
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($items);
            }
            break;

        case 'POST':
            // 新增或更新菜單項
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['name']) || empty($data['price'])) {
                throw new Exception('名稱和價格不能為空');
            }

            if (empty($data['id'])) {
                // 新增菜單項
                $stmt = $db->prepare("
                    INSERT INTO menu_items (name, description, price, category, image, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['name'],
                    $data['description'] ?? '',
                    $data['price'],
                    $data['category'] ?? '其他',
                    $data['image'] ?? null,
                    $data['status'] ?? 'active'
                ]);
                
                // 記錄操作日誌
                logSystem('info', '新增菜單項', [
                    'name' => $data['name'],
                    'user_id' => $_SESSION['user_id']
                ]);
            } else {
                // 更新菜單項
                $stmt = $db->prepare("
                    UPDATE menu_items 
                    SET name = ?, description = ?, price = ?, 
                        category = ?, image = ?, status = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $data['name'],
                    $data['description'] ?? '',
                    $data['price'],
                    $data['category'] ?? '其他',
                    $data['image'] ?? null,
                    $data['status'] ?? 'active',
                    $data['id']
                ]);
                
                // 記錄操作日誌
                logSystem('info', '更新菜單項', [
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'user_id' => $_SESSION['user_id']
                ]);
            }
            
            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            // 刪除菜單項
            $id = basename($_SERVER['REQUEST_URI']);
            
            // 檢查是否被訂單使用
            $stmt = $db->prepare("SELECT COUNT(*) FROM order_items WHERE item_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('此菜單項已被訂單使用，無法刪除');
            }
            
            $stmt = $db->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->execute([$id]);
            
            // 記錄操作日誌
            logSystem('info', '刪除菜單項', [
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
    logSystem('error', '菜單操作失敗', ['error' => $e->getMessage()]);
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 