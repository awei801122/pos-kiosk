<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

// 創建新的 Excel 文件
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// 設置標題
$sheet->setCellValue('A1', '每日銷售報表');
$sheet->setCellValue('A2', '日期: ' . $date);

// 設置銷售概要
$totalRevenue = array_sum(array_column($orders, 'total'));
$orderCount = count($orders);
$avgOrderValue = $orderCount > 0 ? $totalRevenue / $orderCount : 0;

$sheet->setCellValue('A4', '銷售概要');
$sheet->setCellValue('A5', '總營業額');
$sheet->setCellValue('B5', $totalRevenue);
$sheet->setCellValue('A6', '訂單數量');
$sheet->setCellValue('B6', $orderCount);
$sheet->setCellValue('A7', '平均客單價');
$sheet->setCellValue('B7', round($avgOrderValue, 2));

// 設置商品銷售統計
$sheet->setCellValue('A9', '商品銷售統計');
$sheet->setCellValue('A10', '商品名稱');
$sheet->setCellValue('B10', '銷售數量');
$sheet->setCellValue('C10', '銷售金額');

$row = 11;
$itemStats = [];

foreach ($orders as $order) {
    foreach ($order['items'] as $item) {
        $itemName = $item['name'];
        if (!isset($itemStats[$itemName])) {
            $itemStats[$itemName] = [
                'quantity' => 0,
                'amount' => 0
            ];
        }
        $itemStats[$itemName]['quantity'] += $item['quantity'];
        $itemStats[$itemName]['amount'] += $item['quantity'] * $item['price'];
    }
}

foreach ($itemStats as $itemName => $stats) {
    $sheet->setCellValue('A' . $row, $itemName);
    $sheet->setCellValue('B' . $row, $stats['quantity']);
    $sheet->setCellValue('C' . $row, $stats['amount']);
    $row++;
}

// 設置訂單明細
$row += 2;
$sheet->setCellValue('A' . $row, '訂單明細');
$row++;
$sheet->setCellValue('A' . $row, '訂單編號');
$sheet->setCellValue('B' . $row, '時間');
$sheet->setCellValue('C' . $row, '品項');
$sheet->setCellValue('D' . $row, '數量');
$sheet->setCellValue('E' . $row, '金額');
$sheet->setCellValue('F' . $row, '付款方式');
$row++;

foreach ($orders as $order) {
    $sheet->setCellValue('A' . $row, $order['id']);
    $sheet->setCellValue('B' . $row, $order['time']);
    $sheet->setCellValue('C' . $row, implode(', ', array_map(function($item) {
        return $item['name'] . ' x' . $item['quantity'];
    }, $order['items'])));
    $sheet->setCellValue('D' . $row, array_sum(array_column($order['items'], 'quantity')));
    $sheet->setCellValue('E' . $row, $order['total']);
    $sheet->setCellValue('F' . $row, $order['payment_method'] ?? '現金');
    $row++;
}

// 自動調整欄寬
foreach (range('A', 'F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// 設置標題樣式
$sheet->getStyle('A1:F1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A4:F4')->getFont()->setBold(true);
$sheet->getStyle('A9:F9')->getFont()->setBold(true);
$sheet->getStyle('A10:F10')->getFont()->setBold(true);

// 設置檔案名稱
$fileName = '每日報表_' . $date . '.xlsx';

// 設置 header
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

// 創建 Excel 文件
$writer = new Xlsx($spreadsheet);
$writer->save('php://output'); 