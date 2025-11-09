<?php
/**
 * 訂單查詢 API
 * - 支援以訂單或品項兩種模式回傳資料
 * - 透過輸出緩衝與錯誤抑制，避免 PHP 警告污染 JSON 回應
 */

// 停用 Deprecated / Warning 類型輸出，避免與 JSON 混在一起造成解析錯誤
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);
ini_set('display_errors', '0');

// 啟用輸出緩衝，如有殘留訊息可在回傳前清除
ob_start();

header('Content-Type: application/json; charset=utf-8');

/**
 * 以統一方式輸出 JSON，確保回應格式乾淨
 */
function respondJson($data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    if (ob_get_length() > 0) {
        ob_clean();
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db.php';
$pdo->exec("USE pos_db");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 處理 OPTIONS 請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // error_log("[GET ORDERS DEBUG] 進入 get-orders.php\n", 3, __DIR__ . "/../logs/order_debug.log");
    
    if (!isset($pdo)) {
        throw new Exception('資料庫連接失敗');
    }

    $mode = isset($_GET['mode']) ? strtolower(trim($_GET['mode'])) : 'orders';

    if ($mode === 'items') {
        $statusParam = isset($_GET['status']) ? $_GET['status'] : null;
        $statusFilters = [];
        if ($statusParam) {
            $statusParts = array_filter(array_map('trim', explode(',', $statusParam)));
            $allowedStatuses = ['PENDING', 'PREPARING', 'READY', 'COMPLETED', 'CANCELLED'];
            foreach ($statusParts as $part) {
                $upper = strtoupper($part);
                if (in_array($upper, $allowedStatuses, true)) {
                    $statusFilters[] = $upper;
                }
            }
        }
        if (empty($statusFilters)) {
            $statusFilters = ['PENDING', 'PREPARING', 'READY', 'PROCESSING', 'PAID', 'COMPLETED'];
        }

        $categoryFilter = isset($_GET['category']) ? trim($_GET['category']) : null;
        if ($categoryFilter === '' || strtolower($categoryFilter) === 'all') {
            $categoryFilter = null;
        }

        $statusPlaceholders = [];
        $params = [];
        foreach ($statusFilters as $index => $state) {
            $paramName = "status{$index}";
            $placeholder = ":{$paramName}";
            $statusPlaceholders[] = $placeholder;
            $params[$paramName] = $state;
        }

        $itemSql = "
            SELECT 
                oi.id,
                oi.order_id,
                oi.menu_id,
                oi.item_name,
                oi.quantity,
                oi.total_price,
                oi.note,
                COALESCE(NULLIF(oi.category, ''), '未分類') AS category,
                o.order_number,
                o.status,
                o.payment_method,
                o.call_number,
                o.created_at
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status IN (" . implode(',', $statusPlaceholders) . ")
        ";

        if ($categoryFilter !== null) {
            $itemSql .= " AND (CASE WHEN oi.category IS NULL OR oi.category = '' THEN '未分類' ELSE oi.category END) = :categoryFilter";
            $params['categoryFilter'] = $categoryFilter;
        }

        $itemSql .= " ORDER BY category, oi.item_name, o.created_at";

        $itemStmt = $pdo->prepare($itemSql);
        $itemStmt->execute($params);
        $rows = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];

        foreach ($rows as $row) {
            $itemName = $row['item_name'] ?? '未知商品';
            $category = $row['category'] ?? '未分類';
            $groupKey = $itemName . '||' . $category;

            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'itemName' => $itemName,
                    'category' => $category,
                    'menuId' => isset($row['menu_id']) ? (int)$row['menu_id'] : null,
                    'totalQuantity' => 0,
                    'pendingQuantity' => 0,
                    'preparingQuantity' => 0,
                    'readyQuantity' => 0,
                    'completedQuantity' => 0,
                    'orderCount' => 0,
                    'orders' => [],
                ];
            }

            $status = strtoupper($row['status'] ?? '');
            $quantity = (int)($row['quantity'] ?? 0);

            $grouped[$groupKey]['totalQuantity'] += $quantity;
            switch ($status) {
                case 'PENDING':
                case 'PAID':
                    $grouped[$groupKey]['pendingQuantity'] += $quantity;
                    break;
                case 'PREPARING':
                case 'PROCESSING':
                    $grouped[$groupKey]['preparingQuantity'] += $quantity;
                    break;
                case 'READY':
                    $grouped[$groupKey]['readyQuantity'] += $quantity;
                    break;
                case 'COMPLETED':
                    $grouped[$groupKey]['completedQuantity'] += $quantity;
                    break;
            }

            $orderNumber = $row['order_number'] ?? null;
            if ($orderNumber) {
                $normalizedStatus = $status;
                if ($status === 'PROCESSING') {
                    $normalizedStatus = 'PREPARING';
                } elseif ($status === 'PAID') {
                    $normalizedStatus = 'PENDING';
                }

                if (!isset($grouped[$groupKey]['orders'][$orderNumber])) {
                    // 針對付款方式做安全處理，避免傳入 null 觸發 PHP 8.2 的 Deprecated 警告
                    $rawPaymentMethod = $row['payment_method'] ?? '';
                    $normalizedPaymentMethod = $rawPaymentMethod !== null && $rawPaymentMethod !== '' 
                        ? strtolower((string)$rawPaymentMethod) 
                        : '';

                    $grouped[$groupKey]['orders'][$orderNumber] = [
                        'orderNumber' => $orderNumber,
                        'status' => $normalizedStatus,
                        'quantity' => 0,
                        'note' => [],
                        'callNumber' => $row['call_number'],
                        'orderTime' => $row['created_at'],
                        'paymentMethod' => $normalizedPaymentMethod,
                    ];
                }

                $grouped[$groupKey]['orders'][$orderNumber]['quantity'] += $quantity;

                if (!empty($row['note'])) {
                    $grouped[$groupKey]['orders'][$orderNumber]['note'][] = $row['note'];
                }
            }
        }

        $result = [];
        foreach ($grouped as $group) {
            $orders = [];
            foreach ($group['orders'] as $order) {
                $order['note'] = empty($order['note']) ? '' : implode(' / ', array_unique($order['note']));
                $orders[] = $order;
            }
            usort($orders, function ($a, $b) {
                return strcmp($a['orderTime'] ?? '', $b['orderTime'] ?? '');
            });

            $group['orders'] = $orders;
            $group['orderCount'] = count($orders);
            $group['orderNumbers'] = array_map(function ($order) {
                return $order['orderNumber'];
            }, $orders);

            $result[] = $group;
        }

        respondJson($result);
        exit();
    }

    // 取得訂單列表（預設依訂單模式）
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    
    $sql = "
        SELECT o.id, o.order_number, o.call_number, o.order_time, o.total_amount, o.status, o.payment_method, o.created_at, o.updated_at, COUNT(oi.id) as item_count, GROUP_CONCAT(oi.item_name SEPARATOR ', ') as item_names 
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
    ";
    
    // 依狀態篩選
    if ($status && $status !== 'all') {
        $sql .= " WHERE o.status = :status";
    }
    
    $sql .= " GROUP BY o.id, o.order_number, o.call_number, o.order_time, o.total_amount, o.status, o.payment_method, o.created_at, o.updated_at ORDER BY o.created_at DESC LIMIT $limit";
    
    $stmt = $pdo->prepare($sql);
    
    if ($status && $status !== 'all') {
        $stmt->bindValue(':status', strtoupper($status), PDO::PARAM_STR);
    }
    
    $stmt->execute();
    
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 取得訂單明細
    foreach ($orders as &$order) {
        $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
        $stmt->execute(['order_id' => $order['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 轉換訂單屬性名稱以符合前端顯示需求
        foreach ($items as &$item) {
            $item['menuId'] = $item['menu_id'];
            $item['itemName'] = $item['item_name'];
            $item['unitPrice'] = $item['price'];
            $item['note'] = $item['note'];
            $item['totalPrice'] = $item['total_price'];
        }
        
        $order['id'] = $order['id'];
        $order['orderNumber'] = $order['order_number'];
        $order['callNumber'] = $order['call_number'];
        $order['orderTime'] = $order['created_at'];
        $order['total'] = $order['total_amount'];
        // 前端固定使用小寫付款方式代碼，這裡先以 string 轉型避免 null 產生 Deprecated 警告
        $order['paymentMethod'] = isset($order['payment_method']) && $order['payment_method'] !== null
            ? strtolower((string)$order['payment_method'])
            : '';
        $order['items'] = $items;
    }
    
    if ($status === 'READY') {
        $callNumbersOnly = array_map(function($order) {
            return ['call_number' => $order['callNumber']];
        }, $orders);
        respondJson($callNumbersOnly);
        exit();
    }
    respondJson($orders);

} catch (Throwable $e) {
    // error_log("[GET ORDERS ERROR] " . $e->getMessage() . "\n", 3, __DIR__ . "/../logs/order_debug.log");
    respondJson([
        'success' => false,
        'error' => '載入訂單失敗',
        'message' => $e->getMessage()
    ], 500);
}
