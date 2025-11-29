<?php
/**
 * 庫存管理系統
 * Inventory Management System
 * 
 * 功能包含：
 * 1. 取得所有庫存項目 (Get all inventory items)
 * 2. 新增庫存項目 (Add new inventory item)
 * 3. 更新庫存數量 (Update inventory quantity)
 * 4. 刪除庫存項目 (Delete inventory item)
 * 5. 庫存警告設定 (Inventory alert settings)
 */

// 設定 HTTP 回應標頭
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 錯誤處理
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 定義資料檔案路徑
define('DATA_FILE', __DIR__ . '/data/inventory.json');

// 確保資料目錄存在
if (!file_exists(dirname(DATA_FILE))) {
    mkdir(dirname(DATA_FILE), 0777, true);
}

// 確保資料檔案存在
if (!file_exists(DATA_FILE)) {
    file_put_contents(DATA_FILE, json_encode([]));
}

// 讀取資料
function read_data() {
    $json = file_get_contents(DATA_FILE);
    return json_decode($json, true) ?: [];
}

// 寫入資料
function write_data($data) {
    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// 生成唯一ID
function generate_id() {
    return uniqid('item_');
}

// 驗證資料
function validate_item($item) {
    $errors = [];
    
    if (empty($item['name'])) {
        $errors[] = '名稱不能為空';
    }
    
    if (!isset($item['quantity']) || !is_numeric($item['quantity']) || $item['quantity'] < 0) {
        $errors[] = '數量必須為非負數';
    }
    
    if (empty($item['unit'])) {
        $errors[] = '單位不能為空';
    }
    
    if (!isset($item['min_quantity']) || !is_numeric($item['min_quantity']) || $item['min_quantity'] < 0) {
        $errors[] = '最低庫存量必須為非負數';
    }
    
    return $errors;
}

// 處理請求
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            // 讀取所有庫存項目
            $items = read_data();
            
            // 添加警告資訊
            foreach ($items as &$item) {
                $item['alerts'] = [
                    'is_low' => $item['quantity'] <= $item['min_quantity']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => array_values($items)
            ]);
            break;
            
        case 'POST':
            // 讀取 POST 資料
            $input = json_decode(file_get_contents('php://input'), true);
            
            // 驗證資料
            $errors = validate_item($input);
            if (!empty($errors)) {
                throw new Exception(implode(', ', $errors));
            }
            
            $items = read_data();
            
            if ($action === 'update' && !empty($input['id'])) {
                // 更新現有項目
                if (!isset($items[$input['id']])) {
                    throw new Exception('找不到指定的項目');
                }
                
                $items[$input['id']] = array_merge($items[$input['id']], [
                    'name' => $input['name'],
                    'quantity' => (int)$input['quantity'],
                    'unit' => $input['unit'],
                    'min_quantity' => (int)$input['min_quantity'],
                    'supplier' => $input['supplier'],
                    'last_updated' => date('Y-m-d H:i:s')
                ]);
                
                $message = '項目更新成功';
            } else {
                // 新增項目
                $id = generate_id();
                $items[$id] = [
                    'id' => $id,
                    'name' => $input['name'],
                    'quantity' => (int)$input['quantity'],
                    'unit' => $input['unit'],
                    'min_quantity' => (int)$input['min_quantity'],
                    'supplier' => $input['supplier'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'last_updated' => date('Y-m-d H:i:s')
                ];
                
                $message = '項目新增成功';
            }
            
            write_data($items);
            
            echo json_encode([
                'success' => true,
                'message' => $message
            ]);
            break;
            
        case 'DELETE':
            // 刪除項目
            $id = $_GET['id'] ?? '';
            if (empty($id)) {
                throw new Exception('未指定項目ID');
            }
            
            $items = read_data();
            if (!isset($items[$id])) {
                throw new Exception('找不到指定的項目');
            }
            
            unset($items[$id]);
            write_data($items);
            
            echo json_encode([
                'success' => true,
                'message' => '項目刪除成功'
            ]);
            break;
            
        case 'OPTIONS':
            // 處理 CORS 預檢請求
            http_response_code(204);
            break;
            
        default:
            throw new Exception('不支援的請求方法');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 