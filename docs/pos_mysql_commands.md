# POS 系統常用 MySQL 指令整理

---

## ✅ 一、基本資料庫操作指令

| 功能        | 指令                                       |
| --------- | ---------------------------------------- |
| 顯示目前資料庫   | `SELECT DATABASE();`                     |
| 顯示所有資料庫   | `SHOW DATABASES;`                        |
| 使用某個資料庫   | `USE pos_db;`                            |
| 顯示所有資料表   | `SHOW TABLES;`                           |
| 查看資料表欄位結構 | `DESC menu;` 或 `SHOW COLUMNS FROM menu;` |

---

## ✅ 二、資料查詢（SELECT）

```sql
-- 查詢全部菜單
SELECT * FROM menu;

-- 查詢啟用中的菜單（前台載入菜單用）
SELECT * FROM menu WHERE is_active = 1;

-- 查詢特定訂單
SELECT * FROM orders WHERE order_number = '20250420123456';

-- 查詢某筆訂單的明細
SELECT * FROM order_items WHERE order_id = 12;

-- 查詢叫號資料
SELECT * FROM queue;
```

---

## ✅ 三、資料新增（INSERT）

```sql
-- 新增菜單項目
INSERT INTO menu (name_zh, name_en, price, category, image, is_active)
VALUES ('炸雞腿飯', 'Fried Chicken', 90.00, '主食', 'chicken.jpg', 1);

-- 新增訂單
INSERT INTO orders (order_number, total_amount, status, payment_method, call_number, order_time, created_at)
VALUES ('20250420123456', 180, 'PENDING', 'CASH', 88, NOW(), NOW());

-- 新增訂單明細
INSERT INTO order_items (order_id, menu_id, item_name, price, quantity, total_price)
VALUES (12, 1, '炸雞腿飯', 90.00, 2, 180.00);
```

---

## ✅ 四、資料更新（UPDATE）

```sql
-- 更新訂單狀態
UPDATE orders SET status = 'PROCESSING', updated_at = NOW()
WHERE order_number = '20250420123456';

-- 更新菜單價格
UPDATE menu SET price = 100 WHERE id = 1;

-- 軟刪除菜單（將 is_active 設為 0）
UPDATE menu SET is_active = 0 WHERE id = 1;
```

---

## ✅ 五、資料刪除（DELETE）

```sql
-- 永久刪除一筆菜單（不建議）
DELETE FROM menu WHERE id = 1;

-- 刪除叫號資料（例如取餐完成後）
DELETE FROM queue WHERE call_number = 88;
```

---

## ✅ 六、資料備份與匯入

```bash
# 匯出資料庫 pos_db 到備份檔
mysqldump -u root -p pos_db > pos_db_backup.sql

# 匯入備份檔
mysql -u root -p pos_db < pos_db_backup.sql
```

---

## ✅ 七、資料庫管理補充

```sql
-- 建立使用者
CREATE USER 'kiosk'@'localhost' IDENTIFIED BY 'yourpassword';

-- 賦予權限
GRANT ALL PRIVILEGES ON pos_db.* TO 'kiosk'@'localhost';

-- 查看目前使用者權限
SHOW GRANTS FOR CURRENT_USER;
```