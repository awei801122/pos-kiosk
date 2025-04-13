<?php
/**
 * 庫存管理 API
 */

// 設置響應頭
header('Content-Type: application/json');

// 引入必要的文件
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// 檢查權限
if (!checkPermission('inventory.manage')) {
    http_response_code(403);
    echo json_encode(['error' => '沒有權限執行此操作']);
    exit;
}

// 獲取請求方法
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // 獲取庫存列表或單個庫存項
            if (isset($_GET['id'])) {
                // 獲取單個庫存項
                $stmt = $db->prepare("
                    SELECT i.*, mi.name as item_name
                    FROM inventory i
                    LEFT JOIN menu_items mi ON i.item_id = mi.id
                    WHERE i.id = ?
                ");
                $stmt->execute([$_GET['id']]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($item);
            } else {
                // 獲取庫存列表
                $stmt = $db->prepare("
                    SELECT i.*, mi.name as item_name
                    FROM inventory i
                    LEFT JOIN menu_items mi ON i.item_id = mi.id
                    ORDER BY mi.name
                ");
                $stmt->execute();
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($items);
            }
            break;

        case 'POST':
            // 更新庫存
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id']) || !isset($data['quantity'])) {
                throw new Exception('庫存ID和數量不能為空');
            }
            
            // 開始事務
            $db->beginTransaction();
            
            try {
                // 更新庫存
                $stmt = $db->prepare("
                    UPDATE inventory 
                    SET quantity = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$data['quantity'], $data['id']]);
                
                // 記錄庫存變動
                $stmt = $db->prepare("
                    INSERT INTO inventory_logs (inventory_id, quantity, type, note)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['id'],
                    $data['quantity'],
                    $data['type'] ?? 'update',
                    $data['note'] ?? '手動更新庫存'
                ]);
                
                // 記錄操作日誌
                logSystem('info', '更新庫存', [
                    'id' => $data['id'],
                    'quantity' => $data['quantity'],
                    'user_id' => $_SESSION['user_id']
                ]);
                
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
    logSystem('error', '庫存操作失敗', ['error' => $e->getMessage()]);
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 