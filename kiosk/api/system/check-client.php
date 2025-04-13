<?php
// 設置響應頭
header('Content-Type: application/json');

// 載入系統配置
$config = json_decode(file_get_contents(__DIR__ . '/../../config/system.json'), true);

// 獲取客戶端IP
$client_ip = $_SERVER['REMOTE_ADDR'];

// 如果是在開發環境中
if ($client_ip === '127.0.0.1' || $client_ip === '::1') {
    // 檢查是否有特殊的開發模式參數
    $dev_mode = $_GET['mode'] ?? '';
    if ($dev_mode === 'kiosk') {
        echo json_encode(['client_type' => 'kiosk']);
        exit;
    } elseif ($dev_mode === 'pos') {
        echo json_encode(['client_type' => 'pos']);
        exit;
    }
}

// 檢查IP是否在點餐機列表中
if (in_array($client_ip, $config['devices']['kiosk']['allowed_ips'])) {
    echo json_encode(['client_type' => 'kiosk']);
    exit;
}

// 檢查IP是否在POS後台列表中
if (in_array($client_ip, $config['devices']['pos']['allowed_ips'])) {
    echo json_encode(['client_type' => 'pos']);
    exit;
}

// 如果都不匹配，返回錯誤
http_response_code(403);
echo json_encode([
    'error' => true,
    'message' => '未授權的訪問',
    'client_ip' => $client_ip
]); 