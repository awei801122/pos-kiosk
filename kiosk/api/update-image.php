<?php
// 設定 HTTP 回應標頭
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 引入資料庫連線
require_once __DIR__ . '/db.php';

// 定義圖片相關常數
define('UPLOAD_DIR', __DIR__ . '/../ui/img/images/'); // 圖片上傳目錄
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 最大檔案大小 (5MB)
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif']); // 允許的檔案類型

/**
 * 驗證並處理上傳的圖片
 * @param array $file 上傳的檔案資訊
 * @return array 處理結果
 */
function validate_and_process_image($file) {
    // 檢查檔案是否成功上傳
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new RuntimeException('無效的檔案參數');
    }

    // 檢查上傳是否成功
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('檔案超過允許的大小');
        case UPLOAD_ERR_PARTIAL:
            throw new RuntimeException('檔案上傳不完整');
        case UPLOAD_ERR_NO_FILE:
            throw new RuntimeException('沒有檔案被上傳');
        default:
            throw new RuntimeException('未知的錯誤');
    }

    // 檢查檔案大小
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new RuntimeException('檔案大小超過限制');
    }

    // 檢查 MIME 類型
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    if (!in_array($mime_type, ALLOWED_TYPES)) {
        throw new RuntimeException('不支援的檔案類型');
    }

    // 生成安全的檔案名稱
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = sprintf('food-%s.%s', 
        sha1_file($file['tmp_name']), 
        $extension
    );

    // 移動檔案到目標目錄
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
        throw new RuntimeException('無法儲存檔案');
    }

    return [
        'filename' => $filename,
        'path' => 'img/images/' . $filename
    ];
}

/**
 * 更新菜單項目的圖片路徑
 * @param PDO $pdo 資料庫連線
 * @param int $id 菜單項目ID
 * @param string $image_path 圖片路徑
 */
function update_menu_image($pdo, $id, $image_path) {
    $stmt = $pdo->prepare("UPDATE menu SET image = ? WHERE id = ?");
    $stmt->execute([$image_path, $id]);
    
    if ($stmt->rowCount() === 0) {
        throw new RuntimeException('找不到指定的菜單項目');
    }
}

try {
    // 檢查請求方法
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('只允許 POST 請求');
    }

    // 檢查是否有檔案上傳
    if (!isset($_FILES['image'])) {
        throw new RuntimeException('沒有收到圖片檔案');
    }

    // 檢查菜單ID
    $menu_id = filter_input(INPUT_POST, 'menu_id', FILTER_VALIDATE_INT);
    if (!$menu_id) {
        throw new RuntimeException('無效的菜單ID');
    }

    // 處理圖片上傳
    $result = validate_and_process_image($_FILES['image']);
    
    // 更新資料庫中的圖片路徑
    update_menu_image($pdo, $menu_id, $result['path']);

    // 回傳成功訊息
    echo json_encode([
        'status' => 'success',
        'message' => '圖片上傳成功',
        'data' => [
            'image_path' => $result['path']
        ]
    ]);

} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => '伺服器內部錯誤'
    ]);
}
?>