<?php
// 啟動會話
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 檢查用戶是否已登入
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// 獲取當前用戶ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// 獲取當前用戶信息
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $usersFile = __DIR__ . '/../data/users.json';
    if (!file_exists($usersFile)) {
        return null;
    }
    
    $users = json_decode(file_get_contents($usersFile), true);
    foreach ($users['users'] as $user) {
        if ($user['id'] === getCurrentUserId()) {
            return $user;
        }
    }
    
    return null;
}

// 檢查用戶是否有指定權限
function hasPermission($permissionCode) {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    // 管理員擁有所有權限
    if ($user['role'] === 'admin') {
        return true;
    }
    
    // 讀取權限配置
    $permissionsFile = __DIR__ . '/../data/permissions.json';
    if (!file_exists($permissionsFile)) {
        return false;
    }
    
    $permissions = json_decode(file_get_contents($permissionsFile), true);
    return in_array($permissionCode, $permissions['roles'][$user['role']] ?? []);
}

// 記錄日誌
function logMessage($level, $message, $context = null) {
    $logFile = __DIR__ . '/../logs/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    $logEntry = "[$timestamp] [$level] $message $contextStr\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// 用戶登入
function login($username, $password) {
    $usersFile = __DIR__ . '/../data/users.json';
    if (!file_exists($usersFile)) {
        return false;
    }
    
    $users = json_decode(file_get_contents($usersFile), true);
    foreach ($users['users'] as &$user) {
        if ($user['username'] === $username && $user['status'] === 'active') {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                
                // 更新最後登入時間
                $user['last_login'] = date('Y-m-d H:i:s');
                file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
                
                // 記錄登入日誌
                logMessage('info', '用戶登入成功', ['username' => $username]);
                
                return true;
            }
        }
    }
    
    // 記錄登入失敗日誌
    logMessage('error', '用戶登入失敗', ['username' => $username]);
    return false;
}

// 用戶登出
function logout() {
    if (isLoggedIn()) {
        $user = getCurrentUser();
        logMessage('info', '用戶登出', ['username' => $user['username']]);
    }
    
    session_destroy();
}

// 獲取用戶權限列表
function getUserPermissions() {
    $user = getCurrentUser();
    if (!$user) {
        return [];
    }
    
    $permissionsFile = __DIR__ . '/../data/permissions.json';
    if (!file_exists($permissionsFile)) {
        return [];
    }
    
    $permissions = json_decode(file_get_contents($permissionsFile), true);
    return $permissions['roles'][$user['role']] ?? [];
} 