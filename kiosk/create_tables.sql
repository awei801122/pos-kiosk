-- ==========================================
-- 資料庫初始化（全自動建立，確保 utf8mb4 無亂碼）
-- ==========================================
DROP DATABASE IF EXISTS pos_db;
CREATE DATABASE pos_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pos_db;

-- 強制所有 client/connection/session/database 層級都用 utf8mb4
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET character_set_client = utf8mb4;
SET character_set_connection = utf8mb4;
SET character_set_results = utf8mb4;
SET character_set_database = utf8mb4;
SET character_set_server = utf8mb4;

-- ===========================
-- 1. 分類表 categories
-- ===========================
CREATE TABLE `categories` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '分類 ID，自動遞增主鍵',
  `name` VARCHAR(255) NOT NULL COMMENT '分類名稱',
  `description` TEXT DEFAULT NULL COMMENT '分類描述',
  `display_order` INT DEFAULT 0 COMMENT '顯示順序，數字越小越前面',
  `created_at` DATETIME NOT NULL COMMENT '建立時間',
  `updated_at` DATETIME DEFAULT NULL COMMENT '最後更新時間',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================
-- 2. 菜單表 menu
--    - 單一菜色或商品的基本資料
--    - 透過 section/group 與組合表做更細緻的排版或套餐設定
-- ===========================
CREATE TABLE `menu` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '菜單品項 ID，自動遞增主鍵',
  `name_zh` VARCHAR(255) NOT NULL COMMENT '菜名（中文）',
  `name_en` VARCHAR(255) DEFAULT NULL COMMENT '菜名（英文或其他語系）',
  `slug` VARCHAR(255) DEFAULT NULL COMMENT '唯一代號，方便路由或整合',
  `price` DECIMAL(10,2) NOT NULL COMMENT '售價',
  `image` VARCHAR(255) DEFAULT NULL COMMENT '圖片檔名或 URL',
  `category` VARCHAR(255) DEFAULT NULL COMMENT '舊版分類欄位，仍保留相容',
  `section_id` INT DEFAULT NULL COMMENT '所屬章節（menu_sections.id）',
  `group_id` INT DEFAULT NULL COMMENT '所屬群組（menu_groups.id）',
  `display_order` INT DEFAULT 0 COMMENT '章節內顯示順序',
  `is_featured` TINYINT(1) DEFAULT 0 COMMENT '是否為推薦或冠軍菜色',
  `badge_icon` VARCHAR(64) DEFAULT NULL COMMENT '小圖示或徽章，例如豬/牛/Crown',
  `tags` JSON DEFAULT NULL COMMENT '自由標籤（例如辣度、特色）',
  `stock` INT DEFAULT 0 COMMENT '庫存或可販售數量',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT '是否啟用',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '最後更新時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_menu_slug` (`slug`),
  KEY `idx_menu_section` (`section_id`),
  KEY `idx_menu_group` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================
-- 2-1. 菜單章節（表頭/冊別）menu_sections
--      對應紙本菜單的大章節：嚴選套餐、單點、海鮮、湯底等
-- ===========================
CREATE TABLE `menu_sections` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '章節 ID，自動遞增主鍵',
  `name` VARCHAR(255) NOT NULL COMMENT '章節名稱',
  `subtitle` VARCHAR(255) DEFAULT NULL COMMENT '副標題或簡短描述',
  `description` TEXT DEFAULT NULL COMMENT '章節說明文字（可放消費方式等）',
  `display_order` INT DEFAULT 0 COMMENT '整本菜單顯示順序',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT '是否啟用',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '最後更新時間',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================
-- 2-2. 菜單群組 menu_groups
--      章節內的子分類，例如「豬」「牛」「海鮮」「附餐」
-- ===========================
CREATE TABLE `menu_groups` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '群組 ID，自動遞增主鍵',
  `section_id` INT NOT NULL COMMENT '所屬章節 ID（menu_sections.id）',
  `name` VARCHAR(255) NOT NULL COMMENT '群組名稱',
  `icon` VARCHAR(64) DEFAULT NULL COMMENT '小圖示（豬、牛、魚…）',
  `description` TEXT DEFAULT NULL COMMENT '額外說明或備註',
  `display_order` INT DEFAULT 0 COMMENT '顯示順序',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT '是否啟用',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '最後更新時間',
  PRIMARY KEY (`id`),
  KEY `idx_menu_groups_section` (`section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================
-- 2-3. 菜單群組關聯表 menu_group_items
--      讓既有 menu 資料與群組建立多對一關係
--      若未來需要一品多群，可改成多對多結構
-- ===========================
CREATE TABLE `menu_group_items` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '關聯 ID，自動遞增主鍵',
  `group_id` INT NOT NULL COMMENT '群組 ID',
  `menu_id` INT NOT NULL COMMENT '菜單品項 ID',
  `display_order` INT DEFAULT 0 COMMENT '顯示順序',
  `note` VARCHAR(255) DEFAULT NULL COMMENT '群組顯示用備註，例如「限量」「招牌」',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_group_menu` (`group_id`, `menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================
-- 2-4. 菜單章節文字備註 menu_section_notes
--      用來存放「消費方式」「套餐內容說明」等純文字段落
-- ===========================
CREATE TABLE `menu_section_notes` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '備註 ID，自動遞增主鍵',
  `section_id` INT NOT NULL COMMENT '章節 ID',
  `title` VARCHAR(255) DEFAULT NULL COMMENT '標題',
  `content` TEXT NOT NULL COMMENT '內容文字',
  `display_order` INT DEFAULT 0 COMMENT '顯示順序',
  PRIMARY KEY (`id`),
  KEY `idx_section_notes_section` (`section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================
-- 2-5. 套餐定義 menu_sets
--      每個套餐對應一筆記錄，可與 menu.id 對應或獨立存在
-- ===========================
CREATE TABLE `menu_sets` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '套餐 ID，自動遞增主鍵',
  `menu_id` INT DEFAULT NULL COMMENT '對應 menu 表中的套餐品項（如嚴選套餐）',
  `name` VARCHAR(255) NOT NULL COMMENT '套餐名稱',
  `description` TEXT DEFAULT NULL COMMENT '套餐說明',
  `display_order` INT DEFAULT 0 COMMENT '顯示順序',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT '是否啟用',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '最後更新時間',
  PRIMARY KEY (`id`),
  KEY `idx_menu_sets_menu` (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================
-- 2-6. 套餐選項群組 menu_set_groups
--      例如「主食可選一份」「湯底四選一」
-- ===========================
CREATE TABLE `menu_set_groups` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '選項群組 ID，自動遞增主鍵',
  `set_id` INT NOT NULL COMMENT '套餐 ID',
  `group_name` VARCHAR(255) NOT NULL COMMENT '群組名稱',
  `selection_type` ENUM('single','multi') NOT NULL DEFAULT 'single' COMMENT 'single=單選、multi=多選',
  `min_select` INT DEFAULT 1 COMMENT '至少要選幾項',
  `max_select` INT DEFAULT 1 COMMENT '最多可選幾項（NULL 代表不限）',
  `display_order` INT DEFAULT 0 COMMENT '顯示順序',
  PRIMARY KEY (`id`),
  KEY `idx_set_groups_set` (`set_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================
-- 2-7. 套餐選項明細 menu_set_items
--      列出每個選項群組可被選擇的品項
-- ===========================
CREATE TABLE `menu_set_items` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '選項明細 ID，自動遞增主鍵',
  `set_group_id` INT NOT NULL COMMENT '選項群組 ID',
  `menu_id` INT DEFAULT NULL COMMENT '對應 menu 表的品項，若為自定義文字可為 NULL',
  `custom_name` VARCHAR(255) DEFAULT NULL COMMENT '若不是既有品項，可直接填入名稱',
  `custom_price` DECIMAL(10,2) DEFAULT NULL COMMENT '額外加價（若有）',
  `display_order` INT DEFAULT 0 COMMENT '顯示順序',
  PRIMARY KEY (`id`),
  KEY `idx_set_items_group` (`set_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================
-- 3. 訂單明細表 order_items
-- ===========================
CREATE TABLE `order_items` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '訂單明細 ID，自動遞增主鍵',
  `order_id` INT NOT NULL COMMENT '訂單 ID',
  `menu_id` INT NOT NULL COMMENT '菜單品項 ID',
  `item_name` VARCHAR(255) NOT NULL COMMENT '品項名稱',
  `price` DECIMAL(10,2) NOT NULL COMMENT '單價',
  `quantity` INT NOT NULL DEFAULT 1 COMMENT '數量',
  `total_price` DECIMAL(10,2) NOT NULL COMMENT '總價',
  `note` TEXT DEFAULT NULL COMMENT '備註',
  `category` VARCHAR(255) DEFAULT NULL COMMENT '分類名稱',
  `status` ENUM('pending','preparing','ready','served') NOT NULL DEFAULT 'pending' COMMENT '品項出餐狀態',
  `route_station_id` INT NULL COMMENT 'KDS 廚房工作站 ID',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  INDEX `idx_order_items_status` (`status`),
  INDEX `idx_order_items_route_station` (`route_station_id`)
  -- 如果需要關聯 orders，可加外鍵（依需求）
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================
-- 4. 訂單主表 orders
-- ===========================
CREATE TABLE `orders` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '訂單 ID，自動遞增主鍵',
  `order_number` VARCHAR(20) NOT NULL COMMENT '訂單編號',
  `order_time` DATETIME DEFAULT NULL COMMENT '訂單時間',
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '訂單總金額',
  `status` ENUM('PENDING','PROCESSING','PAID','COMPLETED','CANCELLED') NOT NULL DEFAULT 'PENDING' COMMENT '訂單狀態',
  `payment_method` ENUM('CASH','CARD','MOBILE') NOT NULL DEFAULT 'CASH' COMMENT '付款方式',
  `created_at` DATETIME DEFAULT NULL COMMENT '建立時間',
  `updated_at` DATETIME DEFAULT NULL COMMENT '最後更新時間',
  `call_number` INT DEFAULT NULL COMMENT '叫號號碼',
  `adults` INT NOT NULL DEFAULT 1 COMMENT '大人人數',
  `children` INT NOT NULL DEFAULT 0 COMMENT '小孩人數',
  `type` ENUM('dine_in','takeout','delivery') NOT NULL DEFAULT 'takeout' COMMENT '訂單型態：內用/外帶/外送',
  `table_session_id` INT NULL COMMENT '關聯 table_sessions.id，內用訂單使用',
  `table_code` VARCHAR(20) NULL COMMENT '顯示桌號（快取字串）',
  `extra_charge` INT NOT NULL DEFAULT 0 COMMENT '不足套餐加價總額（元）',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================
-- 5. 用餐區域與桌位（內用桌靠管理）
--      - areas：樓層/區域
--      - tables：桌號、倒數時間、狀態
--      - table_sessions：一次用餐座次（接單後開始倒數）
-- ===========================
CREATE TABLE `areas` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '區域 ID，自動遞增主鍵',
  `name` VARCHAR(50) NOT NULL COMMENT '區域名稱（如A區、2F）',
  `display_order` INT NOT NULL DEFAULT 0 COMMENT '顯示順序',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否啟用',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tables` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '桌位 ID，自動遞增主鍵',
  `area_id` INT NULL COMMENT '所屬區域 ID',
  `code` VARCHAR(32) NOT NULL UNIQUE COMMENT '桌號代碼（如A-12）',
  `seats` INT NOT NULL DEFAULT 2 COMMENT '建議座位數',
  `status` ENUM('VACANT','SEATED','ORDERING','SENT','PREPARING','READY','SERVED','CLEANING') NOT NULL DEFAULT 'VACANT' COMMENT '桌位狀態',
  `duration_min` INT NOT NULL DEFAULT 90 COMMENT '用餐時間（分鐘）',
  `started_at` DATETIME NULL COMMENT '倒數開始時間（接單後）',
  `ends_at` DATETIME NULL COMMENT '倒數結束時間',
  `current_session_id` INT NULL COMMENT '目前進行中座次 ID（table_sessions.id）',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否啟用',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
  PRIMARY KEY (`id`),
  KEY `idx_tables_area` (`area_id`),
  CONSTRAINT `fk_tables_area` FOREIGN KEY (`area_id`) REFERENCES `areas`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `table_sessions` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '座次 ID，自動遞增主鍵',
  `table_id` INT NOT NULL COMMENT '桌位 ID',
  `opened_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '開桌時間',
  `closed_at` DATETIME NULL COMMENT '結束時間（結帳/清潔後）',
  `opened_by` VARCHAR(50) NULL COMMENT '開桌人員',
  `note` VARCHAR(255) NULL COMMENT '備註',
  PRIMARY KEY (`id`),
  KEY `idx_table_sessions_table` (`table_id`),
  CONSTRAINT `fk_table_sessions_table` FOREIGN KEY (`table_id`) REFERENCES `tables`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================
-- 6. 叫號計數（每日從88開始）
--      - key：可擴充不同用途（call_no_takeout 等）
--      - scope_date：以日期分區，確保每日歸零
-- ===========================
CREATE TABLE `counters` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '計數 ID，自動遞增主鍵',
  `key` VARCHAR(50) NOT NULL COMMENT '計數鍵值',
  `value` INT NOT NULL COMMENT '計數值',
  `scope_date` DATE NOT NULL COMMENT '作用日期',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_counters_key_date` (`key`, `scope_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================
-- 7. 廚房顯示系統 KDS（可選，支援彙整同品項製作）
-- ===========================
CREATE TABLE `kds_stations` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '工作站 ID，自動遞增主鍵',
  `name` VARCHAR(50) NOT NULL COMMENT '工作站名稱（熱炒/炸物/飲品…）',
  `printer_ip` VARCHAR(64) NULL COMMENT '此站對應的網路印表機 IP',
  `display_order` INT NOT NULL DEFAULT 0 COMMENT '顯示順序',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否啟用',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `kds_tickets` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '票據 ID，自動遞增主鍵',
  `order_id` INT NOT NULL COMMENT '訂單 ID',
  `station_id` INT NOT NULL COMMENT '工作站 ID',
  `status` ENUM('queued','fired','bumped','remake') NOT NULL DEFAULT 'queued' COMMENT '票據狀態',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '最後更新時間',
  PRIMARY KEY (`id`),
  KEY `idx_kds_tickets_order` (`order_id`),
  KEY `idx_kds_tickets_station` (`station_id`),
  CONSTRAINT `fk_kds_tickets_station` FOREIGN KEY (`station_id`) REFERENCES `kds_stations`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================
-- 8. 螢幕保護素材（客戶可上傳圖片做廣告輪播）
-- ===========================
CREATE TABLE `screensavers` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '素材 ID，自動遞增主鍵',
  `filename` VARCHAR(255) NOT NULL COMMENT '檔名或相對路徑',
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '上傳時間',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================
-- 9. 全域系統設定表 system_settings
--    - 儲存所有全局 key-value 設定，如出單策略、公告、營業時間等
-- ===========================
CREATE TABLE `system_settings` (
  `setting_key` VARCHAR(50) NOT NULL PRIMARY KEY COMMENT '設定名稱',
  `setting_value` TEXT NOT NULL COMMENT '設定值'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 預設出單策略：'front' = 點餐出單
INSERT INTO `system_settings` (`setting_key`, `setting_value`)
  VALUES ('printer_strategy', 'front')
  ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);