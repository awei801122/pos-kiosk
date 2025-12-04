<?php
// 資料庫連接設定
$db_config = [
    'host' => 'localhost',      // 資料庫主機
    'dbname' => 'pos_db',   // 資料庫名稱
    'user' => 'root',          // 資料庫用戶名
    'pass' => '',              // 資料庫密碼
    'charset' => 'utf8mb4'     // 字符集
];

try {
    // 建立 PDO 連接
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['user'], $db_config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
} catch (PDOException $e) {
    error_log('資料庫連接錯誤: ' . $e->getMessage());
    die('資料庫連接失敗');
}
?>