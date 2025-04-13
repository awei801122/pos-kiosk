<?php
// 設定回應的 Content-Type
header('Content-Type: application/json');

// 引入必要的檔案
require_once '../config/database.php';
require_once '../utils/auth.php';

// 處理跨域請求
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 處理 OPTIONS 請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 取得請求方法
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// 處理不同的請求
switch ($method) {
    case 'GET':
        switch ($action) {
            case 'list':
                listUsers();
                break;
            case 'get':
                getUser();
                break;
            default:
                sendError('無效的操作');
                break;
        }
        break;
    case 'POST':
        switch ($action) {
            case 'create':
                createUser();
                break;
            case 'update':
                updateUser();
                break;
            case 'delete':
                deleteUser();
                break;
            case 'reset-password':
                resetPassword();
                break;
            default:
                sendError('無效的操作');
                break;
        }
        break;
    default:
        sendError('不支援的請求方法');
        break;
}

// 列出所有使用者
function listUsers() {
    // 檢查權限
    if (!checkPermission('user.view')) {
        sendError('權限不足');
        return;
    }
    
    $users = readJsonFile(USERS_FILE);
    $result = [];
    
    foreach ($users['users'] as $user) {
        // 移除敏感資訊
        unset($user['password']);
        $result[] = $user;
    }
    
    sendSuccess($result);
}

// 取得單一使用者
function getUser() {
    // 檢查權限
    if (!checkPermission('user.view')) {
        sendError('權限不足');
        return;
    }
    
    $userId = $_GET['id'] ?? '';
    if (empty($userId)) {
        sendError('請提供使用者 ID');
        return;
    }
    
    $users = readJsonFile(USERS_FILE);
    foreach ($users['users'] as $user) {
        if ($user['id'] === $userId) {
            // 移除敏感資訊
            unset($user['password']);
            sendSuccess($user);
            return;
        }
    }
    
    sendError('使用者不存在');
}

// 建立使用者
function createUser() {
    // 檢查權限
    if (!checkPermission('user.create')) {
        sendError('權限不足');
        return;
    }
    
    // 取得輸入資料
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 驗證必要欄位
    $requiredFields = ['username', 'password', 'name', 'email', 'role'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            sendError("請提供 {$field}");
            return;
        }
    }
    
    // 檢查使用者名稱是否已存在
    $users = readJsonFile(USERS_FILE);
    foreach ($users['users'] as $user) {
        if ($user['username'] === $input['username']) {
            sendError('使用者名稱已存在');
            return;
        }
    }
    
    // 檢查密碼強度
    if (checkPasswordStrength($input['password']) < 3) {
        sendError('密碼強度不足');
        return;
    }
    
    // 建立新使用者
    $newUser = [
        'id' => uniqid(),
        'username' => $input['username'],
        'password' => hashPassword($input['password']),
        'name' => $input['name'],
        'email' => $input['email'],
        'phone' => $input['phone'] ?? '',
        'role' => $input['role'],
        'status' => 'active',
        'last_login' => null,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // 儲存使用者
    $users['users'][] = $newUser;
    if (!writeJsonFile(USERS_FILE, $users)) {
        sendError('建立使用者失敗');
        return;
    }
    
    // 移除敏感資訊
    unset($newUser['password']);
    sendSuccess($newUser);
}

// 更新使用者
function updateUser() {
    // 檢查權限
    if (!checkPermission('user.update')) {
        sendError('權限不足');
        return;
    }
    
    // 取得輸入資料
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 驗證必要欄位
    if (empty($input['id'])) {
        sendError('請提供使用者 ID');
        return;
    }
    
    // 讀取使用者資料
    $users = readJsonFile(USERS_FILE);
    $userIndex = -1;
    
    // 尋找使用者
    foreach ($users['users'] as $index => $user) {
        if ($user['id'] === $input['id']) {
            $userIndex = $index;
            break;
        }
    }
    
    if ($userIndex === -1) {
        sendError('使用者不存在');
        return;
    }
    
    // 更新使用者資料
    $allowedFields = ['name', 'email', 'phone', 'role', 'status'];
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $users['users'][$userIndex][$field] = $input[$field];
        }
    }
    
    // 更新時間戳
    $users['users'][$userIndex]['updated_at'] = date('Y-m-d H:i:s');
    
    // 儲存更新
    if (!writeJsonFile(USERS_FILE, $users)) {
        sendError('更新使用者失敗');
        return;
    }
    
    // 移除敏感資訊
    $updatedUser = $users['users'][$userIndex];
    unset($updatedUser['password']);
    sendSuccess($updatedUser);
}

// 刪除使用者
function deleteUser() {
    // 檢查權限
    if (!checkPermission('user.delete')) {
        sendError('權限不足');
        return;
    }
    
    // 取得使用者 ID
    $userId = $_POST['id'] ?? '';
    if (empty($userId)) {
        sendError('請提供使用者 ID');
        return;
    }
    
    // 讀取使用者資料
    $users = readJsonFile(USERS_FILE);
    $userIndex = -1;
    
    // 尋找使用者
    foreach ($users['users'] as $index => $user) {
        if ($user['id'] === $userId) {
            $userIndex = $index;
            break;
        }
    }
    
    if ($userIndex === -1) {
        sendError('使用者不存在');
        return;
    }
    
    // 刪除使用者
    array_splice($users['users'], $userIndex, 1);
    
    // 儲存更新
    if (!writeJsonFile(USERS_FILE, $users)) {
        sendError('刪除使用者失敗');
        return;
    }
    
    sendSuccess('使用者已刪除');
}

// 重設密碼
function resetPassword() {
    // 檢查權限
    if (!checkPermission('user.update')) {
        sendError('權限不足');
        return;
    }
    
    // 取得輸入資料
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 驗證必要欄位
    if (empty($input['id']) || empty($input['new_password'])) {
        sendError('請提供使用者 ID 和新密碼');
        return;
    }
    
    // 檢查密碼強度
    if (checkPasswordStrength($input['new_password']) < 3) {
        sendError('密碼強度不足');
        return;
    }
    
    // 更新密碼
    if (!updateUserPassword($input['id'], $input['new_password'])) {
        sendError('重設密碼失敗');
        return;
    }
    
    sendSuccess('密碼已重設');
}

// 發送成功回應
function sendSuccess($data) {
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// 發送錯誤回應
function sendError($message) {
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
} 