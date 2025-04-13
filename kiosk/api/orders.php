<?php
/**
 * 訂單處理API
 * 
 * 處理訂單的創建、更新和查詢
 */

// 設置響應頭
header('Content-Type: application/json');

// 引入必要的文件
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// 獲取請求方法
$method = $_SERVER['REQUEST_METHOD'];

// 處理不同的請求方法
switch ($method) {
    case 'POST':
        // 創建新訂單
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['items']) || !isset($data['total'])) {
            http_response_code(400);
            echo json_encode(['error' => '無效的訂單數據']);
            exit;
        }

        try {
            // 開始事務
            $db->beginTransaction();

            // 創建訂單
            $stmt = $db->prepare("
                INSERT INTO orders (total_amount, status, created_at)
                VALUES (?, 'pending', NOW())
            ");
            $stmt->execute([$data['total']]);
            $orderId = $db->lastInsertId();

            // 添加訂單項目
            $stmt = $db->prepare("
                INSERT INTO order_items (order_id, item_id, item_name, quantity, price, options)
                VALUES (?, ?, ?, 1, ?, ?)
            ");

            foreach ($data['items'] as $item) {
                $options = json_encode($item['options']);
                $stmt->execute([
                    $orderId,
                    $item['id'],
                    $item['name'],
                    $item['totalPrice'],
                    $options
                ]);

                // 更新庫存
                $updateStmt = $db->prepare("
                    UPDATE menu_items 
                    SET stock = stock - 1 
                    WHERE id = ? AND stock > 0
                ");
                $updateStmt->execute([$item['id']]);
            }

            // 提交事務
            $db->commit();

            // 返回成功響應
            echo json_encode([
                'success' => true,
                'order_id' => $orderId,
                'message' => '訂單創建成功'
            ]);

        } catch (Exception $e) {
            // 回滾事務
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['error' => '訂單創建失敗: ' . $e->getMessage()]);
        }
        break;

    case 'GET':
        // 獲取訂單列表
        $query = "SELECT * FROM orders ORDER BY created_at DESC LIMIT 10";
        $stmt = $db->query($query);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($orders);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => '不支援的請求方法']);
        break;
} 