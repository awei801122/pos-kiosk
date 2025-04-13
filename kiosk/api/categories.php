<?php
header('Content-Type: application/json');

// 設置時區
date_default_timezone_set('Asia/Taipei');

// 數據文件路徑
$categoriesFile = __DIR__ . '/../data/categories.json';
$productsFile = __DIR__ . '/../data/products.json';

// 確保數據目錄存在
if (!file_exists(__DIR__ . '/../data')) {
    mkdir(__DIR__ . '/../data', 0755, true);
}

// 初始化數據文件
if (!file_exists($categoriesFile)) {
    file_put_contents($categoriesFile, json_encode(['categories' => []]));
}

// 讀取分類數據
function loadCategories() {
    global $categoriesFile;
    return json_decode(file_get_contents($categoriesFile), true);
}

// 保存分類數據
function saveCategories($data) {
    global $categoriesFile;
    file_put_contents($categoriesFile, json_encode($data, JSON_PRETTY_PRINT));
}

// 讀取商品數據
function loadProducts() {
    global $productsFile;
    return json_decode(file_get_contents($productsFile), true);
}

// 保存商品數據
function saveProducts($data) {
    global $productsFile;
    file_put_contents($productsFile, json_encode($data, JSON_PRETTY_PRINT));
}

// 生成唯一ID
function generateId() {
    return uniqid('cat_');
}

// 處理請求
$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        // 獲取所有分類
        $data = loadCategories();
        echo json_encode($data);
        break;

    case 'POST':
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['name'])) {
                throw new Exception('分類名稱不能為空');
            }
            
            $data = loadCategories();
            
            // 檢查分類名稱是否重複
            foreach ($data['categories'] as $category) {
                if ($category['name'] === $input['name']) {
                    throw new Exception('分類名稱已存在');
                }
            }
            
            $category = [
                'id' => generateId(),
                'name' => $input['name'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $data['categories'][] = $category;
            saveCategories($data);
            
            echo json_encode(['success' => true, 'category' => $category]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'PUT':
        try {
            if (!$id) {
                throw new Exception('缺少分類ID');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['name'])) {
                throw new Exception('分類名稱不能為空');
            }
            
            $data = loadCategories();
            $found = false;
            
            foreach ($data['categories'] as &$category) {
                // 檢查名稱是否與其他分類重複
                if ($category['id'] !== $id && $category['name'] === $input['name']) {
                    throw new Exception('分類名稱已存在');
                }
                
                if ($category['id'] === $id) {
                    $category['name'] = $input['name'];
                    $category['updated_at'] = date('Y-m-d H:i:s');
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                throw new Exception('分類不存在');
            }
            
            saveCategories($data);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'DELETE':
        try {
            if (!$id) {
                throw new Exception('缺少分類ID');
            }
            
            $data = loadCategories();
            $found = false;
            
            foreach ($data['categories'] as $key => $category) {
                if ($category['id'] === $id) {
                    unset($data['categories'][$key]);
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                throw new Exception('分類不存在');
            }
            
            // 重新索引數組
            $data['categories'] = array_values($data['categories']);
            saveCategories($data);
            
            // 更新相關商品的分類
            $products = loadProducts();
            $updated = false;
            
            foreach ($products['products'] as &$product) {
                if ($product['category'] === $id) {
                    $product['category'] = '';
                    $updated = true;
                }
            }
            
            if ($updated) {
                saveProducts($products);
            }
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
} 