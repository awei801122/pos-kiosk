
# YJOVA POS 授權整合說明

本文件整理目前 POS 系統「授權檢查」相關檔案、資料格式與整合方式，之後若有新增 / 修改授權機制，請盡量依照本文件規格進行。

---

## 1. 授權相關檔案總覽

專案路徑以 `kiosk/` 為基準：

- `kiosk/api/system-setting.json`  
  - 系統設定檔，包含 `license_key` 等基本設定。
- `kiosk/api/LicenseClient.php`  
  - 授權用戶端程式，負責：
    - 產生 / 讀取本機 HWID
    - 呼叫授權伺服器 `validate.php`、`activate.php`
    - 管理授權結果快取
- `kiosk/api/license-check.php`  
  - 提供給前端使用的授權檢查 API。  
  - 目前 `menu_update.html` 會在載入時先呼叫此 API。
- `kiosk/api/menu-crud.php`  
  - 商品管理後端 API（新增 / 編輯 / 刪除 / 套餐設定）。  
  - 檔案開頭已整合「授權檢查」，只有授權通過才會處理各種 `action`。

---

## 2. `system-setting.json` 格式

路徑：`kiosk/api/system-setting.json`  
編碼：UTF-8，合法 JSON（不能有多餘逗號）

範例：

```json
{
  "store_name": "測試門市",
  "timezone": "Asia/Taipei",
  "language": "zh-TW",
  "currency": "TWD",
  "license_key": "1a10eadf3dc095463982c20dbc641d51",
  "printer_strategy": "both"
}
```

說明：

- `license_key` 必填，為後台授權系統產生的授權金鑰。
- 其他欄位可視需求增加，但必須保持合法 JSON 格式。

若：

- 找不到 `system-setting.json`  
- JSON 解析失敗  
- 沒有 `license_key` 欄位或為空字串  

授權檢查 API 會直接回傳錯誤，不進行後續動作。

---

## 3. `LicenseClient.php` 行為規格

### 3.1 建構子

```php
$client = new LicenseClient($serverUrl, $licenseKey);
```

參數：

- `$serverUrl`：授權伺服器 Base URL  
  - 目前固定為：`https://license.yjova.com`
  - 注意：**不要在這裡加 `/api` 或檔名**
- `$licenseKey`：從 `system-setting.json` 讀出的 `license_key` 字串

### 3.2 裝置 ID（HWID）

- 儲存位置：`../data/license_hwid.txt`（相對於 `kiosk/api/`）
- 第一次執行時：
  - 若檔案不存在或內容為空，會自動產生一個隨機 ID，例如：`POS-xxxx...`
  - 寫入 `license_hwid.txt`，之後同一台機器就固定使用這個 HWID
- 每次呼叫授權 API，都會帶上同一個 `hwid`，授權伺服器會把「金鑰 + HWID」綁定在一起。

### 3.3 呼叫授權 API

內部使用 `callApi($endpoint)`，實際會：

- `validate.php`：驗證目前金鑰 + HWID 是否有效
- `activate.php`：在 `unregistered` 狀態時，嘗試把該 HWID 啟用並綁定到金鑰

呼叫路徑為：

- `https://license.yjova.com/validate.php`
- `https://license.yjova.com/activate.php`

請注意：

- 這兩支 API 預期接受 JSON body：
  ```json
  {
    "license_key": "金鑰字串",
    "hwid": "本機 HWID"
  }
  ```
- 回傳內容應為 JSON，至少包含：
  - `status`：`valid` / `unregistered` / `expired` / `error` / …
  - `message`：對應狀態說明（顯示給使用者用）
  - （可選）`expires_at`：到期日

若伺服器回傳非 JSON（例如 HTML 404 頁面），`LicenseClient` 會回：

```php
[
  'status'  => 'error',
  'message' => '授權伺服器回傳非 JSON：...'
]
```

### 3.4 授權流程：`getLicenseStatus()`（無快取）

邏輯：

1. 呼叫 `validate.php`
   - 若 `status === 'valid'` → 直接回傳
   - 若 `status === 'unregistered'` → 進入步驟 2
   - 其他狀態（例如 `expired`、`invalid`）→ 直接回傳
2. 呼叫 `activate.php`
   - 若 `status === 'success'` 或 `valid` → 再呼叫一次 `validate.php`，然後回傳結果
   - 否則 → 回傳 `status = 'error'`，`message = 啟用失敗`

### 3.5 授權快取：`getLicenseStatusCached($intervalSeconds)`

- 快取檔案：`../data/license-cache.json`  
- 欄位內容：

```json
{
  "status": "valid",
  "message": "授權有效",
  "checked_at": 1733300000,
  "hwid": "POS-xxxx",
  "license_key": "1a10e..."
}
```

- 若快取存在且 `現在時間 - checked_at < $intervalSeconds`：
  - 直接回傳快取內容，不會再打授權伺服器
- 超過快取時間或快取壞掉：
  - 呼叫 `getLicenseStatus()` 重新驗證，再更新快取

目前設定：

- 測試階段：`$intervalSeconds = 600`（10 分鐘）
- 未來正式版：可改成 `7 * 24 * 60 * 60`（7 天）

---

## 4. `license-check.php` API 規格

### 4.1 路徑

- `GET /api/license-check.php`
- 由前端（例如 `menu_update.html`）在載入時呼叫。

### 4.2 主要流程

1. 讀取 `system-setting.json`，取得 `license_key`
2. 建立 `LicenseClient($licenseServer, $licenseKey)`
3. 呼叫 `$client->getLicenseStatusCached($intervalSeconds)`
4. 根據結果輸出 JSON

### 4.3 回傳格式

#### 授權有效

```json
{
  "ok": true,
  "status": "valid",
  "expires_at": "2025-12-06",
  "hwid": "POS-XXXX",
  "checked_at": 1733300000
}
```

#### 授權失效或錯誤

```json
{
  "ok": false,
  "status": "expired",        // 或 error / invalid / ...
  "expires_at": "2025-12-06", // 若有則帶出
  "message": "系統授權已到期",
  "hwid": "POS-XXXX",
  "checked_at": 1733300000
}
```

前端的處理邏輯：

- 如果 `ok === true && status === 'valid'` → 放行，繼續載入管理頁
- 否則 → 顯示 `message` + `expires_at`，並鎖定畫面（禁止操作）

---

## 5. 後端 API 授權保護（以 `menu-crud.php` 為例）

`kiosk/api/menu-crud.php` 檔案開頭已加入授權檢查區塊：

```php
header('Content-Type: application/json');
require_once 'db.php';
require_once __DIR__ . '/LicenseClient.php';

try {
    // ===== 授權檢查：商品管理 API 使用快取 =====
    $settingFile = __DIR__ . '/system-setting.json';

    if (!file_exists($settingFile)) {
        throw new Exception('找不到 system-setting.json，無法讀取授權設定');
    }

    $setting    = json_decode(file_get_contents($settingFile), true);
    $licenseKey = $setting['license_key'] ?? '';

    if ($licenseKey === '') {
        throw new Exception('尚未設定授權金鑰，請先在系統設定頁填寫 license_key');
    }

    // 授權伺服器網址
    $licenseServer = 'https://license.yjova.com';

    // 檢查頻率：目前測試階段 10 分鐘，未來可改為 7 天
    $intervalSeconds = 600;

    // 建立授權用戶端並依快取檢查授權
    $client = new LicenseClient($licenseServer, $licenseKey);
    $status = $client->getLicenseStatusCached($intervalSeconds);

    // 若授權不是有效狀態，直接回傳錯誤訊息並中止後續動作
    if (($status['status'] ?? 'error') !== 'valid') {
        $msg = $status['message'] ?? '系統授權已失效';
        echo json_encode([
            'success' => false,
            'msg'     => $msg,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 授權通過後才繼續處理 action
    $action = $_POST['action'] ?? $_GET['action'] ?? 'list';

    // ... 後面才是各種 add / edit / delete / list 的邏輯 ...
```

之後若有其他 API（例如：報表匯出、庫存管理等）要加入授權保護，可以直接複製這一段，調整：

- `intervalSeconds` 需要的快取頻率
- 錯誤訊息文字

---

## 6. 測試與除錯流程建議

1. **確認設定檔**
   - 檢查 `kiosk/api/system-setting.json`：
     - 是否為合法 JSON？
     - 是否有 `license_key` 且與後台顯示的金鑰一致？

2. **測試授權 API**
   - 在瀏覽器或 Postman 開：
     - `http://POS主機:PORT/api/license-check.php`
   - 確認回傳為 JSON：
     - 若看到 HTML（例如 Apache 404 頁面），代表路徑或伺服器設定錯誤。

3. **檢查授權系統後台**
   - 查看 `license_system` 的 `access_logs` 表：
     - 是否有 `validate` / `activate` 記錄？
     - `hwid` 是否為預期值？
     - `status` 是否為 `success` / `valid`？

4. **前端錯誤：`Unexpected token '<'`**
   - 代表前端 `fetch` 預期拿到 JSON，結果拿到 HTML（通常是 PHP Fatal error 或 404 頁面）。
   - 做法：
     - 直接在瀏覽器開那個 API URL（例如 `/api/menu-crud.php?action=list`）
     - 看實際畫面錯誤訊息，再回頭修 PHP。

5. **HWID / 裝置綁定問題**
   - 若手動刪除 `license_hwid.txt`，這台機器會產生新的 HWID，相當於「新裝置」。
   - 可能造成：
     - 金鑰綁定數量已滿（例如 1 / 1）→ 新 HWID 無法啟用。
   - 解法：
     - 在授權後台解除 / 釋放舊 HWID 或增加授權數量。

6. **資料夾權限問題**
   - 若無法寫入：
     - `../data/license_hwid.txt`
     - `../data/license-cache.json`
   - 可能導致：
     - 每次啟動都產生新 HWID
     - 無法使用授權快取，頻繁打授權伺服器
   - 解法：
     - 確認 `kiosk/data/` 或 `kiosk/api/` 上層資料夾有寫入權限。

---

## 7. 未來若要修改授權邏輯時的注意事項

- **不要改動授權伺服器的回傳格式前就動到客戶端**
  - 若要調整 `status` / `message` 格式，請同時更新：
    - `LicenseClient.php` 解析邏輯
    - `license-check.php` 和前端彈窗處理
- **Base URL 一律維持為不含檔名**
  - `https://license.yjova.com`  
  - 檔名一律由 `LicenseClient` 自己拼接（`validate.php`、`activate.php`）
- **金鑰只存在 `system-setting.json` 一個地方**
  - 不要在程式碼硬編（hard-code）金鑰，以免維護困難。
- **任何新 API 要加授權保護時**
  - 優先複製 `menu-crud.php` 的授權檢查區塊
  - 保持相同的錯誤回傳格式：`{"success":false,"msg":"..."}`
  - 這樣前端 UI 處理會比較一致。

---

> 之後如果有授權相關 bug，建議先依照「第 6 章 測試與除錯流程」一步一步確認，再把：
> - `system-setting.json`
> - `/api/license-check.php` 回傳內容
> - 授權後台 access_logs 截圖  
>
> 丟出來對照，就可以依照本文件快速定位問題。
