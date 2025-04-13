<?php
/**
 * 報表API
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/session.php';

// 檢查登入狀態和權限
checkLogin();
checkPermission('reports.view');

try {
    // 獲取參數
    $type = $_GET['type'] ?? 'sales';
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    $action = $_GET['action'] ?? 'view';

    // 驗證日期格式
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        throw new Exception('日期格式不正確');
    }

    // 根據報表類型處理數據
    switch ($type) {
        case 'sales':
            // 銷售報表
            $data = getSalesReport($startDate, $endDate);
            break;

        case 'inventory':
            // 庫存報表
            $data = getInventoryReport();
            break;

        case 'logs':
            // 操作日誌
            $data = getLogsReport($startDate, $endDate);
            break;

        default:
            throw new Exception('不支援的報表類型');
    }

    // 處理匯出操作
    if ($action === 'export') {
        exportReport($type, $data);
    } else {
        echo json_encode(['success' => true, 'data' => $data]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * 獲取銷售報表數據
 */
function getSalesReport($startDate, $endDate) {
    global $db;

    // 獲取銷售統計
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT o.id) as total_orders,
            SUM(o.total_amount) as total_sales,
            SUM(oi.quantity) as total_items,
            AVG(o.total_amount) as avg_order_amount
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    // 獲取熱銷商品
    $stmt = $db->prepare("
        SELECT 
            m.name,
            SUM(oi.quantity) as quantity,
            SUM(oi.price * oi.quantity) as total_amount
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at BETWEEN ? AND ?
        GROUP BY m.id
        ORDER BY quantity DESC
        LIMIT 10
    ");
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 獲取每日銷售數據
    $stmt = $db->prepare("
        SELECT 
            DATE(created_at) as date,
            SUM(total_amount) as amount
        FROM orders
        WHERE created_at BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $dailySales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'summary' => $summary,
        'top_products' => $topProducts,
        'daily_sales' => $dailySales
    ];
}

/**
 * 獲取庫存報表數據
 */
function getInventoryReport() {
    global $db;

    // 獲取庫存統計
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total_items,
            COUNT(CASE WHEN quantity <= min_quantity THEN 1 END) as low_stock_count,
            SUM(quantity * cost) as total_stock_value
        FROM inventory
    ");
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    // 獲取庫存列表
    $stmt = $db->query("
        SELECT 
            i.*,
            m.name
        FROM inventory i
        JOIN menu_items m ON i.menu_item_id = m.id
        ORDER BY 
            CASE WHEN i.quantity <= i.min_quantity THEN 0 ELSE 1 END,
            i.quantity ASC
    ");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'summary' => $summary,
        'items' => $items
    ];
}

/**
 * 獲取操作日誌數據
 */
function getLogsReport($startDate, $endDate) {
    global $db;

    $stmt = $db->prepare("
        SELECT 
            l.*,
            u.username
        FROM system_logs l
        LEFT JOIN users u ON l.user_id = u.id
        WHERE l.created_at BETWEEN ? AND ?
        ORDER BY l.created_at DESC
    ");
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return ['logs' => $logs];
}

/**
 * 匯出報表
 */
function exportReport($type, $data) {
    // 設置響應頭
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $type . '_report_' . date('Y-m-d') . '.csv"');

    // 創建輸出流
    $output = fopen('php://output', 'w');
    
    // 寫入 BOM
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    switch ($type) {
        case 'sales':
            // 寫入銷售報表
            fputcsv($output, ['總訂單數', '總銷售額', '總銷售數量', '平均訂單金額']);
            fputcsv($output, [
                $data['summary']['total_orders'],
                $data['summary']['total_sales'],
                $data['summary']['total_items'],
                $data['summary']['avg_order_amount']
            ]);

            fputcsv($output, []);
            fputcsv($output, ['熱銷商品', '銷售數量', '銷售金額']);
            foreach ($data['top_products'] as $product) {
                fputcsv($output, [
                    $product['name'],
                    $product['quantity'],
                    $product['total_amount']
                ]);
            }

            fputcsv($output, []);
            fputcsv($output, ['日期', '銷售金額']);
            foreach ($data['daily_sales'] as $sale) {
                fputcsv($output, [$sale['date'], $sale['amount']]);
            }
            break;

        case 'inventory':
            // 寫入庫存報表
            fputcsv($output, ['商品名稱', '當前庫存', '最低庫存', '單位', '狀態', '最後更新']);
            foreach ($data['items'] as $item) {
                fputcsv($output, [
                    $item['name'],
                    $item['quantity'],
                    $item['min_quantity'],
                    $item['unit'],
                    $item['quantity'] <= $item['min_quantity'] ? '低庫存' : '正常',
                    $item['updated_at']
                ]);
            }
            break;

        case 'logs':
            // 寫入操作日誌
            fputcsv($output, ['時間', '用戶', '操作', 'IP地址', '詳細信息']);
            foreach ($data['logs'] as $log) {
                fputcsv($output, [
                    $log['created_at'],
                    $log['username'],
                    $log['action'],
                    $log['ip_address'],
                    json_encode($log['details'], JSON_UNESCAPED_UNICODE)
                ]);
            }
            break;
    }

    fclose($output);
    exit;
} 