<?php
/**
 * 後台管理儀表板API
 */

// 設置響應頭
header('Content-Type: application/json');

// 引入必要的文件
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// 檢查權限
if (!checkPermission('dashboard.view')) {
    http_response_code(403);
    echo json_encode(['error' => '沒有權限訪問儀表板']);
    exit;
}

try {
    // 獲取今日訂單統計
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as todayOrders,
            COALESCE(SUM(total_amount), 0) as todayRevenue
        FROM orders 
        WHERE DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 獲取待處理訂單數量
    $stmt = $db->prepare("
        SELECT COUNT(*) as pendingOrders
        FROM orders 
        WHERE status = 'pending'
    ");
    $stmt->execute();
    $stats['pendingOrders'] = $stmt->fetchColumn();

    // 獲取庫存不足的商品數量
    $stmt = $db->prepare("
        SELECT COUNT(*) as lowStock
        FROM menu_items 
        WHERE stock < 10 AND status = 'active'
    ");
    $stmt->execute();
    $stats['lowStock'] = $stmt->fetchColumn();

    // 獲取最近訂單
    $stmt = $db->prepare("
        SELECT 
            order_number,
            total_amount,
            status,
            created_at
        FROM orders 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $stats['recentOrders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 獲取熱門商品
    $stmt = $db->prepare("
        SELECT 
            mi.name,
            COUNT(oi.id) as quantity,
            SUM(oi.price) as revenue
        FROM order_items oi
        JOIN menu_items mi ON oi.item_id = mi.id
        WHERE oi.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY mi.id
        ORDER BY quantity DESC
        LIMIT 5
    ");
    $stmt->execute();
    $stats['topProducts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 返回數據
    echo json_encode($stats);

} catch (PDOException $e) {
    // 記錄錯誤
    logSystem('error', '獲取儀表板數據失敗', ['error' => $e->getMessage()]);
    
    // 返回錯誤響應
    http_response_code(500);
    echo json_encode(['error' => '獲取數據失敗']);
} 