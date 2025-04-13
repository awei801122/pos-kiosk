<?php
/**
 * 數據庫連接配置文件
 */

// 數據庫配置
$dbConfig = [
    'host' => 'localhost',
    'dbname' => 'pos_kiosk',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];

try {
    // 創建PDO連接
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    $db = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
} catch (PDOException $e) {
    // 記錄錯誤日誌
    error_log("數據庫連接失敗: " . $e->getMessage());
    
    // 返回錯誤響應
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => '數據庫連接失敗',
        'message' => $e->getMessage()
    ]);
    exit;
} 