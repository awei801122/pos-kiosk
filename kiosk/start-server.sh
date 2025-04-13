#!/bin/bash

# 獲取本機 IP 地址
IP=$(ifconfig | grep "inet " | grep -v 127.0.0.1 | awk '{print $2}')

echo "正在啟動訂餐系統伺服器..."
echo "伺服器 IP: $IP"
echo "伺服器埠口: 8000"

# 啟動 PHP 伺服器
php -S 0.0.0.0:8000

# 如果伺服器停止，顯示訊息
echo "伺服器已停止運行" 