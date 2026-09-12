import { initializeApp } from "https://www.gstatic.com/firebasejs/10.8.1/firebase-app.js";
import { getDatabase, ref, onValue, push, set, query, orderByChild, equalTo, get } from "https://www.gstatic.com/firebasejs/10.8.1/firebase-database.js";

// --- Firebase 配置 ---
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

// --- 多國語言字典 (i18n) ---
const i18n = {
    'zh-TW': {
        appTitle: '烤漆順序管理系統',
        tabToday: '今日作業',
        tabPlan: '明日排程',
        tabHistory: '歷史記錄',
        todayTitle: '今日烤漆作業',
        planTitle: '安排下一個工作日作業',
        planListTitle: '已排定之作業',
        historyTitle: '歷史記錄查詢',
        targetDateLabel: '目標日期：',
        nextSeqLabel: '下一筆序號：',
        labelColor: '顏色 (可選或輸入)',
        labelMachine: '機型 (可選或輸入)',
        labelRemarks: '備註',
        phColor: '請選擇或輸入顏色代碼',
        phMachine: '請選擇或輸入機型',
        phRemark: '輸入備註事項',
        btnSave: '儲存並加入下一筆',
        colSeq: '序號',
        colColor: '顏色',
        colMachine: '機型',
        colRemarks: '備註',
        colDate: '日期',
        filterDate: '日期',
        filterColor: '顏色',
        filterMachine: '機型',
        phSearchColor: '搜尋顏色',
        phSearchMachine: '搜尋機型',
        btnSearch: '查詢',
        btnReset: '重置',
        weekdays: ['日', '一', '二', '三', '四', '五', '六']
    },
    'vi': {
        appTitle: 'Hệ thống quản lý thứ tự sơn',
        tabToday: 'Công việc hôm nay',
        tabPlan: 'Lịch trình ngày mai',
        tabHistory: 'Lịch sử',
        todayTitle: 'Công việc sơn hôm nay',
        planTitle: 'Lên lịch cho ngày làm việc tiếp theo',
        planListTitle: 'Các công việc đã lên lịch',
        historyTitle: 'Tra cứu lịch sử',
        targetDateLabel: 'Ngày mục tiêu:',
        nextSeqLabel: 'STT tiếp theo:',
        labelColor: 'Màu sắc (Chọn hoặc nhập)',
        labelMachine: 'Dòng máy (Chọn hoặc nhập)',
        labelRemarks: 'Ghi chú',
        phColor: 'Chọn hoặc nhập mã màu',
        phMachine: 'Chọn hoặc nhập dòng máy',
        phRemark: 'Nhập ghi chú',
        btnSave: 'Lưu & thêm mục mới',
        colSeq: 'STT',
        colColor: 'Màu sắc',
        colMachine: 'Dòng máy',
        colRemarks: 'Ghi chú',
        colDate: 'Ngày',
        filterDate: 'Ngày',
        filterColor: 'Màu sắc',
        filterMachine: 'Dòng máy',
        phSearchColor: 'Tìm màu sắc',
        phSearchMachine: 'Tìm dòng máy',
        btnSearch: 'Tìm kiếm',
        btnReset: 'Làm mới',
        weekdays: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']
    }
};

let currentLang = 'zh-TW';

// 初始預設顏色清單 (若 Firebase 無資料時使用)
const defaultColors = ["WE","YW","GYW","YWH","BKEBK","BKH","BKS","BKS1","BKS5","BKSA","BKSA1","OE","GN","FGN","PELBE","BEG","DBE","IG","IGS","DGS","ATG","GAST","PK","RD","SAT2"];

// 國定假日與補班名單設定 (以 2026 年台灣為例，可擴充)
const holidaysConfig = [
    '2026-01-01', '2026-02-16', '2026-02-17', '2026-02-18', '2026-02-19', // 元旦, 春節
    '2026-02-20', '2026-02-28', '2026-04-03', '2026-04-06', '2026-05-01', // 和平紀念, 清明, 勞動
    '2026-06-19', '2026-09-25', '2026-10-09' // 端午, 中秋, 國慶
];
const makeUpWorkdays = []; // 補班日名單

// --- 日期與時間工具函數 ---
function getTodayString() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function calculateNextWorkingDay() {
    let d = new Date();
    d.setDate(d.getDate() + 1); // 先加一天
    
    while (true) {
        const dateString = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        const dayOfWeek = d.getDay();
        
        const isWeekend = (dayOfWeek === 0 || dayOfWeek === 6);
        const isHoliday = holidaysConfig.includes(dateString);
        const isMakeUpDay = makeUpWorkdays.includes(dateString);

        // 如果是 (週末且非補班日) 或 (國定假日)，就跳過
        if ((isWeekend && !isMakeUpDay) || isHoliday) {
            d.setDate(d.getDate() + 1);
        } else {
            break;
        }
    }
    return d;
}

function formatDateDisplay(dateObj, lang) {
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const day = String(dateObj.getDate()).padStart(2, '0');
    const weekday = i18n[lang].weekdays[dateObj.getDay()];
    return `${month}/${day} (${weekday})`;
}

function toDateString(dateObj) {
    return `${dateObj.getFullYear()}-${String(dateObj.getMonth() + 1).padStart(2, '0')}-${String(dateObj.getDate()).padStart(2, '0')}`;
}

// 系統核心變數
const todayStr = getTodayString();
const nextWorkingDayObj = calculateNextWorkingDay();
const nextWorkingDayStr = toDateString(nextWorkingDayObj);
let nextSeq = 1; // 明日排程的自動遞增序號

// --- DOM 元素綁定 ---
const langSwitchBtn = document.getElementById('lang-switch-btn');
const langDisplay = document.getElementById('current-lang');
const tabs = document.querySelectorAll('.tab-btn');
const tabContents = document.querySelectorAll('.tab-content');
const colorDatalist = document.getElementById('color-datalist');
const machineDatalist = document.getElementById('machine-datalist');
const remarksContainer = document.getElementById('remarks-container');
const addRecordForm = document.getElementById('add-record-form');

// --- 語言切換邏輯 ---
function applyLanguage(lang) {
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (i18n[lang][key]) el.textContent = i18n[lang][key];
    });
    
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const key = el.getAttribute('data-i18n-placeholder');
        if (i18n[lang][key]) el.placeholder = i18n[lang][key];
    });

    langDisplay.textContent = lang === 'zh-TW' ? '繁體中文' : 'Tiếng Việt';
    
    // 更新日期顯示字串 (包含週期的翻譯)
    document.getElementById('today-date-display').textContent = formatDateDisplay(new Date(), lang);
    document.getElementById('next-working-day-display').textContent = formatDateDisplay(nextWorkingDayObj, lang);
}

langSwitchBtn.addEventListener('click', () => {
    currentLang = currentLang === 'zh-TW' ? 'vi' : 'zh-TW';
    applyLanguage(currentLang);
});

// --- 頁籤切換邏輯 ---
tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        tabContents.forEach(c => c.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById(tab.getAttribute('data-target')).classList.add('active');
    });
});

// --- 動態新增備註欄位 ---
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-add-remark')) {
        const newRow = document.createElement('div');
        newRow.className = 'remark-row';
        newRow.innerHTML = `
            <input type="text" class="remark-input" placeholder="${i18n[currentLang].phRemark}">
            <button type="button" class="btn-icon remove" title="移除"><i class="fas fa-minus"></i></button>
        `;
        remarksContainer.appendChild(newRow);
    }
    if (e.target.closest('.btn-icon.remove')) {
        e.target.closest('.remark-row').remove();
    }
});

// --- 讀取下拉選單資料 (顏色與機型) ---
function loadOptions() {
    // 讀取顏色
    const colorRef = ref(db, 'options/colors');
    onValue(colorRef, (snapshot) => {
        colorDatalist.innerHTML = '';
        let colors = snapshot.exists() ? snapshot.val() : defaultColors;
        
        // 若 DB 沒資料，初始化 DB 寫入預設清單
        if(!snapshot.exists()) set(colorRef, defaultColors);

        colors.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c;
            colorDatalist.appendChild(opt);
        });
    });

    // 讀取機型
    const machineRef = ref(db, 'options/machines');
    onValue(machineRef, (snapshot) => {
        machineDatalist.innerHTML = '';
        if (snapshot.exists()) {
            snapshot.val().forEach(m => {
                const opt = document.createElement('option');
                opt.value = m;
                machineDatalist.appendChild(opt);
            });
        }
    });
}

// 輔助函數：手動輸入後自動加入資料庫下拉選項
async function checkAndAddOption(path, val) {
    if (!val.trim()) return;
    const uppercaseVal = val.trim().toUpperCase();
    const listRef = ref(db, path);
    const snapshot = await get(listRef);
    let currentList = snapshot.exists() ? snapshot.val() : [];
    
    if (!currentList.includes(uppercaseVal)) {
        currentList.push(uppercaseVal);
        await set(listRef, currentList);
    }
    return uppercaseVal;
}

// --- 讀取今日與計畫資料 ---
function listenToRecords() {
    const recordsRef = ref(db, 'records');
    
    onValue(recordsRef, (snapshot) => {
        const todayTbody = document.getElementById('today-tbody');
        const planTbody = document.getElementById('plan-tbody');
        todayTbody.innerHTML = '';
        planTbody.innerHTML = '';
        
        let maxSeqForNextDay = 0;
        
        if (snapshot.exists()) {
            const data = snapshot.val();
            // 將物件轉陣列並排序 (依日期與序號)
            const records = Object.keys(data).map(key => ({id: key, ...data[key]}))
                                  .sort((a, b) => a.seq - b.seq);
            
            records.forEach(record => {
                const rowHTML = `
                    <tr>
                        <td>${record.seq}</td>
                        <td><span style="font-weight:bold; color:var(--primary-color)">${record.color}</span></td>
                        <td>${record.machine}</td>
                        <td>${record.remarks.join(' <br> ')}</td>
                    </tr>
                `;

                if (record.date === todayStr) {
                    todayTbody.insertAdjacentHTML('beforeend', rowHTML);
                } 
                else if (record.date === nextWorkingDayStr) {
                    planTbody.insertAdjacentHTML('beforeend', rowHTML);
                    if (record.seq > maxSeqForNextDay) maxSeqForNextDay = record.seq;
                }
            });
        }
        
        // 更新下一筆序號顯示
        nextSeq = maxSeqForNextDay + 1;
        document.getElementById('next-seq-display').textContent = nextSeq;
    });
}

// --- 提交新增排程 ---
addRecordForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const colorVal = document.getElementById('color-input').value;
    const machineVal = document.getElementById('machine-input').value;
    
    // 收集所有備註
    const remarks = Array.from(document.querySelectorAll('.remark-input'))
                         .map(input => input.value.trim())
                         .filter(val => val !== '');

    // 自動轉大寫並加入 DB 下拉清單 (如果不存在)
    const finalColor = await checkAndAddOption('options/colors', colorVal);
    const finalMachine = await checkAndAddOption('options/machines', machineVal);

    // 儲存至 Firebase Records
    const newRecordRef = push(ref(db, 'records'));
    await set(newRecordRef, {
        date: nextWorkingDayStr,
        displayDate: formatDateDisplay(nextWorkingDayObj, 'zh-TW'), // 紀錄格式化日期做備用
        seq: nextSeq,
        color: finalColor,
        machine: finalMachine,
        remarks: remarks,
        timestamp: Date.now()
    });

    // 表單重置，保留一個空備註欄
    addRecordForm.reset();
    remarksContainer.innerHTML = `
        <div class="remark-row">
            <input type="text" class="remark-input" placeholder="${i18n[currentLang].phRemark}">
            <button type="button" class="btn-icon btn-add-remark" title="新增備註欄位"><i class="fas fa-plus"></i></button>
        </div>
    `;
    document.getElementById('color-input').focus(); // 自動聚焦方便連續輸入
});

// --- 歷史記錄查詢邏輯 ---
document.getElementById('btn-search').addEventListener('click', async () => {
    const qDate = document.getElementById('search-date').value;
    const qColor = document.getElementById('search-color').value.trim().toUpperCase();
    const qMachine = document.getElementById('search-machine').value.trim().toUpperCase();
    
    const historyTbody = document.getElementById('history-tbody');
    historyTbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">查詢中...</td></tr>';

    const recordsRef = ref(db, 'records');
    const snapshot = await get(recordsRef);
    
    historyTbody.innerHTML = '';
    
    if (snapshot.exists()) {
        const data = snapshot.val();
        let results = Object.keys(data).map(key => data[key]);

        // 過濾器
        if (qDate) results = results.filter(r => r.date === qDate);
        if (qColor) results = results.filter(r => r.color.includes(qColor));
        if (qMachine) results = results.filter(r => r.machine.includes(qMachine));
        
        // 排序：日期降冪，序號升冪
        results.sort((a, b) => {
            if (a.date !== b.date) return new Date(b.date) - new Date(a.date);
            return a.seq - b.seq;
        });

        if (results.length === 0) {
            historyTbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:#999;">查無資料</td></tr>';
        } else {
            results.forEach(r => {
                historyTbody.insertAdjacentHTML('beforeend', `
                    <tr>
                        <td>${r.date}</td>
                        <td>${r.seq}</td>
                        <td><span style="font-weight:bold; color:var(--primary-color)">${r.color}</span></td>
                        <td>${r.machine}</td>
                        <td>${r.remarks.join(' <br> ')}</td>
                    </tr>
                `);
            });
        }
    } else {
        historyTbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:#999;">查無資料</td></tr>';
    }
});

document.getElementById('btn-reset').addEventListener('click', () => {
    document.getElementById('search-date').value = '';
    document.getElementById('search-color').value = '';
    document.getElementById('search-machine').value = '';
    document.getElementById('history-tbody').innerHTML = '';
});

// --- 系統初始化 ---
function init() {
    applyLanguage(currentLang);
    loadOptions();
    listenToRecords();
}

init();
