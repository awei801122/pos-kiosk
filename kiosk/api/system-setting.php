<?php
// 系統全局設定：僅存JSON檔，單一檔案管理
$setting_file = __DIR__ . '/system-setting.json';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET' || ($_GET['action'] ?? '') === 'get') {
    if (file_exists($setting_file)) {
        $data = json_decode(file_get_contents($setting_file), true);
        echo json_encode($data);
    } else {
        // 預設值
        echo json_encode([
            'printer_strategy' => 'front'
        ]);
    }
    exit;
}

// 儲存
$action = $_POST['action'] ?? '';
if ($action === 'save') {
    $printer_strategy = $_POST['printer_strategy'] ?? 'front';
    if (!in_array($printer_strategy, ['front','counter','both'])) {
        echo json_encode(['success'=>false,'msg'=>'出單策略參數錯誤']);
        exit;
    }
    $setting = [
        'printer_strategy' => $printer_strategy
        // 其他全局欄位（未來可擴充）
    ];
    $ok = file_put_contents($setting_file, json_encode($setting, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    echo json_encode(['success'=>$ok!==false]);
    exit;
}

echo json_encode(['success'=>false,'msg'=>'未知操作']);