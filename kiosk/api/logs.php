<?php
// 設置響應類型為 JSON
header('Content-Type: application/json');

// 引入必要的文件
require_once '../includes/db.php';
require_once '../includes/auth.php';

// 處理 CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 處理 OPTIONS 請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 檢查用戶是否已登入
if (!isLoggedIn()) {
    sendError('請先登入', 401);
    exit();
}

// 檢查權限
if (!hasPermission('log.view')) {
    sendError('您沒有查看日誌的權限', 403);
    exit();
}

// 獲取請求方法
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// 處理不同的請求
switch ($action) {
    case 'list':
        if ($method === 'GET') {
            listLogs();
        } else {
            sendError('不支援的請求方法', 405);
        }
        break;
        
    case 'export':
        if ($method === 'GET') {
            exportLogs();
        } else {
            sendError('不支援的請求方法', 405);
        }
        break;
        
    case 'clear':
        if ($method === 'POST') {
            clearLogs();
        } else {
            sendError('不支援的請求方法', 405);
        }
        break;
        
    default:
        sendError('無效的操作', 400);
        break;
}

// 列出日誌
function listLogs() {
    global $db;
    
    // 獲取分頁參數
    $page = intval($_GET['page'] ?? 1);
    $perPage = intval($_GET['per_page'] ?? 20);
    $offset = ($page - 1) * $perPage;
    
    // 獲取過濾參數
    $level = $_GET['level'] ?? 'all';
    $date = $_GET['date'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // 構建查詢條件
    $where = [];
    $params = [];
    
    if ($level !== 'all') {
        $where[] = 'level = ?';
        $params[] = $level;
    }
    
    if ($date) {
        $where[] = 'DATE(timestamp) = ?';
        $params[] = $date;
    }
    
    if ($search) {
        $where[] = '(message LIKE ? OR context LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // 獲取總記錄數
    $countQuery = "SELECT COUNT(*) FROM logs $whereClause";
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    
    // 獲取日誌記錄
    $query = "SELECT * FROM logs $whereClause ORDER BY timestamp DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 計算總頁數
    $totalPages = ceil($total / $perPage);
    
    // 返回結果
    sendSuccess([
        'logs' => $logs,
        'total' => $total,
        'total_pages' => $totalPages,
        'current_page' => $page
    ]);
}

// 導出日誌
function exportLogs() {
    global $db;
    
    // 獲取過濾參數
    $level = $_GET['level'] ?? 'all';
    $date = $_GET['date'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // 構建查詢條件
    $where = [];
    $params = [];
    
    if ($level !== 'all') {
        $where[] = 'level = ?';
        $params[] = $level;
    }
    
    if ($date) {
        $where[] = 'DATE(timestamp) = ?';
        $params[] = $date;
    }
    
    if ($search) {
        $where[] = '(message LIKE ? OR context LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // 獲取日誌記錄
    $query = "SELECT * FROM logs $whereClause ORDER BY timestamp DESC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 設置 CSV 頭部
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=logs_' . date('Y-m-d') . '.csv');
    
    // 輸出 CSV 內容
    $output = fopen('php://output', 'w');
    
    // 寫入標題行
    fputcsv($output, ['時間', '級別', '消息', '上下文']);
    
    // 寫入數據行
    foreach ($logs as $log) {
        fputcsv($output, [
            $log['timestamp'],
            $log['level'],
            $log['message'],
            $log['context']
        ]);
    }
    
    fclose($output);
    exit();
}

// 清空日誌
function clearLogs() {
    global $db;
    
    // 檢查權限
    if (!hasPermission('log.delete')) {
        sendError('您沒有清空日誌的權限', 403);
        return;
    }
    
    try {
        // 清空日誌表
        $query = "TRUNCATE TABLE logs";
        $db->exec($query);
        
        sendSuccess('日誌已清空');
    } catch (PDOException $e) {
        sendError('清空日誌失敗：' . $e->getMessage());
    }
}

// 發送成功響應
function sendSuccess($data = null) {
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    exit();
}

// 發送錯誤響應
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit();
} 