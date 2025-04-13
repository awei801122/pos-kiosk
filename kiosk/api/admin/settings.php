<?php
/**
 * 系統設置 API
 */

// 設置響應頭
header('Content-Type: application/json');

// 引入必要的文件
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/session.php';

// 檢查登入狀態和權限
checkLogin();
checkPermission('settings.manage');

// 獲取請求方法
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // 獲取系統設置
            $stmt = $db->query("SELECT * FROM settings");
            $settings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['key']] = $row['value'];
            }
            echo json_encode(['success' => true, 'data' => $settings]);
            break;

        case 'POST':
            // 處理不同的操作
            $action = $_GET['action'] ?? '';
            
            switch ($action) {
                case 'backup':
                    // 備份資料庫
                    $backupFile = __DIR__ . '/../../backups/db_backup_' . date('Y-m-d_H-i-s') . '.sql';
                    $backupDir = dirname($backupFile);
                    
                    // 確保備份目錄存在
                    if (!is_dir($backupDir)) {
                        mkdir($backupDir, 0755, true);
                    }
                    
                    // 執行備份命令
                    $command = sprintf(
                        'mysqldump -u%s -p%s %s > %s',
                        DB_USER,
                        DB_PASS,
                        DB_NAME,
                        $backupFile
                    );
                    
                    exec($command, $output, $returnVar);
                    
                    if ($returnVar === 0) {
                        // 更新最後備份時間
                        $db->prepare("UPDATE settings SET value = ? WHERE `key` = 'last_backup'")
                           ->execute([date('Y-m-d H:i:s')]);
                        
                        echo json_encode(['success' => true, 'message' => '備份成功']);
                    } else {
                        throw new Exception('備份失敗');
                    }
                    break;

                case 'clear_cache':
                    // 清除快取
                    $cacheDir = __DIR__ . '/../../cache';
                    if (is_dir($cacheDir)) {
                        array_map('unlink', glob("$cacheDir/*.*"));
                    }
                    echo json_encode(['success' => true, 'message' => '快取已清除']);
                    break;

                case 'reset':
                    // 重置系統
                    $db->beginTransaction();
                    
                    try {
                        // 清除訂單記錄
                        $db->exec("TRUNCATE TABLE orders");
                        $db->exec("TRUNCATE TABLE order_items");
                        
                        // 重置庫存數量
                        $db->exec("UPDATE inventory SET quantity = 0");
                        
                        // 清除系統日誌
                        $db->exec("TRUNCATE TABLE system_logs");
                        
                        $db->commit();
                        echo json_encode(['success' => true, 'message' => '系統已重置']);
                    } catch (Exception $e) {
                        $db->rollBack();
                        throw $e;
                    }
                    break;

                default:
                    // 保存設置
                    $db->beginTransaction();
                    
                    try {
                        foreach ($_POST as $key => $value) {
                            $stmt = $db->prepare("
                                INSERT INTO settings (`key`, `value`) 
                                VALUES (?, ?) 
                                ON DUPLICATE KEY UPDATE `value` = ?
                            ");
                            $stmt->execute([$key, $value, $value]);
                        }
                        
                        $db->commit();
                        echo json_encode(['success' => true, 'message' => '設置已儲存']);
                    } catch (Exception $e) {
                        $db->rollBack();
                        throw $e;
                    }
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => '不支援的請求方法']);
    }
} catch (Exception $e) {
    logSystem('error', '系統設置操作失敗', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 