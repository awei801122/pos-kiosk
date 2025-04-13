<?php
/**
 * 更新訂單狀態 API
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
$newStatus = $_POST['status'] ?? null;

// 驗證輸入
if (!$orderId || !$newStatus) {
    echo json_encode([
        'success' => false,
        'message' => '缺少必要參數'
    ]);
    exit;
}

// 允許的狀態列表
$allowedStatuses = ['pending', 'paid', 'preparing', 'serving', 'ready', 'completed', 'cancelled'];
if (!in_array($newStatus, $allowedStatuses)) {
    echo json_encode([
        'success' => false,
        'message' => '無效的訂單狀態'
    ]);
    exit;
}

try {
    // 更新訂單狀態
    $db = getDB();
    $stmt = $db->prepare("
        UPDATE orders 
        SET status = :status, 
            updated_at = NOW() 
        WHERE id = :order_id
    ");
    
    $stmt->execute([
        ':status' => $newStatus,
        ':order_id' => $orderId
    ]);
    
    // 記錄狀態變更
    $stmt = $db->prepare("
        INSERT INTO order_status_history 
        (order_id, status, created_at) 
        VALUES (:order_id, :status, NOW())
    ");
    
    $stmt->execute([
        ':order_id' => $orderId,
        ':status' => $newStatus
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => '訂單狀態更新成功'
    ]);
    
} catch (Exception $e) {
    error_log("更新訂單狀態失敗: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '更新訂單狀態失敗'
    ]);
} 