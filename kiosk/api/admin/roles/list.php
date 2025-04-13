<?php
/**
 * 角色列表 API
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../../../includes/session.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../../../includes/cache.php';

// 檢查權限
checkLogin();
checkPermission('roles.manage');

// 檢查請求方法
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => '無效的請求方法']);
    exit;
}

try {
    // 獲取分頁參數
    $page = max(1, intval($_GET['page'] ?? 1));
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    
    // 獲取搜尋參數
    $search = trim($_GET['search'] ?? '');
    
    // 構建查詢條件
    $where = [];
    $params = [];
    
    if (!empty($search)) {
        $where[] = "r.name LIKE ?";
        $params[] = "%{$search}%";
    }
    
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    // 獲取總記錄數
    $countSql = "SELECT COUNT(*) FROM roles r {$whereClause}";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    
    // 獲取角色列表
    $sql = "
        SELECT 
            r.id,
            r.name,
            r.permission_group_id,
            pg.name as permission_group_name,
            r.description,
            r.created_at,
            r.updated_at,
            (SELECT COUNT(*) FROM users WHERE role_id = r.id) as user_count
        FROM roles r
        LEFT JOIN permission_groups pg ON r.permission_group_id = pg.id
        {$whereClause}
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $params[] = $perPage;
    $params[] = $offset;
    $stmt->execute($params);
    
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'roles' => $roles
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 