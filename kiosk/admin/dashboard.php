<?php
/**
 * 儀表板頁面
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

// 檢查登入狀態
checkLogin();

// 設置頁面標題
$pageTitle = '儀表板';

// 獲取今日銷售數據
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as order_count,
        SUM(total_amount) as total_amount,
        SUM(tax_amount) as tax_amount,
        SUM(discount_amount) as discount_amount,
        SUM(net_amount) as net_amount
    FROM orders
    WHERE DATE(created_at) = CURDATE()
");
$stmt->execute();
$todaySales = $stmt->fetch(PDO::FETCH_ASSOC);

// 獲取本月銷售數據
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as order_count,
        SUM(total_amount) as total_amount,
        SUM(tax_amount) as tax_amount,
        SUM(discount_amount) as discount_amount,
        SUM(net_amount) as net_amount
    FROM orders
    WHERE MONTH(created_at) = MONTH(CURDATE())
    AND YEAR(created_at) = YEAR(CURDATE())
");
$stmt->execute();
$monthSales = $stmt->fetch(PDO::FETCH_ASSOC);

// 獲取庫存警報
$stmt = $db->prepare("
    SELECT 
        i.*,
        mi.name as item_name,
        mi.category
    FROM inventory i
    JOIN menu_items mi ON i.menu_item_id = mi.id
    WHERE i.quantity <= i.low_stock_threshold
    ORDER BY i.quantity ASC
    LIMIT 5
");
$stmt->execute();
$lowStockItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 獲取最近訂單
$stmt = $db->prepare("
    SELECT 
        o.*,
        COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 獲取熱門商品
$stmt = $db->prepare("
    SELECT 
        mi.name,
        mi.category,
        COUNT(oi.id) as order_count,
        SUM(oi.quantity) as total_quantity,
        SUM(oi.price * oi.quantity) as total_amount
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY mi.id
    ORDER BY total_quantity DESC
    LIMIT 5
");
$stmt->execute();
$popularItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 引入布局文件
require_once __DIR__ . '/layout.php';
?>

<!-- 統計卡片 -->
<div class="row mb-4">
    <!-- 今日訂單 -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">今日訂單</h5>
                <h2 class="card-text"><?php echo $todaySales['order_count'] ?? 0; ?></h2>
                <p class="text-muted mb-0">今日銷售額：<?php echo formatMoney($todaySales['net_amount'] ?? 0); ?></p>
            </div>
        </div>
    </div>
    
    <!-- 本月銷售 -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">本月銷售</h5>
                <h2 class="card-text"><?php echo formatMoney($monthSales['net_amount'] ?? 0); ?></h2>
                <p class="text-muted mb-0">訂單數：<?php echo $monthSales['order_count'] ?? 0; ?></p>
            </div>
        </div>
    </div>
    
    <!-- 庫存警報 -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">庫存警報</h5>
                <h2 class="card-text"><?php echo count($lowStockItems); ?></h2>
                <p class="text-muted mb-0">需要補貨的商品數量</p>
            </div>
        </div>
    </div>
    
    <!-- 平均訂單金額 -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">平均訂單金額</h5>
                <h2 class="card-text">
                    <?php 
                    $avgAmount = $monthSales['order_count'] > 0 
                        ? $monthSales['net_amount'] / $monthSales['order_count'] 
                        : 0;
                    echo formatMoney($avgAmount);
                    ?>
                </h2>
                <p class="text-muted mb-0">本月平均每筆訂單</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- 最近訂單 -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">最近訂單</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>訂單編號</th>
                                <th>時間</th>
                                <th>金額</th>
                                <th>狀態</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td><?php echo date('H:i', strtotime($order['created_at'])); ?></td>
                                <td><?php echo formatMoney($order['net_amount']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $order['status'] === 'completed' ? 'success' : 
                                            ($order['status'] === 'pending' ? 'warning' : 'secondary'); 
                                    ?>">
                                        <?php 
                                        echo $order['status'] === 'completed' ? '已完成' : 
                                            ($order['status'] === 'pending' ? '處理中' : '已取消'); 
                                        ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 熱門商品 -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">熱門商品</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>商品名稱</th>
                                <th>分類</th>
                                <th>銷售數量</th>
                                <th>銷售金額</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($popularItems as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                <td><?php echo $item['total_quantity']; ?></td>
                                <td><?php echo formatMoney($item['total_amount']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 庫存警報 -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">庫存警報</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>商品名稱</th>
                                <th>分類</th>
                                <th>當前庫存</th>
                                <th>最低庫存</th>
                                <th>狀態</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lowStockItems as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><?php echo $item['low_stock_threshold']; ?></td>
                                <td>
                                    <span class="badge bg-danger">需要補貨</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($lowStockItems)): ?>
                            <tr>
                                <td colspan="5" class="text-center">目前沒有庫存警報</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div> 