// 訂單封存管理
const OrderArchive = {
    // 封存當前訂單
    archiveCurrentOrders: function() {
        try {
            const currentOrders = JSON.parse(localStorage.getItem('orders') || '[]');
            if (currentOrders.length === 0) {
                return { success: false, message: '沒有訂單可以封存' };
            }

            // 取得目前時間作為封存識別
            const archiveTime = new Date();
            const archiveId = archiveTime.toISOString().split('T')[0];
            
            // 準備封存資料
            const archiveData = {
                id: archiveId,
                archiveTime: archiveTime.toISOString(),
                orders: currentOrders,
                summary: this.generateSummary(currentOrders)
            };

            // 獲取現有封存
            const archives = JSON.parse(localStorage.getItem('orderArchives') || '[]');
            
            // 添加新封存
            archives.push(archiveData);
            
            // 儲存封存
            localStorage.setItem('orderArchives', JSON.stringify(archives));
            
            // 清空當前訂單
            localStorage.setItem('orders', '[]');
            
            return { 
                success: true, 
                message: '封存完成',
                archiveId: archiveId
            };
        } catch (error) {
            console.error('封存失敗:', error);
            return { 
                success: false, 
                message: '封存過程發生錯誤: ' + error.message 
            };
        }
    },

    // 生成封存摘要
    generateSummary: function(orders) {
        return {
            totalOrders: orders.length,
            totalAmount: orders.reduce((sum, order) => sum + order.totalAmount, 0),
            ordersByStatus: orders.reduce((acc, order) => {
                acc[order.status] = (acc[order.status] || 0) + 1;
                return acc;
            }, {}),
            paymentMethods: orders.reduce((acc, order) => {
                acc[order.paymentMethod] = (acc[order.paymentMethod] || 0) + 1;
                return acc;
            }, {})
        };
    },

    // 獲取指定日期的封存
    getArchiveByDate: function(date) {
        try {
            const archives = JSON.parse(localStorage.getItem('orderArchives') || '[]');
            return archives.find(archive => archive.id === date) || null;
        } catch (error) {
            console.error('獲取封存失敗:', error);
            return null;
        }
    },

    // 獲取所有封存日期
    getArchiveDates: function() {
        try {
            const archives = JSON.parse(localStorage.getItem('orderArchives') || '[]');
            return archives.map(archive => ({
                id: archive.id,
                time: archive.archiveTime,
                totalOrders: archive.summary.totalOrders,
                totalAmount: archive.summary.totalAmount
            }));
        } catch (error) {
            console.error('獲取封存日期失敗:', error);
            return [];
        }
    },

    // 匯出封存資料
    exportArchive: function(archiveId, format = 'json') {
        try {
            const archive = this.getArchiveByDate(archiveId);
            if (!archive) {
                throw new Error('找不到指定的封存資料');
            }

            let exportData;
            let fileName;
            let mimeType;

            switch (format.toLowerCase()) {
                case 'json':
                    exportData = JSON.stringify(archive, null, 2);
                    fileName = `orders_${archiveId}.json`;
                    mimeType = 'application/json';
                    break;
                    
                case 'csv':
                    // 準備 CSV 內容
                    const headers = ['訂單編號', '下單時間', '狀態', '付款方式', '總金額', '商品明細'];
                    const rows = [headers];
                    
                    archive.orders.forEach(order => {
                        const itemDetails = order.items
                            .map(item => `${item.name}x${item.quantity}`)
                            .join('; ');
                        
                        rows.push([
                            order.orderNumber,
                            new Date(order.orderTime).toLocaleString('zh-TW'),
                            order.status || '未處理',
                            order.paymentMethod,
                            order.totalAmount,
                            itemDetails
                        ]);
                    });
                    
                    // 轉換為 CSV 字串
                    exportData = rows
                        .map(row => row.map(cell => `"${cell}"`).join(','))
                        .join('\n');
                    fileName = `orders_${archiveId}.csv`;
                    mimeType = 'text/csv;charset=utf-8';
                    break;
                    
                default:
                    throw new Error('不支援的匯出格式');
            }

            // 創建下載連結
            const blob = new Blob([exportData], { type: mimeType });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = fileName;
            
            // 觸發下載
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);

            return { success: true, message: '匯出成功' };
        } catch (error) {
            console.error('匯出失敗:', error);
            return { 
                success: false, 
                message: '匯出失敗: ' + error.message 
            };
        }
    }
}; 