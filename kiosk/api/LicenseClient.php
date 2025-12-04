<?php
/**
 * YJOVA 授權客戶端
 * - 自動產生 / 讀取本機 HWID
 * - 呼叫 /api/validate.php 與 /api/activate.php
 * - 提供快取查詢：getLicenseStatusCached($intervalSeconds)
 */

class LicenseClient
{
    /** @var string 授權伺服器 Base URL，例如 https://license.yjova.com */
    private $serverUrl;

    /** @var string 授權金鑰（從 system-setting.json 讀入） */
    private $licenseKey;

    /** @var string 本機裝置 ID（HWID） */
    private $hwid;

    /** @var string HWID 儲存檔案路徑 */
    private $hwidFile;

    /** @var string 快取檔案路徑 */
    private $cacheFile;

    /**
     * 建構子
     * @param string $serverUrl   例如 https://license.yjova.com
     * @param string $licenseKey  授權金鑰
     */
    public function __construct($serverUrl, $licenseKey)
    {
        $this->serverUrl = rtrim($serverUrl, '/');
        $this->licenseKey = $licenseKey;

        // HWID 與快取檔都放在 ../data 底下，請確保目錄可寫入
        $dataDir = realpath(__DIR__ . '/../data');
        if ($dataDir === false) {
            // 如果沒有 data 目錄就直接用 api 目錄
            $dataDir = __DIR__;
        }

        $this->hwidFile  = $dataDir . '/license_hwid.txt';
        $this->cacheFile = $dataDir . '/license-cache.json';

        $this->hwid = $this->loadOrCreateHwid();
    }

    /**
     * 取得目前這台機器的 HWID（若不存在則建立一個隨機值並寫入檔案）
     */
    private function loadOrCreateHwid()
    {
        if (file_exists($this->hwidFile)) {
            $hwid = trim(@file_get_contents($this->hwidFile));
            if ($hwid !== '') {
                return $hwid;
            }
        }

        // 第一次執行，產生一個隨機 ID
        // 之後要改成抓真實硬體序號（MAC / Disk ID）也可以在這裡調整
        $hwid = 'POS-' . bin2hex(random_bytes(8));
        @file_put_contents($this->hwidFile, $hwid);

        return $hwid;
    }

    /**
     * 對授權伺服器呼叫 API
     *
     * @param string $endpoint ex: 'validate.php' 或 'activate.php'
     * @return array [
     *   'ok'      => bool,
     *   'data'    => array|null,   // JSON decode 後的資料
     *   'raw'     => string|null,  // 原始回應字串
     *   'message' => string        // 錯誤訊息（ok=false 時）
     * ]
     */
    private function callApi($endpoint)
    {
        $url = $this->serverUrl . '/api/' . ltrim($endpoint, '/');

        // 伺服器目前預期：JSON body，欄位為 license_key + hwid
        $payload = [
            'license_key' => $this->licenseKey,
            'hwid'        => $this->hwid,
        ];

        // 優先使用 cURL
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            if ($response === false) {
                $err = curl_error($ch);
                curl_close($ch);
                return [
                    'ok'      => false,
                    'data'    => null,
                    'raw'     => null,
                    'message' => '無法連線授權伺服器：' . $err,
                ];
            }
            curl_close($ch);
        } else {
            // 後備方案：使用 file_get_contents
            $context = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/json\r\n",
                    'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    'timeout' => 10,
                ],
            ]);
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                $err = error_get_last();
                return [
                    'ok'      => false,
                    'data'    => null,
                    'raw'     => null,
                    'message' => '無法連線授權伺服器：' . ($err['message'] ?? '未知錯誤'),
                ];
            }
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return [
                'ok'      => false,
                'data'    => null,
                'raw'     => $response,
                'message' => '授權伺服器回傳非 JSON：' . trim($response),
            ];
        }

        return [
            'ok'      => true,
            'data'    => $data,
            'raw'     => $response,
            'message' => '',
        ];
    }

    /**
     * 直接向伺服器詢問目前授權狀態（不含快取）
     * 會在「未註冊」時自動呼叫 activate.php 嘗試綁定
     *
     * @return array ['status' => ..., 'message' => ...]
     */
    public function getLicenseStatus()
    {
        // 1. 先 validate
        $res = $this->callApi('validate.php');
        if (!$res['ok']) {
            return [
                'status'  => 'error',
                'message' => $res['message'],
            ];
        }

        $data = $res['data'];
        $status  = $data['status']  ?? 'error';
        $message = $data['message'] ?? '';

        // valid / expired / invalid 等情況，直接回傳
        if ($status !== 'unregistered') {
            return [
                'status'  => $status,
                'message' => $message,
            ];
        }

        // 2. 如果是未註冊，嘗試自動啟用
        $actRes = $this->callApi('activate.php');
        if (!$actRes['ok']) {
            return [
                'status'  => 'error',
                'message' => '啟用失敗：' . $actRes['message'],
            ];
        }

        $actData = $actRes['data'];
        $actStatus  = $actData['status']  ?? 'error';
        $actMessage = $actData['message'] ?? '';

        if ($actStatus === 'success' || $actStatus === 'valid') {
            return [
                'status'  => 'valid',
                'message' => $actMessage ?: '啟用成功',
            ];
        }

        return [
            'status'  => 'error',
            'message' => $actMessage ?: '啟用失敗',
        ];
    }

    /**
     * 有快取的授權查詢
     *
     * @param int $intervalSeconds 快取秒數（例如 600 = 10 分鐘）
     * @return array ['status' => ..., 'message' => ..., 'checked_at' => timestamp, 'hwid' => ..., 'license_key' => ...]
     */
    public function getLicenseStatusCached($intervalSeconds)
    {
        $now = time();
        $cache = null;

        if (file_exists($this->cacheFile)) {
            $json = @file_get_contents($this->cacheFile);
            $cache = json_decode($json, true);
        }

        if (is_array($cache)
            && isset($cache['checked_at'], $cache['status'])
            && ($now - (int)$cache['checked_at']) < $intervalSeconds
        ) {
            // 快取仍在有效期內，直接回傳
            return $cache;
        }

        // 重新向伺服器查詢
        $status = $this->getLicenseStatus();

        $dataToSave = [
            'status'      => $status['status']  ?? 'error',
            'message'     => $status['message'] ?? '',
            'checked_at'  => $now,
            'hwid'        => $this->hwid,
            'license_key' => $this->licenseKey,
        ];

        @file_put_contents($this->cacheFile, json_encode($dataToSave, JSON_UNESCAPED_UNICODE));

        return $dataToSave;
    }

    /**
     * 給你在別的地方需要顯示 HWID 時用
     */
    public function getHwid()
    {
        return $this->hwid;
    }
}