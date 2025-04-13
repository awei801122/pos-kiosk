<?php
/**
 * 工具函數文件
 */

/**
 * 生成訂單號碼
 * @return string
 */
function generateOrderNumber() {
    return 'ORD' . date('Ymd') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * 格式化金額
 * @param float $amount
 * @return string
 */
function formatMoney($amount) {
    return 'NT$ ' . number_format($amount, 2);
}

/**
 * 獲取訂單狀態名稱
 * @param string $status
 * @return string
 */
function getStatusName($status) {
    $statuses = [
        'pending' => '待處理',
        'confirmed' => '已確認',
        'preparing' => '準備中',
        'ready' => '已完成',
        'delivered' => '已送達',
        'cancelled' => '已取消'
    ];
    return $statuses[$status] ?? $status;
}

/**
 * 檢查庫存是否足夠
 * @param string $itemId
 * @param int $quantity
 * @return bool
 */
function checkStock($itemId, $quantity = 1) {
    global $db;
    
    $stmt = $db->prepare("SELECT stock FROM menu_items WHERE id = ?");
    $stmt->execute([$itemId]);
    $stock = $stmt->fetchColumn();
    
    return $stock >= $quantity;
}

/**
 * 更新庫存
 * @param string $itemId
 * @param int $quantity
 * @return bool
 */
function updateStock($itemId, $quantity) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            UPDATE menu_items 
            SET stock = stock - ? 
            WHERE id = ? AND stock >= ?
        ");
        return $stmt->execute([$quantity, $itemId, $quantity]);
    } catch (PDOException $e) {
        error_log("更新庫存失敗: " . $e->getMessage());
        return false;
    }
}

/**
 * 記錄系統日誌
 * @param string $level
 * @param string $message
 * @param array $context
 */
function logSystem($level, $message, $context = []) {
    $logFile = __DIR__ . '/../logs/system.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message";
    
    if (!empty($context)) {
        $logMessage .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    
    $logMessage .= PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * 發送成功響應
 * @param mixed $data
 */
function sendSuccess($data) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    exit;
}

/**
 * 發送錯誤響應
 * @param string $message
 * @param int $code
 */
function sendError($message, $code = 400) {
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

/**
 * 檢查用戶權限
 * @param string $permission 權限代碼
 * @return bool 是否有權限
 */
function checkPermission($permission) {
    // 檢查是否已登入
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    global $db;
    try {
        // 檢查用戶直接權限
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM user_permissions up
            JOIN permissions p ON up.permission_id = p.id
            WHERE up.user_id = ? AND p.code = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $permission]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }

        // 檢查角色權限
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM users u
            JOIN role_permissions rp ON u.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE u.id = ? AND p.code = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $permission]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        logSystem('error', '檢查權限失敗', ['error' => $e->getMessage()]);
        return false;
    }
}

/**
 * 獲取用戶權限列表
 * @param int $userId 用戶ID
 * @return array 權限列表
 */
function getUserPermissions($userId) {
    global $db;
    try {
        $stmt = $db->prepare("
            SELECT p.code, p.name, p.description
            FROM user_permissions up
            JOIN permissions p ON up.permission_id = p.id
            WHERE up.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logSystem('error', '獲取用戶權限失敗', ['error' => $e->getMessage()]);
        return [];
    }
}

/**
 * 更新用戶權限
 * @param int $userId 用戶ID
 * @param array $permissions 權限代碼列表
 * @return bool 是否成功
 */
function updateUserPermissions($userId, $permissions) {
    global $db;
    try {
        $db->beginTransaction();

        // 刪除現有權限
        $stmt = $db->prepare("DELETE FROM user_permissions WHERE user_id = ?");
        $stmt->execute([$userId]);

        // 添加新權限
        if (!empty($permissions)) {
            $stmt = $db->prepare("
                INSERT INTO user_permissions (user_id, permission_id)
                SELECT ?, p.id FROM permissions p WHERE p.code = ?
            ");
            foreach ($permissions as $permission) {
                $stmt->execute([$userId, $permission]);
            }
        }

        $db->commit();
        return true;
    } catch (PDOException $e) {
        $db->rollBack();
        logSystem('error', '更新用戶權限失敗', [
            'user_id' => $userId,
            'permissions' => $permissions,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * 獲取所有可用權限
 * @return array 權限列表
 */
function getAllPermissions() {
    global $db;
    try {
        $stmt = $db->prepare("SELECT id, code, name, description FROM permissions ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logSystem('error', '獲取權限列表失敗', ['error' => $e->getMessage()]);
        return [];
    }
}

/**
 * 獲取系統設置
 * @param string $key
 * @return mixed
 */
function getSetting($key) {
    global $db;
    
    $stmt = $db->prepare("SELECT value FROM settings WHERE id = ?");
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    
    return $value ? json_decode($value, true) : null;
}

/**
 * 更新系統設置
 * @param string $key
 * @param mixed $value
 * @return bool
 */
function updateSetting($key, $value) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            INSERT INTO settings (id, value, created_at, updated_at)
            VALUES (?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
            value = VALUES(value),
            updated_at = NOW()
        ");
        return $stmt->execute([$key, json_encode($value)]);
    } catch (PDOException $e) {
        error_log("更新設置失敗: " . $e->getMessage());
        return false;
    }
} 