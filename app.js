import { initializeApp } from "https://www.gstatic.com/firebasejs/10.8.1/firebase-app.js";
import { getDatabase, ref, onValue, set, get, child, update } from "https://www.gstatic.com/firebasejs/10.8.1/firebase-database.js";

// ==========================================
// 1. Firebase 初始化配置
// ==========================================
const firebaseConfig = {
  apiKey: "AIzaSyBvx7Ej2h9ZV5S1t_Gag756qop_jcoxy2U",
  authDomain: "shopee-60a13.firebaseapp.com",
  databaseURL: "https://shopee-60a13-default-rtdb.firebaseio.com",
  projectId: "shopee-60a13",
  storageBucket: "shopee-60a13.firebasestorage.app",
  messagingSenderId: "18436404849",
  appId: "1:18436404849:web:f2bdfa4189ff15456fcdd0"
};

const app = initializeApp(firebaseConfig);
const db = getDatabase(app);

// ==========================================
// 2. 國際化多語系支援 (i18n)
// ==========================================
let currentLang = 'zh'; // 預設語言

const i18n = {
    zh: {
        appTitle: "烤漆順序紀錄系統",
        tabMain: "主控台",
        tabHistory: "歷史查詢",
        todayTask: "今日作業資料",
        noDataToday: "今日尚無排程資料",
        nextTask: "次日烤漆作業程序建立",
        targetDate: "目標日期:",
        colSeq: "序號",
        colColor: "顏色",
        colModel: "機型",
        colRemarks: "備註",
        addRow: "加入下一筆資料",
        saveData: "儲存紀錄至雲端",
        historySearch: "歷史記錄查詢",
        searchDate: "日期 (Date)",
        searchBtn: "查詢",
        noHistoryResult: "請設定條件進行查詢，或無符合資料",
        placeholderColor: "選擇或手動輸入顏色",
        placeholderModel: "選擇或手動輸入機型",
        placeholderRemark: "輸入備註內容...",
        toastSaveSuccess: "資料已成功同步至 Firebase!",
        toastSaveFail: "儲存失敗，請檢查網路連線",
        toastEmptyRow: "無法儲存空資料列",
        toastSearchDone: "查詢完成"
    },
    vi: {
        appTitle: "Hệ thống Ghi chú Sơn",
        tabMain: "Bảng điều khiển",
        tabHistory: "Lịch sử",
        todayTask: "Dữ liệu làm việc hôm nay",
        noDataToday: "Không có lịch trình hôm nay",
        nextTask: "Tạo lịch trình sơn ngày tiếp theo",
        targetDate: "Ngày mục tiêu:",
        colSeq: "STT",
        colColor: "Màu sắc",
        colModel: "Mô hình",
        colRemarks: "Ghi chú",
        addRow: "Thêm dòng mới",
        saveData: "Lưu lên đám mây",
        historySearch: "Tìm kiếm lịch sử",
        searchDate: "Ngày (Date)",
        searchBtn: "Tìm kiếm",
        noHistoryResult: "Vui lòng nhập điều kiện hoặc không có dữ liệu",
        placeholderColor: "Chọn hoặc nhập màu",
        placeholderModel: "Chọn hoặc nhập mô hình",
        placeholderRemark: "Nhập ghi chú...",
        toastSaveSuccess: "Đã lưu thành công lên Firebase!",
        toastSaveFail: "Lưu thất bại, kiểm tra kết nối mạng",
        toastEmptyRow: "Không thể lưu dòng trống",
        toastSearchDone: "Tìm kiếm hoàn tất"
    }
};

const weekdays = {
    zh: ["日", "一", "二", "三", "四", "五", "六"],
    vi: ["CN", "T2", "T3", "T4", "T5", "T6", "T7"]
};

// 國定假日設定 (格式: MM-DD)
// 包含台灣常見例假與國定假日，可擴充
const nationalHolidays = [
    "01-01", // 元旦
    "02-28", // 和平紀念日
    "04-04", // 兒童節
    "04-05", // 清明節
    "05-01", // 勞動節
    "10-10"  // 國慶日
];

// 初始預設顏色與機型庫 (當 Firebase 無資料時使用)
const defaultColors = ["WE","YW","GYW","YWH","BKEBK","BKH","BKS","BKS1","BKS5","BKSA","BKSA1","OE","GN","FGN","PELBE","BEG","DBE","IG","IGS","DGS","ATG","GAST","PK","RD","SAT2"];
let globalColors = [...defaultColors];
let globalModels = []; // 動態累積

// ==========================================
// 3. 核心工具函數
// ==========================================

// 格式化日期為 YYYY-MM-DD
function formatDateDB(dateObj) {
    return `${dateObj.getFullYear()}-${String(dateObj.getMonth() + 1).padStart(2, '0')}-${String(dateObj.getDate()).padStart(2, '0')}`;
}

// 取得今日與下一作業日資訊 (自動避開週末與國定假日)
function getDateContext() {
    const today = new Date();
    const todayDB = formatDateDB(today);
    const todayDisplay = `${today.getMonth() + 1}/${today.getDate()} (${weekdays[currentLang][today.getDay()]})`;

    let nextDate = new Date(today);
    let foundNextWorkingDay = false;

    while (!foundNextWorkingDay) {
        nextDate.setDate(nextDate.getDate() + 1);
        const dayOfWeek = nextDate.getDay();
        const mmdd = `${String(nextDate.getMonth() + 1).padStart(2, '0')}-${String(nextDate.getDate()).padStart(2, '0')}`;
        
        // 判斷是否為週末 (0=週日, 6=週六) 或國定假日
        if (dayOfWeek !== 0 && dayOfWeek !== 6 && !nationalHolidays.includes(mmdd)) {
            foundNextWorkingDay = true;
        }
    }

    const nextDB = formatDateDB(nextDate);
    const nextDisplay = `${nextDate.getMonth() + 1}/${nextDate.getDate()} (${weekdays[currentLang][nextDate.getDay()]})`;

    return { todayDB, todayDisplay, nextDB, nextDisplay };
}

// 語系切換執行函數
function applyLanguage() {
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (i18n[currentLang][key]) {
            el.innerText = i18n[currentLang][key];
        }
    });

    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const key = el.getAttribute('data-i18n-placeholder');
        if (i18n[currentLang][key]) {
            el.placeholder = i18n[currentLang][key];
        }
    });

    // 更新日期顯示與按鈕文字
    const dates = getDateContext();
    document.getElementById('today-date-display').innerText = dates.todayDisplay;
    document.getElementById('next-date-display').innerText = dates.nextDisplay;

    const langBtnText = document.getElementById('lang-text');
    langBtnText.innerText = currentLang === 'zh' ? 'Tiếng Việt' : '繁體中文';
}

// 吐司通知 (Toast)
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-emerald-500' : (type === 'error' ? 'bg-red-500' : 'bg-blue-500');
    const icon = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-triangle-exclamation' : 'fa-info-circle');
    
    toast.className = `toast flex items-center space-x-3 text-white px-4 py-3 rounded-lg shadow-lg ${bgColor}`;
    toast.innerHTML = `<i class="fa-solid ${icon}"></i><span>${message}</span>`;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// 更新 Datalist 下拉選單 (顏色與機型)
function updateDatalists() {
    const colorList = document.getElementById('color-options');
    const modelList = document.getElementById('model-options');
    const hColorList = document.getElementById('history-color-list');
    const hModelList = document.getElementById('history-model-list');

    const colorHTML = globalColors.map(c => `<option value="${c}">`).join('');
    const modelHTML = globalModels.map(m => `<option value="${m}">`).join('');

    colorList.innerHTML = colorHTML;
    hColorList.innerHTML = colorHTML;
    modelList.innerHTML = modelHTML;
    hModelList.innerHTML = modelHTML;
}

// ==========================================
// 4. 介面與事件控制
// ==========================================

let nextSeqNum = 1; // 記錄表單自動遞增序號

// 切換 Tab
document.getElementById('tab-main').addEventListener('click', (e) => switchTab('main', e.currentTarget));
document.getElementById('tab-history').addEventListener('click', (e) => switchTab('history', e.currentTarget));

function switchTab(tab, btnElement) {
    document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.nav-btn').forEach(el => el.classList.remove('active'));
    
    document.getElementById(`view-${tab}`).classList.add('active');
    btnElement.classList.add('active');
}

// 語言切換事件
document.getElementById('btn-lang').addEventListener('click', () => {
    currentLang = currentLang === 'zh' ? 'vi' : 'zh';
    applyLanguage();
});

// 新增資料列 (+按鈕)
document.getElementById('btn-add-row').addEventListener('click', () => {
    addNextTaskRow();
});

function addNextTaskRow(data = null) {
    const tbody = document.getElementById('next-task-body');
    const tr = document.createElement('tr');
    tr.className = "hover:bg-gray-50 transition-colors record-row";
    
    const seq = data ? data.seqNo : nextSeqNum++;
    const colorVal = data ? data.color : '';
    const modelVal = data ? data.model : '';
    const remarks = data && data.remarks ? data.remarks : ['']; // 預設至少一個備註空框

    let remarksHTML = '';
    remarks.forEach((rmk, idx) => {
        remarksHTML += `
            <div class="flex items-center space-x-2 mt-2 remark-wrapper first:mt-0">
                <input type="text" value="${rmk}" class="remark-input flex-1 p-2 border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none text-sm" data-i18n-placeholder="placeholderRemark" placeholder="${i18n[currentLang].placeholderRemark}">
                ${idx === 0 ? `<button type="button" class="btn-add-remark text-blue-500 hover:text-blue-700 bg-blue-50 rounded-full w-7 h-7 flex items-center justify-center focus:outline-none"><i class="fa-solid fa-plus text-xs"></i></button>` : `<button type="button" class="btn-rm-remark text-red-400 hover:text-red-600 w-7 h-7 flex items-center justify-center focus:outline-none"><i class="fa-solid fa-minus text-xs"></i></button>`}
            </div>
        `;
    });

    tr.innerHTML = `
        <td class="p-3 text-center font-mono font-bold text-gray-600 seq-col">${seq}</td>
        <td class="p-3">
            <input type="text" list="color-options" value="${colorVal}" class="color-input w-full p-2 border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none text-sm uppercase" data-i18n-placeholder="placeholderColor" placeholder="${i18n[currentLang].placeholderColor}">
        </td>
        <td class="p-3">
            <input type="text" list="model-options" value="${modelVal}" class="model-input w-full p-2 border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none text-sm uppercase" data-i18n-placeholder="placeholderModel" placeholder="${i18n[currentLang].placeholderModel}">
        </td>
        <td class="p-3 remarks-cell">
            ${remarksHTML}
        </td>
        <td class="p-3 text-center">
            <button class="btn-rm-row text-red-500 hover:text-red-700 p-2 rounded hover:bg-red-50 transition-colors" title="刪除此列"><i class="fa-regular fa-trash-can"></i></button>
        </td>
    `;

    tbody.appendChild(tr);

    // 綁定動態事件 (新增/移除備註)
    const btnAddRemark = tr.querySelector('.btn-add-remark');
    const remarksCell = tr.querySelector('.remarks-cell');
    
    btnAddRemark.addEventListener('click', () => {
        const div = document.createElement('div');
        div.className = "flex items-center space-x-2 mt-2 remark-wrapper remark-input-wrapper";
        div.innerHTML = `
            <input type="text" class="remark-input flex-1 p-2 border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none text-sm" data-i18n-placeholder="placeholderRemark" placeholder="${i18n[currentLang].placeholderRemark}">
            <button type="button" class="btn-rm-remark text-red-400 hover:text-red-600 w-7 h-7 flex items-center justify-center focus:outline-none"><i class="fa-solid fa-minus text-xs"></i></button>
        `;
        remarksCell.appendChild(div);
        
        div.querySelector('.btn-rm-remark').addEventListener('click', function() {
            div.remove();
        });
    });

    // 綁定移除列事件與重新排序
    tr.querySelector('.btn-rm-row').addEventListener('click', () => {
        tr.remove();
        recalculateSeq();
    });
}

function recalculateSeq() {
    const rows = document.querySelectorAll('#next-task-body .record-row');
    nextSeqNum = 1;
    rows.forEach(row => {
        row.querySelector('.seq-col').innerText = nextSeqNum++;
    });
}

// 儲存資料到 Firebase
document.getElementById('btn-save-schedule').addEventListener('click', async () => {
    const rows = document.querySelectorAll('#next-task-body .record-row');
    const { nextDB } = getDateContext();
    
    let tasks = [];
    let newColors = new Set();
    let newModels = new Set();

    rows.forEach(row => {
        const seqNo = parseInt(row.querySelector('.seq-col').innerText);
        const color = row.querySelector('.color-input').value.trim().toUpperCase();
        const model = row.querySelector('.model-input').value.trim().toUpperCase();
        
        // 收集所有備註，過濾空白
        const remarkInputs = row.querySelectorAll('.remark-input');
        const remarks = Array.from(remarkInputs).map(inp => inp.value.trim()).filter(val => val !== '');

        if (color || model || remarks.length > 0) {
            tasks.push({ seqNo, color, model, remarks });
            
            // 檢查手動輸入是否需要加入下拉選單資料庫
            if (color && !globalColors.includes(color)) newColors.add(color);
            if (model && !globalModels.includes(model)) newModels.add(model);
        }
    });

    if (tasks.length === 0) {
        showToast(i18n[currentLang].toastEmptyRow, 'error');
        return;
    }

    try {
        // 更新日常任務紀錄
        const dateRef = ref(db, `schedules/${nextDB}`);
        await set(dateRef, tasks);

        // 更新字典庫 (顏色與機型)
        if (newColors.size > 0 || newModels.size > 0) {
            const updates = {};
            if (newColors.size > 0) {
                globalColors = [...new Set([...globalColors, ...newColors])];
                updates['options/colors'] = globalColors;
            }
            if (newModels.size > 0) {
                globalModels = [...new Set([...globalModels, ...newModels])];
                updates['options/models'] = globalModels;
            }
            await update(ref(db), updates);
        }

        showToast(i18n[currentLang].toastSaveSuccess, 'success');
    } catch (error) {
        console.error("Firebase Save Error:", error);
        showToast(i18n[currentLang].toastSaveFail, 'error');
    }
});


// ==========================================
// 5. Firebase 資料讀取與實時監聽
// ==========================================

function listenToDatabase() {
    const { todayDB, nextDB } = getDateContext();

    // 1. 監聽全域下拉選單資料
    onValue(ref(db, 'options/colors'), (snapshot) => {
        if (snapshot.exists()) globalColors = snapshot.val();
        updateDatalists();
    });

    onValue(ref(db, 'options/models'), (snapshot) => {
        if (snapshot.exists()) globalModels = snapshot.val();
        updateDatalists();
    });

    // 2. 監聽今日作業 (唯讀看板)
    onValue(ref(db, `schedules/${todayDB}`), (snapshot) => {
        const tbody = document.getElementById('today-task-body');
        const emptyState = document.getElementById('today-empty-state');
        tbody.innerHTML = '';
        
        if (snapshot.exists() && snapshot.val().length > 0) {
            emptyState.classList.add('hidden');
            const data = snapshot.val();
            data.forEach(item => {
                const tr = document.createElement('tr');
                tr.className = "hover:bg-emerald-50 transition-colors";
                const remarksStr = item.remarks ? item.remarks.join('<br>') : '';
                tr.innerHTML = `
                    <td class="p-3 text-center font-mono text-emerald-700">${item.seqNo}</td>
                    <td class="p-3 font-semibold text-gray-700">${item.color || '-'}</td>
                    <td class="p-3 font-semibold text-gray-700">${item.model || '-'}</td>
                    <td class="p-3 text-sm text-gray-600">${remarksStr || '-'}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            emptyState.classList.remove('hidden');
        }
    });

    // 3. 初始載入次日草稿 (若該日已有排程則載入，否則給予一筆空白列)
    get(ref(db, `schedules/${nextDB}`)).then((snapshot) => {
        document.getElementById('next-task-body').innerHTML = '';
        nextSeqNum = 1;
        if (snapshot.exists() && snapshot.val().length > 0) {
            snapshot.val().forEach(item => addNextTaskRow(item));
        } else {
            addNextTaskRow(); // 預設空白一列
        }
    }).catch(err => console.error("Load Draft Error", err));
}

// ==========================================
// 6. 歷史紀錄查詢邏輯
// ==========================================
document.getElementById('search-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const sDate = document.getElementById('search-date').value;
    const sColor = document.getElementById('search-color').value.trim().toUpperCase();
    const sModel = document.getElementById('search-model').value.trim().toUpperCase();

    const tbody = document.getElementById('history-result-body');
    const emptyState = document.getElementById('history-empty-state');
    tbody.innerHTML = '';

    try {
        let results = [];
        
        // 若有指定日期，僅抓該日；若無，則抓取全庫進行比對
        if (sDate) {
            const snapshot = await get(ref(db, `schedules/${sDate}`));
            if (snapshot.exists()) {
                snapshot.val().forEach(item => {
                    results.push({ date: sDate, ...item });
                });
            }
        } else {
            const snapshot = await get(ref(db, `schedules`));
            if (snapshot.exists()) {
                const allData = snapshot.val();
                for (let dateKey in allData) {
                    allData[dateKey].forEach(item => {
                        results.push({ date: dateKey, ...item });
                    });
                }
            }
        }

        // 依顏色與機型二次過濾
        if (sColor) results = results.filter(r => r.color && r.color.includes(sColor));
        if (sModel) results = results.filter(r => r.model && r.model.includes(sModel));

        // 渲染結果
        if (results.length > 0) {
            emptyState.classList.add('hidden');
            // 按日期倒序，序號正序排列
            results.sort((a, b) => b.date.localeCompare(a.date) || a.seqNo - b.seqNo);

            results.forEach(item => {
                const tr = document.createElement('tr');
                tr.className = "hover:bg-indigo-50 transition-colors";
                const remarksStr = item.remarks ? item.remarks.join('; ') : '';
                tr.innerHTML = `
                    <td class="p-3 font-mono text-sm text-indigo-700">${item.date}</td>
                    <td class="p-3 text-center text-gray-500">${item.seqNo}</td>
                    <td class="p-3 font-semibold">${item.color || '-'}</td>
                    <td class="p-3 font-semibold">${item.model || '-'}</td>
                    <td class="p-3 text-sm text-gray-600">${remarksStr || '-'}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            emptyState.classList.remove('hidden');
        }

        showToast(i18n[currentLang].toastSearchDone, 'success');

    } catch (err) {
        console.error("Search Error", err);
        showToast("查詢過程發生錯誤", "error");
    }
});

// 初始化啟動
document.addEventListener('DOMContentLoaded', () => {
    applyLanguage();
    updateDatalists();
    listenToDatabase();
});
