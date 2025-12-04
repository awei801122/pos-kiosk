<?php
// kiosk/api/license-check.php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/LicenseClient.php';

$settingFile = __DIR__ . '/system-setting.json';

if (!file_exists($settingFile)) {
    echo json_encode([
        'ok'      => false,
        'status'  => 'config_missing',
        'message' => '找不到 system-setting.json，無法讀取授權設定。'
    ]);
    exit;
}

$setting    = json_decode(file_get_contents($settingFile), true);
$licenseKey = $setting['license_key'] ?? '';

// 授權伺服器網址（注意：這裡不要加 /api，讓 LicenseClient 去組合 validate.php / activate.php）
// 例如： https://license.yjova.com
$licenseServer = 'https://license.yjova.com';

// ★ 檢查頻率：測試階段 10 分鐘，正式上線改成 7 天
$intervalSeconds = 60;             // 10 * 60
// 之後穩定時改成：$intervalSeconds = 7 * 24 * 60 * 60;

try {
    $client = new LicenseClient($licenseServer, $licenseKey);
    $status = $client->getLicenseStatusCached($intervalSeconds);

    if ($status['status'] === 'valid') {
        echo json_encode([
            'ok'         => true,
            'status'     => 'valid',
            'expires_at' => $status['expires_at'] ?? null,
            'hwid'       => $status['hwid']       ?? null,
            'checked_at' => $status['checked_at'] ?? null,
        ]);
    } else {
        echo json_encode([
            'ok'         => false,
            'status'     => $status['status'],
            'expires_at' => $status['expires_at'] ?? null,
            'message'    => $status['message'] ?? '授權已失效',
            'hwid'       => $status['hwid']       ?? null,
            'checked_at' => $status['checked_at'] ?? null,
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'ok'      => false,
        'status'  => 'error',
        'message' => $e->getMessage(),
    ]);
}