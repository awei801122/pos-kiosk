<?php
/**
 * 登出頁面
 */
require_once __DIR__ . '/../includes/functions.php';

// 記錄登出日誌
if (isset($_SESSION['user_id'])) {
    logSystem('info', '用戶登出', [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username']
    ]);
}

// 清除會話
session_unset();
session_destroy();

// 跳轉到登入頁面
header('Location: /admin/login.php');
exit; 