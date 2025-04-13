<?php
/**
 * 庫存批量導入 API
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

// 檢查文件
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => '文件上傳失敗']);
    exit;
}

$file = $_FILES['file'];
$fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// 檢查文件類型
if (!in_array($fileType, ['csv', 'xlsx'])) {
    echo json_encode(['success' => false, 'message' => '不支援的文件格式']);
    exit;
}

try {
    // 開始事務
    $db->beginTransaction();
    
    // 處理 CSV 文件
    if ($fileType === 'csv') {
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            throw new Exception('無法讀取文件');
        }
        
        // 跳過標題行
        fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 4) continue;
            
            $menuItemId = (int)$data[0];
            $currentStock = (int)$data[1];
            $lowStockThreshold = (int)$data[2];
            $unit = trim($data[3]);
            
            // 檢查商品是否存在
            $stmt = $db->prepare("
                SELECT id 
                FROM menu_items 
                WHERE id = ?
            ");
            $stmt->execute([$menuItemId]);
            if (!$stmt->fetch()) {
                continue;
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
            $stmt->execute([$currentStock, $lowStockThreshold, $unit, $menuItemId]);
            
            // 記錄庫存變動
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
                $menuItemId,
                0, // 批量導入不計算變動量
                $currentStock,
                $_SESSION['user_id'],
                '批量導入'
            ]);
        }
        
        fclose($handle);
    }
    // 處理 Excel 文件
    else {
        require_once __DIR__ . '/../../../vendor/autoload.php';
        
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // 跳過標題行
        array_shift($rows);
        
        foreach ($rows as $row) {
            if (count($row) < 4) continue;
            
            $menuItemId = (int)$row[0];
            $currentStock = (int)$row[1];
            $lowStockThreshold = (int)$row[2];
            $unit = trim($row[3]);
            
            // 檢查商品是否存在
            $stmt = $db->prepare("
                SELECT id 
                FROM menu_items 
                WHERE id = ?
            ");
            $stmt->execute([$menuItemId]);
            if (!$stmt->fetch()) {
                continue;
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
            $stmt->execute([$currentStock, $lowStockThreshold, $unit, $menuItemId]);
            
            // 記錄庫存變動
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
                $menuItemId,
                0, // 批量導入不計算變動量
                $currentStock,
                $_SESSION['user_id'],
                '批量導入'
            ]);
        }
    }
    
    // 提交事務
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => '庫存導入成功'
    ]);
    
} catch (Exception $e) {
    // 回滾事務
    $db->rollBack();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 