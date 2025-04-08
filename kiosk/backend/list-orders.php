<?php
header('Content-Type: application/json');

// 初始化回應陣列
$response = [
    'success' => true,
    'orders' => [],
    'error' => null
];

try {
    // 讀取所有訂單檔案
    $orders = [];
    $ordersDir = __DIR__ . '/../orders/';
    $files = glob($ordersDir . '*.json');

    if ($files === false) {
        throw new Exception('無法讀取訂單目錄');
    }

    // 讀取完成的訂單
    $completedOrders = [];
    $doneFile = __DIR__ . '/../done.json';
    if (file_exists($doneFile)) {
        $doneContent = @file_get_contents($doneFile);
        if ($doneContent === false) {
            throw new Exception('無法讀取完成訂單檔案');
        }
        $completedOrders = json_decode($doneContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('完成訂單檔案格式錯誤: ' . json_last_error_msg());
        }
        $completedOrders = $completedOrders ?? [];
    }

    foreach ($files as $file) {
        try {
            $content = @file_get_contents($file);
            if ($content === false) {
                continue; // 跳過無法讀取的檔案
            }
            
            $orderData = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                continue; // 跳過格式錯誤的檔案
            }
            
            if ($orderData) {
                // 檢查訂單是否完成
                $orderData['completed'] = in_array($orderData['number'], $completedOrders);
                $orders[] = $orderData;
            }
        } catch (Exception $e) {
            // 記錄個別訂單的錯誤但繼續處理其他訂單
            error_log("處理訂單檔案 {$file} 時發生錯誤: " . $e->getMessage());
            continue;
        }
    }

    // 依時間排序，最新的在前面
    usort($orders, function($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });

    $response['orders'] = $orders;

} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
    error_log("讀取訂單時發生錯誤: " . $e->getMessage());
}

echo json_encode($response);
?>
