<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 設定正確的 Content-Type
$requestUri = $_SERVER["REQUEST_URI"];
$extension = pathinfo($requestUri, PATHINFO_EXTENSION);

// 移除查詢字串和開頭的 /kiosk
$path = parse_url($requestUri, PHP_URL_PATH);
$path = preg_replace('/^\/kiosk\//', '/', $path);
$file = __DIR__ . $path;

// 記錄請求資訊
error_log("Request URI: " . $requestUri);
error_log("Cleaned path: " . $path);
error_log("File path: " . $file);
error_log("Extension: " . $extension);

if ($extension === 'php') {
    // 如果是 PHP 檔案，直接包含它
    if (file_exists($file)) {
        include $file;
        exit;
    }
} else {
    // 為其他檔案類型設定正確的 Content-Type
    switch ($extension) {
        case 'js':
            header('Content-Type: application/javascript; charset=UTF-8');
            break;
        case 'json':
            header('Content-Type: application/json; charset=UTF-8');
            break;
        case 'html':
            header('Content-Type: text/html; charset=UTF-8');
            break;
        case 'ico':
            header('Content-Type: image/x-icon');
            break;
        default:
            error_log("Unknown extension: " . $extension);
            break;
    }

    // 如果檔案存在，直接輸出內容
    if (file_exists($file)) {
        error_log("File exists: " . $file);
        readfile($file);
        exit;
    }
}

// 如果檔案不存在，返回 404
error_log("File not found: " . $file);
http_response_code(404);
echo "File not found: " . htmlspecialchars($path); 