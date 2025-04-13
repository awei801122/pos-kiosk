<?php
/**
 * 庫存調整 API
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/session.php';

// 檢查權限
checkLogin();
checkPermission('inventory.manage');

// 檢查請求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '不支援的請求方法']);
    exit;
}

// 獲取參數
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$currentStock = isset($_POST['current_stock']) ? (int)$_POST['current_stock'] : 0;
$lowStockThreshold = isset($_POST['low_stock_threshold']) ? (int)$_POST['low_stock_threshold'] : 0;
$unit = isset($_POST['unit']) ? trim($_POST['unit']) : '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

// 驗證參數
if (!$id || $currentStock < 0 || $lowStockThreshold < 0 || !$unit) {
    echo json_encode(['success' => false, 'message' => '參數錯誤']);
    exit;
}

try {
    // 開始事務
    $db->beginTransaction();
    
    // 檢查商品是否存在
    $stmt = $db->prepare("
        SELECT i.current_stock
        FROM inventory i
        WHERE i.menu_item_id = ?
    ");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        throw new Exception('商品不存在');
    }
    
    // 更新庫存
    $stmt = $db->prepare("
        UPDATE inventory 
        SET current_stock = ?,
            low_stock_threshold = ?,
            unit = ?,
            updated_at = NOW()
        WHERE menu_item_id = ?
    ");
    $stmt->execute([$currentStock, $lowStockThreshold, $unit, $id]);
    
    // 記錄庫存變動
    $changeAmount = $currentStock - $item['current_stock'];
    $stmt = $db->prepare("
        INSERT INTO inventory_logs (
            menu_item_id,
            change_amount,
            new_quantity,
            operator_id,
            notes,
            created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $id,
        $changeAmount,
        $currentStock,
        $_SESSION['user_id'],
        $notes
    ]);
    
    // 提交事務
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => '庫存調整成功'
    ]);
    
} catch (Exception $e) {
    // 回滾事務
    $db->rollBack();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 