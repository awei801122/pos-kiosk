<?php
header('Content-Type: application/json');
require_once '../config.php';

// 設置時區
date_default_timezone_set('Asia/Taipei');

// 數據文件路徑
$inventoryFile = __DIR__ . '/../data/inventory.json';
$stockLogFile = __DIR__ . '/../data/stock_log.json';

// 確保數據目錄存在
if (!file_exists(__DIR__ . '/../data')) {
    mkdir(__DIR__ . '/../data', 0755, true);
}

// 初始化數據文件
if (!file_exists($inventoryFile)) {
    file_put_contents($inventoryFile, json_encode(['items' => []]));
}
if (!file_exists($stockLogFile)) {
    file_put_contents($stockLogFile, json_encode(['logs' => []]));
}

// 讀取庫存數據
function loadInventory() {
    global $inventoryFile;
    return json_decode(file_get_contents($inventoryFile), true);
}

// 保存庫存數據
function saveInventory($data) {
    global $inventoryFile;
    file_put_contents($inventoryFile, json_encode($data, JSON_PRETTY_PRINT));
}

// 讀取庫存記錄
function loadStockLog() {
    global $stockLogFile;
    return json_decode(file_get_contents($stockLogFile), true);
}

// 保存庫存記錄
function saveStockLog($data) {
    global $stockLogFile;
    file_put_contents($stockLogFile, json_encode($data, JSON_PRETTY_PRINT));
}

// 生成唯一ID
function generateId() {
    return uniqid('item_');
}

// 計算本月進貨支出
function calculateMonthlyExpense() {
    $logs = loadStockLog();
    $currentMonth = date('Y-m');
    $expense = 0;

    foreach ($logs['logs'] as $log) {
        if (substr($log['date'], 0, 7) === $currentMonth && $log['type'] === 'in') {
            $expense += $log['quantity'] * $log['unitCost'];
        }
    }

    return $expense;
}

// 讀取庫存資料
function getInventory() {
    $inventoryFile = __DIR__ . '/inventory.json';
    if (!file_exists($inventoryFile)) {
        return [
            'items' => [],
            'categories' => [],
            'suppliers' => [],
            'settings' => [
                'low_stock_alert' => true,
                'alert_threshold' => 20,
                'auto_restock' => false,
                'restock_threshold' => 30
            ]
        ];
    }
    return json_decode(file_get_contents($inventoryFile), true);
}

// 處理 API 請求
$path = $_SERVER['PATH_INFO'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($path) {
    case '/items':
        if ($method === 'GET') {
            // 取得所有庫存品項
            $inventory = getInventory();
            echo json_encode($inventory['items']);
        } elseif ($method === 'POST') {
            // 新增庫存品項
            $input = json_decode(file_get_contents('php://input'), true);
            $inventory = getInventory();
            
            // 生成新 ID
            $newId = 1;
            if (!empty($inventory['items'])) {
                $newId = max(array_column($inventory['items'], 'id')) + 1;
            }
            
            $newItem = [
                'id' => $newId,
                'name' => $input['name'],
                'category' => $input['category'],
                'quantity' => $input['quantity'] ?? 0,
                'unit' => $input['unit'],
                'cost_price' => $input['cost_price'],
                'selling_price' => $input['selling_price'],
                'min_stock' => $input['min_stock'] ?? 0,
                'supplier' => $input['supplier'],
                'last_restock' => date('Y-m-d'),
                'created_at' => date('Y-m-d'),
                'updated_at' => date('Y-m-d')
            ];
            
            $inventory['items'][] = $newItem;
            saveInventory($inventory);
            
            echo json_encode($newItem);
        }
        break;
        
    case '/items/{id}':
        if ($method === 'GET') {
            // 取得單一庫存品項
            $id = intval(explode('/', $path)[2]);
            $inventory = getInventory();
            $item = null;
            
            foreach ($inventory['items'] as $i) {
                if ($i['id'] === $id) {
                    $item = $i;
                    break;
                }
            }
            
            if ($item) {
                echo json_encode($item);
            } else {
                http_response_code(404);
                echo json_encode(['message' => '找不到指定的庫存品項']);
            }
        } elseif ($method === 'PUT') {
            // 更新庫存品項
            $id = intval(explode('/', $path)[2]);
            $input = json_decode(file_get_contents('php://input'), true);
            $inventory = getInventory();
            $itemIndex = null;
            
            foreach ($inventory['items'] as $index => $item) {
                if ($item['id'] === $id) {
                    $itemIndex = $index;
                    break;
                }
            }
            
            if ($itemIndex !== null) {
                $inventory['items'][$itemIndex] = array_merge($inventory['items'][$itemIndex], $input);
                $inventory['items'][$itemIndex]['updated_at'] = date('Y-m-d');
                saveInventory($inventory);
                echo json_encode($inventory['items'][$itemIndex]);
            } else {
                http_response_code(404);
                echo json_encode(['message' => '找不到指定的庫存品項']);
            }
        } elseif ($method === 'DELETE') {
            // 刪除庫存品項
            $id = intval(explode('/', $path)[2]);
            $inventory = getInventory();
            $itemIndex = null;
            
            foreach ($inventory['items'] as $index => $item) {
                if ($item['id'] === $id) {
                    $itemIndex = $index;
                    break;
                }
            }
            
            if ($itemIndex !== null) {
                array_splice($inventory['items'], $itemIndex, 1);
                saveInventory($inventory);
                echo json_encode(['message' => '庫存品項已刪除']);
            } else {
                http_response_code(404);
                echo json_encode(['message' => '找不到指定的庫存品項']);
            }
        }
        break;
        
    case '/categories':
        if ($method === 'GET') {
            // 取得所有分類
            $inventory = getInventory();
            echo json_encode($inventory['categories']);
        } elseif ($method === 'POST') {
            // 新增分類
            $input = json_decode(file_get_contents('php://input'), true);
            $inventory = getInventory();
            
            if (!in_array($input['name'], $inventory['categories'])) {
                $inventory['categories'][] = $input['name'];
                saveInventory($inventory);
                echo json_encode(['message' => '分類已新增']);
            } else {
                http_response_code(400);
                echo json_encode(['message' => '分類已存在']);
            }
        }
        break;
        
    case '/suppliers':
        if ($method === 'GET') {
            // 取得所有供應商
            $inventory = getInventory();
            echo json_encode($inventory['suppliers']);
        } elseif ($method === 'POST') {
            // 新增供應商
            $input = json_decode(file_get_contents('php://input'), true);
            $inventory = getInventory();
            
            // 生成新 ID
            $newId = 1;
            if (!empty($inventory['suppliers'])) {
                $newId = max(array_column($inventory['suppliers'], 'id')) + 1;
            }
            
            $newSupplier = [
                'id' => $newId,
                'name' => $input['name'],
                'contact' => $input['contact'],
                'email' => $input['email'],
                'address' => $input['address'],
                'created_at' => date('Y-m-d')
            ];
            
            $inventory['suppliers'][] = $newSupplier;
            saveInventory($inventory);
            
            echo json_encode($newSupplier);
        }
        break;
        
    case '/settings':
        if ($method === 'GET') {
            // 取得設定
            $inventory = getInventory();
            echo json_encode($inventory['settings']);
        } elseif ($method === 'POST') {
            // 更新設定
            $input = json_decode(file_get_contents('php://input'), true);
            $inventory = getInventory();
            $inventory['settings'] = array_merge($inventory['settings'], $input);
            saveInventory($inventory);
            echo json_encode(['message' => '設定已更新']);
        }
        break;
        
    case '/low-stock':
        if ($method === 'GET') {
            // 取得低庫存品項
            $inventory = getInventory();
            $lowStockItems = array_filter($inventory['items'], function($item) use ($inventory) {
                return $item['quantity'] <= $inventory['settings']['alert_threshold'];
            });
            echo json_encode(array_values($lowStockItems));
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['message' => '未找到對應的路由']);
        break;
} 