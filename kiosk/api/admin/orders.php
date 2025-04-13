<?php
/**
 * 訂單管理 API
 */

// 設置響應頭
header('Content-Type: application/json');

// 引入必要的文件
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/session.php';

// 檢查登入狀態和權限
checkLogin();
checkPermission('orders.manage');

// 獲取請求方法
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // 獲取訂單列表或詳情
            if (isset($_GET['id'])) {
                // 獲取訂單詳情
                $order = getOrderDetail($_GET['id']);
                echo json_encode(['success' => true, 'data' => $order]);
            } else {
                // 獲取訂單列表
                $page = $_GET['page'] ?? 1;
                $status = $_GET['status'] ?? 'all';
                $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
                $endDate = $_GET['end_date'] ?? date('Y-m-d');
                $orderNo = $_GET['order_no'] ?? '';
                
                // 檢查是否為匯出操作
                if (isset($_GET['action']) && $_GET['action'] === 'export') {
                    exportOrders($status, $startDate, $endDate, $orderNo);
                } else {
                    $result = getOrders($page, $status, $startDate, $endDate, $orderNo);
                    echo json_encode(['success' => true, 'data' => $result]);
                }
            }
            break;

        case 'POST':
            // 處理 POST 請求
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (isset($data['action'])) {
                switch ($data['action']) {
                    case 'update_status':
                        // 更新訂單狀態
                        if (!isset($data['order_id']) || !isset($data['status'])) {
                            throw new Exception('缺少必要參數');
                        }
                        
                        updateOrderStatus($data['order_id'], $data['status']);
                        echo json_encode(['success' => true, 'message' => '訂單狀態已更新']);
                        break;
                        
                    default:
                        throw new Exception('不支援的操作');
                }
            } else {
                throw new Exception('缺少操作類型');
            }
            break;

        case 'DELETE':
            // 刪除訂單
            $id = basename($_SERVER['REQUEST_URI']);
            
            // 開始事務
            $db->beginTransaction();
            
            try {
                // 刪除訂單項目
                $stmt = $db->prepare("DELETE FROM order_items WHERE order_id = ?");
                $stmt->execute([$id]);
                
                // 刪除訂單
                $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
                $stmt->execute([$id]);
                
                // 記錄操作日誌
                logSystem('info', '刪除訂單', [
                    'order_id' => $id,
                    'user_id' => $_SESSION['user_id']
                ]);
                
                $db->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        default:
            throw new Exception('不支援的請求方法');
    }
} catch (Exception $e) {
    logSystem('error', '訂單操作失敗', ['error' => $e->getMessage()]);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * 獲取訂單列表
 */
function getOrders($page, $status, $startDate, $endDate, $orderNo) {
    global $db;
    
    // 計算分頁
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // 構建查詢條件
    $where = [];
    $params = [];
    
    if ($status !== 'all') {
        $where[] = 'o.status = ?';
        $params[] = $status;
    }
    
    if ($startDate) {
        $where[] = 'DATE(o.created_at) >= ?';
        $params[] = $startDate;
    }
    
    if ($endDate) {
        $where[] = 'DATE(o.created_at) <= ?';
        $params[] = $endDate;
    }
    
    if ($orderNo) {
        $where[] = 'o.order_no LIKE ?';
        $params[] = "%$orderNo%";
    }
    
    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // 獲取總記錄數
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM orders o
        $whereClause
    ");
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 獲取訂單列表
    $stmt = $db->prepare("
        SELECT 
            o.id,
            o.order_no,
            o.created_at,
            o.total_amount,
            o.payment_method,
            o.status
        FROM orders o
        $whereClause
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'orders' => $orders,
        'total_pages' => ceil($total / $limit)
    ];
}

/**
 * 獲取訂單詳情
 */
function getOrderDetail($orderId) {
    global $db;
    
    // 獲取訂單基本信息
    $stmt = $db->prepare("
        SELECT 
            o.*,
            p.status as payment_status,
            p.paid_at as payment_time
        FROM orders o
        LEFT JOIN payments p ON o.id = p.order_id
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('訂單不存在');
    }
    
    // 獲取訂單商品
    $stmt = $db->prepare("
        SELECT 
            oi.*,
            m.name
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $order;
}

/**
 * 更新訂單狀態
 */
function updateOrderStatus($orderId, $status) {
    global $db;
    
    // 檢查訂單是否存在
    $stmt = $db->prepare("SELECT id FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    if (!$stmt->fetch()) {
        throw new Exception('訂單不存在');
    }
    
    // 更新訂單狀態
    $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $orderId]);
    
    // 記錄系統日誌
    logSystem('update_order_status', [
        'order_id' => $orderId,
        'new_status' => $status
    ]);
}

/**
 * 匯出訂單
 */
function exportOrders($status, $startDate, $endDate, $orderNo) {
    global $db;
    
    // 構建查詢條件
    $where = [];
    $params = [];
    
    if ($status !== 'all') {
        $where[] = 'o.status = ?';
        $params[] = $status;
    }
    
    if ($startDate) {
        $where[] = 'DATE(o.created_at) >= ?';
        $params[] = $startDate;
    }
    
    if ($endDate) {
        $where[] = 'DATE(o.created_at) <= ?';
        $params[] = $endDate;
    }
    
    if ($orderNo) {
        $where[] = 'o.order_no LIKE ?';
        $params[] = "%$orderNo%";
    }
    
    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // 獲取訂單數據
    $stmt = $db->prepare("
        SELECT 
            o.order_no,
            o.created_at,
            o.total_amount,
            o.payment_method,
            o.status,
            p.status as payment_status,
            p.paid_at as payment_time
        FROM orders o
        LEFT JOIN payments p ON o.id = p.order_id
        $whereClause
        ORDER BY o.created_at DESC
    ");
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 設置響應頭
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d') . '.csv"');
    
    // 創建輸出流
    $output = fopen('php://output', 'w');
    
    // 寫入 BOM
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // 寫入表頭
    fputcsv($output, [
        '訂單編號',
        '下單時間',
        '訂單金額',
        '付款方式',
        '訂單狀態',
        '付款狀態',
        '付款時間'
    ]);
    
    // 寫入數據
    foreach ($orders as $order) {
        fputcsv($output, [
            $order['order_no'],
            $order['created_at'],
            $order['total_amount'],
            $order['payment_method'],
            $order['status'],
            $order['payment_status'],
            $order['payment_time']
        ]);
    }
    
    fclose($output);
    exit;
} 