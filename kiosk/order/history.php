<?php
/**
 * 訂單歷史頁面
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

// 檢查是否已登入
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 獲取分頁參數
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 獲取訂單總數
$stmt = $db->prepare("
    SELECT COUNT(*) as total
    FROM orders
    WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($total / $limit);

// 獲取訂單列表
$stmt = $db->prepare("
    SELECT 
        o.*,
        p.status as payment_status,
        p.paid_at as payment_time
    FROM orders o
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$_SESSION['user_id'], $limit, $offset]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 設置頁面標題
$pageTitle = '訂單歷史';
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
        .order-card {
            transition: all 0.3s;
        }
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .order-status {
            font-size: 0.875rem;
        }
        .order-items {
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?php echo $pageTitle; ?></h1>
            <a href="/menu.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> 返回菜單
            </a>
        </div>
        
        <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <i class="bi bi-receipt text-muted" style="font-size: 4rem;"></i>
            <h4 class="mt-3">暫無訂單記錄</h4>
            <p class="text-muted">您還沒有下過任何訂單</p>
            <a href="/menu.php" class="btn btn-primary mt-3">開始點餐</a>
        </div>
        <?php else: ?>
        <!-- 訂單列表 -->
        <div class="row g-4">
            <?php foreach ($orders as $order): ?>
            <div class="col-md-6">
                <div class="card order-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">訂單編號：<?php echo htmlspecialchars($order['order_no']); ?></h5>
                        <span class="order-status badge <?php
                            switch ($order['status']) {
                                case 'paid':
                                    echo 'bg-success';
                                    break;
                                case 'pending':
                                    echo 'bg-warning';
                                    break;
                                case 'cancelled':
                                    echo 'bg-danger';
                                    break;
                                default:
                                    echo 'bg-secondary';
                            }
                        ?>">
                            <?php
                            switch ($order['status']) {
                                case 'paid':
                                    echo '已支付';
                                    break;
                                case 'pending':
                                    echo '處理中';
                                    break;
                                case 'cancelled':
                                    echo '已取消';
                                    break;
                                default:
                                    echo '未知';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">下單時間：</small>
                            <p class="mb-0"><?php echo date('Y-m-d H:i:s', strtotime($order['created_at'])); ?></p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">訂單金額：</small>
                            <p class="mb-0"><?php echo formatMoney($order['total_amount']); ?></p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">支付狀態：</small>
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
                        <div class="mb-3">
                            <small class="text-muted">支付時間：</small>
                            <p class="mb-0">
                                <?php echo $order['payment_time'] ? date('Y-m-d H:i:s', strtotime($order['payment_time'])) : '未支付'; ?>
                            </p>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="/order/detail.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-primary btn-sm">
                            查看詳情
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- 分頁 -->
        <?php if ($totalPages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 