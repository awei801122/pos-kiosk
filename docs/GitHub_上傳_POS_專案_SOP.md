# GitHub 上傳 POS 專案流程 SOP

此流程適用於 POS 系統專案，包含 Git 初始化、上傳、排除不必要檔案、並推送至 GitHub。

---

## ✅ 一、初始化 Git 專案

於你的專案根目錄（例如 `pos-mvp/`）執行以下指令：

```bash
git init
echo "node_modules/
dist/
build/
electron/
.DS_Store
*.exe
*.log
*.sqlite
temp/
tmp/" > .gitignore
git add .
git commit -m "首次提交：初始化 POS 專案"
```

---

## ✅ 二、連接 GitHub 遠端倉庫

請先前往 GitHub 建立一個新的 Repository，然後連接遠端：

```bash
git remote add origin https://github.com/你的帳號/pos-mvp.git
git branch -M main
```

---

## ✅ 三、推送到 GitHub

```bash
git push -u origin main
```

若之前有上傳過，需要強制推送：

```bash
git push -u --force origin main
```

---

## ✅ 四、確認上傳是否成功

請開啟瀏覽器，造訪你的倉庫：

```
https://github.com/awei801122/pos-mvp
```

確認有看到你的檔案（如 `order.html`, `kiosk/api/menu.php`, `.gitignore` 等）

---

## ✅ 五、日後更新檔案再推送

每次修改完請執行：

```bash
git add .
git commit -m "v2,3:結帳，點餐，銷售報表功能mvp完成"
git push
```

---

## ✅ 六、排除錯誤狀況參考

- 若看到 `File too large > 100MB`：
  - 執行：`git rm -r --cached node_modules/` 並重新 commit。
- 若不小心追蹤到錯誤檔案，請參考 `.gitignore` 加入忽略規則。

---

本文件可做為 POS 系統開發者的 Git SOP 使用。
