-- 創建日誌表
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level VARCHAR(20) NOT NULL COMMENT '日誌級別 (error, info, debug)',
    message TEXT NOT NULL COMMENT '日誌消息',
    context TEXT COMMENT '日誌上下文 (JSON格式)',
    timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '記錄時間',
    INDEX idx_level (level),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系統日誌表';

-- 創建用戶表
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT '用戶名',
    password VARCHAR(255) NOT NULL COMMENT '密碼 (加密後)',
    name VARCHAR(100) NOT NULL COMMENT '姓名',
    email VARCHAR(100) COMMENT '電子郵件',
    phone VARCHAR(20) COMMENT '電話號碼',
    role_id INT NOT NULL COMMENT '角色ID',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active' COMMENT '狀態',
    last_login DATETIME COMMENT '最後登入時間',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '創建時間',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    INDEX idx_username (username),
    INDEX idx_role (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用戶表';

-- 創建角色表
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE COMMENT '角色名稱',
    code VARCHAR(50) NOT NULL UNIQUE COMMENT '角色代碼',
    description TEXT COMMENT '角色描述',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '創建時間',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色表';

-- 創建權限表
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE COMMENT '權限名稱',
    code VARCHAR(50) NOT NULL UNIQUE COMMENT '權限代碼',
    description TEXT COMMENT '權限描述',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '創建時間',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='權限表';

-- 創建角色權限關聯表
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL COMMENT '角色ID',
    permission_id INT NOT NULL COMMENT '權限ID',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '創建時間',
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色權限關聯表';

-- 插入預設角色
INSERT INTO roles (name, code, description) VALUES
('管理員', 'admin', '系統管理員'),
('店長', 'manager', '店鋪管理員'),
('店員', 'staff', '一般店員');

-- 插入預設權限
INSERT INTO permissions (name, code, description) VALUES
('查看日誌', 'log.view', '查看系統日誌'),
('刪除日誌', 'log.delete', '刪除系統日誌'),
('查看用戶', 'user.view', '查看用戶列表'),
('創建用戶', 'user.create', '創建新用戶'),
('更新用戶', 'user.update', '更新用戶信息'),
('刪除用戶', 'user.delete', '刪除用戶'),
('查看角色', 'role.view', '查看角色列表'),
('創建角色', 'role.create', '創建新角色'),
('更新角色', 'role.update', '更新角色信息'),
('刪除角色', 'role.delete', '刪除角色'),
('查看權限', 'permission.view', '查看權限列表'),
('創建權限', 'permission.create', '創建新權限'),
('更新權限', 'permission.update', '更新權限信息'),
('刪除權限', 'permission.delete', '刪除權限');

-- 為管理員角色分配所有權限
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.code = 'admin';

-- 訂單表
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 訂單項目表
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id VARCHAR(20) NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    options JSON,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_item_id (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 菜單項目表
CREATE TABLE IF NOT EXISTS menu_items (
    id VARCHAR(20) PRIMARY KEY,
    category_id VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    options JSON,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    stock INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_category_id (category_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 分類表
CREATE TABLE IF NOT EXISTS categories (
    id VARCHAR(20) PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    image VARCHAR(255),
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 系統設置表
CREATE TABLE IF NOT EXISTS settings (
    id VARCHAR(50) PRIMARY KEY,
    value TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 