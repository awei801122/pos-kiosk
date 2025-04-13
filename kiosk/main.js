const { app, BrowserWindow, ipcMain } = require('electron');
const path = require('path');
const fs = require('fs');

// 保持窗口對象的全局引用
let mainWindow;

// 配置文件路徑
const configPath = path.join(process.cwd(), 'config.json');

// 默認配置
const defaultConfig = {
    SERVER: {
        HOST: 'http://localhost',
        PORT: '8000'
    }
};

// 讀取配置文件
function loadConfig() {
    try {
        if (fs.existsSync(configPath)) {
            const configData = fs.readFileSync(configPath, 'utf8');
            return JSON.parse(configData);
        }
        // 如果配置文件不存在，創建默認配置
        fs.writeFileSync(configPath, JSON.stringify(defaultConfig, null, 2));
        return defaultConfig;
    } catch (error) {
        console.error('讀取配置文件時發生錯誤：', error);
        return defaultConfig;
    }
}

// 保存配置文件
function saveConfig(config) {
    try {
        fs.writeFileSync(configPath, JSON.stringify(config, null, 2));
        return true;
    } catch (error) {
        console.error('保存配置文件時發生錯誤：', error);
        return false;
    }
}

let config = loadConfig();

function createWindow() {
    // 創建瀏覽器窗口
    mainWindow = new BrowserWindow({
        width: 1920,
        height: 1080,
        fullscreen: true,
        webPreferences: {
            nodeIntegration: true,
            contextIsolation: false
        }
    });

    // 載入訂單頁面
    mainWindow.loadURL(`${config.SERVER.HOST}:${config.SERVER.PORT}/order.html`);

    // 禁用右鍵菜單
    mainWindow.webContents.on('context-menu', (e) => {
        e.preventDefault();
    });

    // 允許 F12 開啟開發者工具（僅在開發模式）
    if (process.env.NODE_ENV === 'development') {
        mainWindow.webContents.on('before-input-event', (event, input) => {
            if (input.key === 'F12') {
                mainWindow.webContents.openDevTools();
                event.preventDefault();
            }
        });
    }

    // 當窗口關閉時觸發
    mainWindow.on('closed', () => {
        mainWindow = null;
    });
}

// 當 Electron 完成初始化時觸發
app.whenReady().then(createWindow);

// 當所有窗口關閉時退出應用
app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        app.quit();
    }
});

app.on('activate', () => {
    if (mainWindow === null) {
        createWindow();
    }
});

// 防止多個實例運行
const gotTheLock = app.requestSingleInstanceLock();
if (!gotTheLock) {
    app.quit();
} else {
    app.on('second-instance', () => {
        if (mainWindow) {
            if (mainWindow.isMinimized()) mainWindow.restore();
            mainWindow.focus();
        }
    });
} 