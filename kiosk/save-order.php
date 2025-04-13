<?php
/**
 * 訂單保存處理
 */
// 啟用錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 設置 CORS 標頭
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// 處理 OPTIONS 請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 確保只處理 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '只允許 POST 請求']);
    exit();
}

try {
    // 讀取 POST 數據
    $postData = json_decode(file_get_contents('php://input'), true);
    
    if (!$postData) {
        throw new Exception('無效的 JSON 數據');
    }

    // 檢查必要欄位
    $requiredFields = ['id', 'orderNumber', 'items', 'total', 'paymentMethod', 'status'];
    foreach ($requiredFields as $field) {
        if (!isset($postData[$field])) {
            throw new Exception("缺少必要欄位：{$field}");
        }
    }

    // 確保訂單目錄存在
    $ordersDir = __DIR__ . '/orders';
    if (!file_exists($ordersDir)) {
        if (!mkdir($ordersDir, 0755, true)) {
            throw new Exception('無法創建訂單目錄');
        }
    }

    // 讀取當前訂單
    $ordersFile = $ordersDir . '/current_orders.json';
    $orders = [];
    
    if (file_exists($ordersFile)) {
        $orders = json_decode(file_get_contents($ordersFile), true);
        if (!is_array($orders)) {
            $orders = [];
        }
    }

    // 標準化訂單狀態
    $validStatuses = ['PENDING', 'PREPARING', 'READY', 'COMPLETED', 'CANCELLED'];
    $status = strtoupper($postData['status']);
    if (!in_array($status, $validStatuses)) {
        $status = 'PENDING';
    }

    // 添加新訂單
    $newOrder = [
        'id' => $postData['id'],
        'orderNumber' => $postData['orderNumber'],
        'items' => $postData['items'],
        'total' => $postData['total'],
        'paymentMethod' => $postData['paymentMethod'],
        'status' => $status,
        'createdAt' => date('c'),
        'updatedAt' => date('c')
    ];

    // 添加到訂單列表
    array_unshift($orders, $newOrder);

    // 保存訂單
    if (!file_put_contents($ordersFile, json_encode($orders, JSON_PRETTY_PRINT))) {
        throw new Exception('無法保存訂單數據');
    }

    // 返回成功響應
    echo json_encode([
        'success' => true,
        'message' => '訂單已保存',
        'order_id' => $newOrder['id']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 