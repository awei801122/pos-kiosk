<?php
/**
 * 會話管理
 */

// 啟動會話
session_start();

// 設置會話安全選項
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

// 設置會話過期時間（30分鐘）
ini_set('session.gc_maxlifetime', 1800);
ini_set('session.cookie_lifetime', 1800);

// 檢查會話是否過期
function checkSessionExpired() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        // 會話過期，清除會話並跳轉到登入頁面
        session_unset();
        session_destroy();
        header('Location: /admin/login.php?expired=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// 檢查是否已登入
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /admin/login.php');
        exit;
    }
    checkSessionExpired();
}

// 檢查權限
function checkPermission($permission, $permissions = null) {
    // 如果沒有提供權限列表，從會話中獲取
    if ($permissions === null) {
        $permissions = $_SESSION['permissions'] ?? [];
    }
    
    // 管理員擁有所有權限
    if (isset($_SESSION['role_id']) && $_SESSION['role_id'] === 1) {
        return true;
    }
    
    // 檢查權限
    return in_array($permission, $permissions);
}

// 獲取當前用戶ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// 獲取當前用戶名
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

// 獲取當前用戶角色ID
function getCurrentRoleId() {
    return $_SESSION['role_id'] ?? null;
}

// 設置會話變量
function setSession($key, $value) {
    $_SESSION[$key] = $value;
}

// 獲取會話變量
function getSession($key, $default = null) {
    return $_SESSION[$key] ?? $default;
}

// 清除會話變量
function unsetSession($key) {
    unset($_SESSION[$key]);
}

// 清除所有會話變量
function clearSession() {
    session_unset();
}

// 銷毀會話
function destroySession() {
    session_destroy();
} 