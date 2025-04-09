const { contextBridge, ipcRenderer } = require('electron');

// Expose a flag to identify Electron environment
window.electron = true;

contextBridge.exposeInMainWorld('electronAPI', {
  loadConfig: () => ipcRenderer.invoke('load-config'),
  saveConfig: (config) => ipcRenderer.invoke('save-config', config),
  isElectron: true
});