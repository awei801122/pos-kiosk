<?php
/**
 * 叫號功能 API
 */
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/session.php';

// 檢查登入狀態和權限
checkLogin();
checkPermission('orders.manage');

// 設置回應頭
header('Content-Type: application/json');

// 獲取 POST 數據
$orderId = $_POST['order_id'] ?? null;

// 驗證輸入
if (!$orderId) {
    echo json_encode([
        'success' => false,
        'message' => '缺少訂單 ID'
    ]);
    exit;
}

try {
    // 獲取訂單信息
    $db = getDB();
    $stmt = $db->prepare("
        SELECT order_no, status 
        FROM orders 
        WHERE id = :order_id
    ");
    
    $stmt->execute([':order_id' => $orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode([
            'success' => false,
            'message' => '訂單不存在'
        ]);
        exit;
    }
    
    // 檢查訂單狀態
    if ($order['status'] !== 'ready') {
        echo json_encode([
            'success' => false,
            'message' => '只有待取餐的訂單才能呼叫'
        ]);
        exit;
    }
    
    // 記錄叫號
    $stmt = $db->prepare("
        INSERT INTO order_calls 
        (order_id, called_at) 
        VALUES (:order_id, NOW())
    ");
    
    $stmt->execute([':order_id' => $orderId]);
    
    echo json_encode([
        'success' => true,
        'message' => '呼叫成功',
        'order_no' => $order['order_no']
    ]);
    
} catch (Exception $e) {
    error_log("呼叫訂單失敗: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '呼叫失敗'
    ]);
} 