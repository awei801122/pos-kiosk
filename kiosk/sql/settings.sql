-- 創建系統設置表
CREATE TABLE IF NOT EXISTS `settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `key` varchar(50) NOT NULL,
    `value` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入默認設置
INSERT INTO `settings` (`key`, `value`) VALUES
('system_name', 'POS系統'),
('business_hours_start', '09:00'),
('business_hours_end', '22:00'),
('tax_rate', '5'),
('decimal_places', '2'),
('printer_name', ''),
('print_copies', '1'),
('print_kitchen', '1'),
('print_customer', '1'),
('last_backup', NULL)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`); 