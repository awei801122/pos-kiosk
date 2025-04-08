<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 允許跨域訪問
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 記錄所有請求的詳細資訊到文件
$logFile = __DIR__ . '/debug.log';
file_put_contents($logFile, "\n\n=== New Request ===\n", FILE_APPEND);
file_put_contents($logFile, date('Y-m-d H:i:s') . "\n", FILE_APPEND);

try {
    // 設定正確的 Content-Type
    $requestUri = $_SERVER["REQUEST_URI"];
    $extension = pathinfo($requestUri, PATHINFO_EXTENSION);

    // 記錄所有相關的伺服器變數
    $debug_info = [
        'REQUEST_URI' => $_SERVER['REQUEST_URI'],
        'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'],
        'PHP_SELF' => $_SERVER['PHP_SELF'],
        'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'],
        'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'],
        '__DIR__' => __DIR__,
        'getcwd()' => getcwd(),
        'include_path' => get_include_path()
    ];

    // 記錄到文件
    foreach ($debug_info as $key => $value) {
        file_put_contents($logFile, "$key: $value\n", FILE_APPEND);
    }

    // 移除查詢字串和開頭的 /kiosk
    $path = parse_url($requestUri, PHP_URL_PATH);
    $originalPath = $path;

    // 處理根路徑
    if (empty($path) || $path === '/' || $path === '/kiosk/') {
        $indexFile = __DIR__ . '/index.html';
        file_put_contents($logFile, "Trying to serve index.html from: $indexFile\n", FILE_APPEND);
        if (file_exists($indexFile)) {
            header('Content-Type: text/html; charset=UTF-8');
            readfile($indexFile);
            exit;
        }
    }

    // 移除開頭的 /kiosk
    $path = preg_replace('/^\/kiosk\//', '/', $path);

    // 構建完整的文件路徑
    $file = __DIR__ . $path;
    file_put_contents($logFile, "Looking for file: $file\n", FILE_APPEND);

    // 如果是 PHP 檔案，直接包含它
    if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'php') {
        if (file_exists($file)) {
            file_put_contents($logFile, "Including PHP file: $file\n", FILE_APPEND);
            include $file;
            exit;
        }
    }

    // 處理其他檔案
    if (file_exists($file) && is_file($file)) {
        // 設定正確的 Content-Type
        $mime_types = [
            'js' => 'application/javascript; charset=UTF-8',
            'json' => 'application/json; charset=UTF-8',
            'html' => 'text/html; charset=UTF-8',
            'ico' => 'image/x-icon',
            'css' => 'text/css; charset=UTF-8',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif'
        ];

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (isset($mime_types[$ext])) {
            header('Content-Type: ' . $mime_types[$ext]);
        }

        file_put_contents($logFile, "Serving file: $file\n", FILE_APPEND);
        readfile($file);
        exit;
    } else {
        file_put_contents($logFile, "File not found: $file\n", FILE_APPEND);
        
        // 嘗試在 backend 目錄中查找
        $backendFile = __DIR__ . '/backend' . $path;
        if (file_exists($backendFile) && is_file($backendFile)) {
            file_put_contents($logFile, "Found in backend: $backendFile\n", FILE_APPEND);
            if (strtolower(pathinfo($backendFile, PATHINFO_EXTENSION)) === 'php') {
                include $backendFile;
                exit;
            }
        }
        
        http_response_code(404);
        echo "File not found: " . htmlspecialchars($path);
    }
} catch (Exception $e) {
    file_put_contents($logFile, "Error: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo "Server error: " . htmlspecialchars($e->getMessage());
} 