/**
 * 系統配置文件
 */
const CONFIG = {
    // 伺服器設定
    SERVER: {
        // 請將此 IP 改為您的開發電腦的區網 IP
        HOST: 'http://localhost',
        PORT: '8000',
        get BASE_URL() {
            return `${this.HOST}:${this.PORT}`;
        }
    },
    
    // 系統設定
    SYSTEM: {
        // 訂單更新檢查間隔（毫秒）
        ORDER_CHECK_INTERVAL: 3000,
        // 訂單音效設定
        SOUND: {
            enabled: true,
            volume: 0.8,
            files: {
                newOrder: '/assets/sounds/new-order.mp3',
                statusChange: '/assets/sounds/status-change.mp3'
            }
        }
    },
    
    // 本地存儲鍵值
    STORAGE_KEYS: {
        ORDERS: 'orders',
        CART: 'cart',
        SOUND_SETTINGS: 'soundSettings'
    }
};

// 防止意外修改配置
Object.freeze(CONFIG); 