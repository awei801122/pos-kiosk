<?php
// 載入資料庫連接
require_once 'db.php';

// 檢查訂單ID
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$order_id) {
    header('Content-Type: text/html; charset=utf-8');
    die('訂單ID不能為空');
}

try {
    // 查詢訂單資料
    $stmt = $pdo->prepare("
        SELECT * FROM orders WHERE id = :id
    ");
    $stmt->execute(['id' => $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Content-Type: text/html; charset=utf-8');
        die('找不到該訂單');
    }
    
    // 查詢訂單明細
    $stmt = $pdo->prepare("
        SELECT * FROM order_items WHERE order_id = :order_id
    ");
    $stmt->execute(['order_id' => $order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 生成HTML格式的收據
    header('Content-Type: text/html; charset=utf-8');
    
    echo '<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>收據 #' . $order['order_number'] . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .receipt {
            max-width: 400px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header, .footer {
            text-align: center;
            margin-bottom: 20px;
        }
        .divider {
            border-top: 1px dashed #ddd;
            margin: 15px 0;
        }
        .order-info {
            margin-bottom: 20px;
        }
        .order-items {
            width: 100%;
            border-collapse: collapse;
        }
        .order-items th, .order-items td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .order-items th {
            background-color: #f8f9fa;
        }
        .total {
            text-align: right;
            font-weight: bold;
            margin-top: 20px;
        }
        .pickup-number {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin: 10px 0;
        }
        .print-button {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            margin-top: 20px;
            cursor: pointer;
            font-size: 16px;
        }
        @media print {
            .print-button {
                display: none;
            }
            body {
                padding: 0;
                background-color: white;
            }
            .receipt {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <h1>自助點餐系統</h1>
            <p>地址：台北市忠孝東路100號</p>
            <p>電話：02-1234-5678</p>
        </div>
        
        <div class="divider"></div>
        
        <div class="order-info">
            <p><strong>訂單編號：</strong>' . $order['order_number'] . '</p>
            <p><strong>日期時間：</strong>' . date('Y-m-d H:i:s', strtotime($order['created_at'])) . '</p>
            <p><strong>付款方式：</strong>' . $order['payment_method'] . '</p>
        </div>
        
        <div class="divider"></div>
        
        <table class="order-items">
            <thead>
                <tr>
                    <th>品項</th>
                    <th>數量</th>
                    <th>單價</th>
                    <th>金額</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($items as $item) {
        echo '<tr>
                <td>' . htmlspecialchars($item['item_name']) . '</td>
                <td>' . $item['quantity'] . '</td>
                <td>NT$ ' . number_format($item['unit_price'], 0) . '</td>
                <td>NT$ ' . number_format($item['total_price'], 0) . '</td>
            </tr>';
        if (!empty($item['note'])) {
            echo '<tr>
                    <td colspan="4" style="padding-left: 20px; font-size: 0.9em; color: #666;">
                        備註: ' . htmlspecialchars($item['note']) . '
                    </td>
                </tr>';
        }
    }
    
    echo '</tbody>
        </table>
        
        <div class="total">
            <p>總計：NT$ ' . number_format($order['total_amount'], 0) . '</p>
        </div>
        
        <div class="divider"></div>
        
        <div class="header">
            <h2>取餐號碼</h2>
            <div class="pickup-number">#' . $order['order_number'] . '</div>
            <p>請妥善保存此收據作為取餐憑證</p>
            <p>感謝您的光臨！</p>
        </div>
        
        <button class="print-button" onclick="window.print()">列印收據</button>
    </div>
    
    <script>
        // 自動觸發列印
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>
</body>
</html>';
    
} catch (Exception $e) {
    header('Content-Type: text/html; charset=utf-8');
    die('生成收據時發生錯誤: ' . $e->getMessage());
}
?> 