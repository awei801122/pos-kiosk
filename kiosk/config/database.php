<?php
// 資料庫設定檔

// 定義 JSON 檔案路徑
define('DATA_DIR', __DIR__ . '/../data/');

// 定義各資料檔案路徑
define('USERS_FILE', DATA_DIR . 'users.json');
define('PRODUCTS_FILE', DATA_DIR . 'products.json');
define('CATEGORIES_FILE', DATA_DIR . 'categories.json');
define('INVENTORY_FILE', DATA_DIR . 'inventory.json');
define('ORDERS_FILE', DATA_DIR . 'orders.json');
define('SALES_REPORTS_FILE', DATA_DIR . 'sales_reports.json');
define('PERMISSIONS_FILE', DATA_DIR . 'permissions.json');

// 確保資料目錄存在
if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

// 確保各 JSON 檔案存在
$files = [
    USERS_FILE,
    PRODUCTS_FILE,
    CATEGORIES_FILE,
    INVENTORY_FILE,
    ORDERS_FILE,
    SALES_REPORTS_FILE,
    PERMISSIONS_FILE
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        file_put_contents($file, json_encode([
            'users' => [],
            'products' => [],
            'categories' => [],
            'inventory' => [],
            'orders' => [],
            'reports' => [],
            'permissions' => []
        ], JSON_PRETTY_PRINT));
    }
}

// 讀取 JSON 檔案
function readJsonFile($file) {
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?? [];
}

// 寫入 JSON 檔案
function writeJsonFile($file, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT);
    return file_put_contents($file, $json) !== false;
}

// 備份 JSON 檔案
function backupJsonFile($file) {
    if (!file_exists($file)) {
        return false;
    }
    
    $backupDir = DATA_DIR . 'backups/';
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $filename = basename($file);
    $backupFile = $backupDir . $filename . '.' . date('Y-m-d-H-i-s') . '.json';
    
    return copy($file, $backupFile);
} 