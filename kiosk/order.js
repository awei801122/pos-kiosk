let lang = "zh";
let cart = [];
let menuData = [];

function loadMenu() {
  console.log('Attempting to load menu...');
  fetch("menu.json")
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

  fetch("save.php", {
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

document.addEventListener("DOMContentLoaded", () => {
  loadMenu();
  document.getElementById("langBtn").addEventListener("click", toggleLanguage);
  document.getElementById("submitOrder").addEventListener("click", submitOrder);
}); 