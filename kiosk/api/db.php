<?php
// 這個檔案適用於 Docker 部署（服務名稱：db，帳號/密碼：root）

$db_config = [
    'host' => 'pos-mvp-db',           // Docker Compose 的服務名稱
    'dbname' => 'pos_db',     // 資料庫名稱
    'user' => 'root',         // 帳號
    'pass' => 'root',         // 密碼
    'charset' => 'utf8mb4'
];

try {
    $dsn = "mysql:host={$db_config['host']};port=3306;dbname={$db_config['dbname']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['user'], $db_config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    error_log('資料庫連接錯誤: ' . $e->getMessage());
    die('資料庫連接失敗: ' . $e->getMessage());
}
?>