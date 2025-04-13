<?php
/**
 * 登入頁面
 */
require_once __DIR__ . '/../includes/functions.php';

// 如果已經登入，跳轉到儀表板
if (isset($_SESSION['user_id'])) {
    header('Location: /admin/dashboard.php');
    exit;
}

// 處理登入請求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        // 驗證輸入
        if (empty($username) || empty($password)) {
            throw new Exception('請輸入用戶名和密碼');
        }
        
        // 查詢用戶
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 驗證用戶
        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception('用戶名或密碼錯誤');
        }
        
        // 設置會話
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role_id'] = $user['role_id'];
        
        // 記錄登入日誌
        logSystem('info', '用戶登入', [
            'user_id' => $user['id'],
            'username' => $user['username']
        ]);
        
        // 跳轉到儀表板
        header('Location: /admin/dashboard.php');
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登入 - POS 系統</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 15px;
            margin: auto;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo h1 {
            font-size: 2rem;
            color: #333;
        }
        .login-form {
            background: #fff;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
        }
        .form-floating {
            margin-bottom: 1rem;
        }
        .btn-login {
            padding: 0.75rem;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <h1>POS 系統</h1>
        </div>
        
        <div class="login-form">
            <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="用戶名" required>
                    <label for="username">用戶名</label>
                </div>
                
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="password" name="password" placeholder="密碼" required>
                    <label for="password">密碼</label>
                </div>
                
                <button class="w-100 btn btn-lg btn-primary btn-login" type="submit">
                    <i class="fas fa-sign-in-alt"></i> 登入
                </button>
            </form>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 