<?php
try {
    $pdo = new PDO('mysql:host=pos-mvp-db;dbname=pos_db;charset=utf8mb4', 'root', 'root');
    echo "DB Connect Success!";
} catch (PDOException $e) {
    echo "連線失敗：" . $e->getMessage();
}