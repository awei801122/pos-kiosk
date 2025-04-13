<?php
/**
 * JKoPay 支付回調處理
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// 獲取回調數據
$data = json_decode(file_get_contents('php://input'), true);

// 驗證簽名
if (!verifySignature($data)) {
    http_response_code(400);
    exit('簽名驗證失敗');
}

try {
    // 開始事務
    $db->beginTransaction();
    
    // 更新支付記錄
    $stmt = $db->prepare("
        UPDATE payments 
        SET status = ?, 
            paid_at = CASE WHEN ? = 'paid' THEN NOW() ELSE paid_at END,
            updated_at = NOW()
        WHERE payment_id = ?
    ");
    $stmt->execute([
        $data['status'],
        $data['status'],
        $data['payment_id']
    ]);
    
    // 如果支付成功，更新訂單狀態
    if ($data['status'] === 'paid') {
        $stmt = $db->prepare("
            UPDATE orders 
            SET status = 'paid', 
                updated_at = NOW()
            WHERE id = (
                SELECT order_id 
                FROM payments 
                WHERE payment_id = ?
            )
        ");
        $stmt->execute([$data['payment_id']]);
    }
    
    // 提交事務
    $db->commit();
    
    // 返回成功響應
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // 回滾事務
    $db->rollBack();
    
    // 記錄錯誤日誌
    logSystem('error', '支付回調處理失敗', [
        'error' => $e->getMessage(),
        'data' => $data
    ]);
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * 驗證簽名
 */
function verifySignature($data) {
    $sign = $data['sign'];
    unset($data['sign']);
    
    ksort($data);
    $string = http_build_query($data) . JKOPAY_API_KEY;
    $expectedSign = hash('sha256', $string);
    
    return $sign === $expectedSign;
} 