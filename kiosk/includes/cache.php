<?php
/**
 * 權限快取機制
 */

// 快取目錄
define('CACHE_DIR', __DIR__ . '/../cache');

// 確保快取目錄存在
if (!file_exists(CACHE_DIR)) {
    mkdir(CACHE_DIR, 0755, true);
}

/**
 * 獲取權限快取
 * @param string $key 快取鍵名
 * @return mixed|null 快取數據或null
 */
function getPermissionCache($key) {
    $cacheFile = CACHE_DIR . '/' . md5($key) . '.cache';
    
    // 檢查快取文件是否存在且未過期
    if (file_exists($cacheFile)) {
        $data = file_get_contents($cacheFile);
        $cache = json_decode($data, true);
        
        // 檢查快取是否過期
        if ($cache && isset($cache['expires']) && $cache['expires'] > time()) {
            return $cache['data'];
        }
        
        // 快取已過期，刪除文件
        unlink($cacheFile);
    }
    
    return null;
}

/**
 * 設置權限快取
 * @param string $key 快取鍵名
 * @param mixed $data 要快取的數據
 * @param int $ttl 快取時間（秒），預設1小時
 * @return bool 是否成功
 */
function setPermissionCache($key, $data, $ttl = 3600) {
    $cacheFile = CACHE_DIR . '/' . md5($key) . '.cache';
    
    $cache = [
        'data' => $data,
        'expires' => time() + $ttl
    ];
    
    return file_put_contents($cacheFile, json_encode($cache)) !== false;
}

/**
 * 清除權限快取
 * @param string|null $key 快取鍵名，為null時清除所有快取
 * @return bool 是否成功
 */
function clearPermissionCache($key = null) {
    if ($key === null) {
        // 清除所有快取
        $files = glob(CACHE_DIR . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }
    
    // 清除指定快取
    $cacheFile = CACHE_DIR . '/' . md5($key) . '.cache';
    if (file_exists($cacheFile)) {
        return unlink($cacheFile);
    }
    
    return true;
}

/**
 * 獲取角色權限快取
 * @param int $roleId 角色ID
 * @return array|null 角色權限或null
 */
function getRolePermissionsCache($roleId) {
    return getPermissionCache("role_permissions_{$roleId}");
}

/**
 * 設置角色權限快取
 * @param int $roleId 角色ID
 * @param array $permissions 權限列表
 * @return bool 是否成功
 */
function setRolePermissionsCache($roleId, $permissions) {
    return setPermissionCache("role_permissions_{$roleId}", $permissions);
}

/**
 * 清除角色權限快取
 * @param int|null $roleId 角色ID，為null時清除所有角色權限快取
 * @return bool 是否成功
 */
function clearRolePermissionsCache($roleId = null) {
    if ($roleId === null) {
        return clearPermissionCache();
    }
    return clearPermissionCache("role_permissions_{$roleId}");
}

/**
 * 獲取權限組快取
 * @param string $groupId 權限組ID
 * @return array|null 權限組數據或null
 */
function getPermissionGroupCache($groupId) {
    return getPermissionCache("permission_group_{$groupId}");
}

/**
 * 設置權限組快取
 * @param string $groupId 權限組ID
 * @param array $data 權限組數據
 * @return bool 是否成功
 */
function setPermissionGroupCache($groupId, $data) {
    return setPermissionCache("permission_group_{$groupId}", $data);
}

/**
 * 清除權限組快取
 * @param string|null $groupId 權限組ID，為null時清除所有權限組快取
 * @return bool 是否成功
 */
function clearPermissionGroupCache($groupId = null) {
    if ($groupId === null) {
        return clearPermissionCache();
    }
    return clearPermissionCache("permission_group_{$groupId}");
} 