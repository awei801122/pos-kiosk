-- 權限表
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 用戶權限關聯表
CREATE TABLE IF NOT EXISTS user_permissions (
    user_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, permission_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 角色權限關聯表
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 清空現有數據
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE role_permissions;
TRUNCATE TABLE user_permissions;
TRUNCATE TABLE permissions;
SET FOREIGN_KEY_CHECKS = 1;

-- 插入基本權限
INSERT INTO permissions (code, name, description) VALUES
('dashboard.view', '儀表板查看', '允許查看系統儀表板'),
('orders.manage', '訂單管理', '允許管理訂單'),
('menu.manage', '菜單管理', '允許管理菜單'),
('inventory.manage', '庫存管理', '允許管理庫存'),
('users.manage', '用戶管理', '允許管理用戶'),
('permissions.manage', '權限管理', '允許管理權限'),
('settings.manage', '系統設置', '允許管理系統設置'),
('reports.view', '報表查看', '允許查看系統報表'),
('logs.view', '日誌查看', '允許查看系統日誌');

-- 為管理員角色分配所有權限
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'admin';

-- 為用戶分配權限（基於其角色）
INSERT INTO user_permissions (user_id, permission_id)
SELECT u.id, rp.permission_id
FROM users u
JOIN role_permissions rp ON u.role_id = rp.role_id; 