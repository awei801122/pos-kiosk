<?php
header('Content-Type: application/json');

// 取得日期參數，預設為今天
$date = $_GET['date'] ?? date("Y-m-d");

// 載入菜單（含成本）
$menuFile = "../menu.json";
if (!file_exists($menuFile)) {
  echo json_encode(["error" => "找不到 menu.json"]);
  exit;
}
$menu = json_decode(file_get_contents($menuFile), true);
$cost_map = [];
foreach ($menu as $item) {
  $cost_map[$item["name_zh"]] = $item["cost"] ?? 0;
}

// 掃描訂單檔案
$orderDir = "../orders";
if (!is_dir($orderDir)) {
  echo json_encode(["error" => "orders 資料夾不存在"]);
  exit;
}

$files = scandir($orderDir);
$orders = array_filter($files, function($f) use ($date) {
  return strpos($f, $date) === 0 && substr($f, -5) === ".json";
});

$total = 0;
$total_cost = 0;
$item_counts = [];

foreach ($orders as $file) {
  $path = "$orderDir/$file";
  if (!file_exists($path)) continue;

  $data = json_decode(file_get_contents($path), true);
  if (!isset($data["cart"]) || !is_array($data["cart"])) continue;

  foreach ($data["cart"] as $item) {
    $name = $item["name_zh"] ?? "";
    $price = $item["price"] ?? 0;
    $cost = $item["cost"] ?? ($cost_map[$name] ?? 0);

    $total += $price;
    $total_cost += $cost;
    if ($name !== "") {
      $item_counts[$name] = ($item_counts[$name] ?? 0) + 1;
    }
  }
}

// 回傳統計結果
echo json_encode([
  "date" => $date,
  "total_orders" => count($orders),
  "total_sales" => $total,
  "total_cost" => $total_cost,
  "profit" => $total - $total_cost,
  "items" => $item_counts
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
