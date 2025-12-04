// 全域變數
let menuItems = []; // 儲存菜單項目
let categories = []; // 儲存分類
let cart = []; // 儲存購物車項目

// DOM 元素
const menuGrid = document.getElementById('menuGrid');
const categoryList = document.getElementById('categoryList');
const cartCount = document.getElementById('cartCount');
const totalAmount = document.getElementById('totalAmount');
const cartContent = document.getElementById('cartContent');

// 初始化
document.addEventListener('DOMContentLoaded', () => {
    loadMenu();
    setupEventListeners();
});

// 載入菜單
async function loadMenu() {
    try {
        const response = await fetch('../api/menu.php');
        const data = await response.json();
        
        if (data.success) {
            menuItems = data.items;
            categories = data.categories;
            
            // 渲染分類
            renderCategories();
            // 渲染所有菜單項目
            renderMenuItems();
        } else {
            showMessage('載入菜單失敗', 'error');
        }
    } catch (error) {
        console.error('載入菜單錯誤:', error);
        showMessage('載入菜單時發生錯誤', 'error');
    }
}

// 渲染分類列表
function renderCategories() {
    categoryList.innerHTML = categories.map(category => `
        <div class="category-item" data-category="${category.id}">
            ${category.name}
        </div>
    `).join('');
}

// 渲染菜單項目
function renderMenuItems(categoryId = null) {
    const filteredItems = categoryId 
        ? menuItems.filter(item => item.category_id === categoryId)
        : menuItems;
    
    menuGrid.innerHTML = filteredItems.map(item => `
        <div class="menu-item" data-id="${item.id}">
            <img src="${item.image_url}" alt="${item.name}" onerror="this.src='img/default-food.jpg'">
            <div class="menu-item-content">
                <div class="menu-item-title">${item.name}</div>
                <div class="menu-item-price">NT$ ${item.price}</div>
                <div class="menu-item-actions">
                    <div class="quantity-control">
                        <button class="quantity-btn decrease" onclick="decreaseQuantity(${item.id})">-</button>
                        <span class="quantity-display" id="quantity-${item.id}">1</span>
                        <button class="quantity-btn increase" onclick="increaseQuantity(${item.id})">+</button>
                    </div>
                    <button class="add-to-cart-btn" onclick="addToCart(${item.id})">加入購物車</button>
                </div>
            </div>
        </div>
    `).join('');
}

// 設置事件監聽器
function setupEventListeners() {
    // 分類點擊事件
    categoryList.addEventListener('click', (e) => {
        const categoryItem = e.target.closest('.category-item');
        if (categoryItem) {
            const categoryId = parseInt(categoryItem.dataset.category);
            // 移除其他分類的active狀態
            document.querySelectorAll('.category-item').forEach(item => {
                item.classList.remove('active');
            });
            // 添加當前分類的active狀態
            categoryItem.classList.add('active');
            // 渲染該分類的菜單項目
            renderMenuItems(categoryId);
        }
    });
}

// 增加數量
function increaseQuantity(itemId) {
    const quantityElement = document.getElementById(`quantity-${itemId}`);
    let quantity = parseInt(quantityElement.textContent);
    if (quantity < 99) {
        quantity++;
        quantityElement.textContent = quantity;
    }
}

// 減少數量
function decreaseQuantity(itemId) {
    const quantityElement = document.getElementById(`quantity-${itemId}`);
    let quantity = parseInt(quantityElement.textContent);
    if (quantity > 1) {
        quantity--;
        quantityElement.textContent = quantity;
    }
}

// 加入購物車
function addToCart(itemId) {
    const item = menuItems.find(item => item.id === itemId);
    const quantity = parseInt(document.getElementById(`quantity-${itemId}`).textContent);
    
    if (item) {
        // 檢查購物車中是否已有此商品
        const existingItem = cart.find(cartItem => cartItem.id === itemId);
        
        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            cart.push({
                id: item.id,
                name: item.name,
                price: item.price,
                quantity: quantity
            });
        }
        
        updateCartUI();
        showMessage('已加入購物車');
    }
}

// 更新購物車UI
function updateCartUI() {
    // 更新購物車數量標籤
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    cartCount.textContent = totalItems;
    cartCount.style.display = totalItems > 0 ? 'inline' : 'none';
    
    // 更新總金額
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    totalAmount.textContent = `NT$ ${total}`;
}

// 查看購物車
function viewCart() {
    if (cart.length === 0) {
        cartContent.innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-cart-x" style="font-size: 3rem; color: #dee2e6;"></i>
                <p class="mt-3 text-muted">購物車是空的</p>
            </div>
        `;
    } else {
        cartContent.innerHTML = `
            <div class="cart-items">
                ${cart.map(item => `
                    <div class="cart-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">${item.name}</h6>
                                <small class="text-muted">NT$ ${item.price} x ${item.quantity}</small>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="me-3">NT$ ${item.price * item.quantity}</span>
                                <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${item.id})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
            <div class="border-top pt-3 mt-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5>總計金額：</h5>
                    <h5 class="text-primary">NT$ ${cart.reduce((sum, item) => sum + (item.price * item.quantity), 0)}</h5>
                </div>
            </div>
        `;
    }
    
    new bootstrap.Modal(document.getElementById('cartModal')).show();
}

// 從購物車移除商品
function removeFromCart(itemId) {
    cart = cart.filter(item => item.id !== itemId);
    updateCartUI();
    viewCart(); // 重新渲染購物車內容
}

// 處理結帳
function processPayment() {
    if (cart.length === 0) {
        showMessage('購物車是空的', 'warning');
        return;
    }

    // 準備訂單數據
    const orderData = {
        items: cart.map(item => ({
            id: item.id,
            name: item.name,
            quantity: item.quantity,
            price: item.price,
            totalPrice: item.price * item.quantity
        })),
        total: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0),
        orderTime: new Date().toISOString()
    };

    // 發送訂單到後端
    fetch('../api/save_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(orderData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 關閉購物車 Modal
            const cartModal = bootstrap.Modal.getInstance(document.getElementById('cartModal'));
            if (cartModal) {
                cartModal.hide();
            }
            
            // 清空購物車
            cart = [];
            updateCartUI();
            
            // 跳轉到收據頁面
            window.location.href = `receipt.html?orderId=${data.order_id}`;
        } else {
            throw new Error(data.message || '訂單提交失敗');
        }
    })
    .catch(error => {
        console.error('提交訂單時出錯:', error);
        showMessage('訂單提交失敗，請稍後再試', 'error');
    });
}

// 顯示訊息
function showMessage(message, type = 'success') {
    Swal.fire({
        text: message,
        icon: type,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
    });
}
