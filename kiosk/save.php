<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$cart = $data['cart'] ?? [];

if (empty($cart)) {
    echo json_encode(["status" => "error", "message" => "沒有餐點資料"]);
    exit;
}

// 產生叫號號碼（例如：053625）
$number = date("His");

// 儲存訂單
$order = [
    "number" => $number,
    "cart" => $cart,
    "time" => date("Y-m-d H:i:s")
];

if (!is_dir("orders")) {
    mkdir("orders", 0777, true);
}

$filename = "orders/" . date("Y-m-d_His") . ".json";
file_put_contents($filename, json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// 回傳成功
echo json_encode(["status" => "ok", "number" => $number]);
