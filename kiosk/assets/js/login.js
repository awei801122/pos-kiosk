// 登入頁面的 JavaScript 程式碼
document.addEventListener('DOMContentLoaded', function() {
    // 檢查是否已經登入
    checkLoginStatus();

    // 登入表單提交事件
    document.getElementById('loginForm').addEventListener('submit', handleLogin);
});

// 檢查登入狀態
function checkLoginStatus() {
    const token = localStorage.getItem('auth_token');
    if (token) {
        // 如果已經登入，跳轉到首頁
        window.location.href = 'index.html';
    }
}

// 處理登入
async function handleLogin(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('api/auth.php?action=login', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // 儲存 token 和使用者資訊
            localStorage.setItem('auth_token', data.token);
            localStorage.setItem('user_info', JSON.stringify(data.user));
            
            // 如果選擇記住我，設定較長的過期時間
            if (formData.get('remember_me')) {
                localStorage.setItem('remember_me', 'true');
            }
            
            // 顯示成功訊息
            showSuccess('登入成功');
            
            // 跳轉到首頁
            window.location.href = 'index.html';
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('登入失敗，請稍後再試');
    }
}

// 顯示成功訊息
function showSuccess(message) {
    // 實作成功訊息的顯示邏輯
    alert(message);
}

// 顯示錯誤訊息
function showError(message) {
    // 實作錯誤訊息的顯示邏輯
    alert(message);
} 