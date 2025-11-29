
# 開發者協作流程

本文件為 POS 系統專案的開發者協作指南，目標是確保團隊協作一致、高效、安全。

---

## ✅ Git 分支規範

- `main`：正式穩定版，僅從 `develop` 合併，禁止直接提交
- `develop`：開發整合分支，所有功能/修復需合併至此
- `feature/xxx`：功能開發分支（從 develop 建立）
- `bugfix/xxx`：錯誤修正分支（從 develop 建立）
- `hotfix/xxx`：緊急修補（從 main 建立，修後回合 develop）

**範例：**
```bash
git checkout -b feature/menu-edit
```

---

## ✅ 提交訊息格式（Commit Message）

使用 [Conventional Commits](https://www.conventionalcommits.org/) 標準：

```
<type>: <subject>

[body]
```

- `feat`: 新功能
- `fix`: 修 bug
- `docs`: 文件變更
- `style`: 格式修正（非邏輯）
- `refactor`: 重構
- `test`: 增加測試
- `chore`: 其他建置、工具維護項目

**範例：**
```
feat: 新增 POS 前台點餐畫面
fix: 修正後台 queue 管理無法刪除問題
```

---

## ✅ Pull Request 流程

1. 功能完成後 Push 上自己的 `feature/` 分支
2. 在 GitHub 發起 Pull Request → 目標為 `develop`
3. 指定 Reviewer，進行 Code Review
4. 通過後由 Maintainer 合併

---

## ✅ 代碼風格規範

- JavaScript / TypeScript：
  - 使用 `Prettier` 格式化
  - 縮排：2 spaces
  - 命名規則：`camelCase`、`PascalCase`、`UPPER_SNAKE_CASE`
- PHP：
  - 使用 PSR-12 標準（建議搭配 PHP-CS-Fixer）

---

## ✅ 專案啟動流程（開發者）

```bash
# 前端
npm install
npm run dev

# 後端
cd kiosk
php -S 0.0.0.0:8000
```

---

## ✅ 禁止上傳的內容（請加入 .gitignore）

- `node_modules/`
- `dist/` / `build/`
- `*.exe`, `*.dmg`, `*.log`
- `*.sqlite`, `*.zip`, `*.bak`
- `.env`、私密金鑰、憑證

---

## ✅ 團隊約定

- 每日 Push 至 GitHub（保持備份）
- 每週 Review 進度
- PR 必須通過測試 + 1位審查人同意
- 大改動需先開 issue 討論

---

本文件可由 PM 或技術主管依據實際情況擴充。
