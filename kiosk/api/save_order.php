<?php
// 顯示錯誤訊息（開發階段用）
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Taipei');

// 設定回傳 JSON 格式
header('Content-Type: application/json');

// 建立資料庫連線
$dsn = "mysql:host=pos-mvp-db;dbname=pos_db;charset=utf8mb4";
$user = "root";
$password = "root";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '資料庫連線錯誤：' . $e->getMessage()]);
    exit;
}

// 取得前端傳來的 JSON
$input = file_get_contents("php://input");
$data = json_decode($input, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '接收資料格式錯誤']);
    exit;
}
file_put_contents(__DIR__ . '/../logs/order_debug.log', "[" . date('Y-m-d H:i:s') . "]\n" . print_r($data, true) . "\n", FILE_APPEND);

// 資料提取
$order_number = $data['order_number'];
$order_time = date('Y-m-d H:i:s', strtotime($data['order_time']));
$items = $data['items'];
$total_amount = $data['total_amount'];
$status = $data['status'];
$payment_method = $data['payment_method'];
$created_at = date('Y-m-d H:i:s', strtotime($data['created_at']));
$updated_at = date('Y-m-d H:i:s', strtotime($data['updated_at']));

// 自動產生當日叫號號碼（從 1 開始）
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT MAX(call_number) AS max_no FROM orders WHERE DATE(order_time) = ?");
$stmt->execute([$today]);
$row = $stmt->fetch();
$last_call = $row['max_no'] ?? 0;
$call_number = $last_call + 1;

try {
    $pdo->beginTransaction();

    // 插入 orders 表
    $stmt = $pdo->prepare("INSERT INTO orders (order_number, order_time, total_amount, status, payment_method, created_at, updated_at, call_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$order_number, $order_time, $total_amount, $status, $payment_method, $created_at, $updated_at, $call_number]);

    $order_id = $pdo->lastInsertId();

    // 預設填入 note 欄位（避免沒有傳遞 note 時出錯）
    foreach ($items as $index => $item) {
        if (!isset($items[$index]['note'])) {
            $items[$index]['note'] = '';
        }
    }

    // 驗證 menu_id 和其他必要欄位
    foreach ($items as $index => $item) {
        if (!isset($item['menu_id'])) {
            throw new Exception("第 {$index} 個商品缺少 menu_id");
        }
        if (!isset($item['name'])) {
            throw new Exception("第 {$index} 個商品缺少 name");
        }
        if (!isset($item['price'])) {
            throw new Exception("第 {$index} 個商品缺少 price");
        }
        if (!isset($item['quantity'])) {
            throw new Exception("第 {$index} 個商品缺少 quantity");
        }
        if (!isset($item['subtotal'])) {
            throw new Exception("第 {$index} 個商品缺少 subtotal");
        }
    }

    // 取得每個商品的分類 category
    $stmt_category = $pdo->prepare("SELECT category FROM menu WHERE id = ?");
    foreach ($items as &$item) {
        $stmt_category->execute([$item['menu_id']]);
        $menuRow = $stmt_category->fetch(PDO::FETCH_ASSOC);
        $item['category'] = $menuRow['category'] ?? '未分類';
    }
    unset($item); // 解除引用

    // 插入 order_items 表
    $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, menu_id, item_name, price, quantity, note, total_price, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($items as $item) {
        $stmt_item->execute([
            $order_id,
            $item['menu_id'],  // 正確：使用 menu_id
            $item['name'],
            $item['price'],
            $item['quantity'],
            $item['note'] ?? '',
            $item['subtotal'],
            $item['category']
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'order' => [
            'id' => $order_id,
            'orderNumber' => $order_number,
            'items' => $items,
            'total' => $total_amount,
            'paymentMethod' => $payment_method,
            'callNumber' => $call_number
        ]
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    file_put_contents(__DIR__ . '/../logs/order_error.log', "[" . date('Y-m-d H:i:s') . "] " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '訂單儲存失敗：' . $e->getMessage()]);
}