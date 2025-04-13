<?php
/**
 * JKoPay 支付處理 API
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// JKoPay 配置
define('JKOPAY_MERCHANT_ID', 'YOUR_MERCHANT_ID');
define('JKOPAY_API_KEY', 'YOUR_API_KEY');
define('JKOPAY_API_URL', 'https://api.jkopay.com/v1');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['action'])) {
                throw new Exception('缺少操作類型');
            }
            
            switch ($data['action']) {
                case 'create_payment':
                    // 創建支付訂單
                    if (!isset($data['order_id']) || !isset($data['amount'])) {
                        throw new Exception('缺少必要參數');
                    }
                    
                    $result = createPayment($data['order_id'], $data['amount']);
                    echo json_encode(['success' => true, 'data' => $result]);
                    break;
                    
                case 'check_payment':
                    // 查詢支付狀態
                    if (!isset($data['payment_id'])) {
                        throw new Exception('缺少支付ID');
                    }
                    
                    $result = checkPayment($data['payment_id']);
                    echo json_encode(['success' => true, 'data' => $result]);
                    break;
                    
                default:
                    throw new Exception('不支援的操作');
            }
            break;
            
        default:
            throw new Exception('不支援的請求方法');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * 創建支付訂單
 */
function createPayment($orderId, $amount) {
    global $db;
    
    // 獲取訂單信息
    $stmt = $db->prepare("
        SELECT order_no, total_amount 
        FROM orders 
        WHERE id = ? AND status = 'pending'
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('訂單不存在或狀態不正確');
    }
    
    // 生成支付ID
    $paymentId = uniqid('jkopay_');
    
    // 調用 JKoPay API 創建支付訂單
    $response = callJKoPayAPI('payment/create', [
        'merchant_id' => JKOPAY_MERCHANT_ID,
        'payment_id' => $paymentId,
        'order_no' => $order['order_no'],
        'amount' => $amount,
        'currency' => 'TWD',
        'callback_url' => 'https://your-domain.com/api/payment/callback.php',
        'return_url' => 'https://your-domain.com/order/complete.php'
    ]);
    
    // 保存支付記錄
    $stmt = $db->prepare("
        INSERT INTO payments (
            order_id, 
            payment_id, 
            amount, 
            status, 
            created_at
        ) VALUES (?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([
        $orderId,
        $paymentId,
        $amount
    ]);
    
    return [
        'payment_id' => $paymentId,
        'payment_url' => $response['payment_url']
    ];
}

/**
 * 查詢支付狀態
 */
function checkPayment($paymentId) {
    global $db;
    
    // 調用 JKoPay API 查詢支付狀態
    $response = callJKoPayAPI('payment/status', [
        'merchant_id' => JKOPAY_MERCHANT_ID,
        'payment_id' => $paymentId
    ]);
    
    // 更新支付記錄
    $stmt = $db->prepare("
        UPDATE payments 
        SET status = ?, 
            paid_at = CASE WHEN ? = 'paid' THEN NOW() ELSE paid_at END,
            updated_at = NOW()
        WHERE payment_id = ?
    ");
    $stmt->execute([
        $response['status'],
        $response['status'],
        $paymentId
    ]);
    
    // 如果支付成功，更新訂單狀態
    if ($response['status'] === 'paid') {
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
        $stmt->execute([$paymentId]);
    }
    
    return $response;
}

/**
 * 調用 JKoPay API
 */
function callJKoPayAPI($endpoint, $data) {
    $ch = curl_init(JKOPAY_API_URL . '/' . $endpoint);
    
    // 添加簽名
    $data['sign'] = generateSignature($data);
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . JKOPAY_API_KEY
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('JKoPay API 請求失敗');
    }
    
    $result = json_decode($response, true);
    
    if (!$result['success']) {
        throw new Exception($result['message']);
    }
    
    return $result['data'];
}

/**
 * 生成簽名
 */
function generateSignature($data) {
    ksort($data);
    $string = http_build_query($data) . JKOPAY_API_KEY;
    return hash('sha256', $string);
} 