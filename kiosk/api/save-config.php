<?php
header('Content-Type: application/json');

// 取得 POST 的原始資料
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['host'])) {
    echo json_encode(['status' => 'error', 'message' => '缺少主機 IP']);
    exit;
}

$configPath = dirname(__DIR__) . '/config.json';
$config = ['host' => $input['host']];

// 儲存至 config.json
file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT));

echo json_encode(['status' => 'success']);
?>