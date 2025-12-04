# POS 點餐系統啟動與測試指南

本指南適用於目前 POS-MVP 專案的開發與測試環境，提供完整啟動 PHP 伺服器、MySQL 服務以及連結各個系統功能頁面。

---

## ✅ 1. 啟動 PHP 內建伺服器

請在專案根目錄下執行以下指令（`kiosk` 資料夾為網站根目錄）：

```
cd ~/pos-mvp
php -S 0.0.0.0:8000 -t kiosk
```

這將啟動 PHP 伺服器，監聽 8000 埠口，並使用 `kiosk/` 為網站根目錄。

---

## ✅ 2. 匯入 MySQL 初始化資料庫

若尚未建立資料庫，請使用以下指令匯入 `init.sql`（假設您使用 root 帳號登入 MySQL）：

```
mysql -u root -p < kiosk/data/init.sql
```

登入後也可以手動執行：

```
mysql -u root -p
```

再於 MySQL shell 中執行：

```sql
SOURCE kiosk/data/init.sql;
```

> 若顯示「Unknown database」，代表 `init.sql` 未正確建立資料庫，請確認 `init.sql` 檔案內容。

---

## ✅ 3. 各個功能頁面連結

請確保主機 IP 為：`192.168.1.105`，以下是完整網址，可複製於瀏覽器打開測試：

- 🔹 點餐畫面（前台）  
  http://192.168.1.105:8000/ui/order.html

- 🔹 後台訂單管理  
  http://192.168.1.105:8000/ui/admin.html

- 🔹 商品管理（未實作或路徑保留）  
  http://192.168.1.105:8000/ui/menu-management.html

- 🔹 銷售報表  
  http://192.168.1.105:8000/ui/sales-report.html

- 🔹 叫號顯示畫面（顧客取餐畫面）  
  http://192.168.1.105:8000/ui/display.html

- 🔹 手動更新圖片腳本（測試用）  
  http://192.168.1.105:8000/api/update-image.php

---

## ✅ 4. 注意事項

- 若伺服器無法開啟，請確認防火牆或網路設定允許 port 8000。
- 建議將 `php` 執行檔加入系統環境變數，確保可於任意目錄執行 `php -S`。
- 可透過 `Ctrl + C` 中斷伺服器。

---

本文件由 ChatGPT 自動生成。
