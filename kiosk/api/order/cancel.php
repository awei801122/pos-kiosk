<?php
/**
 * 訂單取消 API
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/session.php';

// 檢查是否已登入
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

// 檢查請求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '不支援的請求方法']);
    exit;
}

// 獲取訂單ID
$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => '訂單ID不能為空']);
    exit;
}

try {
    // 開始事務
    $db->beginTransaction();
    
    // 檢查訂單是否存在且屬於當前用戶
    $stmt = $db->prepare("
        SELECT id, status, payment_status
        FROM orders o
        LEFT JOIN payments p ON o.id = p.order_id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('訂單不存在');
    }
    
    // 檢查訂單狀態
    if ($order['status'] === 'cancelled') {
        throw new Exception('訂單已取消');
    }
    
    if ($order['status'] === 'paid') {
        throw new Exception('已支付的訂單無法取消');
    }
    
    if ($order['payment_status'] === 'paid') {
        throw new Exception('已支付的訂單無法取消');
    }
    
    // 更新訂單狀態
    $stmt = $db->prepare("
        UPDATE orders 
        SET status = 'cancelled', 
            updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$orderId]);
    
    // 記錄訂單狀態變更
    $stmt = $db->prepare("
        INSERT INTO order_status_logs (
            order_id, 
            status, 
            created_by, 
            created_at
        ) VALUES (?, 'cancelled', ?, NOW())
    ");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    
    // 提交事務
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => '訂單已取消'
    ]);
    
} catch (Exception $e) {
    // 回滾事務
    $db->rollBack();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 