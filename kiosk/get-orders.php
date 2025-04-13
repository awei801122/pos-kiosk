<?php
// 啟用錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 設置 CORS 標頭
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// 處理 OPTIONS 請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 確保只處理 GET 請求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => '只允許 GET 請求']);
    exit();
}

try {
    // 讀取當前訂單
    $ordersFile = __DIR__ . '/orders/current_orders.json';
    
    if (!file_exists($ordersFile)) {
        echo json_encode([]);
        exit();
    }

    $orders = json_decode(file_get_contents($ordersFile), true);
    
    if (!is_array($orders)) {
        echo json_encode([]);
        exit();
    }

    // 返回訂單列表
    echo json_encode($orders);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 