const { app, BrowserWindow, globalShortcut, ipcMain } = require('electron');
const path = require('path');
const fs = require('fs');

let mainWindow;
let configPath = path.join(__dirname, 'config.json');

ipcMain.handle('load-config', async () => {
  return JSON.parse(fs.readFileSync(configPath, 'utf8'));
});

ipcMain.handle('save-config', async (event, config) => {
  fs.writeFileSync(configPath, JSON.stringify(config, null, 2));
});

function createWindow() {
  mainWindow = new BrowserWindow({
    width: 800,
    height: 600,
    fullscreen: true,
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      contextIsolation: true, // ✅ 啟用 contextIsolation
      nodeIntegration: false, // ✅ 禁用 nodeIntegration
      devTools: true
    }
  });

  loadMainUI();
}

function loadMainUI() {
  const config = JSON.parse(fs.readFileSync(configPath, 'utf8'));
  let serverUrl = config['server_url'];

  // ✅ 自動補上 http:// 前綴
  if (!serverUrl.startsWith("http")) {
    serverUrl = "http://" + serverUrl;
  }

  const targetUrl = `${serverUrl}/index.html`;
  console.log("Loading URL:", targetUrl);
  mainWindow.loadURL(targetUrl);
}

function loadConfigUI() {
  const filePath = path.join(__dirname, 'config-ui.html');
  mainWindow.loadFile(filePath);
}

app.whenReady().then(() => {
  createWindow();

  // F2 切換 IP 設定畫面
  globalShortcut.register('F2', () => {
    loadConfigUI();
  });

  // F3 重新載入主畫面
  globalShortcut.register('F3', () => {
    console.log("F3 pressed - reloading main UI");
    loadMainUI();
  });

  // F12 開啟 DevTools
  globalShortcut.register('F12', () => {
    mainWindow.webContents.openDevTools();
  });
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') app.quit();
});
