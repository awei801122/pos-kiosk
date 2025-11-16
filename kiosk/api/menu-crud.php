<?php
header('Content-Type: application/json');
require_once 'db.php';

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? 'list';
    if ($action == 'add') {
        // 新增商品
        $name_zh = $_POST['name_zh'];
        $name_en = $_POST['name_en'];
        $category = $_POST['category'];
        $price = $_POST['price'];
        $stock = $_POST['stock'] ?? null;
        $is_active = $_POST['is_active'];

        // 圖片上傳（新增）
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $imagePath = '';
        if(isset($_FILES['image']) && $_FILES['image']['error']==0 && !empty($_FILES['image']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                echo json_encode(['success'=>false, 'msg'=>'僅允許上傳圖片檔案(jpg/png/webp/gif)']);
                exit;
            }
            $filename = 'food-'.time().'-'.rand(1000,9999).'.'.$ext;
            $target = '../ui/img/products/'.$filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $imagePath = 'ui/img/products/'.$filename;
            } else {
                echo json_encode(['success'=>false, 'msg'=>'圖片上傳失敗']);
                exit;
            }
        }

        $sql = "INSERT INTO menu (name_zh, name_en, category, price, stock, is_active, image, created_at, updated_at)
                VALUES (?,?,?,?,?,?,?,NOW(),NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name_zh, $name_en, $category, $price, $stock, $is_active, $imagePath]);
        echo json_encode(['success'=>true]);
        exit;

    } elseif ($action == 'edit') {
        // 編輯商品
        $id = $_POST['id'];
        $name_zh = $_POST['name_zh'];
        $name_en = $_POST['name_en'];
        $category = $_POST['category'];
        $price = $_POST['price'];
        $stock = $_POST['stock'] ?? null;
        $is_active = $_POST['is_active'];

        // 取得 DB 現有圖片路徑（防止被空值覆蓋）
        $stmt = $pdo->prepare("SELECT image FROM menu WHERE id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $db_old_image = $row ? $row['image'] : '';

        // 強制只要沒新圖就用資料庫舊圖
        $imagePath = $db_old_image;

        // 若有新圖片才處理上傳
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if(isset($_FILES['image']) && $_FILES['image']['error']==0 && !empty($_FILES['image']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                echo json_encode(['success'=>false, 'msg'=>'僅允許上傳圖片檔案(jpg/png/webp/gif)']);
                exit;
            }
            $filename = 'food-'.time().'-'.rand(1000,9999).'.'.$ext;
            $targetDir = '../ui/img/products/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $target = $targetDir.$filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $imagePath = 'ui/img/products/'.$filename;
                // 刪除舊圖
                if(!empty($db_old_image) && file_exists('../'.$db_old_image)) unlink('../'.$db_old_image);
            } else {
                echo json_encode(['success'=>false, 'msg'=>'圖片上傳失敗']);
                exit;
            }
        }

        $sql = "UPDATE menu SET name_zh=?, name_en=?, category=?, price=?, stock=?, is_active=?, image=?, updated_at=NOW() WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name_zh, $name_en, $category, $price, $stock, $is_active, $imagePath, $id]);
        echo json_encode(['success'=>true]);
        exit;

    } elseif ($action == 'delete') {
        // 刪除商品
        $id = $_POST['id'];
        // 查詢舊圖
        $stmt = $pdo->prepare("SELECT image FROM menu WHERE id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row && !empty($row['image']) && file_exists('../'.$row['image'])){
            unlink('../'.$row['image']);
        }
        // 刪資料
        $stmt = $pdo->prepare("DELETE FROM menu WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(['success'=>true]);
        exit;

    } elseif ($action == 'list') {
        // 查詢所有商品
        if(isset($_GET['category'])){
            $cat = $_GET['category'];
            $stmt = $pdo->prepare("SELECT * FROM menu WHERE category=? ORDER BY id");
            $stmt->execute([$cat]);
        }else{
            $stmt = $pdo->query("SELECT * FROM menu ORDER BY id");
        }
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as &$row) {
            if (isset($row['image']) && !empty($row['image']) && strpos($row['image'], 'http') !== 0) {
                $row['image'] = '/'.$row['image'];
            }
        }
        unset($row);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;

    } elseif ($action == 'add_combo') {
        // 新增套餐
        $name_zh = $_POST['name_zh'];
        $name_en = $_POST['name_en'] ?? '';
        $description = $_POST['description'] ?? '';
        $base_price = $_POST['base_price'];
        $stock = $_POST['stock'] ?? null;
        $is_active = $_POST['is_active'] ?? 1;
        
        // 圖片上傳
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $imagePath = '';
        if(isset($_FILES['image']) && $_FILES['image']['error']==0 && !empty($_FILES['image']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                echo json_encode(['success'=>false, 'msg'=>'僅允許上傳圖片檔案(jpg/png/webp/gif)']);
                exit;
            }
            $filename = 'combo-'.time().'-'.rand(1000,9999).'.'.$ext;
            $target = '../ui/img/products/'.$filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $imagePath = 'ui/img/products/'.$filename;
            } else {
                echo json_encode(['success'=>false, 'msg'=>'圖片上傳失敗']);
                exit;
            }
        }
        
        $pdo->beginTransaction();
        
        // 1. 先在 menu 表中建立套餐品項
        $sql = "INSERT INTO menu (name_zh, name_en, price, stock, category, image, is_active, created_at, updated_at)
                VALUES (?,?,?,?,?,?,?,NOW(),NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name_zh, $name_en, $base_price, $stock, '套餐', $imagePath, $is_active]);
        $menu_id = $pdo->lastInsertId();
        
        // 2. 在 menu_sets 表中建立套餐定義
        $sql = "INSERT INTO menu_sets (menu_id, name, description, is_active, created_at, updated_at)
                VALUES (?,?,?,?,NOW(),NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$menu_id, $name_zh, $description, $is_active]);
        $set_id = $pdo->lastInsertId();
        
        // 3. 建立套餐選項群組（主餐、湯底、菜盤、附餐）
        $groups = [
            ['name' => '主餐選擇', 'type' => 'single', 'min' => 1, 'max' => 1, 'order' => 1],
            ['name' => '湯底選擇', 'type' => 'single', 'min' => 1, 'max' => 1, 'order' => 2],
            ['name' => '菜盤選擇', 'type' => 'single', 'min' => 1, 'max' => 1, 'order' => 3],
            ['name' => '附餐選擇', 'type' => 'single', 'min' => 1, 'max' => 1, 'order' => 4]
        ];
        
        foreach ($groups as $group) {
            $sql = "INSERT INTO menu_set_groups (set_id, group_name, selection_type, min_select, max_select, display_order)
                    VALUES (?,?,?,?,?,?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$set_id, $group['name'], $group['type'], $group['min'], $group['max'], $group['order']]);
        }
        
        $pdo->commit();
        echo json_encode(['success'=>true, 'set_id'=>$set_id, 'menu_id'=>$menu_id]);
        exit;
        
    } elseif ($action == 'edit_combo') {
        // 編輯套餐
        $set_id = $_POST['set_id'];
        $name_zh = $_POST['name_zh'];
        $name_en = $_POST['name_en'] ?? '';
        $description = $_POST['description'] ?? '';
        $base_price = $_POST['base_price'];
        $stock = $_POST['stock'] ?? null;
        $is_active = $_POST['is_active'] ?? 1;
        
        // 取得對應的 menu_id
        $stmt = $pdo->prepare("SELECT menu_id FROM menu_sets WHERE id=?");
        $stmt->execute([$set_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new Exception('套餐不存在');
        }
        $menu_id = $row['menu_id'];
        
        // 取得現有圖片路徑
        $stmt = $pdo->prepare("SELECT image FROM menu WHERE id=?");
        $stmt->execute([$menu_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $db_old_image = $row ? $row['image'] : '';
        $imagePath = $db_old_image;
        
        // 處理圖片上傳
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if(isset($_FILES['image']) && $_FILES['image']['error']==0 && !empty($_FILES['image']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                echo json_encode(['success'=>false, 'msg'=>'僅允許上傳圖片檔案(jpg/png/webp/gif)']);
                exit;
            }
            $filename = 'combo-'.time().'-'.rand(1000,9999).'.'.$ext;
            $targetDir = '../ui/img/products/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $target = $targetDir.$filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $imagePath = 'ui/img/products/'.$filename;
                // 刪除舊圖
                if(!empty($db_old_image) && file_exists('../'.$db_old_image)) unlink('../'.$db_old_image);
            } else {
                echo json_encode(['success'=>false, 'msg'=>'圖片上傳失敗']);
                exit;
            }
        }
        
        $pdo->beginTransaction();
        
        // 更新 menu 表
        $sql = "UPDATE menu SET name_zh=?, name_en=?, price=?, stock=?, image=?, is_active=?, updated_at=NOW() WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name_zh, $name_en, $base_price, $stock, $imagePath, $is_active, $menu_id]);
        
        // 更新 menu_sets 表
        $sql = "UPDATE menu_sets SET name=?, description=?, is_active=?, updated_at=NOW() WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name_zh, $description, $is_active, $set_id]);
        
        $pdo->commit();
        echo json_encode(['success'=>true]);
        exit;
        
    } elseif ($action == 'delete_combo') {
        // 刪除套餐
        $set_id = $_POST['set_id'];
        
        // 取得對應的 menu_id 和圖片
        $stmt = $pdo->prepare("SELECT ms.menu_id, m.image FROM menu_sets ms LEFT JOIN menu m ON ms.menu_id = m.id WHERE ms.id=?");
        $stmt->execute([$set_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new Exception('套餐不存在');
        }
        
        $pdo->beginTransaction();
        
        // 刪除套餐選項明細
        $stmt = $pdo->prepare("DELETE msi FROM menu_set_items msi 
                              INNER JOIN menu_set_groups msg ON msi.set_group_id = msg.id 
                              WHERE msg.set_id = ?");
        $stmt->execute([$set_id]);
        
        // 刪除套餐選項群組
        $stmt = $pdo->prepare("DELETE FROM menu_set_groups WHERE set_id=?");
        $stmt->execute([$set_id]);
        
        // 刪除套餐定義
        $stmt = $pdo->prepare("DELETE FROM menu_sets WHERE id=?");
        $stmt->execute([$set_id]);
        
        // 刪除 menu 表中的套餐品項
        if ($row['menu_id']) {
            $stmt = $pdo->prepare("DELETE FROM menu WHERE id=?");
            $stmt->execute([$row['menu_id']]);
        }
        
        // 刪除圖片檔案
        if($row['image'] && file_exists('../'.$row['image'])){
            unlink('../'.$row['image']);
        }
        
        $pdo->commit();
        echo json_encode(['success'=>true]);
        exit;
        
    } elseif ($action == 'list_combos') {
        // 查詢所有套餐
        $sql = "SELECT ms.id as set_id, ms.menu_id, ms.name, ms.description, ms.is_active,
                       m.name_zh, m.name_en, m.price, m.stock, m.image, ms.created_at, ms.updated_at
                FROM menu_sets ms 
                LEFT JOIN menu m ON ms.menu_id = m.id
                ORDER BY ms.id DESC";
        $stmt = $pdo->query($sql);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($result as &$row) {
            if (isset($row['image']) && !empty($row['image']) && strpos($row['image'], 'http') !== 0) {
                $row['image'] = '/'.$row['image'];
            }
        }
        unset($row);
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
        
    } elseif ($action == 'get_combo_details') {
        // 取得套餐詳細設定（包含選項群組和選項）
        $set_id = $_GET['set_id'];
        
        // 取得套餐基本資訊
        $sql = "SELECT ms.*, m.price, m.image FROM menu_sets ms LEFT JOIN menu m ON ms.menu_id = m.id WHERE ms.id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$set_id]);
        $combo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$combo) {
            echo json_encode(['success'=>false, 'msg'=>'套餐不存在']);
            exit;
        }
        
        // 取得選項群組
        $sql = "SELECT * FROM menu_set_groups WHERE set_id=? ORDER BY display_order";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$set_id]);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 取得每個群組的選項
        foreach ($groups as &$group) {
            $sql = "SELECT msi.*, m.name_zh, m.price as menu_price 
                    FROM menu_set_items msi 
                    LEFT JOIN menu m ON msi.menu_id = m.id 
                    WHERE msi.set_group_id=? 
                    ORDER BY msi.display_order";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$group['id']]);
            $group['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($group);
        
        $combo['groups'] = $groups;
        echo json_encode(['success'=>true, 'combo'=>$combo], JSON_UNESCAPED_UNICODE);
        exit;
        
    } elseif ($action == 'update_combo_items') {
        // 更新套餐選項設定
        $set_id = $_POST['set_id'];
        $groups_data = json_decode($_POST['groups_data'], true);
        $addon_data = isset($_POST['addon_data']) ? json_decode($_POST['addon_data'], true) : null;
        
        $pdo->beginTransaction();
        
        // 更新一般選項群組
        foreach ($groups_data as $group_data) {
            $group_id = $group_data['group_id'];
            $items = $group_data['items'];
            
            // 先刪除該群組的所有選項
            $stmt = $pdo->prepare("DELETE FROM menu_set_items WHERE set_group_id=?");
            $stmt->execute([$group_id]);
            
            // 重新插入選項
            foreach ($items as $index => $item) {
                if (!empty($item['menu_id'])) {
                    $sql = "INSERT INTO menu_set_items (set_group_id, menu_id, custom_price, display_order) 
                            VALUES (?,?,?,?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$group_id, $item['menu_id'], $item['custom_price'] ?? 0, $index]);
                }
            }
        }
        
        // 處理活動加價購（需要先確保有活動加價購群組）
        if ($addon_data) {
            // 檢查是否已有活動加價購群組
            $stmt = $pdo->prepare("SELECT id FROM menu_set_groups WHERE set_id=? AND group_name='活動加價購'");
            $stmt->execute([$set_id]);
            $addon_group = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$addon_group) {
                // 建立活動加價購群組
                $sql = "INSERT INTO menu_set_groups (set_id, group_name, selection_type, min_select, max_select, display_order)
                        VALUES (?,?,?,?,?,?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$set_id, '活動加價購', 'single', 0, 1, 5]);
                $addon_group_id = $pdo->lastInsertId();
            } else {
                $addon_group_id = $addon_group['id'];
            }
            
            // 清除舊的活動加價購選項
            $stmt = $pdo->prepare("DELETE FROM menu_set_items WHERE set_group_id=?");
            $stmt->execute([$addon_group_id]);
            
            // 插入新的活動加價購選項
            $sql = "INSERT INTO menu_set_items (set_group_id, menu_id, custom_price, display_order) 
                    VALUES (?,?,?,?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$addon_group_id, $addon_data['menu_id'], -$addon_data['discount_price'], 0]);
        }
        
        $pdo->commit();
        echo json_encode(['success'=>true]);
        exit;
        
    } elseif ($action == 'get_available_items') {
        // 取得可用於套餐的商品選項（按分類分組）
        $category = $_GET['category'] ?? null;
        
        if ($category) {
            $stmt = $pdo->prepare("SELECT id, name_zh, price, category FROM menu WHERE category=? AND is_active=1 ORDER BY name_zh");
            $stmt->execute([$category]);
        } else {
            $stmt = $pdo->query("SELECT id, name_zh, price, category FROM menu WHERE is_active=1 AND category != '套餐' ORDER BY category, name_zh");
        }
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
        
    } else {
        throw new Exception('未知的 action');
    }
} catch(Exception $e) {
    echo json_encode(['success'=>false, 'msg'=>$e->getMessage()]);
}
?>