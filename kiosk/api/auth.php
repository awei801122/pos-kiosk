<?php
// 設置錯誤報告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('error_log', '../logs/php-error.log');

// 設置響應類型為 JSON
header('Content-Type: application/json; charset=utf-8');

// 引入必要的文件
try {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/auth.php';
} catch (Exception $e) {
    die(json_encode([
        'success' => false,
        'message' => '系統錯誤：' . $e->getMessage()
    ]));
}

// 處理 CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// 處理 OPTIONS 請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 只允許 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('不支援的請求方法', 405);
    exit();
}

// 記錄請求信息
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
error_log("Raw input: " . file_get_contents('php://input'));

// 檢查 Content-Type
if (!isset($_SERVER['CONTENT_TYPE'])) {
    error_log("Content-Type header is missing");
    sendError('請求必須包含 Content-Type', 400);
    exit();
}

// 檢查是否為 JSON 格式
$contentType = strtolower($_SERVER['CONTENT_TYPE']);
error_log("Normalized Content-Type: " . $contentType);
if (strpos($contentType, 'application/json') === false) {
    error_log("Invalid Content-Type: " . $contentType);
    sendError('請求格式必須為 JSON', 400);
    exit();
}

// 獲取請求數據
$input = file_get_contents('php://input');
if (!$input) {
    sendError('未收到請求數據', 400);
    exit();
}

$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    sendError('無效的 JSON 格式：' . json_last_error_msg(), 400);
    exit();
}

// 驗證請求數據
if (!isset($data['username']) || !isset($data['password'])) {
    sendError('請提供用戶名和密碼');
    exit();
}

$username = trim($data['username']);
$password = trim($data['password']);

if (empty($username) || empty($password)) {
    sendError('用戶名和密碼不能為空');
    exit();
}

// 直接從 JSON 文件查詢用戶
try {
    $usersFile = __DIR__ . '/../data/users.json';
    if (!file_exists($usersFile)) {
        sendError('用戶數據文件不存在');
        exit();
    }
    
    $usersData = json_decode(file_get_contents($usersFile), true);
    $user = null;
    
    // 調試信息
    error_log("Attempting login for username: " . $username);
    
    foreach ($usersData['users'] as $u) {
        if ($u['username'] === $username) {
            $user = $u;
            error_log("Found user: " . json_encode($u));
            break;
        }
    }
    
    if ($user) {
        error_log("Verifying password for user: " . $user['username']);
        error_log("Stored hash: " . $user['password']);
        error_log("Password verify result: " . (password_verify($password, $user['password']) ? 'true' : 'false'));
        
        if (password_verify($password, $user['password'])) {
            // 更新最後登入時間
            $user['last_login'] = date('Y-m-d H:i:s');
            foreach ($usersData['users'] as &$u) {
                if ($u['id'] === $user['id']) {
                    $u = $user;
                    break;
                }
            }
            file_put_contents($usersFile, json_encode($usersData, JSON_PRETTY_PRINT));
            
            // 設置會話
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $user['id'];
            
            sendSuccess([
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'name' => $user['name'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            sendError('用戶名或密碼錯誤');
        }
    } else {
        sendError('用戶名或密碼錯誤');
    }
} catch (Exception $e) {
    sendError('系統錯誤：' . $e->getMessage());
}

// 發送成功響應
function sendSuccess($data = null) {
    echo json_encode([
        'success' => true,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 發送錯誤響應
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit();
} 