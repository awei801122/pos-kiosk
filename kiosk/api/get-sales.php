<?php
header('Content-Type: application/json');
$date = $_GET['date'] ?? date('Y-m-d');

try {
    $pdo = new PDO('mysql:host=pos-mvp-db;dbname=pos_db', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE DATE(order_time) = ? AND status = 'COMPLETED'");
    $stmt->execute([$date]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($orders as &$order) {
        $itemStmt = $pdo->prepare("SELECT item_name, quantity, price, total_price, note, 
            IFNULL(NULLIF(category, ''), '未分類') AS category 
            FROM order_items WHERE order_id = ?");
        $itemStmt->execute([$order['id']]);
        $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (isset($_GET['export']) && $_GET['export'] == '1') {
        $stmt = $pdo->prepare("
            SELECT o.id AS call_number, o.order_number, i.item_name, i.quantity, i.price, i.total_price,
                   o.payment_method, o.status, i.note
            FROM orders o
            JOIN order_items i ON o.id = i.order_id
            WHERE DATE(o.order_time) = ?
              AND o.status IN ('COMPLETED', 'CANCELLED')
        ");
        $stmt->execute([$date]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="sales-export-' . $date . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['訂單編號', '取餐號', '商品名稱', '數量', '單價', '總價', '支付方式', '商品狀態', '備註'], ',', '"', '"');

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['order_number'],
                $row['call_number'],
                $row['item_name'],
                $row['quantity'],
                $row['price'],
                $row['total_price'],
                $row['payment_method'] === 'CASH' ? '現金' : '行動支付',
                $row['status'] === 'COMPLETED' ? '已完成' : '已取消',
                $row['note']
            ], ',', '"', '"');
        }

        fclose($output);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $orders]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>