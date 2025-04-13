<?php
header('Content-Type: application/json');

// 設置時區
date_default_timezone_set('Asia/Taipei');

// 數據文件路徑
$productsFile = __DIR__ . '/../data/products.json';
$uploadDir = __DIR__ . '/../images/products/';

// 確保目錄存在
if (!file_exists(__DIR__ . '/../data')) {
    mkdir(__DIR__ . '/../data', 0755, true);
}
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 初始化數據文件
if (!file_exists($productsFile)) {
    file_put_contents($productsFile, json_encode(['products' => []]));
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
    return uniqid('prod_');
}

// 處理圖片上傳
function handleImageUpload($file) {
    global $uploadDir;
    
    // 檢查文件類型
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('不支援的圖片格式');
    }
    
    // 生成唯一文件名
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $targetPath = $uploadDir . $filename;
    
    // 移動上傳的文件
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('圖片上傳失敗');
    }
    
    return '/kiosk/images/products/' . $filename;
}

// 處理請求
$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        $data = loadProducts();
        
        if ($id) {
            // 獲取單個商品
            $product = null;
            foreach ($data['products'] as $p) {
                if ($p['id'] === $id) {
                    $product = $p;
                    break;
                }
            }
            
            if ($product) {
                echo json_encode($product);
            } else {
                http_response_code(404);
                echo json_encode(['error' => '商品不存在']);
            }
        } else {
            // 獲取所有商品
            echo json_encode($data);
        }
        break;

    case 'POST':
        try {
            $data = loadProducts();
            $product = [
                'id' => generateId(),
                'name' => $_POST['name'],
                'category' => $_POST['category'],
                'price' => (float)$_POST['price'],
                'description' => $_POST['description'] ?? '',
                'status' => $_POST['status'] ?? 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // 處理圖片上傳
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $product['image'] = handleImageUpload($_FILES['image']);
            }
            
            $data['products'][] = $product;
            saveProducts($data);
            
            echo json_encode(['success' => true, 'product' => $product]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'PUT':
        try {
            if (!$id) {
                throw new Exception('缺少商品ID');
            }
            
            $data = loadProducts();
            $found = false;
            
            foreach ($data['products'] as &$product) {
                if ($product['id'] === $id) {
                    // 更新基本信息
                    $product['name'] = $_POST['name'];
                    $product['category'] = $_POST['category'];
                    $product['price'] = (float)$_POST['price'];
                    $product['description'] = $_POST['description'] ?? $product['description'];
                    $product['status'] = $_POST['status'] ?? $product['status'];
                    $product['updated_at'] = date('Y-m-d H:i:s');
                    
                    // 處理圖片上傳
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        // 刪除舊圖片
                        if (isset($product['image'])) {
                            $oldImage = __DIR__ . '/..' . parse_url($product['image'], PHP_URL_PATH);
                            if (file_exists($oldImage)) {
                                unlink($oldImage);
                            }
                        }
                        
                        $product['image'] = handleImageUpload($_FILES['image']);
                    }
                    
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                throw new Exception('商品不存在');
            }
            
            saveProducts($data);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'DELETE':
        try {
            if (!$id) {
                throw new Exception('缺少商品ID');
            }
            
            $data = loadProducts();
            $found = false;
            
            foreach ($data['products'] as $key => $product) {
                if ($product['id'] === $id) {
                    // 刪除商品圖片
                    if (isset($product['image'])) {
                        $imagePath = __DIR__ . '/..' . parse_url($product['image'], PHP_URL_PATH);
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                    }
                    
                    // 移除商品數據
                    unset($data['products'][$key]);
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                throw new Exception('商品不存在');
            }
            
            // 重新索引數組
            $data['products'] = array_values($data['products']);
            saveProducts($data);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
} 