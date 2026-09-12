# Painting Sequence Management System (烤漆順序管理系統)

這是一個專為烤漆產線設計的 SPA (單頁面應用程式)。支援繁體中文與越南文即時切換、自動跳過休假日運算、以及 Firebase 即時資料庫同步。

## 功能特色
1. **多國語言支援**: 一鍵切換繁體中文與越南文。
2. **自動日期運算**: 系統會自動辨識當前日期，排程時會自動計算「下一個工作日」(略過週末與內建設定的國定假日)。
3. **智慧下拉選單**: 「顏色」與「機型」支援下拉選擇與關鍵字搜尋。手動輸入未在列表中的新資料後，系統會自動將其加入雲端資料庫並擴充下拉選單。
4. **序號自動遞增**: 新增排程紀錄時，系統會自動抓取該日期的最大序號並 +1。
5. **備註擴充**: 可無限點擊 `+` 號新增多條備註欄位。
6. **即時同步**: 透過 Firebase Realtime Database，多台裝置開啟網頁資料能即時同步更新。
7. **歷史查詢**: 可用日期、顏色、機型進行交集過濾。

## 如何架設於 GitHub Pages

1. **建立儲存庫**: 
   在您的 GitHub 帳號中建立一個新的 Repository (例如命名為 `painting-sequence`)。

2. **上傳檔案**: 
   將提供的三個檔案直接上傳到該 Repository 根目錄中：
   - `index.html`
   - `styles.css`
   - `app.js`

3. **啟用 GitHub Pages**:
   - 進入您的 Repository 頁面。
   - 點擊上方的 **Settings** 標籤。
   - 在左側選單中找到 **Pages**。
   - 在 **Build and deployment** 下方的 **Source** 選擇 `Deploy from a branch`。
   - 在 **Branch** 選單中選擇 `main` (或 `master`)，資料夾選擇 `/ (root)`，然後點擊 **Save**。
   - 等待約 1-2 分鐘，頁面上方會顯示您的網站專屬連結 (例如 `https://您的帳號.github.io/painting-sequence/`)。

## Firebase 資料庫權限設定注意
若您發現無法讀寫，請前往 Firebase 控制台 -> Realtime Database -> **Rules**，將規則設定為允許讀寫 (測試環境適用)：
```json
{
  "rules": {
    ".read": true,
    ".write": true
  }
}
