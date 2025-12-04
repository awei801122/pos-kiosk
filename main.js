const { app, BrowserWindow, globalShortcut, session } = require('electron');
const path = require('path');

const fs = require('fs');

// config.json 路徑建議放專案根目錄，或 __dirname
const configPath = path.join(__dirname, 'kiosk', 'ui', 'config.json');
let API_HOST = '192.168.1.109';
let API_PORT = '8080';

// 自動讀 config.json（有就覆蓋）
if (fs.existsSync(configPath)) {
  try {
    const config = JSON.parse(fs.readFileSync(configPath, 'utf8'));
    API_HOST = config.API_HOST || API_HOST;
    API_PORT = config.API_PORT || API_PORT;
  } catch (err) {
    console.log('config.json 讀取錯誤，採用預設 IP');
  }
}

function setupInterceptor() {
  session.defaultSession.webRequest.onBeforeRequest({ urls: ['file://*/*'] }, (details, callback) => {
    const url = details.url;
    // 攔截 /img/products/
    const imgProductsMatch = url.match(/file:\/\/.*?\/img\/products\/(.+)$/);
    if (imgProductsMatch) {
      const filename = imgProductsMatch[1];
      const remoteUrl = `http://${API_HOST}:${API_PORT}/ui/img/products/${filename}`;
      callback({ redirectURL: remoteUrl });
      return;
    }
    // 攔截 /uploads/
    const uploadsMatch = url.match(/file:\/\/.*?\/uploads\/(.+)$/);
    if (uploadsMatch) {
      const filename = uploadsMatch[1];
      const remoteUrl = `http://${API_HOST}:${API_PORT}/uploads/${filename}`;
      callback({ redirectURL: remoteUrl });
      return;
    }
    // 攔截 API
    const apiMatch = url.match(/file:\/\/.*?\/api\/(.+)$/);
    if (apiMatch) {
      const apiPath = apiMatch[1];
      const remoteApiUrl = `http://${API_HOST}:${API_PORT}/api/${apiPath}`;
      callback({ redirectURL: remoteApiUrl });
      return;
    }
    callback({});
  });
}

app.whenReady().then(() => {
  setupInterceptor();
  createWindow();
});

function createWindow () {
  const win = new BrowserWindow({
    width: 1080,
    height: 1920,
    resizable: false,
    kiosk: true,
    frame: false,
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true,
      devTools: false,
      preload: path.join(__dirname, 'preload.js')
    }
  });

  win.loadFile(path.join(__dirname, 'kiosk', 'ui', 'order.html'));

  app.whenReady().then(() => {
    globalShortcut.register('F2', () => {
      win.loadFile(path.join(__dirname, 'kiosk', 'ui', 'order.html'));
    });
    globalShortcut.register('F3', () => {
      win.loadFile(path.join(__dirname, 'kiosk', 'ui', 'admin.html'));
    });
    globalShortcut.register('F4', () => {
      win.loadFile(path.join(__dirname, 'kiosk', 'ui', 'config-ui.html'));
    });
  });
}

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') app.quit();
});
app.on('will-quit', () => {
  globalShortcut.unregisterAll();
});