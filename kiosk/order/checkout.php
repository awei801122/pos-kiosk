<?php
/**
 * 訂單結帳頁面
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

// 檢查購物車是否為空
if (empty($_SESSION['cart'])) {
    header('Location: /menu.php');
    exit;
}

// 計算訂單總金額
$totalAmount = 0;
foreach ($_SESSION['cart'] as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}

// 設置頁面標題
$pageTitle = '訂單結帳';
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
        .payment-method {
            cursor: pointer;
            transition: all 0.3s;
        }
        .payment-method:hover {
            background-color: #f8f9fa;
        }
        .payment-method.selected {
            background-color: #e9ecef;
            border-color: #0d6efd;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <h1 class="mb-4"><?php echo $pageTitle; ?></h1>
        
        <!-- 訂單商品列表 -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">訂單商品</h5>
            </div>
            <div class="card-body">
                <?php foreach ($_SESSION['cart'] as $item): ?>
                <div class="order-item">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
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
                        <h5 class="mb-0"><?php echo formatMoney($totalAmount); ?></h5>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 支付方式選擇 -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">選擇支付方式</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="payment-method p-3 border rounded mb-3" data-method="jkopay">
                            <div class="d-flex align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" value="jkopay" checked>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-1">JKoPay 支付</h6>
                                    <small class="text-muted">使用 JKoPay 進行支付</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 提交訂單 -->
        <div class="d-grid gap-2">
            <button type="button" class="btn btn-primary btn-lg" id="submitOrder">
                提交訂單
            </button>
            <a href="/menu.php" class="btn btn-outline-secondary">
                返回菜單
            </a>
        </div>
    </div>
    
    <!-- 支付中提示框 -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 class="mb-3">正在處理支付...</h5>
                    <p class="text-muted mb-0">請勿關閉此視窗</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 支付方式選擇
        document.querySelectorAll('.payment-method').forEach(function(el) {
            el.addEventListener('click', function() {
                document.querySelectorAll('.payment-method').forEach(function(e) {
                    e.classList.remove('selected');
                });
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });
        
        // 提交訂單
        document.getElementById('submitOrder').addEventListener('click', function() {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            
            // 顯示支付中提示框
            const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
            paymentModal.show();
            
            // 創建訂單
            fetch('/api/orders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'create',
                    items: <?php echo json_encode($_SESSION['cart']); ?>,
                    total_amount: <?php echo $totalAmount; ?>,
                    payment_method: paymentMethod
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 創建支付訂單
                    return fetch('/api/payment/jkopay.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'create_payment',
                            order_id: data.data.order_id,
                            amount: <?php echo $totalAmount; ?>
                        })
                    });
                } else {
                    throw new Error(data.message);
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 跳轉到支付頁面
                    window.location.href = data.data.payment_url;
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                paymentModal.hide();
                alert('訂單提交失敗：' + error.message);
            });
        });
    });
    </script>
</body>
</html> 