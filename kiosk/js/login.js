// 登入表單提交處理
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // 取得表單資料
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const rememberMe = document.getElementById('rememberMe').checked;
    
    try {
        // 發送登入請求
        const response = await fetch('/api/auth/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, password, rememberMe })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            // 登入成功，儲存 token 並跳轉
            localStorage.setItem('token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));
            
            // 如果選擇記住我，設定較長的過期時間
            if (rememberMe) {
                localStorage.setItem('rememberMe', 'true');
            }
            
            window.location.href = '/index.html';
        } else {
            // 顯示錯誤訊息
            const errorMessage = document.getElementById('errorMessage');
            errorMessage.textContent = data.message;
            errorMessage.classList.remove('d-none');
        }
    } catch (error) {
        console.error('登入錯誤:', error);
        const errorMessage = document.getElementById('errorMessage');
        errorMessage.textContent = '系統錯誤，請稍後再試';
        errorMessage.classList.remove('d-none');
    }
});

// 檢查是否已登入
window.addEventListener('DOMContentLoaded', () => {
    const token = localStorage.getItem('token');
    if (token) {
        // 驗證 token 是否有效
        fetch('/api/auth/verify', {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        })
        .then(response => {
            if (response.ok) {
                window.location.href = '/index.html';
            } else {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
            }
        })
        .catch(error => {
            console.error('Token 驗證錯誤:', error);
            localStorage.removeItem('token');
            localStorage.removeItem('user');
        });
    }
}); 