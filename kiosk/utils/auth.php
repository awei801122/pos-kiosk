<?php
// 認證工具函式庫

// 檢查使用者權限
function checkPermission($requiredPermission) {
    // 取得當前使用者
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    // 讀取權限設定
    $permissions = readJsonFile(PERMISSIONS_FILE);
    if (empty($permissions['roles'][$user['role']])) {
        return false;
    }
    
    // 檢查權限
    $rolePermissions = $permissions['roles'][$user['role']];
    return in_array($requiredPermission, $rolePermissions);
}

// 取得當前使用者
function getCurrentUser() {
    $token = getAuthToken();
    if (!$token) {
        return null;
    }
    
    try {
        $payload = verifyToken($token);
        $users = readJsonFile(USERS_FILE);
        
        foreach ($users['users'] as $user) {
            if ($user['id'] === $payload['id']) {
                return $user;
            }
        }
    } catch (Exception $e) {
        return null;
    }
    
    return null;
}

// 取得認證 token
function getAuthToken() {
    // 從請求標頭取得
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        return str_replace('Bearer ', '', $headers['Authorization']);
    }
    
    // 從 POST 資料取得
    if (isset($_POST['token'])) {
        return $_POST['token'];
    }
    
    // 從 GET 參數取得
    if (isset($_GET['token'])) {
        return $_GET['token'];
    }
    
    return null;
}

// 產生密碼雜湊
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// 驗證密碼
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// 產生隨機密碼
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    return $password;
}

// 檢查密碼強度
function checkPasswordStrength($password) {
    $strength = 0;
    
    // 長度檢查
    if (strlen($password) >= 8) {
        $strength++;
    }
    
    // 包含大寫字母
    if (preg_match('/[A-Z]/', $password)) {
        $strength++;
    }
    
    // 包含小寫字母
    if (preg_match('/[a-z]/', $password)) {
        $strength++;
    }
    
    // 包含數字
    if (preg_match('/[0-9]/', $password)) {
        $strength++;
    }
    
    // 包含特殊字符
    if (preg_match('/[^A-Za-z0-9]/', $password)) {
        $strength++;
    }
    
    return $strength;
}

// 檢查是否需要重設密碼
function needPasswordReset($user) {
    if (!isset($user['last_password_change'])) {
        return true;
    }
    
    $lastChange = strtotime($user['last_password_change']);
    $now = time();
    $daysSinceLastChange = floor(($now - $lastChange) / (60 * 60 * 24));
    
    // 如果超過 90 天未變更密碼，需要重設
    return $daysSinceLastChange >= 90;
}

// 更新使用者密碼
function updateUserPassword($userId, $newPassword) {
    $users = readJsonFile(USERS_FILE);
    
    foreach ($users['users'] as &$user) {
        if ($user['id'] === $userId) {
            $user['password'] = hashPassword($newPassword);
            $user['last_password_change'] = date('Y-m-d H:i:s');
            break;
        }
    }
    
    return writeJsonFile(USERS_FILE, $users);
} 