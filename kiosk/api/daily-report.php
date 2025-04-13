<?php
header('Content-Type: application/json');

// 設置時區
date_default_timezone_set('Asia/Taipei');

// 獲取請求的日期
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// 讀取訂單數據
$ordersDir = __DIR__ . '/../orders/';
$orders = [];

// 讀取指定日期的訂單文件
$orderFiles = glob($ordersDir . $date . '*.json');
foreach ($orderFiles as $file) {
    if (file_exists($file)) {
        $orderData = json_decode(file_get_contents($file), true);
        if ($orderData) {
            $orders[] = $orderData;
        }
    }
}

// 初始化統計數據
$totalRevenue = 0;
$orderCount = count($orders);
$totalItems = 0;
$hourlyData = array_fill(0, 24, ['hour' => 0, 'revenue' => 0]);
$itemData = [];

// 處理訂單數據
foreach ($orders as $order) {
    // 計算總收入
    $totalRevenue += $order['total'];
    
    // 計算商品數量
    foreach ($order['items'] as $item) {
        $totalItems += $item['quantity'];
        
        // 更新商品銷售統計
        $itemName = $item['name'];
        if (!isset($itemData[$itemName])) {
            $itemData[$itemName] = ['name' => $itemName, 'quantity' => 0];
        }
        $itemData[$itemName]['quantity'] += $item['quantity'];
    }
    
    // 更新每小時銷售數據
    $hour = (int)date('G', strtotime($order['time']));
    $hourlyData[$hour]['hour'] = $hour;
    $hourlyData[$hour]['revenue'] += $order['total'];
}

// 計算平均客單價
$avgOrderValue = $orderCount > 0 ? $totalRevenue / $orderCount : 0;

// 格式化訂單數據用於表格顯示
$formattedOrders = array_map(function($order) {
    return [
        'id' => $order['id'],
        'time' => $order['time'],
        'items' => implode(', ', array_map(function($item) {
            return $item['name'] . ' x' . $item['quantity'];
        }, $order['items'])),
        'quantity' => array_sum(array_column($order['items'], 'quantity')),
        'amount' => $order['total'],
        'paymentMethod' => $order['payment_method'] ?? '現金'
    ];
}, $orders);

// 返回 JSON 數據
echo json_encode([
    'totalRevenue' => $totalRevenue,
    'orderCount' => $orderCount,
    'avgOrderValue' => round($avgOrderValue, 2),
    'totalItems' => $totalItems,
    'hourlyData' => array_values($hourlyData),
    'itemData' => array_values($itemData),
    'orders' => $formattedOrders
]); 