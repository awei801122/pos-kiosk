/**
 * 菜單編輯JS - 用於管理菜單類別和商品
 * Menu Edit JS - Used for managing menu categories and items
 */

// 全局變數 Global variables
let categories = []; // 存放所有分類 Stores all categories
let items = []; // 存放所有商品 Stores all items
let selectedCategoryId = null; // 當前選擇的分類ID Current selected category ID

// 頁面初始化 Page initialization
document.addEventListener('DOMContentLoaded', function() {
    // 初始化頁面 Initialize page
    initPage();
    
    // 綁定事件 Bind events
    bindEvents();
});

/**
 * 初始化頁面 - 載入分類和商品資料
 * Initialize page - Load categories and items data
 */
function initPage() {
    // 顯示載入中訊息 Show loading message
    showMessage('正在載入資料...', 'info');
    
    // 載入分類 Load categories
    fetch('../kiosk/menu.php?action=getCategories')
        .then(response => {
            if (!response.ok) {
                throw new Error('無法載入分類資料');
            }
            return response.json();
        })
        .then(data => {
            categories = data;
            renderCategories();
            
            // 如果有分類，選擇第一個 If there are categories, select the first one
            if (categories.length > 0) {
                selectCategory(categories[0].id);
            } else {
                // 沒有分類時顯示空狀態 Show empty state when no categories
                document.querySelector('.item-list').innerHTML = '<div class="empty-state">尚未建立分類，請先新增分類</div>';
            }
            
            // 載入商品 Load items
            return fetch('../kiosk/menu.php?action=getItems');
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('無法載入商品資料');
            }
            return response.json();
        })
        .then(data => {
            items = data;
            renderItems();
            hideMessage();
        })
        .catch(error => {
            console.error('載入資料時發生錯誤:', error);
            showMessage('載入資料發生錯誤: ' + error.message, 'error');
        });
}

/**
 * 綁定頁面事件
 * Bind page events
 */
function bindEvents() {
    // 分類相關按鈕 Category related buttons
    document.getElementById('add-category-btn').addEventListener('click', () => openCategoryModal());
    
    // 商品相關按鈕 Item related buttons
    document.getElementById('add-item-btn').addEventListener('click', () => openItemModal());
    
    // 商品搜尋框 Item search input
    document.getElementById('search-input').addEventListener('input', filterItems);
    
    // 綁定模態窗按鈕事件 Bind modal window button events
    document.querySelectorAll('.close-btn, .btn-cancel').forEach(button => {
        button.addEventListener('click', closeAllModals);
    });
    
    // 保存分類按鈕 Save category button
    document.getElementById('save-category-btn').addEventListener('click', saveCategory);
    
    // 保存商品按鈕 Save item button
    document.getElementById('save-item-btn').addEventListener('click', saveItem);
    
    // 確認刪除按鈕 Confirm delete button
    document.getElementById('confirm-delete-btn').addEventListener('click', confirmDelete);
    
    // 商品庫存管理切換 Item inventory management toggle
    document.getElementById('item-track-inventory').addEventListener('change', function() {
        document.getElementById('inventory-fields').style.display = this.checked ? 'block' : 'none';
    });
}

/**
 * 渲染所有分類
 * Render all categories
 */
function renderCategories() {
    const categoryList = document.querySelector('.category-section .category-list');
    
    // 清空分類列表 Clear category list
    categoryList.innerHTML = '';
    
    // 如果沒有分類，顯示空狀態 If no categories, show empty state
    if (categories.length === 0) {
        categoryList.innerHTML = '<div class="empty-state">尚未新增任何分類</div>';
        return;
    }
    
    // 排序分類 Sort categories by sort order (descending)
    categories.sort((a, b) => (b.sort_order || 0) - (a.sort_order || 0));
    
    // 渲染每個分類 Render each category
    categories.forEach(category => {
        const categoryItem = document.createElement('div');
        categoryItem.className = 'category-item';
        if (selectedCategoryId === category.id) {
            categoryItem.classList.add('active');
        }
        
        categoryItem.innerHTML = `
            <div class="category-info">
                <h3>${category.name}</h3>
                <p>${category.description || '無描述'}</p>
            </div>
            <div class="category-actions">
                <button class="btn btn-icon edit-btn" data-id="${category.id}" title="編輯分類">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-icon delete-btn" data-id="${category.id}" title="刪除分類">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        
        // 點擊分類時選擇該分類 Select category when clicked
        categoryItem.querySelector('.category-info').addEventListener('click', () => {
            selectCategory(category.id);
        });
        
        // 編輯分類按鈕 Edit category button
        categoryItem.querySelector('.edit-btn').addEventListener('click', (e) => {
            e.stopPropagation();
            openCategoryModal(category);
        });
        
        // 刪除分類按鈕 Delete category button
        categoryItem.querySelector('.delete-btn').addEventListener('click', (e) => {
            e.stopPropagation();
            openDeleteConfirmModal('category', category.id, `確定要刪除「${category.name}」分類嗎？此操作將會同時刪除該分類下的所有商品，且無法恢復。`);
        });
        
        categoryList.appendChild(categoryItem);
    });
}

/**
 * 選擇分類
 * Select category
 * @param {string} categoryId - 分類ID Category ID
 */
function selectCategory(categoryId) {
    selectedCategoryId = categoryId;
    
    // 更新分類選中狀態 Update category selection state
    document.querySelectorAll('.category-item').forEach(item => {
        item.classList.remove('active');
    });
    
    const selectedCategory = document.querySelector(`.category-item .edit-btn[data-id="${categoryId}"]`);
    if (selectedCategory) {
        selectedCategory.closest('.category-item').classList.add('active');
    }
    
    // 渲染該分類下的商品 Render items in this category
    renderItems();
}

/**
 * 渲染商品列表
 * Render item list
 */
function renderItems() {
    const itemList = document.querySelector('.item-section .item-list');
    
    // 清空商品列表 Clear item list
    itemList.innerHTML = '';
    
    // 過濾當前分類下的商品 Filter items in current category
    const filteredItems = selectedCategoryId 
        ? items.filter(item => item.category_id === selectedCategoryId)
        : items;
    
    // 如果沒有商品，顯示空狀態 If no items, show empty state
    if (filteredItems.length === 0) {
        itemList.innerHTML = '<div class="empty-state">目前分類下沒有商品</div>';
        return;
    }
    
    // 渲染每個商品 Render each item
    filteredItems.forEach(item => {
        const itemCard = document.createElement('div');
        itemCard.className = 'item-card';
        if (!item.active) {
            itemCard.classList.add('inactive');
        }
        
        // 找到該商品所屬的分類名稱 Find the category name of this item
        const categoryName = categories.find(cat => cat.id === item.category_id)?.name || '未分類';
        
        itemCard.innerHTML = `
            <div class="item-header">
                <h3>${item.name}</h3>
                <div class="item-status">
                    ${!item.active ? '<span class="badge badge-inactive">未上架</span>' : ''}
                    ${item.track_inventory ? '<span class="badge badge-inventory">庫存管理</span>' : ''}
                </div>
            </div>
            <div class="item-content">
                <div class="item-info">
                    <p><strong>價格:</strong> $${parseFloat(item.price).toFixed(2)}</p>
                    <p><strong>分類:</strong> ${categoryName}</p>
                    ${item.description ? `<p class="description"><strong>描述:</strong> ${item.description}</p>` : ''}
                    ${item.track_inventory ? `
                    <p><strong>庫存:</strong> ${item.stock_quantity || 0} ${item.low_stock_alert && item.stock_quantity <= item.low_stock_alert ? '<span class="badge badge-warning">庫存不足</span>' : ''}</p>
                    ` : ''}
                </div>
                <div class="item-actions">
                    <button class="btn btn-icon edit-btn" data-id="${item.id}" title="編輯商品">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-icon delete-btn" data-id="${item.id}" title="刪除商品">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        
        // 編輯商品按鈕 Edit item button
        itemCard.querySelector('.edit-btn').addEventListener('click', () => {
            openItemModal(item);
        });
        
        // 刪除商品按鈕 Delete item button
        itemCard.querySelector('.delete-btn').addEventListener('click', () => {
            openDeleteConfirmModal('item', item.id, `確定要刪除「${item.name}」商品嗎？此操作無法恢復。`);
        });
        
        itemList.appendChild(itemCard);
    });
}

/**
 * 篩選商品 (依搜尋關鍵字)
 * Filter items (by search keyword)
 */
function filterItems() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase().trim();
    
    // 如果沒有搜尋關鍵字，顯示所有商品 If no search term, show all items
    if (!searchTerm) {
        renderItems();
        return;
    }
    
    const itemList = document.querySelector('.item-section .item-list');
    
    // 清空商品列表 Clear item list
    itemList.innerHTML = '';
    
    // 過濾符合搜尋關鍵字的商品 Filter items that match search term
    const filteredItems = items.filter(item => {
        return (
            item.name.toLowerCase().includes(searchTerm) || 
            (item.description && item.description.toLowerCase().includes(searchTerm))
        );
    });
    
    // 如果沒有符合的商品，顯示空狀態 If no matching items, show empty state
    if (filteredItems.length === 0) {
        itemList.innerHTML = '<div class="empty-state">沒有符合搜尋的商品</div>';
        return;
    }
    
    // 渲染符合的商品 Render matching items
    filteredItems.forEach(item => {
        const itemCard = document.createElement('div');
        itemCard.className = 'item-card';
        if (!item.active) {
            itemCard.classList.add('inactive');
        }
        
        // 找到該商品所屬的分類名稱 Find the category name of this item
        const categoryName = categories.find(cat => cat.id === item.category_id)?.name || '未分類';
        
        itemCard.innerHTML = `
            <div class="item-header">
                <h3>${item.name}</h3>
                <div class="item-status">
                    ${!item.active ? '<span class="badge badge-inactive">未上架</span>' : ''}
                    ${item.track_inventory ? '<span class="badge badge-inventory">庫存管理</span>' : ''}
                </div>
            </div>
            <div class="item-content">
                <div class="item-info">
                    <p><strong>價格:</strong> $${parseFloat(item.price).toFixed(2)}</p>
                    <p><strong>分類:</strong> ${categoryName}</p>
                    ${item.description ? `<p class="description"><strong>描述:</strong> ${item.description}</p>` : ''}
                    ${item.track_inventory ? `
                    <p><strong>庫存:</strong> ${item.stock_quantity || 0} ${item.low_stock_alert && item.stock_quantity <= item.low_stock_alert ? '<span class="badge badge-warning">庫存不足</span>' : ''}</p>
                    ` : ''}
                </div>
                <div class="item-actions">
                    <button class="btn btn-icon edit-btn" data-id="${item.id}" title="編輯商品">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-icon delete-btn" data-id="${item.id}" title="刪除商品">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        
        // 編輯商品按鈕 Edit item button
        itemCard.querySelector('.edit-btn').addEventListener('click', () => {
            openItemModal(item);
        });
        
        // 刪除商品按鈕 Delete item button
        itemCard.querySelector('.delete-btn').addEventListener('click', () => {
            openDeleteConfirmModal('item', item.id, `確定要刪除「${item.name}」商品嗎？此操作無法恢復。`);
        });
        
        itemList.appendChild(itemCard);
    });
}

/**
 * 開啟分類編輯模態窗
 * Open category edit modal
 * @param {Object} category - 要編輯的分類，不提供則為新增模式 Category to edit, if not provided, it's in add mode
 */
function openCategoryModal(category = null) {
    const modal = document.getElementById('category-modal');
    const modalTitle = modal.querySelector('.modal-header h3');
    const categoryIdInput = document.getElementById('category-id');
    const categoryNameInput = document.getElementById('category-name');
    const categoryDescriptionInput = document.getElementById('category-description');
    const categorySortOrderInput = document.getElementById('category-sort-order');
    
    // 設置模態窗標題 Set modal title
    modalTitle.textContent = category ? '編輯分類' : '新增分類';
    
    // 填充表單資料 Fill form data
    categoryIdInput.value = category ? category.id : '';
    categoryNameInput.value = category ? category.name : '';
    categoryDescriptionInput.value = category ? category.description || '' : '';
    categorySortOrderInput.value = category ? category.sort_order || 0 : 0;
    
    // 顯示模態窗 Show modal
    modal.style.display = 'flex';
}

/**
 * 開啟商品編輯模態窗
 * Open item edit modal
 * @param {Object} item - 要編輯的商品，不提供則為新增模式 Item to edit, if not provided, it's in add mode
 */
function openItemModal(item = null) {
    const modal = document.getElementById('item-modal');
    const modalTitle = modal.querySelector('.modal-header h3');
    const itemIdInput = document.getElementById('item-id');
    const itemNameInput = document.getElementById('item-name');
    const itemPriceInput = document.getElementById('item-price');
    const itemCategorySelect = document.getElementById('item-category');
    const itemDescriptionInput = document.getElementById('item-description');
    const itemActiveCheck = document.getElementById('item-active');
    const itemTrackInventoryCheck = document.getElementById('item-track-inventory');
    const inventoryFields = document.getElementById('inventory-fields');
    const itemStockQuantityInput = document.getElementById('item-stock-quantity');
    const itemLowStockAlertInput = document.getElementById('item-low-stock-alert');
    
    // 設置模態窗標題 Set modal title
    modalTitle.textContent = item ? '編輯商品' : '新增商品';
    
    // 填充分類選項 Fill category options
    itemCategorySelect.innerHTML = '';
    categories.forEach(category => {
        const option = document.createElement('option');
        option.value = category.id;
        option.textContent = category.name;
        itemCategorySelect.appendChild(option);
    });
    
    // 填充表單資料 Fill form data
    itemIdInput.value = item ? item.id : '';
    itemNameInput.value = item ? item.name : '';
    itemPriceInput.value = item ? parseFloat(item.price).toFixed(2) : '';
    itemCategorySelect.value = item ? item.category_id : selectedCategoryId || '';
    itemDescriptionInput.value = item ? item.description || '' : '';
    itemActiveCheck.checked = item ? !!item.active : true;
    
    // 設置庫存相關欄位 Set inventory related fields
    const trackInventory = item ? !!item.track_inventory : false;
    itemTrackInventoryCheck.checked = trackInventory;
    inventoryFields.style.display = trackInventory ? 'block' : 'none';
    
    itemStockQuantityInput.value = item && item.stock_quantity ? item.stock_quantity : 0;
    itemLowStockAlertInput.value = item && item.low_stock_alert ? item.low_stock_alert : 0;
    
    // 顯示模態窗 Show modal
    modal.style.display = 'flex';
}

/**
 * 開啟刪除確認模態窗
 * Open delete confirmation modal
 * @param {string} type - 刪除類型 (category/item) Delete type (category/item)
 * @param {string} id - 要刪除的ID ID to delete
 * @param {string} message - 顯示的確認訊息 Confirmation message to display
 */
function openDeleteConfirmModal(type, id, message) {
    const modal = document.getElementById('confirm-modal');
    const messageElement = document.getElementById('confirm-message');
    const deleteIdInput = document.getElementById('delete-id');
    const deleteTypeInput = document.getElementById('delete-type');
    
    messageElement.textContent = message;
    deleteIdInput.value = id;
    deleteTypeInput.value = type;
    
    // 顯示模態窗 Show modal
    modal.style.display = 'flex';
}

/**
 * 關閉所有模態窗
 * Close all modals
 */
function closeAllModals() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.style.display = 'none';
    });
}

/**
 * 保存分類資料
 * Save category data
 */
function saveCategory() {
    const categoryId = document.getElementById('category-id').value;
    const categoryName = document.getElementById('category-name').value;
    const categoryDescription = document.getElementById('category-description').value;
    const categorySortOrder = document.getElementById('category-sort-order').value || 0;
    
    // 驗證表單資料 Validate form data
    if (!categoryName) {
        showMessage('請輸入分類名稱', 'error');
        return;
    }
    
    // 顯示載入中訊息 Show loading message
    showMessage('正在保存分類資料...', 'info');
    
    // 準備要發送的資料 Prepare data to send
    const categoryData = {
        id: categoryId || null,
        name: categoryName,
        description: categoryDescription,
        sort_order: parseInt(categorySortOrder)
    };
    
    // 發送請求到後端 Send request to backend
    fetch('../kiosk/menu.php?action=saveCategory', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(categoryData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('無法保存分類資料');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // 關閉模態窗 Close modal
            closeAllModals();
            
            // 重新載入資料 Reload data
            initPage();
            
            // 顯示成功訊息 Show success message
            showMessage('分類保存成功！', 'success');
        } else {
            throw new Error(data.message || '保存分類時發生錯誤');
        }
    })
    .catch(error => {
        console.error('保存分類時發生錯誤:', error);
        showMessage('保存分類時發生錯誤: ' + error.message, 'error');
    });
}

/**
 * 保存商品資料
 * Save item data
 */
function saveItem() {
    const itemId = document.getElementById('item-id').value;
    const itemName = document.getElementById('item-name').value;
    const itemPrice = document.getElementById('item-price').value;
    const itemCategory = document.getElementById('item-category').value;
    const itemDescription = document.getElementById('item-description').value;
    const itemActive = document.getElementById('item-active').checked;
    const itemTrackInventory = document.getElementById('item-track-inventory').checked;
    const itemStockQuantity = document.getElementById('item-stock-quantity').value || 0;
    const itemLowStockAlert = document.getElementById('item-low-stock-alert').value || 0;
    
    // 驗證表單資料 Validate form data
    if (!itemName) {
        showMessage('請輸入商品名稱', 'error');
        return;
    }
    
    if (!itemPrice || isNaN(parseFloat(itemPrice)) || parseFloat(itemPrice) < 0) {
        showMessage('請輸入有效的商品價格', 'error');
        return;
    }
    
    if (!itemCategory) {
        showMessage('請選擇商品分類', 'error');
        return;
    }
    
    // 顯示載入中訊息 Show loading message
    showMessage('正在保存商品資料...', 'info');
    
    // 準備要發送的資料 Prepare data to send
    const itemData = {
        id: itemId || null,
        name: itemName,
        price: parseFloat(itemPrice),
        category_id: itemCategory,
        description: itemDescription,
        active: itemActive,
        track_inventory: itemTrackInventory,
        stock_quantity: parseInt(itemStockQuantity),
        low_stock_alert: parseInt(itemLowStockAlert)
    };
    
    // 發送請求到後端 Send request to backend
    fetch('../kiosk/menu.php?action=saveItem', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(itemData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('無法保存商品資料');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // 關閉模態窗 Close modal
            closeAllModals();
            
            // 重新載入資料 Reload data
            initPage();
            
            // 顯示成功訊息 Show success message
            showMessage('商品保存成功！', 'success');
        } else {
            throw new Error(data.message || '保存商品時發生錯誤');
        }
    })
    .catch(error => {
        console.error('保存商品時發生錯誤:', error);
        showMessage('保存商品時發生錯誤: ' + error.message, 'error');
    });
}

/**
 * 確認刪除
 * Confirm deletion
 */
function confirmDelete() {
    const deleteId = document.getElementById('delete-id').value;
    const deleteType = document.getElementById('delete-type').value;
    
    // 顯示載入中訊息 Show loading message
    showMessage(`正在刪除${deleteType === 'category' ? '分類' : '商品'}...`, 'info');
    
    // 準備API端點 Prepare API endpoint
    const endpoint = deleteType === 'category' ? 'deleteCategory' : 'deleteItem';
    
    // 發送請求到後端 Send request to backend
    fetch(`../kiosk/menu.php?action=${endpoint}&id=${deleteId}`, {
        method: 'DELETE'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`無法刪除${deleteType === 'category' ? '分類' : '商品'}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // 關閉模態窗 Close modal
            closeAllModals();
            
            // 重新載入資料 Reload data
            initPage();
            
            // 顯示成功訊息 Show success message
            showMessage(`${deleteType === 'category' ? '分類' : '商品'}刪除成功！`, 'success');
        } else {
            throw new Error(data.message || `刪除${deleteType === 'category' ? '分類' : '商品'}時發生錯誤`);
        }
    })
    .catch(error => {
        console.error(`刪除${deleteType === 'category' ? '分類' : '商品'}時發生錯誤:`, error);
        showMessage(`刪除${deleteType === 'category' ? '分類' : '商品'}時發生錯誤: ` + error.message, 'error');
    });
}

/**
 * 顯示訊息
 * Show message
 * @param {string} text - 訊息文字 Message text
 * @param {string} type - 訊息類型 (success/error/info) Message type
 */
function showMessage(text, type = 'info') {
    const messageElement = document.getElementById('message');
    
    // 設置訊息類型樣式 Set message type style
    messageElement.className = 'message';
    messageElement.classList.add(`message-${type}`);
    
    // 設置訊息內容 Set message content
    messageElement.textContent = text;
    
    // 顯示訊息 Show message
    messageElement.style.display = 'block';
    
    // 如果是成功或錯誤訊息，設定自動隱藏 If success or error message, set auto hide
    if (type === 'success' || type === 'error') {
        setTimeout(() => {
            hideMessage();
        }, 3000);
    }
}

/**
 * 隱藏訊息
 * Hide message
 */
function hideMessage() {
    const messageElement = document.getElementById('message');
    messageElement.style.display = 'none';
} 