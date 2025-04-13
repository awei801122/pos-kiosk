<?php
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
        $where[] = "name LIKE ?";
        $params[] = "%{$search}%";
    }
    
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    // 獲取總記錄數
    $countSql = "SELECT COUNT(*) FROM permission_groups {$whereClause}";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    
    // 獲取權限組列表
    $sql = "
        SELECT 
            id,
            name,
            permissions,
            description,
            created_at,
            updated_at
        FROM permission_groups
        {$whereClause}
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $params[] = $perPage;
    $params[] = $offset;
    $stmt->execute($params);
    
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 處理權限數據
    foreach ($groups as &$group) {
        $group['permissions'] = json_decode($group['permissions'], true);
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'groups' => $groups
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 