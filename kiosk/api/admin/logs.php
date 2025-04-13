<?php
/**
 * 日誌查看 API
 */

// 設置響應頭
header('Content-Type: application/json');

// 引入必要的文件
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// 檢查權限
if (!checkPermission('logs.view')) {
    http_response_code(403);
    echo json_encode(['error' => '沒有權限執行此操作']);
    exit;
}

// 獲取請求方法
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method !== 'GET') {
        throw new Exception('只支援 GET 請求方法');
    }

    // 獲取查詢參數
    $type = $_GET['type'] ?? null;        // 日誌類型
    $startDate = $_GET['start_date'] ?? null;  // 開始日期
    $endDate = $_GET['end_date'] ?? null;      // 結束日期
    $userId = $_GET['user_id'] ?? null;        // 用戶ID
    $page = max(1, intval($_GET['page'] ?? 1));  // 頁碼
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));  // 每頁數量
    
    // 構建查詢條件
    $where = [];
    $params = [];
    
    if ($type) {
        $where[] = "type = ?";
        $params[] = $type;
    }
    
    if ($startDate) {
        $where[] = "created_at >= ?";
        $params[] = $startDate . ' 00:00:00';
    }
    
    if ($endDate) {
        $where[] = "created_at <= ?";
        $params[] = $endDate . ' 23:59:59';
    }
    
    if ($userId) {
        $where[] = "user_id = ?";
        $params[] = $userId;
    }
    
    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // 獲取總記錄數
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM system_logs
        $whereClause
    ");
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 計算總頁數
    $totalPages = ceil($total / $limit);
    
    // 獲取日誌數據
    $stmt = $db->prepare("
        SELECT 
            l.*,
            u.username,
            u.name as user_name
        FROM system_logs l
        LEFT JOIN users u ON l.user_id = u.id
        $whereClause
        ORDER BY l.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $offset = ($page - 1) * $limit;
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 記錄操作日誌
    logSystem('info', '查看系統日誌', [
        'type' => $type,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'user_id' => $userId,
        'page' => $page,
        'limit' => $limit,
        'viewer_id' => $_SESSION['user_id']
    ]);
    
    echo json_encode([
        'success' => true,
        'data' => $logs,
        'pagination' => [
            'total' => $total,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'per_page' => $limit
        ]
    ]);
    
} catch (Exception $e) {
    logSystem('error', '日誌操作失敗', ['error' => $e->getMessage()]);
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 