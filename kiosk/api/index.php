<?php
/**
 * API 入口點
 * 用於處理 PHP 內建伺服器啟動時的請求路由
 */

// 設定跨域存取控制
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 處理 OPTIONS 請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}

// 取得請求路徑
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// 路由處理 - API 請求
if (strpos($path, '/api/') === 0) {
    // 取得 API 路徑
    $api_path = substr($path, 5); // 移除 "/api/" 前綴

    // 如果路徑以 / 結尾，去除斜線
    if (substr($api_path, -1) === '/') {
        $api_path = substr($api_path, 0, -1);
    }

    // 映射 API 請求到相應的 PHP 文件
    switch ($api_path) {
        case 'menu':
        case 'menu.php':
            require_once __DIR__ . '/menu.php';
            break;
            
        case 'save_order':
        case 'save_order.php':
            require_once __DIR__ . '/save_order.php';
            break;
            
        case 'receipt':
        case 'receipt.php':
            require_once __DIR__ . '/receipt.php';
            break;
            
        case 'get-orders':
        case 'get-orders.php':
            require_once __DIR__ . '/get-orders.php';
            break;
            
        case 'update-order-status':
        case 'update-order-status.php':
            require_once __DIR__ . '/update-order-status.php';
            break;
            
        case 'inventory':
        case 'inventory.php':
            require_once __DIR__ . '/inventory.php';
            break;
            
        case 'update-image':
        case 'update-image.php':
            require_once __DIR__ . '/update-image.php';
            break;
            
        default:
            // API 端點不存在
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'API 端點不存在']);
            break;
    }
    exit();
}

// 檢查是否為靜態檔案
$file_path = __DIR__ . '/..' . $path;
if (file_exists($file_path) && is_file($file_path)) {
    // 獲取檔案副檔名
    $extension = pathinfo($file_path, PATHINFO_EXTENSION);
    
    // 設定適當的 Content-Type
    switch ($extension) {
        case 'html':
            header('Content-Type: text/html; charset=UTF-8');
            break;
        case 'css':
            header('Content-Type: text/css');
            break;
        case 'js':
            header('Content-Type: application/javascript');
            break;
        case 'json':
            header('Content-Type: application/json');
            break;
        case 'png':
            header('Content-Type: image/png');
            break;
        case 'jpg':
        case 'jpeg':
            header('Content-Type: image/jpeg');
            break;
        case 'gif':
            header('Content-Type: image/gif');
            break;
        case 'svg':
            header('Content-Type: image/svg+xml');
            break;
    }
    
    // 輸出檔案內容
    readfile($file_path);
    exit();
}

// 預設導向到前端入口頁面
header('Location: /ui/index.html');
exit();
?> 