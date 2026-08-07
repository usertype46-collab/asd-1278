// 引入 Firebase SDK (使用瀏覽器端 CDN 模組)
import { initializeApp } from "https://www.gstatic.com/firebasejs/10.8.1/firebase-app.js";
import { getFirestore, collection, addDoc, onSnapshot, deleteDoc, doc, query, orderBy, serverTimestamp } from "https://www.gstatic.com/firebasejs/10.8.1/firebase-firestore.js";

// 您提供的 Firebase 配置參數
const firebaseConfig = {
  apiKey: "AIzaSyC3wmOmhpFG3gaHk9uUHmcjS09sdAAHM9k",
  authDomain: "smart-matching-system-services.firebaseapp.com",
  projectId: "smart-matching-system-services",
  storageBucket: "smart-matching-system-services.firebasestorage.app",
  messagingSenderId: "699119431060",
  appId: "1:699119431060:web:0672bf06c3cd7707f8f381",
  measurementId: "G-RMS75RXKK0"
};

// 初始化 Firebase & Firestore
const app = initializeApp(firebaseConfig);
const db = getFirestore(app);

// 系統狀態暫存
let state = {
  clients: [],
  techs: [],
  bookings: []
};

// ==========================================
// 1. 即時監聽資料庫 (Real-time Listeners)
// ==========================================

// 監聽技師資料
onSnapshot(collection(db, "technicians"), (snapshot) => {
  state.techs = snapshot.docs.map(doc => ({ id: doc.id, ...doc.data() }));
  renderTechList();
  updateTechDropdown(); // 當技師更新時，連動更新預約下拉選單
});

// 監聽客戶資料
onSnapshot(collection(db, "clients"), (snapshot) => {
  state.clients = snapshot.docs.map(doc => ({ id: doc.id, ...doc.data() }));
  renderClientList();
  updateClientDropdown();
});

// 監聽預約資料
const q = query(collection(db, "appointments"), orderBy("time", "desc"));
onSnapshot(q, (snapshot) => {
  state.bookings = snapshot.docs.map(doc => ({ id: doc.id, ...doc.data() }));
  renderBookingList();
  renderClientList(); // 預約更新時，重新計算客戶造訪頻率
});


// ==========================================
// 2. 表單送出處理 (CRUD Operations)
// ==========================================

// 新增技師
document.getElementById('tech-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const name = document.getElementById('tech-name').value;
  // 獲取勾選的技能
  const skillCheckboxes = document.querySelectorAll('#tech-skills-checkboxes input:checked');
  const skills = Array.from(skillCheckboxes).map(cb => cb.value);

  if (skills.length === 0) return alert("請至少選擇一項技能！");

  await addDoc(collection(db, "technicians"), { name, skills });
  e.target.reset();
  alert("技師新增成功！");
});

// 移除技師
window.deleteTech = async (id) => {
  if (confirm("確定要移除此技師嗎？")) {
    await deleteDoc(doc(db, "technicians", id));
  }
};

// 新增客戶
document.getElementById('client-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const name = document.getElementById('client-name').value;
  const phone = document.getElementById('client-phone').value;
  const note = document.getElementById('client-note').value;

  await addDoc(collection(db, "clients"), { name, phone, note, createdAt: serverTimestamp() });
  e.target.reset();
  alert("客戶建立成功！");
});

// 新增預約
document.getElementById('booking-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const clientId = document.getElementById('book-client').value;
  const need = document.getElementById('book-need').value;
  const techId = document.getElementById('book-tech').value;
  const time = document.getElementById('book-time').value;

  if(!clientId || !techId) return alert("請完整填寫預約資訊！");

  const clientName = state.clients.find(c => c.id === clientId).name;
  const techName = state.techs.find(t => t.id === techId).name;

  await addDoc(collection(db, "appointments"), { 
    clientId, clientName, 
    techId, techName, 
    service: need, 
    time,
    status: '已預約'
  });
  e.target.reset();
  document.getElementById('book-tech').disabled = true;
  document.getElementById('book-tech').innerHTML = '<option value="">請先選擇客戶需求</option>';
  alert("預約成功！");
});


// ==========================================
// 3. 智能媒合連動邏輯 (Client Need -> Technician)
// ==========================================
document.getElementById('book-need').addEventListener('change', (e) => {
  const selectedNeed = e.target.value;
  const techSelect = document.getElementById('book-tech');
  
  if (!selectedNeed) {
    techSelect.disabled = true;
    techSelect.innerHTML = '<option value="">請先選擇客戶需求</option>';
    return;
  }

  // 篩選出具備該項技能的技師
  const matchedTechs = state.techs.filter(tech => tech.skills.includes(selectedNeed));
  
  techSelect.disabled = false;
  if (matchedTechs.length === 0) {
    techSelect.innerHTML = '<option value="">無符合此技能的技師</option>';
    techSelect.disabled = true;
  } else {
    techSelect.innerHTML = '<option value="">請選擇技師...</option>' + 
      matchedTechs.map(t => `<option value="${t.id}">${t.name}</option>`).join('');
  }
});


// ==========================================
// 4. 畫面渲染函式 (UI Rendering)
// ==========================================

function renderTechList() {
  const list = document.getElementById('tech-list');
  list.innerHTML = state.techs.map(tech => `
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100 flex justify-between items-center">
      <div>
        <h3 class="font-bold text-gray-800">${tech.name}</h3>
        <p class="text-xs text-teal-600 mt-1">${tech.skills.join('、')}</p>
      </div>
      <button onclick="deleteTech('${tech.id}')" class="text-red-500 hover:text-red-700 bg-red-50 p-2 rounded-full">
        <i class="fa-solid fa-trash"></i>
      </button>
    </div>
  `).join('');
}

function renderClientList() {
  const list = document.getElementById('client-list');
  list.innerHTML = state.clients.map(client => {
    // 統計客戶造訪頻率
    const visitCount = state.bookings.filter(b => b.clientId === client.id).length;
    let badgeClass = visitCount > 3 ? 'bg-orange-100 text-orange-600' : 'bg-gray-100 text-gray-600';
    let badgeText = visitCount > 3 ? '常客 / VIP' : '一般客戶';

    return `
      <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
        <div class="flex justify-between items-start">
          <div>
            <h3 class="font-bold text-gray-800">${client.name} <span class="text-sm font-normal text-gray-500 ml-1">${client.phone}</span></h3>
            <p class="text-xs text-gray-500 mt-1">備註：${client.note || '無'}</p>
          </div>
          <div class="text-right">
            <span class="text-xs px-2 py-1 rounded-full ${badgeClass} font-bold">${badgeText}</span>
            <p class="text-xs font-bold mt-2 text-teal-600">造訪次數：${visitCount} 次</p>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

function renderBookingList() {
  const list = document.getElementById('booking-list');
  list.innerHTML = state.bookings.map(book => {
    const timeObj = new Date(book.time);
    const formattedTime = `${timeObj.getMonth()+1}/${timeObj.getDate()} ${timeObj.getHours().toString().padStart(2,'0')}:${timeObj.getMinutes().toString().padStart(2,'0')}`;
    return `
      <div class="bg-white p-3 rounded-lg shadow-sm border-l-4 border-teal-500 flex justify-between items-center">
        <div>
          <p class="text-sm font-bold text-gray-800">${book.clientName} <span class="text-xs font-normal text-gray-500">預約了</span> ${book.service}</p>
          <p class="text-xs text-gray-500 mt-1"><i class="fa-regular fa-clock mr-1"></i>${formattedTime} | 技師：${book.techName}</p>
        </div>
        <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded">${book.status}</span>
      </div>
    `;
  }).join('');
}

function updateClientDropdown() {
  const select = document.getElementById('book-client');
  select.innerHTML = '<option value="">請選擇客戶...</option>' + 
    state.clients.map(c => `<option value="${c.id}">${c.name} (${c.phone.slice(-4)})</option>`).join('');
}

// 防呆機制：若技師資料被刪除，清理下拉選單
function updateTechDropdown() {
  const need = document.getElementById('book-need').value;
  if(need) {
    const event = new Event('change');
    document.getElementById('book-need').dispatchEvent(event);
  }
}
