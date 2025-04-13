@echo off
echo 正在啟動訂餐系統客戶端...

:: 設定 Chrome 啟動參數
set CHROME_PARAMS=--kiosk --disable-pinch --overscroll-history-navigation=0 --app=http://localhost:8000/order.html

:: 檢查 Chrome 是否安裝在預設位置
if exist "C:\Program Files\Google\Chrome\Application\chrome.exe" (
    start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" %CHROME_PARAMS%
) else if exist "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe" (
    start "" "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe" %CHROME_PARAMS%
) else (
    echo 找不到 Google Chrome，請確認已安裝
    pause
    exit /b 1
)

echo 客戶端已啟動
exit /b 0 