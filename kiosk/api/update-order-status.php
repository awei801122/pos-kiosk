<?php
require_once 'db.php';
date_default_timezone_set('Asia/Taipei');
header('Content-Type: application/json');

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $orderNumber = $data['order_number'] ?? null;
    $rawStatus = $data['status'] ?? null;
    $status = isset($rawStatus) ? strtoupper($rawStatus) : null;


    // Log 請求
    $logMessage = sprintf(
        "[%s] 收到更新請求：order_number=%s, 原始狀態=%s, 最終狀態=%s\n",
        date('Y-m-d H:i:s'),
        $orderNumber ?? 'null',
        $rawStatus ?? 'null',
        $status ?? 'null'
    );
    file_put_contents(__DIR__ . '/../logs/order_status_debug.log', $logMessage, FILE_APPEND);

    // SQL 更新
    $stmt = $pdo->prepare("UPDATE orders SET status = :status, updated_at = NOW() WHERE order_number = :order_number");
    $success = $stmt->execute([
        'order_number' => $orderNumber,
        'status' => $status
    ]);
    file_put_contents(__DIR__ . '/../logs/order_status_debug.log', "[" . date("Y-m-d H:i:s") . "] ⚙️ 更新結果：rowCount=" . $stmt->rowCount() . "\n", FILE_APPEND);

    if (!$success) {
        file_put_contents(__DIR__ . '/../logs/order_status_debug.log', "[" . date("Y-m-d H:i:s") . "] ❗ echo 資料庫執行失敗\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => '資料庫執行失敗']);
        exit;
    }

    if ($stmt->rowCount() === 0) {
        $checkStmt = $pdo->prepare("SELECT status FROM orders WHERE order_number = ?");
        $checkStmt->execute([$orderNumber]);
        $existing = $checkStmt->fetch();

        if (!$existing) {
            file_put_contents(__DIR__ . '/../logs/order_status_debug.log', "[" . date("Y-m-d H:i:s") . "] ❗ echo 找不到該訂單\n", FILE_APPEND);
            echo json_encode(['success' => false, 'message' => '找不到該訂單']);
            exit;
        } else {
            file_put_contents(__DIR__ . '/../logs/order_status_debug.log', "[" . date("Y-m-d H:i:s") . "] ⚠️ 狀態未變更：order_number={$orderNumber}, status={$status}\n", FILE_APPEND);
        }
    }

    if ($status === 'COMPLETED') {
        $stmtInfo = $pdo->prepare("SELECT order_number, call_number FROM orders WHERE order_number = ?");
        $stmtInfo->execute([$orderNumber]);
        $orderData = $stmtInfo->fetch(PDO::FETCH_ASSOC);

        if ($orderData && !empty($orderData['call_number'])) {
            $queueFile = __DIR__ . '/../data/queue.json';
            $queue = [];

            if (file_exists($queueFile)) {
                $jsonQueue = file_get_contents($queueFile);
                $queue = json_decode($jsonQueue, true);
                if (!is_array($queue)) {
                    $queue = [];
                }
            }

            // 檢查是否已存在同一筆叫號
            $exists = false;
            foreach ($queue as $q) {
                if ($q['order_number'] === $orderNumber) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $queue[] = [
                    'order_number' => $orderData['order_number'],
                    'call_number' => $orderData['call_number'],
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                file_put_contents($queueFile, json_encode($queue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
                file_put_contents(__DIR__ . '/../logs/order_status_debug.log', "[" . date("Y-m-d H:i:s") . "] ✅ 寫入 queue.json：order_number={$orderNumber}\n", FILE_APPEND);
            }
        }
    }

    file_put_contents(__DIR__ . '/../logs/order_status_debug.log', "[" . date("Y-m-d H:i:s") . "] ✅ 成功回傳狀態更新 OK 給前端\n", FILE_APPEND);
    echo json_encode(['success' => true, 'message' => '狀態已更新']);

} catch (Exception $e) {
    file_put_contents(__DIR__ . '/../logs/order_status_debug.log', "[" . date("Y-m-d H:i:s") . "] ❌ 例外錯誤回傳：{$e->getMessage()}\n", FILE_APPEND);
    echo json_encode([
        'success' => false,
        'message' => '更新失敗',
        'error' => $e->getMessage()
    ]);
}