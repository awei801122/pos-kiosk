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
        $stock = $_POST['stock'];
        $is_active = $_POST['is_active'];

        // 圖片上傳
        $imagePath = '';
        if(isset($_FILES['image']) && $_FILES['image']['error']==0) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'food-'.time().'-'.rand(1000,9999).'.'.$ext;
            $target = '../ui/img/products/'.$filename;
            move_uploaded_file($_FILES['image']['tmp_name'], $target);
            $imagePath = 'ui/img/products/'.$filename;
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
        $stock = $_POST['stock'];
        $is_active = $_POST['is_active'];
        $old_image = $_POST['old_image'];

        $imagePath = $old_image;
        if(isset($_FILES['image']) && $_FILES['image']['error']==0) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'food-'.time().'-'.rand(1000,9999).'.'.$ext;
            $target = '../ui/img/products/'.$filename;
            move_uploaded_file($_FILES['image']['tmp_name'], $target);
            $imagePath = 'ui/img/products/'.$filename;
            if(!empty($old_image) && file_exists('../'.$old_image)) unlink('../'.$old_image);
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
        $stmt = $pdo->query("SELECT * FROM menu ORDER BY id");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as &$row) {
            if (isset($row['image']) && !empty($row['image']) && strpos($row['image'], 'http') !== 0) {
                $row['image'] = '/'.$row['image'];
            }
        }
        unset($row);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;

    } else {
        throw new Exception('未知的 action');
    }
} catch(Exception $e) {
    echo json_encode(['success'=>false, 'msg'=>$e->getMessage()]);
}
?>