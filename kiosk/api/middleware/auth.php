<?php
// 權限驗證中介層
function authenticate() {
    $headers = getallheaders();
    $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
    
    if (!$token) {
        http_response_code(401);
        echo json_encode(['message' => '未提供 token']);
        exit();
    }
    
    try {
        $payload = json_decode(base64_decode($token), true);
        
        if (!$payload || !isset($payload['user_id']) || !isset($payload['exp'])) {
            throw new Exception('無效的 token');
        }
        
        if (time() > $payload['exp']) {
            throw new Exception('Token 已過期');
        }
        
        // 讀取使用者資料
        $usersFile = __DIR__ . '/../users.json';
        if (!file_exists($usersFile)) {
            throw new Exception('使用者資料不存在');
        }
        
        $users = json_decode(file_get_contents($usersFile), true);
        $user = null;
        
        foreach ($users['users'] as $u) {
            if ($u['id'] === $payload['user_id']) {
                $user = $u;
                break;
            }
        }
        
        if (!$user) {
            throw new Exception('使用者不存在');
        }
        
        // 將使用者資訊存入全域變數
        $GLOBALS['current_user'] = $user;
        
        return $user;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['message' => $e->getMessage()]);
        exit();
    }
}

// 檢查權限
function checkPermission($requiredPermission) {
    $user = $GLOBALS['current_user'] ?? null;
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['message' => '未登入']);
        exit();
    }
    
    // 管理員擁有所有權限
    if ($user['role'] === 'admin') {
        return true;
    }
    
    // 讀取角色權限
    $usersFile = __DIR__ . '/../users.json';
    if (!file_exists($usersFile)) {
        http_response_code(500);
        echo json_encode(['message' => '系統錯誤']);
        exit();
    }
    
    $users = json_decode(file_get_contents($usersFile), true);
    $rolePermissions = $users['roles'][$user['role']]['permissions'] ?? [];
    
    // 檢查是否擁有所需權限
    if (!in_array($requiredPermission, $rolePermissions) && !in_array('all', $rolePermissions)) {
        http_response_code(403);
        echo json_encode(['message' => '權限不足']);
        exit();
    }
    
    return true;
} 