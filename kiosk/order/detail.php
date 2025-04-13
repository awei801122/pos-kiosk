<?php
/**
 * 訂單詳情頁面
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

// 檢查是否已登入
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 獲取訂單ID
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 獲取訂單詳情
$stmt = $db->prepare("
    SELECT 
        o.*,
        p.status as payment_status,
        p.paid_at as payment_time,
        p.payment_id
    FROM orders o
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: /order/history.php');
    exit;
}

// 獲取訂單商品
$stmt = $db->prepare("
    SELECT 
        oi.*,
        m.name,
        m.description,
        m.price
    FROM order_items oi
    JOIN menu_items m ON oi.menu_item_id = m.id
    WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 設置頁面標題
$pageTitle = '訂單詳情';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .order-item {
            border-bottom: 1px solid #eee;
            padding: 1rem 0;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .order-info {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?php echo $pageTitle; ?></h1>
            <a href="/order/history.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> 返回訂單歷史
            </a>
        </div>
        
        <!-- 訂單基本信息 -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">訂單信息</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="order-info mb-3">
                            <h6 class="text-muted mb-2">訂單編號</h6>
                            <p class="mb-0"><?php echo htmlspecialchars($order['order_no']); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="order-info mb-3">
                            <h6 class="text-muted mb-2">下單時間</h6>
                            <p class="mb-0"><?php echo date('Y-m-d H:i:s', strtotime($order['created_at'])); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="order-info mb-3">
                            <h6 class="text-muted mb-2">訂單狀態</h6>
                            <p class="mb-0">
                                <?php
                                switch ($order['status']) {
                                    case 'paid':
                                        echo '<span class="badge bg-success">已支付</span>';
                                        break;
                                    case 'pending':
                                        echo '<span class="badge bg-warning">處理中</span>';
                                        break;
                                    case 'cancelled':
                                        echo '<span class="badge bg-danger">已取消</span>';
                                        break;
                                    default:
                                        echo '<span class="badge bg-secondary">未知</span>';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="order-info mb-3">
                            <h6 class="text-muted mb-2">支付狀態</h6>
                            <p class="mb-0">
                                <?php
                                switch ($order['payment_status']) {
                                    case 'paid':
                                        echo '<span class="badge bg-success">已支付</span>';
                                        break;
                                    case 'pending':
                                        echo '<span class="badge bg-warning">處理中</span>';
                                        break;
                                    case 'failed':
                                        echo '<span class="badge bg-danger">支付失敗</span>';
                                        break;
                                    default:
                                        echo '<span class="badge bg-secondary">未知</span>';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="order-info mb-3">
                            <h6 class="text-muted mb-2">支付時間</h6>
                            <p class="mb-0">
                                <?php echo $order['payment_time'] ? date('Y-m-d H:i:s', strtotime($order['payment_time'])) : '未支付'; ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="order-info mb-3">
                            <h6 class="text-muted mb-2">訂單金額</h6>
                            <p class="mb-0"><?php echo formatMoney($order['total_amount']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 訂單商品 -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">訂單商品</h5>
            </div>
            <div class="card-body">
                <?php foreach ($items as $item): ?>
                <div class="order-item">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($item['description']); ?></small>
                        </div>
                        <div class="col-md-2 text-center">
                            <span class="text-muted">數量：</span>
                            <span><?php echo $item['quantity']; ?></span>
                        </div>
                        <div class="col-md-2 text-center">
                            <span class="text-muted">單價：</span>
                            <span><?php echo formatMoney($item['price']); ?></span>
                        </div>
                        <div class="col-md-2 text-end">
                            <span class="text-muted">小計：</span>
                            <span><?php echo formatMoney($item['price'] * $item['quantity']); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="col-md-8">
                        <h5 class="mb-0">訂單總金額</h5>
                    </div>
                    <div class="col-md-4 text-end">
                        <h5 class="mb-0"><?php echo formatMoney($order['total_amount']); ?></h5>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 操作按鈕 -->
        <div class="d-grid gap-2">
            <?php if ($order['status'] === 'pending' && $order['payment_status'] !== 'paid'): ?>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                <i class="bi bi-x-circle"></i> 取消訂單
            </button>
            <?php endif; ?>
            <a href="/order/history.php" class="btn btn-primary">
                返回訂單歷史
            </a>
        </div>
    </div>
    
    <!-- 取消訂單確認對話框 -->
    <div class="modal fade" id="cancelOrderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">確認取消訂單</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>您確定要取消此訂單嗎？此操作無法撤銷。</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-danger" id="confirmCancel">確認取消</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 取消訂單
        document.getElementById('confirmCancel').addEventListener('click', function() {
            const formData = new FormData();
            formData.append('order_id', <?php echo $orderId; ?>);
            
            fetch('/api/order/cancel.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('訂單已取消');
                    window.location.reload();
                } else {
                    alert(data.message || '取消訂單失敗');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('取消訂單時發生錯誤');
            });
        });
    </script>
</body>
</html> 