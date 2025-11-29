# Sequel Ace 設定教學

本教學協助你設定 Sequel Ace 以連線至本地或區網內的 MySQL 資料庫。

---

## 🔧 1. 開啟 Sequel Ace 並新增連線

- 選擇左上角「+」或「快速連線」
- 設定連線方式為：`TCP/IP`
- 輸入以下資訊：

| 欄位名稱     | 設定內容                    |
| -------- | ----------------------- |
| Name     | Pos-mvp                 |
| Host     | 127.0.0.1 或 192.168.x.x |
| Username | user                    |
| Password | （留空，或依實際設定）             |
| Port     | 3306                    |
| Database | optional（可不填）           |

---

## 🧪 2. 測試連線

- 點選下方 `Test Connection`
- 出現 `Success` 表示連線正常
- 點 `連線` 開始操作資料庫

---

## 💡 使用建議情境

- 使用 127.0.0.1 連接本機開發資料庫
- 使用 192.168.x.x 可連接區網內其他設備的 MySQL 主機（需允許遠端）
- 若遇到連線失敗，請確認：
  - MySQL 已啟動 (`brew services list`)
  - `bind-address = 0.0.0.0` 已正確設定
  - 防火牆未阻擋 3306 port
