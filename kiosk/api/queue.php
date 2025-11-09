

<?php
require_once 'db.php';
date_default_timezone_set('Asia/Taipei');
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    file_put_contents(__DIR__ . '/../logs/order_status_debug.log', "[" . date("Y-m-d H:i:s") . "] 📥 收到資料: " . json_encode($data) . "\n", FILE_APPEND);
    $orderNumber = $data['order_number'] ?? null;

    if (!$orderNumber) {
        throw new Exception("缺少 order_number");
    }

    // 查詢訂單資料
    $stmt = $pdo->prepare("SELECT order_number, call_number FROM orders WHERE order_number = ?");
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    file_put_contents(__DIR__ . '/../logs/order_status_debug.log', "[" . date("Y-m-d H:i:s") . "] 🔍 查詢結果: " . json_encode($order) . "\n", FILE_APPEND);

    if (!$order || !$order['call_number']) {
        throw new Exception("找不到叫號資料");
    }

    // 載入 queue.json
    $queueFile = __DIR__ . '/../data/queue.json';
    $queue = file_exists($queueFile) ? json_decode(file_get_contents($queueFile), true) : [];
    if (!is_array($queue)) {
        $queue = [];
    }

    // 避免重複加入
    $exists = array_filter($queue, fn($q) => $q['order_number'] === $orderNumber);
    if (empty($exists)) {
        $queue[] = [
            'order_number' => $order['order_number'],
            'call_number' => $order['call_number'],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        file_put_contents($queueFile, json_encode($queue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        file_put_contents(__DIR__ . '/../logs/order_status_debug.log', "[" . date("Y-m-d H:i:s") . "] ✅ queue.php 成功寫入 queue.json for {$orderNumber}\n", FILE_APPEND);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/../logs/order_status_debug.log', "[" . date("Y-m-d H:i:s") . "] ❌ queue.php 錯誤: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}