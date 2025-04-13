// 緩存設定
define('CACHE_DIR', __DIR__ . '/../cache');
define('CACHE_EXPIRY', 3600); // 1小時

// 確保緩存目錄存在
if (!file_exists(CACHE_DIR)) {
    mkdir(CACHE_DIR, 0755, true);
}

// 緩存類
class Cache {
    private static $instance = null;
    private $cacheDir;
    
    private function __construct() {
        $this->cacheDir = CACHE_DIR;
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get($key) {
        $file = $this->getCacheFile($key);
        if (!file_exists($file)) {
            return null;
        }
        
        $data = file_get_contents($file);
        $cache = json_decode($data, true);
        
        if ($cache === null || $cache['expiry'] < time()) {
            unlink($file);
            return null;
        }
        
        return $cache['data'];
    }
    
    public function set($key, $data, $expiry = CACHE_EXPIRY) {
        $file = $this->getCacheFile($key);
        $cache = [
            'data' => $data,
            'expiry' => time() + $expiry
        ];
        
        return file_put_contents($file, json_encode($cache));
    }
    
    public function delete($key) {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }
    
    public function clear() {
        $files = glob($this->cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }
    
    private function getCacheFile($key) {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
}

// 數據庫類擴展
class Database {
    private $pdo;
    private $cache;
    private $lastError;
    
    public function __construct($host, $dbname, $username, $password) {
        try {
            $this->pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            $this->cache = Cache::getInstance();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            throw new Exception('數據庫連接失敗: ' . $e->getMessage());
        }
    }
    
    public function query($sql, $params = [], $useCache = false) {
        try {
            // 檢查緩存
            if ($useCache) {
                $cacheKey = md5($sql . serialize($params));
                $cachedResult = $this->cache->get($cacheKey);
                if ($cachedResult !== null) {
                    return $cachedResult;
                }
            }
            
            // 執行查詢
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            
            // 存入緩存
            if ($useCache) {
                $this->cache->set($cacheKey, $result);
            }
            
            return $result;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            throw new Exception('查詢執行失敗: ' . $e->getMessage());
        }
    }
    
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            // 清除相關緩存
            $this->clearRelatedCache($sql);
            
            return $result;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            throw new Exception('執行失敗: ' . $e->getMessage());
        }
    }
    
    public function getLastError() {
        return $this->lastError;
    }
    
    private function clearRelatedCache($sql) {
        // 根據SQL類型清除相關緩存
        if (stripos($sql, 'INSERT') !== false || 
            stripos($sql, 'UPDATE') !== false || 
            stripos($sql, 'DELETE') !== false) {
            $this->cache->clear();
        }
    }
    
    // 創建索引
    public function createIndexes() {
        try {
            // 訂單表索引
            $this->execute("CREATE INDEX IF NOT EXISTS idx_orders_created_at ON orders(created_at)");
            $this->execute("CREATE INDEX IF NOT EXISTS idx_orders_customer_id ON orders(customer_id)");
            $this->execute("CREATE INDEX IF NOT EXISTS idx_orders_type ON orders(type)");
            $this->execute("CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status)");
            
            // 訂單項目表索引
            $this->execute("CREATE INDEX IF NOT EXISTS idx_order_items_order_id ON order_items(order_id)");
            $this->execute("CREATE INDEX IF NOT EXISTS idx_order_items_product_id ON order_items(product_id)");
            
            // 客戶表索引
            $this->execute("CREATE INDEX IF NOT EXISTS idx_customers_email ON customers(email)");
            $this->execute("CREATE INDEX IF NOT EXISTS idx_customers_phone ON customers(phone)");
            
            // 商品表索引
            $this->execute("CREATE INDEX IF NOT EXISTS idx_products_category ON products(category)");
            $this->execute("CREATE INDEX IF NOT EXISTS idx_products_status ON products(status)");
            
            return true;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            throw new Exception('創建索引失敗: ' . $e->getMessage());
        }
    }
    
    // 優化表
    public function optimizeTables() {
        try {
            $tables = ['orders', 'order_items', 'customers', 'products'];
            foreach ($tables as $table) {
                $this->execute("OPTIMIZE TABLE $table");
            }
            return true;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            throw new Exception('優化表失敗: ' . $e->getMessage());
        }
    }
    
    // 數據驗證
    public function validateData($table, $data) {
        $rules = [
            'orders' => [
                'customer_id' => ['required', 'integer'],
                'type' => ['required', 'in:dine-in,takeout,delivery'],
                'status' => ['required', 'in:pending,confirmed,preparing,ready,delivered,cancelled'],
                'total' => ['required', 'numeric', 'min:0']
            ],
            'order_items' => [
                'order_id' => ['required', 'integer'],
                'product_id' => ['required', 'integer'],
                'quantity' => ['required', 'integer', 'min:1'],
                'price' => ['required', 'numeric', 'min:0']
            ],
            'customers' => [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'phone' => ['required', 'string', 'max:20']
            ],
            'products' => [
                'name' => ['required', 'string', 'max:255'],
                'price' => ['required', 'numeric', 'min:0'],
                'category' => ['required', 'string', 'max:100'],
                'status' => ['required', 'in:active,inactive']
            ]
        ];
        
        if (!isset($rules[$table])) {
            throw new Exception('未知的表名');
        }
        
        $errors = [];
        foreach ($rules[$table] as $field => $validations) {
            foreach ($validations as $validation) {
                switch ($validation) {
                    case 'required':
                        if (!isset($data[$field]) || $data[$field] === '') {
                            $errors[$field][] = '此欄位為必填';
                        }
                        break;
                    case 'integer':
                        if (isset($data[$field]) && !is_numeric($data[$field])) {
                            $errors[$field][] = '必須為整數';
                        }
                        break;
                    case 'numeric':
                        if (isset($data[$field]) && !is_numeric($data[$field])) {
                            $errors[$field][] = '必須為數字';
                        }
                        break;
                    case 'email':
                        if (isset($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = '必須為有效的電子郵件地址';
                        }
                        break;
                    case 'string':
                        if (isset($data[$field]) && !is_string($data[$field])) {
                            $errors[$field][] = '必須為字串';
                        }
                        break;
                    default:
                        if (strpos($validation, 'max:') === 0) {
                            $max = (int)substr($validation, 4);
                            if (isset($data[$field]) && strlen($data[$field]) > $max) {
                                $errors[$field][] = "長度不能超過 $max 個字元";
                            }
                        } elseif (strpos($validation, 'min:') === 0) {
                            $min = (int)substr($validation, 4);
                            if (isset($data[$field]) && $data[$field] < $min) {
                                $errors[$field][] = "不能小於 $min";
                            }
                        } elseif (strpos($validation, 'in:') === 0) {
                            $allowed = explode(',', substr($validation, 3));
                            if (isset($data[$field]) && !in_array($data[$field], $allowed)) {
                                $errors[$field][] = '無效的值';
                            }
                        }
                }
            }
        }
        
        return $errors;
    }
} 