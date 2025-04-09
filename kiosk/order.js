let lang = "zh";
let cart = [];
let menuData = [];

// 根據環境返回正確的 API URL
function getApiUrl(path) {
  // 如果是 Electron 環境，使用當前頁面的主機名
  if (window.electronAPI?.isElectron) {
    return `${window.location.protocol}//${window.location.host}/${path}`;
  }
  // 其他環境使用相對路徑
  return path;
}

function loadMenu() {
  console.log('Attempting to load menu...');
  
  fetch(getApiUrl('menu.json'))
    .then(response => {
      console.log('Menu fetch response:', response);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then(data => {
      console.log('Menu data loaded:', data);
      menuData = data;
      renderMenu();
    })
    .catch(error => {
      console.error('Error loading menu:', error);
      document.getElementById("menu").innerHTML = `
        <div class="error">無法載入菜單：${error.message}</div>
      `;
    });
}

function renderMenu() {
  const menu = document.getElementById("menu");
  menu.innerHTML = "";
  menuData.forEach(item => {
    const div = document.createElement("div");
    div.className = "item";
    div.innerHTML = `
      <img src="${item.image}" alt="${item.name_en}">
      <h3>${lang === "zh" ? item.name_zh : item.name_en}</h3>
      <p>$${item.price}</p>
      <button onclick='addToCart(${JSON.stringify(item)})'>
        ${lang === "zh" ? "加入" : "Add"}
      </button>
    `;
    menu.appendChild(div);
  });
}

function addToCart(item) {
  cart.push(item);
  updateCartDisplay();
}

function updateCartDisplay() {
  const cartList = document.getElementById("cart");
  const total = document.getElementById("total");
  cartList.innerHTML = "";
  let sum = 0;
  cart.forEach(item => {
    const li = document.createElement("li");
    li.innerText = `${lang === "zh" ? item.name_zh : item.name_en} - $${item.price}`;
    cartList.appendChild(li);
    sum += item.price;
  });
  total.innerText = `$${sum}`;
}

function toggleLanguage() {
  lang = lang === "zh" ? "en" : "zh";
  document.getElementById("langBtn").innerText = lang === "zh" ? "English" : "中文";
  renderMenu();
}

function submitOrder() {
  if (cart.length === 0) {
    alert("請先選擇餐點！");
    return;
  }

  fetch(getApiUrl('save.php'), {
    method: "POST",
    body: JSON.stringify({ cart }),
    headers: {
      "Content-Type": "application/json"
    }
  })
    .then(res => res.json())
    .then(result => {
      if (result.status === "ok") {
        alert("訂單已送出，您的號碼是：" + result.number);
        cart = [];
        updateCartDisplay();
      } else {
        alert("送出失敗：" + (result.message || "未知錯誤"));
      }
    })
    .catch(err => {
      console.error("送出訂單錯誤：", err);
      alert("無法送出訂單，請稍後再試。");
    });
}

function checkOrderStatus() {
  fetch(getApiUrl('list-orders.php'))
    .then(res => res.json())
    .then(orders => {
      console.log("Current orders:", orders);
      // 可以在這裡添加更新UI的邏輯
    })
    .catch(err => {
      console.error("檢查訂單狀態時發生錯誤：", err);
    });
}

document.addEventListener("DOMContentLoaded", () => {
  loadMenu();
  document.getElementById("langBtn").addEventListener("click", toggleLanguage);
  document.getElementById("submitOrder").addEventListener("click", submitOrder);
  
  // 每30秒檢查一次訂單狀態
  setInterval(checkOrderStatus, 30000);
  // 初始檢查
  checkOrderStatus();
}); 