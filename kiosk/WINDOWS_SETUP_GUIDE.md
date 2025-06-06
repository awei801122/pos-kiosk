# Windows 自助點餐系統安裝指南

## 檔案清單

### 必要檔案
1. `自助點餐系統-Setup-1.0.0.exe`
   - 位置：`dist` 資料夾
   - 用途：Windows 系統安裝程式（64位元版本）

### 選擇性檔案（免安裝版本）
1. `win-unpacked` 資料夾
   - 位置：`dist` 資料夾
   - 用途：免安裝版本，可直接執行（64位元版本）

## 安裝步驟

### 方法一：使用安裝程式（推薦）

1. **準備工作**
   - 確保 Windows 系統版本為 Windows 7 64位元或以上版本
   - 確保有管理員權限
   - 確保有足夠的硬碟空間（建議至少 500MB）

2. **安裝步驟**
   - 雙擊執行 `自助點餐系統-Setup-1.0.0.exe`
   - 選擇安裝語言
   - 選擇安裝位置（預設為 `C:\Program Files\自助點餐系統`）
   - 等待安裝完成
   - 選擇是否創建桌面捷徑

3. **初始設定**
   - 找到安裝目錄中的 `config.js` 檔案
   - 使用記事本開啟
   - 修改伺服器設定：
     ```javascript
     SERVER: {
         HOST: 'http://192.168.1.104',  // 改為您的伺服器 IP
         PORT: '8000'
     }
     ```
   - 儲存並關閉檔案

### 方法二：使用免安裝版本

1. **準備工作**
   - 建立一個資料夾（例如：`C:\POS-Kiosk`）
   - 複製 `win-unpacked` 資料夾的所有內容到此資料夾

2. **設定步驟**
   - 在資料夾中找到 `config.js`
   - 修改伺服器設定（同上）
   - 建立桌面捷徑（選擇性）

## 系統要求

1. **硬體需求**
   - 處理器：64位元（x64）處理器
   - 記憶體：至少 4GB RAM
   - 硬碟空間：至少 500MB 可用空間
   - 顯示器：解析度建議 1920x1080 或以上
   - 網路：有線網路連接建議（可使用無線網路）

2. **作業系統**
   - Windows 7 64位元或更新版本
   - Windows 10 64位元（推薦）
   - Windows 11 64位元（支援）

3. **其他要求**
   - 網路連接：必須能夠連接到伺服器（同一區域網路）
   - 防火牆：需允許應用程式網路訪問
   - 顯示設定：建議使用 100% 縮放比例

## 啟動系統

1. **使用安裝版**
   - 使用桌面捷徑
   - 或從開始選單啟動

2. **使用免安裝版**
   - 執行資料夾中的 `自助點餐系統.exe`

## 系統功能

1. **點餐介面**
   - 自動全螢幕顯示
   - 觸控優化界面
   - 即時購物車更新

2. **後台管理**
   - 網址：`http://[伺服器IP]:8000/admin.html`
   - 訂單即時監控
   - 狀態更新管理

3. **銷售報表**
   - 網址：`http://[伺服器IP]:8000/sales-report.html`
   - 銷售數據統計
   - 報表匯出功能

## 注意事項

1. **網路設定**
   - 確保與伺服器在同一個區域網路
   - 防火牆可能需要允許應用程式訪問網路
   - 檢查 8000 端口是否可以訪問

2. **常見問題處理**
   - 如果無法連接伺服器：
     * 檢查網路連接
     * 確認伺服器 IP 設定
     * 確認伺服器是否運行中
   - 如果程式無法啟動：
     * 檢查是否以管理員身份運行
     * 確認安裝是否完整
     * 檢查系統相容性

## 技術支援

如遇到問題，請聯繫系統管理員：
- 電話：[您的支援電話]
- Email：[您的支援信箱]

## 更新記錄

- 版本：1.0.0
- 發布日期：2024/04/12
- 更新內容：
  * 初始版本發布
  * 全螢幕模式優化
  * 觸控支援優化 