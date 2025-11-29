/**
 * 自助點餐 POS 系統 - 登入頁面邏輯
 * 處理使用者登入認證和表單提交等功能
 */

// 當文件載入完成後執行
// Execute when document is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // 獲取DOM元素
    // Get DOM elements
    const loginForm = document.querySelector('.login-form');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const rememberCheckbox = document.getElementById('remember');
    const loginButton = document.getElementById('login-btn');
    const loginMessage = document.getElementById('login-message');

    // 檢查本地儲存的帳號密碼
    // Check locally stored credentials
    check_stored_credentials();
    
    // 監聽登入按鈕點擊事件
    // Listen for login button click event
    loginButton.addEventListener('click', function(event) {
        event.preventDefault();
        
        // 檢查表單是否填寫完整
        // Check if form is filled completely
        if (!validate_form()) {
            return;
        }
        
        // 執行登入
        // Perform login
        perform_login();
    });
    
    // 監聽按鍵事件，支援按Enter鍵登入
    // Listen for key events, support Enter key to login
    passwordInput.addEventListener('keypress', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            loginButton.click();
        }
    });
    
    /**
     * 檢查本地儲存的帳號密碼
     * Check locally stored credentials
     */
    function check_stored_credentials() {
        // 嘗試從 localStorage 讀取用戶名
        // Try to read username from localStorage
        const storedUsername = localStorage.getItem('username');
        const rememberMe = localStorage.getItem('rememberMe');
        
        if (storedUsername && rememberMe === 'true') {
            usernameInput.value = storedUsername;
            rememberCheckbox.checked = true;
        }
    }
    
    /**
     * 驗證表單是否填寫完整
     * Validate if form is filled completely
     */
    function validate_form() {
        // 重置訊息
        // Reset message
        reset_message();
        
        // 檢查使用者名稱
        // Check username
        if (!usernameInput.value.trim()) {
            show_error('請輸入帳號');
            usernameInput.focus();
            return false;
        }
        
        // 檢查密碼
        // Check password
        if (!passwordInput.value) {
            show_error('請輸入密碼');
            passwordInput.focus();
            return false;
        }
        
        return true;
    }
    
    /**
     * 執行登入操作
     * Perform login operation
     */
    function perform_login() {
        // 顯示登入中訊息
        // Show logging in message
        show_message('登入中...', 'info');
        
        // 準備登入資料
        // Prepare login data
        const formData = new FormData();
        formData.append('username', usernameInput.value.trim());
        formData.append('password', passwordInput.value);
        
        // 發送登入請求到後端
        // Send login request to backend
        fetch('../kiosk/auth.php?action=login', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('網路錯誤，請稍後再試');
            }
            return response.json();
        })
        .then(data => {
            // 處理登入結果
            // Handle login result
            if (data.success) {
                // 登入成功
                // Login successful
                handle_login_success(data);
            } else {
                // 登入失敗
                // Login failed
                show_error(data.message || '帳號或密碼錯誤');
            }
        })
        .catch(error => {
            // 處理錯誤
            // Handle error
            show_error(error.message);
            console.error('登入錯誤:', error);
        });
    }
    
    /**
     * 處理登入成功
     * Handle login success
     */
    function handle_login_success(data) {
        show_message('登入成功，正在跳轉...', 'success');
        
        // 儲存記住我選項
        // Save remember me option
        if (rememberCheckbox.checked) {
            localStorage.setItem('username', usernameInput.value.trim());
            localStorage.setItem('rememberMe', 'true');
        } else {
            localStorage.removeItem('username');
            localStorage.removeItem('rememberMe');
        }
        
        // 根據角色跳轉到不同頁面
        // Redirect to different pages based on role
        setTimeout(() => {
            switch (data.data.role) {
                case 'admin':
                    // 管理員前往管理頁面
                    // Admin goes to admin page
                    window.location.href = 'admin.html';
                    break;
                case 'staff':
                    // 店員前往點餐頁面
                    // Staff goes to order page
                    window.location.href = 'order.html';
                    break;
                default:
                    // 默認跳轉到點餐頁面
                    // Default redirect to order page
                    window.location.href = 'order.html';
            }
        }, 1500);
    }
    
    /**
     * 顯示錯誤訊息
     * Show error message
     */
    function show_error(message) {
        loginMessage.textContent = message;
        loginMessage.className = 'login-message error';
    }
    
    /**
     * 顯示訊息
     * Show message
     */
    function show_message(message, type = 'info') {
        loginMessage.textContent = message;
        loginMessage.className = `login-message ${type}`;
    }
    
    /**
     * 重置訊息區塊
     * Reset message block
     */
    function reset_message() {
        loginMessage.textContent = '';
        loginMessage.className = 'login-message';
    }
});
