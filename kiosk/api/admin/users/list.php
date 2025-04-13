<?php
/**
 * 獲取用戶列表 API
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/session.php';

// 檢查權限
checkLogin();
checkPermission('users.manage');

// 檢查請求方法
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => '不支援的請求方法']);
    exit;
}

// 獲取參數
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$roleId = isset($_GET['role_id']) ? (int)$_GET['role_id'] : 0;

try {
    // 構建查詢條件
    $where = [];
    $params = [];
    
    if ($search) {
        $where[] = "(u.username LIKE ? OR u.name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($status) {
        $where[] = "u.status = ?";
        $params[] = $status;
    }
    
    if ($roleId) {
        $where[] = "u.role_id = ?";
        $params[] = $roleId;
    }
    
    $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";
    
    // 獲取總記錄數
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM users u
        $whereClause
    ");
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 計算總頁數
    $totalPages = ceil($total / $perPage);
    
    // 獲取用戶列表
    $offset = ($page - 1) * $perPage;
    $stmt = $db->prepare("
        SELECT 
            u.id,
            u.username,
            u.name,
            u.status,
            u.created_at,
            u.updated_at,
            r.name as role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        $whereClause
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $perPage;
    $params[] = $offset;
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'users' => $users,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages
            ]
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 