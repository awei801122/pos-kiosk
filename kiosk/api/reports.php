<?php
header('Content-Type: application/json');
require_once '../config.php';

// 讀取報表資料
function getReports() {
    $reportsFile = __DIR__ . '/reports.json';
    if (!file_exists($reportsFile)) {
        return [
            'daily_reports' => [],
            'monthly_reports' => [],
            'yearly_reports' => [],
            'report_settings' => [
                'auto_generate' => true,
                'generate_time' => '23:59',
                'retention_days' => 365
            ]
        ];
    }
    return json_decode(file_get_contents($reportsFile), true);
}

// 儲存報表資料
function saveReports($data) {
    $reportsFile = __DIR__ . '/reports.json';
    file_put_contents($reportsFile, json_encode($data, JSON_PRETTY_PRINT));
}

// 生成每日報表
function generateDailyReport($date = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    // 讀取訂單資料
    $ordersFile = __DIR__ . '/orders.json';
    if (!file_exists($ordersFile)) {
        return null;
    }
    
    $orders = json_decode(file_get_contents($ordersFile), true);
    $dailyOrders = array_filter($orders, function($order) use ($date) {
        return date('Y-m-d', strtotime($order['created_at'])) === $date;
    });
    
    // 計算銷售統計
    $totalSales = 0;
    $totalItems = 0;
    $categorySales = [];
    $paymentMethods = [];
    
    foreach ($dailyOrders as $order) {
        $totalSales += $order['total'];
        $totalItems += count($order['items']);
        
        // 分類銷售統計
        foreach ($order['items'] as $item) {
            $category = $item['category'];
            if (!isset($categorySales[$category])) {
                $categorySales[$category] = 0;
            }
            $categorySales[$category] += $item['price'] * $item['quantity'];
        }
        
        // 支付方式統計
        $paymentMethod = $order['payment_method'];
        if (!isset($paymentMethods[$paymentMethod])) {
            $paymentMethods[$paymentMethod] = 0;
        }
        $paymentMethods[$paymentMethod] += $order['total'];
    }
    
    // 生成報表
    $report = [
        'date' => $date,
        'total_sales' => $totalSales,
        'total_items' => $totalItems,
        'category_sales' => $categorySales,
        'payment_methods' => $paymentMethods,
        'order_count' => count($dailyOrders),
        'average_order_value' => $totalItems > 0 ? $totalSales / $totalItems : 0,
        'generated_at' => date('Y-m-d H:i:s')
    ];
    
    return $report;
}

// 處理 API 請求
$path = $_SERVER['PATH_INFO'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($path) {
    case '/daily':
        if ($method === 'GET') {
            // 取得指定日期的報表
            $date = $_GET['date'] ?? date('Y-m-d');
            $reports = getReports();
            
            // 檢查是否已有該日期的報表
            $existingReport = null;
            foreach ($reports['daily_reports'] as $report) {
                if ($report['date'] === $date) {
                    $existingReport = $report;
                    break;
                }
            }
            
            if ($existingReport) {
                echo json_encode($existingReport);
            } else {
                // 生成新的報表
                $newReport = generateDailyReport($date);
                if ($newReport) {
                    $reports['daily_reports'][] = $newReport;
                    saveReports($reports);
                    echo json_encode($newReport);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => '找不到該日期的訂單資料']);
                }
            }
        }
        break;
        
    case '/monthly':
        if ($method === 'GET') {
            // 取得指定月份的報表
            $year = $_GET['year'] ?? date('Y');
            $month = $_GET['month'] ?? date('m');
            
            $reports = getReports();
            $monthlyReport = null;
            
            foreach ($reports['monthly_reports'] as $report) {
                if ($report['year'] === $year && $report['month'] === $month) {
                    $monthlyReport = $report;
                    break;
                }
            }
            
            if ($monthlyReport) {
                echo json_encode($monthlyReport);
            } else {
                // 生成新的月報表
                // TODO: 實現月報表生成邏輯
                http_response_code(501);
                echo json_encode(['message' => '月報表功能尚未實現']);
            }
        }
        break;
        
    case '/settings':
        if ($method === 'GET') {
            $reports = getReports();
            echo json_encode($reports['report_settings']);
        } elseif ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $reports = getReports();
            $reports['report_settings'] = array_merge($reports['report_settings'], $input);
            saveReports($reports);
            echo json_encode(['message' => '設定已更新']);
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['message' => '未找到對應的路由']);
        break;
} 