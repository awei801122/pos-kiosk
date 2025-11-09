// assets/js/config.js
let config = {
    api_base_url: ''
  };
  
  fetch("assets/config.json")
    .then(res => res.json())
    .then(data => {
      config = data;
      console.log("✅ Config Loaded:", config);
    })
    .catch(error => {
      console.error("❌ 無法載入 config.json", error);
    });