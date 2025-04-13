<?php
/**
 * 訂單完成頁面
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

// 獲取訂單ID
$orderId = $_GET['order_id'] ?? null;
$paymentId = $_GET['payment_id'] ?? null;

if (!$orderId || !$paymentId) {
    header('Location: /menu.php');
    exit;
}

// 查詢訂單狀態
$stmt = $db->prepare("
    SELECT 
        o.*,
        p.status as payment_status,
        p.paid_at as payment_time
    FROM orders o
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE o.id = ? AND p.payment_id = ?
");
$stmt->execute([$orderId, $paymentId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: /menu.php');
    exit;
}

// 設置頁面標題
$pageTitle = '訂單完成';
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
        .status-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
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
        <div class="text-center mb-4">
            <?php if ($order['payment_status'] === 'paid'): ?>
            <i class="bi bi-check-circle-fill text-success status-icon"></i>
            <h1 class="mb-3">支付成功</h1>
            <p class="text-muted">您的訂單已成功支付，我們將盡快為您準備餐點。</p>
            <?php else: ?>
            <i class="bi bi-exclamation-circle-fill text-warning status-icon"></i>
            <h1 class="mb-3">支付處理中</h1>
            <p class="text-muted">您的訂單正在處理中，請稍後查看支付狀態。</p>
            <?php endif; ?>
        </div>
        
        <!-- 訂單信息 -->
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
                            <h6 class="text-muted mb-2">訂單金額</h6>
                            <p class="mb-0"><?php echo formatMoney($order['total_amount']); ?></p>
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
                </div>
            </div>
        </div>
        
        <!-- 操作按鈕 -->
        <div class="d-grid gap-2">
            <a href="/menu.php" class="btn btn-primary">
                返回菜單
            </a>
            <a href="/order/history.php" class="btn btn-outline-secondary">
                查看訂單歷史
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // 如果支付狀態為處理中，定期檢查支付狀態
    <?php if ($order['payment_status'] === 'pending'): ?>
    function checkPaymentStatus() {
        fetch('/api/payment/jkopay.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'check_payment',
                payment_id: '<?php echo $paymentId; ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.status === 'paid') {
                // 支付成功，刷新頁面
                window.location.reload();
            }
        });
    }
    
    // 每5秒檢查一次支付狀態
    setInterval(checkPaymentStatus, 5000);
    <?php endif; ?>
    </script>
</body>
</html> 