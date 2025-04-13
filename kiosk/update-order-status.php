<?php
// 設定錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 允許跨域請求
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理 OPTIONS 請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 確保是 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '只允許 POST 請求']);
    exit();
}

// 讀取 POST 資料
$input = json_decode(file_get_contents('php://input'), true);

// 驗證必要參數
if (!isset($input['orderId']) || !isset($input['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '缺少必要參數']);
    exit();
}

try {
    // 確保訂單目錄存在
    $ordersDir = __DIR__ . '/orders';
    if (!file_exists($ordersDir)) {
        mkdir($ordersDir, 0755, true);
    }

    // 讀取當前訂單列表
    $ordersFile = $ordersDir . '/current_orders.json';
    if (!file_exists($ordersFile)) {
        throw new Exception('訂單檔案不存在');
    }

    $orders = json_decode(file_get_contents($ordersFile), true);
    if (!is_array($orders)) {
        throw new Exception('訂單資料格式錯誤');
    }

    // 標準化狀態名稱
    $validStatuses = ['PENDING', 'PREPARING', 'READY', 'COMPLETED', 'CANCELLED'];
    if (!in_array($input['status'], $validStatuses)) {
        throw new Exception('無效的訂單狀態');
    }

    // 尋找並更新訂單
    $orderFound = false;
    foreach ($orders as $key => $order) {
        if ($order['orderNumber'] === $input['orderId'] || $order['id'] === $input['orderId']) {
            $orders[$key]['status'] = $input['status'];
            $orders[$key]['updatedAt'] = date('c');
            
            // 如果訂單完成，移至歸檔
            if ($input['status'] === 'COMPLETED') {
                $orders[$key]['completedAt'] = date('c');
                
                // 確保歸檔目錄存在
                if (!file_exists($ordersDir)) {
                    mkdir($ordersDir, 0755, true);
                }
                
                // 讀取歸檔訂單
                $archivedOrdersFile = $ordersDir . '/archived_orders.json';
                $archivedOrders = file_exists($archivedOrdersFile) 
                    ? json_decode(file_get_contents($archivedOrdersFile), true) 
                    : [];
                
                if (!is_array($archivedOrders)) {
                    $archivedOrders = [];
                }
                
                // 將訂單加入歸檔
                $orders[$key]['archivedAt'] = date('c');
                array_unshift($archivedOrders, $orders[$key]);
                
                // 儲存歸檔訂單
                if (!file_put_contents($archivedOrdersFile, json_encode($archivedOrders, JSON_PRETTY_PRINT))) {
                    throw new Exception('儲存歸檔訂單失敗');
                }
                
                // 從當前訂單中移除
                unset($orders[$key]);
            }
            
            $orderFound = true;
            break;
        }
    }

    if (!$orderFound) {
        throw new Exception('找不到指定訂單');
    }

    // 重新索引陣列並儲存更新後的訂單列表
    $orders = array_values($orders);
    if (!file_put_contents($ordersFile, json_encode($orders, JSON_PRETTY_PRINT))) {
        throw new Exception('儲存訂單更新失敗');
    }

    // 回傳成功訊息
    echo json_encode([
        'success' => true, 
        'message' => '訂單狀態已更新',
        'status' => $input['status']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 