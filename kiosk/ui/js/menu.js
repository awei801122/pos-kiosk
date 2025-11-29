// 菜單管理頁面主要JavaScript文件
// 初始化變數
let categories = []; // 存放所有分類
let items = []; // 存放所有商品
let currentCategory = null; // 當前選中的分類
let filteredItems = []; // 過濾後的商品列表

// DOM元素載入完成後執行
document.addEventListener('DOMContentLoaded', () => {
    // 初始化頁面
    init_page();
    
    // 註冊所有按鈕事件
    register_button_events();
    
    // 註冊搜尋事件
    register_search_event();
    
    // 註冊模態窗事件
    register_modal_events();
});

// 初始化頁面，載入資料
async function init_page() {
    try {
        // 顯示載入中訊息
        show_message('正在載入資料...', 'info');
        
        // 從伺服器取得菜單資料
        const response = await fetch('../kiosk/menu.php?action=get_all');
        
        if (!response.ok) {
            throw new Error('無法取得菜單資料');
        }
        
        const data = await response.json();
        
        // 檢查回應是否成功
        if (data.status === 'success') {
            // 儲存分類和商品資料
            categories = data.categories || [];
            items = data.items || [];
            filteredItems = [...items]; // 初始時過濾列表等於完整列表
            
            // 渲染分類和商品列表
            render_categories();
            render_items();
            
            // 如果有分類，預設選中第一個
            if (categories.length > 0) {
                select_category(categories[0].id);
            }
            
            show_message('資料載入完成', 'success');
        } else {
            throw new Error(data.message || '載入資料失敗');
        }
    } catch (error) {
        console.error('初始化錯誤:', error);
        show_message('載入資料失敗: ' + error.message, 'error');
    }
}

// 註冊按鈕點擊事件
function register_button_events() {
    // 註冊新增分類按鈕事件
    document.getElementById('add-category-btn').addEventListener('click', () => {
        // 重置表單並打開模態窗
        document.getElementById('category-form').reset();
        document.getElementById('category-id').value = ''; // 清空ID，表示這是新增操作
        document.getElementById('category-modal-title').textContent = '新增分類';
        toggle_modal('category-modal', true);
    });
    
    // 註冊新增商品按鈕事件
    document.getElementById('add-item-btn').addEventListener('click', () => {
        // 重置表單並打開模態窗
        document.getElementById('item-form').reset();
        document.getElementById('item-id').value = ''; // 清空ID，表示這是新增操作
        document.getElementById('item-modal-title').textContent = '新增商品';
        
        // 設定分類下拉選單
        update_category_dropdown();
        
        // 預設選擇當前選中的分類
        if (currentCategory) {
            document.getElementById('item-category').value = currentCategory;
        }
        
        // 庫存設定顯示邏輯
        toggle_inventory_fields();
        
        toggle_modal('item-modal', true);
    });
    
    // 註冊確認刪除按鈕事件
    document.getElementById('confirm-delete-btn').addEventListener('click', async () => {
        const id = document.getElementById('delete-id').value;
        const type = document.getElementById('delete-type').value;
        
        try {
            await delete_item_or_category(id, type);
            toggle_modal('delete-confirm-modal', false);
            show_message(`成功刪除${type === 'category' ? '分類' : '商品'}`, 'success');
        } catch (error) {
            show_message('刪除失敗: ' + error.message, 'error');
        }
    });
    
    // 註冊啟用庫存選項切換事件
    document.getElementById('item-has-inventory').addEventListener('change', toggle_inventory_fields);
}

// 註冊搜尋功能事件
function register_search_event() {
    const searchInput = document.getElementById('search-input');
    
    // 輸入時觸發搜尋
    searchInput.addEventListener('input', () => {
        const searchTerm = searchInput.value.trim().toLowerCase();
        
        if (searchTerm === '') {
            // 搜尋詞為空，顯示當前分類的所有商品
            filter_items_by_category(currentCategory);
        } else {
            // 搜尋詞不為空，根據搜尋詞過濾商品
            filteredItems = items.filter(item => {
                return (
                    item.name.toLowerCase().includes(searchTerm) ||
                    (item.description && item.description.toLowerCase().includes(searchTerm))
                );
            });
            
            render_items();
        }
    });
}

// 註冊模態窗相關事件
function register_modal_events() {
    // 註冊所有關閉按鈕事件
    document.querySelectorAll('.close-btn, .btn-cancel').forEach(button => {
        button.addEventListener('click', (e) => {
            // 找到最近的模態窗並關閉
            const modal = e.target.closest('.modal');
            if (modal) {
                toggle_modal(modal.id, false);
            }
        });
    });
    
    // 註冊分類表單提交事件
    document.getElementById('category-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const categoryId = document.getElementById('category-id').value;
        const categoryName = document.getElementById('category-name').value;
        const categoryDescription = document.getElementById('category-description').value;
        
        // 組織資料
        const categoryData = {
            name: categoryName,
            description: categoryDescription
        };
        
        try {
            if (categoryId) {
                // 更新現有分類
                await update_category(categoryId, categoryData);
                show_message('分類更新成功', 'success');
            } else {
                // 新增分類
                await add_category(categoryData);
                show_message('分類新增成功', 'success');
            }
            
            // 關閉模態窗
            toggle_modal('category-modal', false);
            
        } catch (error) {
            show_message('操作失敗: ' + error.message, 'error');
        }
    });
    
    // 註冊商品表單提交事件
    document.getElementById('item-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // 獲取表單資料
        const itemId = document.getElementById('item-id').value;
        const itemData = {
            name: document.getElementById('item-name').value,
            description: document.getElementById('item-description').value,
            category_id: document.getElementById('item-category').value,
            price: parseFloat(document.getElementById('item-price').value),
            image_url: document.getElementById('item-image').value,
            active: document.getElementById('item-active').checked ? 1 : 0,
            has_inventory: document.getElementById('item-has-inventory').checked ? 1 : 0
        };
        
        // 如果啟用庫存，添加庫存相關資料
        if (itemData.has_inventory) {
            itemData.stock = parseInt(document.getElementById('item-stock').value) || 0;
            itemData.min_stock = parseInt(document.getElementById('item-min-stock').value) || 0;
            itemData.max_stock = parseInt(document.getElementById('item-max-stock').value) || 0;
        }
        
        try {
            if (itemId) {
                // 更新現有商品
                await update_item(itemId, itemData);
                show_message('商品更新成功', 'success');
            } else {
                // 新增商品
                await add_item(itemData);
                show_message('商品新增成功', 'success');
            }
            
            // 關閉模態窗
            toggle_modal('item-modal', false);
            
        } catch (error) {
            show_message('操作失敗: ' + error.message, 'error');
        }
    });
}

// 渲染分類列表
function render_categories() {
    const categoryList = document.getElementById('category-list');
    
    // 清空現有內容
    categoryList.innerHTML = '';
    
    // 如果沒有分類，顯示提示訊息
    if (categories.length === 0) {
        categoryList.innerHTML = '<div class="empty-state">尚未建立任何分類</div>';
        return;
    }
    
    // 渲染每個分類
    categories.forEach(category => {
        const isActive = currentCategory === category.id;
        
        const categoryElement = document.createElement('div');
        categoryElement.className = `category-item ${isActive ? 'active' : ''}`;
        categoryElement.dataset.id = category.id;
        
        categoryElement.innerHTML = `
            <div class="category-info">
                <div class="category-name">${category.name}</div>
                <div class="category-actions">
                    <button class="btn btn-small btn-edit edit-category-btn" data-id="${category.id}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-small btn-danger delete-category-btn" data-id="${category.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        
        // 點擊分類時選中該分類
        categoryElement.addEventListener('click', () => {
            select_category(category.id);
        });
        
        // 註冊編輯按鈕事件
        categoryElement.querySelector('.edit-category-btn').addEventListener('click', (e) => {
            e.stopPropagation(); // 防止觸發分類選中事件
            edit_category(category.id);
        });
        
        // 註冊刪除按鈕事件
        categoryElement.querySelector('.delete-category-btn').addEventListener('click', (e) => {
            e.stopPropagation(); // 防止觸發分類選中事件
            confirm_delete('category', category.id, category.name);
        });
        
        categoryList.appendChild(categoryElement);
    });
}

// 渲染商品列表
function render_items() {
    const itemList = document.getElementById('item-list');
    
    // 清空現有內容
    itemList.innerHTML = '';
    
    // 如果沒有過濾後的商品，顯示提示訊息
    if (filteredItems.length === 0) {
        itemList.innerHTML = '<div class="empty-state">沒有符合條件的商品</div>';
        return;
    }
    
    // 渲染每個商品
    filteredItems.forEach(item => {
        const categoryName = get_category_name(item.category_id);
        
        const itemElement = document.createElement('div');
        itemElement.className = 'item-card';
        
        itemElement.innerHTML = `
            <div class="item-image">
                <img src="${item.image_url || 'img/no-image.png'}" alt="${item.name}" onerror="this.src='img/no-image.png'">
            </div>
            <div class="item-info">
                <h3 class="item-name">${item.name}</h3>
                <div class="item-category">${categoryName}</div>
                <p class="item-description">${item.description || '無描述'}</p>
                <div class="item-price">NT$ ${item.price.toFixed(2)}</div>
                <div class="item-status">
                    狀態: <span class="${item.active ? 'active' : 'inactive'}">${item.active ? '販售中' : '已下架'}</span>
                </div>
                ${item.has_inventory ? `
                <div class="item-inventory">
                    庫存: ${item.stock || 0} ${item.min_stock ? `(最低: ${item.min_stock})` : ''}
                </div>
                ` : ''}
            </div>
            <div class="item-actions">
                <button class="btn btn-small btn-edit edit-item-btn" data-id="${item.id}">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-small btn-danger delete-item-btn" data-id="${item.id}">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        
        // 註冊編輯按鈕事件
        itemElement.querySelector('.edit-item-btn').addEventListener('click', () => {
            edit_item(item.id);
        });
        
        // 註冊刪除按鈕事件
        itemElement.querySelector('.delete-item-btn').addEventListener('click', () => {
            confirm_delete('item', item.id, item.name);
        });
        
        itemList.appendChild(itemElement);
    });
}

// 選中分類
function select_category(categoryId) {
    currentCategory = categoryId;
    
    // 更新分類列表顯示
    document.querySelectorAll('.category-item').forEach(item => {
        item.classList.toggle('active', item.dataset.id === categoryId);
    });
    
    // 根據分類過濾商品
    filter_items_by_category(categoryId);
    
    // 更新類別標題
    const categoryName = get_category_name(categoryId);
    document.getElementById('current-category-name').textContent = categoryName || '所有商品';
}

// 根據分類過濾商品
function filter_items_by_category(categoryId) {
    // 清空搜尋框
    document.getElementById('search-input').value = '';
    
    if (!categoryId) {
        // 沒有選中分類時顯示所有商品
        filteredItems = [...items];
    } else {
        // 按分類過濾
        filteredItems = items.filter(item => item.category_id === categoryId);
    }
    
    // 重新渲染商品列表
    render_items();
}

// 獲取分類名稱
function get_category_name(categoryId) {
    const category = categories.find(c => c.id === categoryId);
    return category ? category.name : '未分類';
}

// 編輯分類
function edit_category(categoryId) {
    const category = categories.find(c => c.id === categoryId);
    
    if (!category) {
        show_message('找不到該分類', 'error');
        return;
    }
    
    // 填充表單資料
    document.getElementById('category-id').value = category.id;
    document.getElementById('category-name').value = category.name;
    document.getElementById('category-description').value = category.description || '';
    
    // 更新模態窗標題
    document.getElementById('category-modal-title').textContent = '編輯分類';
    
    // 顯示模態窗
    toggle_modal('category-modal', true);
}

// 編輯商品
function edit_item(itemId) {
    const item = items.find(i => i.id === itemId);
    
    if (!item) {
        show_message('找不到該商品', 'error');
        return;
    }
    
    // 更新分類下拉選單
    update_category_dropdown();
    
    // 填充表單資料
    document.getElementById('item-id').value = item.id;
    document.getElementById('item-name').value = item.name;
    document.getElementById('item-description').value = item.description || '';
    document.getElementById('item-category').value = item.category_id;
    document.getElementById('item-price').value = item.price;
    document.getElementById('item-image').value = item.image_url || '';
    document.getElementById('item-active').checked = !!item.active;
    document.getElementById('item-has-inventory').checked = !!item.has_inventory;
    
    // 填充庫存相關欄位
    document.getElementById('item-stock').value = item.stock || 0;
    document.getElementById('item-min-stock').value = item.min_stock || 0;
    document.getElementById('item-max-stock').value = item.max_stock || 0;
    
    // 更新庫存欄位顯示
    toggle_inventory_fields();
    
    // 更新模態窗標題
    document.getElementById('item-modal-title').textContent = '編輯商品';
    
    // 顯示模態窗
    toggle_modal('item-modal', true);
}

// 更新分類下拉選單
function update_category_dropdown() {
    const categorySelect = document.getElementById('item-category');
    
    // 清空現有選項
    categorySelect.innerHTML = '';
    
    // 添加每個分類作為選項
    categories.forEach(category => {
        const option = document.createElement('option');
        option.value = category.id;
        option.textContent = category.name;
        categorySelect.appendChild(option);
    });
}

// 顯示或隱藏模態窗
function toggle_modal(modalId, show) {
    const modal = document.getElementById(modalId);
    
    if (show) {
        modal.classList.add('active');
    } else {
        modal.classList.remove('active');
    }
}

// 顯示確認刪除模態窗
function confirm_delete(type, id, name) {
    document.getElementById('delete-id').value = id;
    document.getElementById('delete-type').value = type;
    
    const message = type === 'category' 
        ? `確定要刪除分類「${name}」嗎？此操作將同時刪除該分類下的所有商品。`
        : `確定要刪除商品「${name}」嗎？`;
    
    document.getElementById('delete-message').textContent = message;
    
    toggle_modal('delete-confirm-modal', true);
}

// 顯示或隱藏庫存相關欄位
function toggle_inventory_fields() {
    const hasInventory = document.getElementById('item-has-inventory').checked;
    const inventoryFields = document.getElementById('inventory-fields');
    
    if (hasInventory) {
        inventoryFields.style.display = 'block';
    } else {
        inventoryFields.style.display = 'none';
    }
}

// 顯示提示訊息
function show_message(message, type = 'success') {
    const messageBox = document.getElementById('message');
    const messageContent = document.getElementById('message-content');
    
    // 設置訊息內容和類型
    messageContent.textContent = message;
    messageBox.className = `message ${type}`;
    
    // 顯示訊息
    messageBox.classList.add('active');
    
    // 3秒後自動隱藏
    setTimeout(() => {
        messageBox.classList.remove('active');
    }, 3000);
}

// 新增分類
async function add_category(categoryData) {
    try {
        const response = await fetch('../kiosk/menu.php?action=add_category', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(categoryData)
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            // 將新分類添加到本地列表
            categories.push({
                id: data.category_id,
                name: categoryData.name,
                description: categoryData.description
            });
            
            // 重新渲染分類列表
            render_categories();
            
            return data.category_id;
        } else {
            throw new Error(data.message || '新增分類失敗');
        }
    } catch (error) {
        console.error('新增分類錯誤:', error);
        throw error;
    }
}

// 更新分類
async function update_category(categoryId, categoryData) {
    try {
        const response = await fetch(`../kiosk/menu.php?action=update_category&id=${categoryId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(categoryData)
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            // 更新本地分類資料
            const index = categories.findIndex(c => c.id === categoryId);
            
            if (index !== -1) {
                categories[index] = {
                    ...categories[index],
                    name: categoryData.name,
                    description: categoryData.description
                };
            }
            
            // 重新渲染分類列表
            render_categories();
            
            return true;
        } else {
            throw new Error(data.message || '更新分類失敗');
        }
    } catch (error) {
        console.error('更新分類錯誤:', error);
        throw error;
    }
}

// 新增商品
async function add_item(itemData) {
    try {
        const response = await fetch('../kiosk/menu.php?action=add_item', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(itemData)
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            // 將新商品添加到本地列表
            items.push({
                id: data.item_id,
                ...itemData
            });
            
            // 更新過濾後的商品列表
            filter_items_by_category(currentCategory);
            
            return data.item_id;
        } else {
            throw new Error(data.message || '新增商品失敗');
        }
    } catch (error) {
        console.error('新增商品錯誤:', error);
        throw error;
    }
}

// 更新商品
async function update_item(itemId, itemData) {
    try {
        const response = await fetch(`../kiosk/menu.php?action=update_item&id=${itemId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(itemData)
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            // 更新本地商品資料
            const index = items.findIndex(i => i.id === itemId);
            
            if (index !== -1) {
                items[index] = {
                    ...items[index],
                    ...itemData
                };
            }
            
            // 更新過濾後的商品列表
            filter_items_by_category(currentCategory);
            
            return true;
        } else {
            throw new Error(data.message || '更新商品失敗');
        }
    } catch (error) {
        console.error('更新商品錯誤:', error);
        throw error;
    }
}

// 刪除商品或分類
async function delete_item_or_category(id, type) {
    try {
        const action = type === 'category' ? 'delete_category' : 'delete_item';
        const response = await fetch(`../kiosk/menu.php?action=${action}&id=${id}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            if (type === 'category') {
                // 從本地列表中移除分類
                categories = categories.filter(c => c.id !== id);
                
                // 從列表中移除該分類下的所有商品
                items = items.filter(i => i.category_id !== id);
                
                // 如果刪除的是當前選中的分類，則重置選中狀態
                if (currentCategory === id) {
                    currentCategory = categories.length > 0 ? categories[0].id : null;
                }
                
                // 重新渲染分類列表
                render_categories();
            } else {
                // 從本地列表中移除商品
                items = items.filter(i => i.id !== id);
            }
            
            // 更新過濾後的商品列表
            filter_items_by_category(currentCategory);
            
            return true;
        } else {
            throw new Error(data.message || `刪除${type === 'category' ? '分類' : '商品'}失敗`);
        }
    } catch (error) {
        console.error(`刪除${type}錯誤:`, error);
        throw error;
    }
}
