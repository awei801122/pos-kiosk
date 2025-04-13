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
                listPermissions();
                break;
            case 'get':
                getPermission();
                break;
            case 'roles':
                listRoles();
                break;
            case 'role':
                getRole();
                break;
            default:
                sendError('無效的操作');
                break;
        }
        break;
    case 'POST':
        switch ($action) {
            case 'create':
                createPermission();
                break;
            case 'update':
                updatePermission();
                break;
            case 'delete':
                deletePermission();
                break;
            case 'create-role':
                createRole();
                break;
            case 'update-role':
                updateRole();
                break;
            case 'delete-role':
                deleteRole();
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

// 列出所有權限
function listPermissions() {
    // 檢查權限
    if (!checkPermission('permission.view')) {
        sendError('權限不足');
        return;
    }
    
    $permissions = readJsonFile(PERMISSIONS_FILE);
    sendSuccess($permissions['permissions'] ?? []);
}

// 取得單一權限
function getPermission() {
    // 檢查權限
    if (!checkPermission('permission.view')) {
        sendError('權限不足');
        return;
    }
    
    $permissionId = $_GET['id'] ?? '';
    if (empty($permissionId)) {
        sendError('請提供權限 ID');
        return;
    }
    
    $permissions = readJsonFile(PERMISSIONS_FILE);
    foreach ($permissions['permissions'] as $permission) {
        if ($permission['id'] === $permissionId) {
            sendSuccess($permission);
            return;
        }
    }
    
    sendError('權限不存在');
}

// 建立權限
function createPermission() {
    // 檢查權限
    if (!checkPermission('permission.create')) {
        sendError('權限不足');
        return;
    }
    
    // 取得輸入資料
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 驗證必要欄位
    $requiredFields = ['name', 'code', 'description'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            sendError("請提供 {$field}");
            return;
        }
    }
    
    // 檢查權限代碼是否已存在
    $permissions = readJsonFile(PERMISSIONS_FILE);
    foreach ($permissions['permissions'] as $permission) {
        if ($permission['code'] === $input['code']) {
            sendError('權限代碼已存在');
            return;
        }
    }
    
    // 建立新權限
    $newPermission = [
        'id' => uniqid(),
        'name' => $input['name'],
        'code' => $input['code'],
        'description' => $input['description'],
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // 儲存權限
    $permissions['permissions'][] = $newPermission;
    if (!writeJsonFile(PERMISSIONS_FILE, $permissions)) {
        sendError('建立權限失敗');
        return;
    }
    
    sendSuccess($newPermission);
}

// 更新權限
function updatePermission() {
    // 檢查權限
    if (!checkPermission('permission.update')) {
        sendError('權限不足');
        return;
    }
    
    // 取得輸入資料
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 驗證必要欄位
    if (empty($input['id'])) {
        sendError('請提供權限 ID');
        return;
    }
    
    // 讀取權限資料
    $permissions = readJsonFile(PERMISSIONS_FILE);
    $permissionIndex = -1;
    
    // 尋找權限
    foreach ($permissions['permissions'] as $index => $permission) {
        if ($permission['id'] === $input['id']) {
            $permissionIndex = $index;
            break;
        }
    }
    
    if ($permissionIndex === -1) {
        sendError('權限不存在');
        return;
    }
    
    // 更新權限資料
    $allowedFields = ['name', 'code', 'description'];
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $permissions['permissions'][$permissionIndex][$field] = $input[$field];
        }
    }
    
    // 更新時間戳
    $permissions['permissions'][$permissionIndex]['updated_at'] = date('Y-m-d H:i:s');
    
    // 儲存更新
    if (!writeJsonFile(PERMISSIONS_FILE, $permissions)) {
        sendError('更新權限失敗');
        return;
    }
    
    sendSuccess($permissions['permissions'][$permissionIndex]);
}

// 刪除權限
function deletePermission() {
    // 檢查權限
    if (!checkPermission('permission.delete')) {
        sendError('權限不足');
        return;
    }
    
    // 取得權限 ID
    $permissionId = $_POST['id'] ?? '';
    if (empty($permissionId)) {
        sendError('請提供權限 ID');
        return;
    }
    
    // 讀取權限資料
    $permissions = readJsonFile(PERMISSIONS_FILE);
    $permissionIndex = -1;
    
    // 尋找權限
    foreach ($permissions['permissions'] as $index => $permission) {
        if ($permission['id'] === $permissionId) {
            $permissionIndex = $index;
            break;
        }
    }
    
    if ($permissionIndex === -1) {
        sendError('權限不存在');
        return;
    }
    
    // 檢查權限是否被角色使用
    foreach ($permissions['roles'] as $role) {
        if (in_array($permissionId, $role['permissions'])) {
            sendError('無法刪除已被角色使用的權限');
            return;
        }
    }
    
    // 刪除權限
    array_splice($permissions['permissions'], $permissionIndex, 1);
    
    // 儲存更新
    if (!writeJsonFile(PERMISSIONS_FILE, $permissions)) {
        sendError('刪除權限失敗');
        return;
    }
    
    sendSuccess('權限已刪除');
}

// 列出所有角色
function listRoles() {
    // 檢查權限
    if (!checkPermission('role.view')) {
        sendError('權限不足');
        return;
    }
    
    $permissions = readJsonFile(PERMISSIONS_FILE);
    sendSuccess($permissions['roles'] ?? []);
}

// 取得單一角色
function getRole() {
    // 檢查權限
    if (!checkPermission('role.view')) {
        sendError('權限不足');
        return;
    }
    
    $roleId = $_GET['id'] ?? '';
    if (empty($roleId)) {
        sendError('請提供角色 ID');
        return;
    }
    
    $permissions = readJsonFile(PERMISSIONS_FILE);
    foreach ($permissions['roles'] as $role) {
        if ($role['id'] === $roleId) {
            sendSuccess($role);
            return;
        }
    }
    
    sendError('角色不存在');
}

// 建立角色
function createRole() {
    // 檢查權限
    if (!checkPermission('role.create')) {
        sendError('權限不足');
        return;
    }
    
    // 取得輸入資料
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 驗證必要欄位
    $requiredFields = ['name', 'code', 'permissions'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            sendError("請提供 {$field}");
            return;
        }
    }
    
    // 檢查角色代碼是否已存在
    $permissions = readJsonFile(PERMISSIONS_FILE);
    foreach ($permissions['roles'] as $role) {
        if ($role['code'] === $input['code']) {
            sendError('角色代碼已存在');
            return;
        }
    }
    
    // 建立新角色
    $newRole = [
        'id' => uniqid(),
        'name' => $input['name'],
        'code' => $input['code'],
        'permissions' => $input['permissions'],
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // 儲存角色
    $permissions['roles'][] = $newRole;
    if (!writeJsonFile(PERMISSIONS_FILE, $permissions)) {
        sendError('建立角色失敗');
        return;
    }
    
    sendSuccess($newRole);
}

// 更新角色
function updateRole() {
    // 檢查權限
    if (!checkPermission('role.update')) {
        sendError('權限不足');
        return;
    }
    
    // 取得輸入資料
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 驗證必要欄位
    if (empty($input['id'])) {
        sendError('請提供角色 ID');
        return;
    }
    
    // 讀取角色資料
    $permissions = readJsonFile(PERMISSIONS_FILE);
    $roleIndex = -1;
    
    // 尋找角色
    foreach ($permissions['roles'] as $index => $role) {
        if ($role['id'] === $input['id']) {
            $roleIndex = $index;
            break;
        }
    }
    
    if ($roleIndex === -1) {
        sendError('角色不存在');
        return;
    }
    
    // 更新角色資料
    $allowedFields = ['name', 'code', 'permissions'];
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $permissions['roles'][$roleIndex][$field] = $input[$field];
        }
    }
    
    // 更新時間戳
    $permissions['roles'][$roleIndex]['updated_at'] = date('Y-m-d H:i:s');
    
    // 儲存更新
    if (!writeJsonFile(PERMISSIONS_FILE, $permissions)) {
        sendError('更新角色失敗');
        return;
    }
    
    sendSuccess($permissions['roles'][$roleIndex]);
}

// 刪除角色
function deleteRole() {
    // 檢查權限
    if (!checkPermission('role.delete')) {
        sendError('權限不足');
        return;
    }
    
    // 取得角色 ID
    $roleId = $_POST['id'] ?? '';
    if (empty($roleId)) {
        sendError('請提供角色 ID');
        return;
    }
    
    // 讀取角色資料
    $permissions = readJsonFile(PERMISSIONS_FILE);
    $roleIndex = -1;
    
    // 尋找角色
    foreach ($permissions['roles'] as $index => $role) {
        if ($role['id'] === $roleId) {
            $roleIndex = $index;
            break;
        }
    }
    
    if ($roleIndex === -1) {
        sendError('角色不存在');
        return;
    }
    
    // 檢查角色是否被使用者使用
    $users = readJsonFile(USERS_FILE);
    foreach ($users['users'] as $user) {
        if ($user['role'] === $roleId) {
            sendError('無法刪除已被使用者使用的角色');
            return;
        }
    }
    
    // 刪除角色
    array_splice($permissions['roles'], $roleIndex, 1);
    
    // 儲存更新
    if (!writeJsonFile(PERMISSIONS_FILE, $permissions)) {
        sendError('刪除角色失敗');
        return;
    }
    
    sendSuccess('角色已刪除');
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