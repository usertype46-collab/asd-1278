// 直接從 Google CDN 引入 Firebase (完全不需要 npm 或編譯，適合 GitHub Pages)
import { initializeApp } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-app.js";
import { getFirestore, collection, addDoc, onSnapshot, deleteDoc, doc, updateDoc, query, where, getDocs, serverTimestamp, orderBy } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore.js";

// 您的 Firebase 設定
const firebaseConfig = {
  apiKey: "AIzaSyC3wmOmhpFG3gaHk9uUHmcjS09sdAAHM9k",
  authDomain: "smart-matching-system-services.firebaseapp.com",
  projectId: "smart-matching-system-services",
  storageBucket: "smart-matching-system-services.firebasestorage.app",
  messagingSenderId: "699119431060",
  appId: "1:699119431060:web:0672bf06c3cd7707f8f381",
  measurementId: "G-RMS75RXKK0"
};

// 初始化 Firebase 與 Firestore
const app = initializeApp(firebaseConfig);
const db = getFirestore(app);

// ==========================
// 模組 1：技師管理 (CRUD)
// ==========================
const techForm = document.getElementById('tech-form');
techForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  const id = document.getElementById('tech-id').value;
  const name = document.getElementById('tech-name').value;
  const skills = document.getElementById('tech-skills').value;

  try {
    if (id) {
      await updateDoc(doc(db, "technicians", id), { name, skills });
      document.getElementById('tech-submit-btn').textContent = "新增技師";
    } else {
      await addDoc(collection(db, "technicians"), { name, skills, status: "available" });
    }
    techForm.reset();
    document.getElementById('tech-id').value = "";
    alert("技師資料已儲存！");
  } catch (error) { console.error("Error:", error); }
});

window.editTech = (id, name, skills) => {
  document.getElementById('tech-id').value = id;
  document.getElementById('tech-name').value = name;
  document.getElementById('tech-skills').value = skills;
  document.getElementById('tech-submit-btn').textContent = "更新技師";
  switchTab('view-techs');
};

window.deleteTech = async (id) => {
  if(confirm("確定移除此技師？")) {
    await deleteDoc(doc(db, "technicians", id));
  }
};

// 即時監聽技師資料
onSnapshot(collection(db, "technicians"), (snapshot) => {
  const techsList = document.getElementById('techs-list');
  const techSelect = document.getElementById('tech-select');
  techsList.innerHTML = "";
  techSelect.innerHTML = '<option value="" disabled selected>請選擇搭配技師</option>';
  
  snapshot.forEach((doc) => {
    const data = doc.data();
    // 渲染技師列表
    techsList.innerHTML += `
      <div class="list-card">
        <h3>${data.name}</h3>
        <p>專長：${data.skills}</p>
        <div class="action-btns">
          <button class="btn-small btn-edit" onclick="editTech('${doc.id}', '${data.name}', '${data.skills}')">編輯</button>
          <button class="btn-small btn-delete" onclick="deleteTech('${doc.id}')">刪除</button>
        </div>
      </div>
    `;
    // 更新預約選單
    techSelect.innerHTML += `<option value="${doc.id}|${data.name}">${data.name} (${data.skills})</option>`;
  });
});

// ==========================
// 模組 2：預約與客戶追蹤連動
// ==========================
const bookingForm = document.getElementById('booking-form');
bookingForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  const clientName = document.getElementById('client-name').value;
  const need = document.getElementById('client-need').value;
  const techData = document.getElementById('tech-select').value.split('|');
  const techId = techData[0];
  const techName = techData[1];
  const bookTime = document.getElementById('book-time').value;

  try {
    // 1. 建立預約紀錄
    await addDoc(collection(db, "appointments"), {
      clientName, need, techId, techName, bookTime, createdAt: serverTimestamp()
    });

    // 2. 更新或新增客戶紀錄 (統計造訪頻率)
    const clientQuery = query(collection(db, "clients"), where("name", "==", clientName));
    const querySnapshot = await getDocs(clientQuery);
    
    if (!querySnapshot.empty) {
      // 老客戶，造訪次數 +1
      const clientDoc = querySnapshot.docs[0];
      const newCount = clientDoc.data().visitCount + 1;
      await updateDoc(doc(db, "clients", clientDoc.id), { 
        visitCount: newCount, 
        lastVisit: bookTime,
        lastNeed: need
      });
    } else {
      // 新客戶
      await addDoc(collection(db, "clients"), {
        name: clientName, visitCount: 1, lastVisit: bookTime, lastNeed: need
      });
    }

    bookingForm.reset();
    alert("預約成功！客戶紀錄已連動更新。");
  } catch (error) { console.error("Error:", error); }
});

// 即時監聽預約紀錄 (依時間排序)
const q_appt = query(collection(db, "appointments"), orderBy("bookTime", "desc"));
onSnapshot(q_appt, (snapshot) => {
  const list = document.getElementById('appointments-list');
  list.innerHTML = "";
  snapshot.forEach((doc) => {
    const data = doc.data();
    list.innerHTML += `
      <div class="list-card">
        <h3>客戶：${data.clientName}</h3>
        <p>服務需求：${data.need}</p>
        <p>指派技師：<strong>${data.techName}</strong></p>
        <p>預約時間：${data.bookTime.replace('T', ' ')}</p>
      </div>
    `;
  });
});

// ==========================
// 模組 3：即時客戶名單與頻率
// ==========================
const q_clients = query(collection(db, "clients"), orderBy("visitCount", "desc"));
onSnapshot(q_clients, (snapshot) => {
  const list = document.getElementById('clients-list');
  list.innerHTML = "";
  snapshot.forEach((doc) => {
    const data = doc.data();
    // 依造訪次數給予不同標籤
    const tag = data.visitCount >= 3 ? "🔥 常客 (VIP)" : "🌱 新客";
    list.innerHTML += `
      <div class="list-card">
        <h3>${data.name} <span style="font-size:0.8rem; color:red;">${tag}</span></h3>
        <p>總造訪次數：<strong>${data.visitCount} 次</strong></p>
        <p>上次消費：${data.lastVisit.replace('T', ' ')}</p>
        <p>偏好服務：${data.lastNeed}</p>
      </div>
    `;
  });
});
