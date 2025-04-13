<?php
require_once __DIR__ . '/api/middleware/auth.php';

// 允許跨域訪問
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 記錄所有請求的詳細資訊到文件
$logFile = __DIR__ . '/logs/router.log';
file_put_contents($logFile, "\n\n=== New Request ===\n", FILE_APPEND);
file_put_contents($logFile, date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// 路由配置
$routes = [
    // 系統API
    '/api/system/check-client' => ['file' => __DIR__ . '/api/system/check-client.php', 'auth' => false],
    
    // 認證相關
    '/api/auth/login' => ['file' => __DIR__ . '/api/auth.php', 'auth' => false],
    '/api/auth/verify' => ['file' => __DIR__ . '/api/auth.php', 'auth' => false],
    
    // 點餐機API
    '/api/kiosk/menu' => ['file' => __DIR__ . '/api/kiosk/menu.php', 'auth' => false],
    '/api/kiosk/order' => ['file' => __DIR__ . '/api/kiosk/order.php', 'auth' => false],
    
    // 後台管理API
    '/api/admin/products' => ['file' => __DIR__ . '/api/admin/products.php', 'auth' => true, 'permission' => 'manage_products'],
    '/api/admin/inventory' => ['file' => __DIR__ . '/api/admin/inventory.php', 'auth' => true, 'permission' => 'manage_inventory'],
    '/api/admin/reports' => ['file' => __DIR__ . '/api/admin/reports.php', 'auth' => true, 'permission' => 'view_reports'],
    '/api/admin/orders' => ['file' => __DIR__ . '/api/admin/orders.php', 'auth' => true, 'permission' => 'process_orders'],
    '/api/admin/users' => ['file' => __DIR__ . '/api/admin/users.php', 'auth' => true, 'permission' => 'manage_users']
];

try {
    // 記錄請求信息
    $debug_info = [
        'REQUEST_URI' => $_SERVER['REQUEST_URI'],
        'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'],
        'PHP_SELF' => $_SERVER['PHP_SELF'],
        'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR']
    ];
    foreach ($debug_info as $key => $value) {
        file_put_contents($logFile, "$key: $value\n", FILE_APPEND);
    }

    // 取得當前請求的路徑
    $request_uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($request_uri, PHP_URL_PATH);
    
    // 檢查是否為API請求
    if (strpos($path, '/api/') === 0) {
        // API請求處理
        $matched_route = null;
        foreach ($routes as $route => $config) {
            if (strpos($path, $route) === 0) {
                $matched_route = $config;
                break;
            }
        }
        
        if ($matched_route) {
            if ($matched_route['auth']) {
                $user = authenticate();
                if (isset($matched_route['permission'])) {
                    checkPermission($matched_route['permission']);
                }
            }
            require_once $matched_route['file'];
        } else {
            http_response_code(404);
            echo json_encode(['message' => '未找到對應的API']);
        }
    } else {
        // 靜態文件處理
        $file = ltrim($path, '/');
        if (empty($file)) {
            $file = 'index.html';
        }
        
        // 檢查文件是否存在
        if (file_exists($file)) {
            // 根據文件類型設置Content-Type
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $content_types = [
                'html' => 'text/html',
                'css' => 'text/css',
                'js' => 'application/javascript',
                'json' => 'application/json',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif'
            ];
            
            if (isset($content_types[$ext])) {
                header('Content-Type: ' . $content_types[$ext]);
            }
            
            readfile($file);
        } else {
            http_response_code(404);
            echo '404 Not Found';
        }
    }
} catch (Exception $e) {
    file_put_contents($logFile, "Error: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['message' => '系統錯誤：' . $e->getMessage()]);
} 