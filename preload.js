const { contextBridge } = require('electron');
const fs = require('fs');
const path = require('path');

// 注意！路徑必須修正成 config.json 真實位置
const configPath = path.join(__dirname, 'kiosk', 'ui', 'config.json');

contextBridge.exposeInMainWorld('configAPI', {
  load: () => {
    try {
      return JSON.parse(fs.readFileSync(configPath, 'utf8'));
    } catch (e) {
      return { api_host: "http://127.0.0.1:8080" };
    }
  },
  save: (obj) => fs.writeFileSync(configPath, JSON.stringify(obj, null, 2), 'utf8')
});