<?php
/**
 * 資料庫初始化程式
 * 
 * 用於創建資料表結構和初始資料
 */

// 設定錯誤報告
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 載入資料庫連接
require_once 'db.php';

echo "開始初始化資料庫...\n";

try {
    if (!isset($pdo)) {
        throw new Exception('資料庫連接失敗');
    }
    
    // 讀取 SQL 腳本
    $sql = file_get_contents(__DIR__ . '/create_tables.sql');
    
    if (!$sql) {
        throw new Exception('無法讀取 SQL 文件');
    }
    
    echo "讀取 SQL 腳本成功\n";
    
    // 將腳本拆分為單個查詢並執行
    $queries = explode(';', $sql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        
        if (empty($query)) {
            continue;
        }
        
        $result = $pdo->exec($query);
        
        if ($result === false) {
            $error = $pdo->errorInfo();
            echo "SQL 錯誤: " . $error[2] . "\n";
            echo "Query: " . $query . "\n";
        } else {
            echo "SQL 查詢執行成功\n";
        }
    }
    
    echo "資料表創建完成\n";
    
    // 如果沒有菜單，創建一些基本項目
    $stmt = $pdo->query("SELECT COUNT(*) FROM menu");
    $menuCount = $stmt->fetchColumn();
    
    if ($menuCount == 0) {
        echo "新增示範菜單項目...\n";
        
        // 首先添加菜單分類
        $categories = [
            ['name' => '漢堡', 'description' => '美味多汁的各式漢堡'],
            ['name' => '薯條', 'description' => '酥脆可口的薯條'],
            ['name' => '飲料', 'description' => '清涼解渴的飲品']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
        
        foreach ($categories as $category) {
            $stmt->bindParam(':name', $category['name']);
            $stmt->bindParam(':description', $category['description']);
            $stmt->execute();
        }
        
        echo "菜單分類已創建\n";
        
        // 獲取剛才創建的分類 ID
        $burgerCategoryId = $pdo->lastInsertId() - 2;
        $friesCategoryId = $pdo->lastInsertId() - 1;
        $drinkCategoryId = $pdo->lastInsertId();
        
        // 添加菜單項目
        $menuItems = [
            [
                'name' => '經典漢堡',
                'description' => '牛肉漢堡排、生菜、番茄、起司、特製醬汁',
                'price' => 120.00,
                'category_id' => $burgerCategoryId,
                'image_url' => 'img/food-1.jpg',
                'is_available' => 1,
                'track_inventory' => 1,
                'inventory_count' => 50
            ],
            [
                'name' => '雙層起司漢堡',
                'description' => '雙層牛肉漢堡排、雙層起司、洋蔥、酸黃瓜、特製醬汁',
                'price' => 150.00,
                'category_id' => $burgerCategoryId,
                'image_url' => 'img/food-2.jpg',
                'is_available' => 1,
                'track_inventory' => 1,
                'inventory_count' => 40
            ],
            [
                'name' => '雙層牛肉漢堡',
                'description' => '雙層厚切牛肉漢堡排、生菜、番茄、起司、培根',
                'price' => 180.00,
                'category_id' => $burgerCategoryId,
                'image_url' => 'img/food-3.jpg',
                'is_available' => 1,
                'track_inventory' => 1,
                'inventory_count' => 30
            ],
            [
                'name' => '薯條 (中)',
                'description' => '酥脆可口的炸薯條，搭配番茄醬',
                'price' => 60.00,
                'category_id' => $friesCategoryId,
                'image_url' => 'img/fries.jpg',
                'is_available' => 1,
                'track_inventory' => 1,
                'inventory_count' => 100
            ],
            [
                'name' => '薯條 (大)',
                'description' => '大份量酥脆可口的炸薯條，搭配番茄醬',
                'price' => 80.00,
                'category_id' => $friesCategoryId,
                'image_url' => 'img/fries-large.jpg',
                'is_available' => 1,
                'track_inventory' => 1,
                'inventory_count' => 80
            ],
            [
                'name' => '可樂',
                'description' => '冰涼可口的可樂',
                'price' => 40.00,
                'category_id' => $drinkCategoryId,
                'image_url' => 'img/cola.jpg',
                'is_available' => 1,
                'track_inventory' => 1,
                'inventory_count' => 200
            ],
            [
                'name' => '雪碧',
                'description' => '清爽冰涼的雪碧',
                'price' => 40.00,
                'category_id' => $drinkCategoryId,
                'image_url' => 'img/sprite.jpg',
                'is_available' => 1,
                'track_inventory' => 1,
                'inventory_count' => 200
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO menu (name, description, price, category_id, image_url, is_available, track_inventory, inventory_count)
            VALUES (:name, :description, :price, :category_id, :image_url, :is_available, :track_inventory, :inventory_count)
        ");
        
        foreach ($menuItems as $item) {
            $stmt->execute($item);
        }
        
        echo "菜單項目已創建\n";
    } else {
        echo "菜單已存在，跳過初始化步驟\n";
    }
    
    echo "資料庫初始化完成！\n";
    
} catch (Exception $e) {
    echo "初始化失敗：" . $e->getMessage() . "\n";
    echo "錯誤位置：" . $e->getTraceAsString() . "\n";
    exit(1);
}
?> 