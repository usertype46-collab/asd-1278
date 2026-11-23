<?php
/**
 * Byethost 專屬網站編輯與檔案管理系統 (Single-File Edition)
 * 適用主機: pkweb.byethost18.com (b18_43495342)
 * PHP 版本相容性: PHP 7.4 - 8.3+
 */

session_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: text/html; charset=utf-8');

// ============================================================================
// 1. 系統預設配置與 Byethost 主機資訊
// ============================================================================
define('SYS_TITLE', 'Byethost 網站管理終端');
define('AUTH_FILE', __DIR__ . '/pk_auth_config.json');
define('ROOT_DIR', realpath(__DIR__)); // 鎖定在 htdocs 根目錄內，避免觸發 Byethost open_basedir 錯誤

$HOST_INFO = [
    'current_user' => 'b18_43495342',
    'domain'       => 'pkweb.byethost18.com',
    'home_dir'     => '/home/vol11_1/byethost18.com/b18_43495342',
    'last_ip'      => '106.1.115.245',
    'ftp_host'     => 'ftpupload.net',
    'mysql_host'   => 'sql204.byethost18.com',
    'volume'       => 'vol11_1'
];

// ============================================================================
// 2. 帳號密碼管理核心 (支援多帳號切換與密碼修改)
// ============================================================================
function initAuthConfig() {
    if (!file_exists(AUTH_FILE)) {
        $defaultAccounts = [
            'active_user' => 'b18_43495342',
            'accounts' => [
                'b18_43495342' => [
                    'password' => password_hash('pkweb2026', PASSWORD_DEFAULT),
                    'role'     => '主管理員',
                    'updated'  => date('Y-m-d H:i:s')
                ],
                'editor' => [
                    'password' => password_hash('editor123', PASSWORD_DEFAULT),
                    'role'     => '網站編輯員',
                    'updated'  => date('Y-m-d H:i:s')
                ]
            ]
        ];
        file_put_contents(AUTH_FILE, json_encode($defaultAccounts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    return json_decode(file_get_contents(AUTH_FILE), true);
}

function saveAuthConfig($data) {
    return file_put_contents(AUTH_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$authData = initAuthConfig();

// 處理登出
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// 處理登入驗證
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_login'])) {
    $inputUser = trim($_POST['username'] ?? '');
    $inputPass = $_POST['password'] ?? '';

    if (isset($authData['accounts'][$inputUser]) && password_verify($inputPass, $authData['accounts'][$inputUser]['password'])) {
        $_SESSION['pk_logged_in'] = true;
        $_SESSION['pk_user'] = $inputUser;
        $_SESSION['pk_role'] = $authData['accounts'][$inputUser]['role'];
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $loginError = '帳號或密碼錯誤，請重新輸入！';
    }
}

// ============================================================================
// 3. 登入畫面渲染 (未登入時顯示)
// ============================================================================
if (empty($_SESSION['pk_logged_in'])):
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登入 - <?= SYS_TITLE ?></title>
    <style>
        :root { --bg: #0f172a; --card: #1e293b; --primary: #3b82f6; --text: #f8fafc; --muted: #94a3b8; --border: #334155; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: var(--bg); color: var(--text); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .login-box { background: var(--card); border: 1px solid var(--border); border-radius: 12px; width: 100%; max-width: 420px; padding: 32px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); }
        .logo { text-align: center; margin-bottom: 24px; }
        .logo h1 { font-size: 1.4rem; color: #fff; margin-bottom: 6px; }
        .logo p { font-size: 0.85rem; color: var(--muted); }
        .badge { display: inline-block; background: rgba(59,130,246,0.15); color: #60a5fa; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; margin-top: 8px; border: 1px solid rgba(59,130,246,0.3); }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 0.85rem; color: var(--muted); margin-bottom: 6px; }
        input, select { width: 100%; padding: 11px 14px; background: #0f172a; border: 1px solid var(--border); border-radius: 8px; color: #fff; font-size: 0.95rem; outline: none; transition: 0.2s; }
        input:focus, select:focus { border-color: var(--primary); }
        button { width: 100%; padding: 12px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: 0.2s; }
        button:hover { background: #2563eb; }
        .error { background: rgba(239,68,68,0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 10px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 16px; text-align: center; }
        .info-footer { margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border); font-size: 0.75rem; color: var(--muted); line-height: 1.6; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">
            <h1><?= SYS_TITLE ?></h1>
            <p><?= htmlspecialchars($HOST_INFO['domain']) ?></p>
            <span class="badge">Volume: <?= htmlspecialchars($HOST_INFO['volume']) ?> | User: <?= htmlspecialchars($HOST_INFO['current_user']) ?></span>
        </div>
        <?php if ($loginError): ?>
            <div class="error"><?= htmlspecialchars($loginError) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>快速選擇預設帳號或手動輸入</label>
                <select onchange="document.getElementById('username').value = this.value">
                    <?php foreach ($authData['accounts'] as $accName => $accInfo): ?>
                        <option value="<?= htmlspecialchars($accName) ?>"><?= htmlspecialchars($accName) ?> (<?= htmlspecialchars($accInfo['role']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>管理員帳號</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars(array_key_first($authData['accounts'])) ?>" required>
            </div>
            <div class="form-group">
                <label>登入密碼</label>
                <input type="password" name="password" placeholder="請輸入密碼 (預設: pkweb2026)" required>
            </div>
            <button type="submit" name="do_login">安全登入系統</button>
        </form>
        <div class="info-footer">
            <div>FTP 主機：<?= htmlspecialchars($HOST_INFO['ftp_host']) ?></div>
            <div>MySQL 主機：<?= htmlspecialchars($HOST_INFO['mysql_host']) ?></div>
            <div>目前連線 IP：<?= htmlspecialchars($_SERVER['REMOTE_ADDR']) ?></div>
        </div>
    </div>
</body>
</html>
<?php
exit;
endif;

// ============================================================================
// 4. 已登入後台邏輯處理 (檔案操作、帳號切換、密碼變更、MySQL 工具)
// ============================================================================
$msg = '';
$msgType = 'success';

// 路徑安全解析函數 (防止目錄穿越攻擊)
function getSafePath($relPath) {
    $relPath = str_replace(['..', "\0"], '', $relPath);
    $relPath = trim($relPath, '/\\');
    $fullPath = ROOT_DIR . ($relPath !== '' ? DIRECTORY_SEPARATOR . $relPath : '');
    return $fullPath;
}

$currentRelDir = isset($_GET['dir']) ? trim(str_replace(['..', "\0"], '', $_GET['dir']), '/\\') : '';
$currentFullDir = getSafePath($currentRelDir);
if (!is_dir($currentFullDir)) {
    $currentRelDir = '';
    $currentFullDir = ROOT_DIR;
}

// --- A. 帳號密碼管理與切換處理 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auth_action'])) {
    $act = $_POST['auth_action'];
    if ($act === 'change_password') {
        $targetUser = $_POST['target_user'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $newRole = trim($_POST['new_role'] ?? '管理員');
        if (isset($authData['accounts'][$targetUser]) && strlen($newPass) >= 4) {
            $authData['accounts'][$targetUser]['password'] = password_hash($newPass, PASSWORD_DEFAULT);
            $authData['accounts'][$targetUser]['role'] = $newRole;
            $authData['accounts'][$targetUser]['updated'] = date('Y-m-d H:i:s');
            saveAuthConfig($authData);
            $msg = "帳號 [{$targetUser}] 的密碼與權限角色已成功更新！";
        } else {
            $msg = "密碼長度至少需要 4 個字元！";
            $msgType = 'error';
        }
    } elseif ($act === 'add_account') {
        $newUser = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['new_username'] ?? '');
        $newPass = $_POST['new_user_password'] ?? '';
        $newRole = trim($_POST['new_user_role'] ?? '網站編輯員');
        if ($newUser !== '' && strlen($newPass) >= 4 && !isset($authData['accounts'][$newUser])) {
            $authData['accounts'][$newUser] = [
                'password' => password_hash($newPass, PASSWORD_DEFAULT),
                'role'     => $newRole,
                'updated'  => date('Y-m-d H:i:s')
            ];
            saveAuthConfig($authData);
            $msg = "已成功新增管理帳號：[{$newUser}]！";
        } else {
            $msg = "帳號名稱無效、已存在，或密碼少於 4 碼！";
            $msgType = 'error';
        }
    } elseif ($act === 'delete_account') {
        $delUser = $_POST['del_user'] ?? '';
        if (count($authData['accounts']) > 1 && isset($authData['accounts'][$delUser]) && $delUser !== $_SESSION['pk_user']) {
            unset($authData['accounts'][$delUser]);
            saveAuthConfig($authData);
            $msg = "已刪除帳號：[{$delUser}]";
        } else {
            $msg = "無法刪除目前登入中的帳號，或系統至少需保留一組帳號！";
            $msgType = 'error';
        }
    } elseif ($act === 'switch_account') {
        $switchUser = $_POST['switch_to_user'] ?? '';
        if (isset($authData['accounts'][$switchUser])) {
            $_SESSION['pk_user'] = $switchUser;
            $_SESSION['pk_role'] = $authData['accounts'][$switchUser]['role'];
            $msg = "已無縫切換登入身分為：[{$switchUser}] ({$_SESSION['pk_role']})";
        }
    }
}

// --- B. 檔案與資料夾操作 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_action'])) {
    $fAct = $_POST['file_action'];

    // 1. 儲存編輯的檔案內容
    if ($fAct === 'save_file') {
        $targetFile = getSafePath($_POST['file_path'] ?? '');
        if (is_file($targetFile) && realpath($targetFile) !== realpath(AUTH_FILE)) {
            if (file_put_contents($targetFile, $_POST['file_content']) !== false) {
                $msg = "檔案儲存成功：" . basename($targetFile) . " (" . date('H:i:s') . ")";
            } else {
                $msg = "儲存失敗，請檢查檔案寫入權限！";
                $msgType = 'error';
            }
        }
    }
    // 2. 新增檔案
    elseif ($fAct === 'create_file') {
        $newName = basename(trim($_POST['new_name'] ?? ''));
        if ($newName !== '') {
            $newPath = $currentFullDir . DIRECTORY_SEPARATOR . $newName;
            if (!file_exists($newPath)) {
                file_put_contents($newPath, "<?php\n// Created by Byethost Manager\n");
                $msg = "已建立檔案：{$newName}";
            } else {
                $msg = "檔案或資料夾已存在！";
                $msgType = 'error';
            }
        }
    }
    // 3. 新增資料夾
    elseif ($fAct === 'create_folder') {
        $folderName = basename(trim($_POST['folder_name'] ?? ''));
        if ($folderName !== '') {
            $newDir = $currentFullDir . DIRECTORY_SEPARATOR . $folderName;
            if (!file_exists($newDir)) {
                mkdir($newDir, 0755, true);
                $msg = "已建立資料夾：{$folderName}";
            } else {
                $msg = "資料夾已存在！";
                $msgType = 'error';
            }
        }
    }
    // 4. 上傳檔案
    elseif ($fAct === 'upload_file' && isset($_FILES['upload_item'])) {
        $upFile = $_FILES['upload_item'];
        if ($upFile['error'] === UPLOAD_ERR_OK) {
            $dest = $currentFullDir . DIRECTORY_SEPARATOR . basename($upFile['name']);
            if (move_uploaded_file($upFile['tmp_name'], $dest)) {
                $msg = "檔案上傳成功：" . basename($upFile['name']);
            } else {
                $msg = "檔案移動失敗！";
                $msgType = 'error';
            }
        } else {
            $msg = "上傳錯誤代碼：" . $upFile['error'];
            $msgType = 'error';
        }
    }
    // 5. 重新命名
    elseif ($fAct === 'rename_item') {
        $oldName = basename($_POST['old_name'] ?? '');
        $newName = basename(trim($_POST['new_name'] ?? ''));
        $oldPath = $currentFullDir . DIRECTORY_SEPARATOR . $oldName;
        $newPath = $currentFullDir . DIRECTORY_SEPARATOR . $newName;
        if ($oldName !== '' && $newName !== '' && file_exists($oldPath) && !file_exists($newPath)) {
            rename($oldPath, $newPath);
            $msg = "已將 [{$oldName}] 重新命名為 [{$newName}]";
        } else {
            $msg = "重新命名失敗，請確認目標名稱未重複！";
            $msgType = 'error';
        }
    }
    // 6. 刪除檔案或資料夾
    elseif ($fAct === 'delete_item') {
        $delName = basename($_POST['del_name'] ?? '');
        $delPath = $currentFullDir . DIRECTORY_SEPARATOR . $delName;
        if (realpath($delPath) === realpath(__FILE__) || realpath($delPath) === realpath(AUTH_FILE)) {
            $msg = "系統保護機制：禁止刪除管理系統本身或認證設定檔！";
            $msgType = 'error';
        } elseif (is_file($delPath)) {
            unlink($delPath);
            $msg = "已刪除檔案：{$delName}";
        } elseif (is_dir($delPath)) {
            if (@rmdir($delPath)) {
                $msg = "已刪除空資料夾：{$delName}";
            } else {
                $msg = "資料夾非空，請先清空內部檔案再刪除！";
                $msgType = 'error';
            }
        }
    }
}

// --- C. MySQL 連線測試與快速 SQL 執行 ---
$sqlResult = null;
$sqlError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_action'])) {
    $dbHost = trim($_POST['db_host'] ?? $HOST_INFO['mysql_host']);
    $dbUser = trim($_POST['db_user'] ?? $HOST_INFO['current_user']);
    $dbPass = $_POST['db_pass'] ?? '';
    $dbName = trim($_POST['db_name'] ?? '');
    $sqlQuery = trim($_POST['sql_query'] ?? 'SHOW TABLES;');

    try {
        $mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
        if ($mysqli->connect_error) {
            $sqlError = "連線失敗 (" . $mysqli->connect_errno . "): " . $mysqli->connect_error;
        } else {
            $mysqli->set_charset("utf8mb4");
            $msg = "MySQL 資料庫連線成功！伺服器版本：" . $mysqli->server_info;
            if ($sqlQuery !== '') {
                $res = $mysqli->query($sqlQuery);
                if ($res === false) {
                    $sqlError = "SQL 語法錯誤: " . $mysqli->error;
                } elseif ($res === true) {
                    $sqlResult = "執行成功，影響列數：" . $mysqli->affected_rows;
                } else {
                    $rows = [];
                    while ($row = $res->fetch_assoc()) {
                        $rows[] = $row;
                    }
                    $sqlResult = $rows;
                }
            }
            $mysqli->close();
        }
    } catch (Exception $e) {
        $sqlError = "例外錯誤：" . $e->getMessage();
    }
}

// 取得目前目錄下的檔案列表
$items = scandir($currentFullDir);
$folders = [];
$files = [];
foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $full = $currentFullDir . DIRECTORY_SEPARATOR . $item;
    if (is_dir($full)) {
        $folders[] = $item;
    } else {
        $files[] = $item;
    }
}

// 檢查是否處於「編輯檔案模式」
$editRelPath = isset($_GET['edit']) ? trim(str_replace(['..', "\0"], '', $_GET['edit']), '/\\') : '';
$editFullPath = $editRelPath !== '' ? getSafePath($editRelPath) : '';
$isEditing = ($editFullPath !== '' && is_file($editFullPath));
$editContent = $isEditing ? file_get_contents($editFullPath) : '';

// 當前分頁模式
$tab = $_GET['tab'] ?? 'files';
if ($isEditing) $tab = 'editor';

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SYS_TITLE ?> - <?= htmlspecialchars($HOST_INFO['domain']) ?></title>
    <style>
        :root {
            --bg: #0f172a; --sidebar: #1e293b; --card: #1e293b; --border: #334155;
            --primary: #3b82f6; --primary-hover: #2563eb; --success: #10b981;
            --danger: #ef4444; --warning: #f59e0b; --text: #f1f5f9; --muted: #94a3b8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Microsoft JhengHei", sans-serif; }
        body { background: var(--bg); color: var(--text); display: flex; min-height: 100vh; font-size: 14px; }
        
        /* 側邊欄 */
        .sidebar { width: 260px; background: var(--sidebar); border-right: 1px solid var(--border); display: flex; flex-direction: column; flex-shrink: 0; }
        .brand { padding: 20px; border-bottom: 1px solid var(--border); }
        .brand h2 { font-size: 1.15rem; color: #fff; display: flex; align-items: center; gap: 8px; }
        .brand .domain { font-size: 0.8rem; color: #60a5fa; margin-top: 4px; word-break: break-all; }
        
        .nav { padding: 15px 10px; flex: 1; }
        .nav a { display: flex; align-items: center; gap: 10px; padding: 11px 14px; color: var(--muted); text-decoration: none; border-radius: 8px; margin-bottom: 6px; font-weight: 500; transition: 0.2s; }
        .nav a:hover, .nav a.active { background: rgba(59,130,246,0.15); color: #fff; }
        .nav a.active { border-left: 3px solid var(--primary); }

        .host-card { margin: 12px; padding: 14px; background: #0f172a; border: 1px solid var(--border); border-radius: 8px; font-size: 0.78rem; color: var(--muted); line-height: 1.7; }
        .host-card strong { color: #e2e8f0; display: block; margin-bottom: 4px; font-size: 0.82rem; border-bottom: 1px solid var(--border); padding-bottom: 4px; }
        .host-card span { color: #38bdf8; font-family: monospace; }

        /* 主內容區 */
        .main { flex: 1; display: flex; flex-direction: column; overflow-x: hidden; }
        .topbar { height: 60px; background: var(--sidebar); border-bottom: 1px solid var(--border); padding: 0 24px; display: flex; align-items: center; justify-content: space-between; }
        .user-switcher { display: flex; align-items: center; gap: 10px; }
        .badge-role { background: rgba(16,185,129,0.15); color: #34d399; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; border: 1px solid rgba(16,185,129,0.3); }
        
        .content { padding: 24px; max-width: 1400px; width: 100%; margin: 0 auto; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: rgba(16,185,129,0.15); border: 1px solid var(--success); color: #6ee7b7; }
        .alert-error { background: rgba(239,68,68,0.15); border: 1px solid var(--danger); color: #fca5a5; }

        /* 卡片與表格 */
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 10px; }
        .card-title { font-size: 1.05rem; font-weight: 600; color: #fff; }

        .toolbar { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; background: #0f172a; padding: 12px; border-radius: 8px; border: 1px solid var(--border); }
        .toolbar form { display: flex; gap: 6px; align-items: center; }
        input[type="text"], input[type="password"], select, textarea { background: #0f172a; border: 1px solid var(--border); color: #fff; padding: 7px 12px; border-radius: 6px; font-size: 0.88rem; outline: none; }
        input:focus, select:focus, textarea:focus { border-color: var(--primary); }
        
        .btn { padding: 7px 14px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.85rem; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: 0.15s; color: #fff; }
        .btn-primary { background: var(--primary); }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-success { background: var(--success); }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: var(--danger); }
        .btn-danger:hover { background: #dc2626; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .btn-outline:hover { background: #334155; }
        .btn-sm { padding: 4px 10px; font-size: 0.8rem; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 11px 12px; text-align: left; border-bottom: 1px solid var(--border); }
        th { color: var(--muted); font-weight: 600; font-size: 0.82rem; background: rgba(15,23,42,0.5); }
        tr:hover { background: rgba(255,255,255,0.02); }
        .file-link { color: #e2e8f0; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 8px; }
        .file-link:hover { color: #60a5fa; }
        .dir-icon { color: #fbbf24; }
        .file-icon { color: #38bdf8; }

        /* 程式碼編輯器 */
        .editor-container { display: flex; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; background: #0b1120; }
        .line-numbers { padding: 14px 10px; background: #0f172a; color: #475569; text-align: right; font-family: "Fira Code", Consolas, monospace; font-size: 13px; line-height: 1.6; user-select: none; border-right: 1px solid var(--border); min-width: 48px; }
        #codeEditor { width: 100%; min-height: 520px; background: transparent; border: none; color: #e2e8f0; font-family: "Fira Code", Consolas, monospace; font-size: 13px; line-height: 1.6; padding: 14px; resize: vertical; tab-size: 4; white-space: pre; overflow-x: auto; }

        .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px; }
        .breadcrumb { display: flex; align-items: center; gap: 6px; font-size: 0.9rem; color: var(--muted); margin-bottom: 12px; flex-wrap: wrap; }
        .breadcrumb a { color: #60a5fa; text-decoration: none; }
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; border-right: none; border-bottom: 1px solid var(--border); }
        }
    </style>
</head>
<body>

    <!-- 左側導覽與主機資訊 -->
    <aside class="sidebar">
        <div class="brand">
            <h2>⚡ <?= SYS_TITLE ?></h2>
            <div class="domain">🌐 <?= htmlspecialchars($HOST_INFO['domain']) ?></div>
        </div>
        <nav class="nav">
            <a href="?tab=files" class="<?= $tab === 'files' ? 'active' : '' ?>">📁 網站檔案總管</a>
            <?php if ($isEditing): ?>
                <a href="#" class="active">📝 正在編輯：<?= htmlspecialchars(basename($editFullPath)) ?></a>
            <?php endif; ?>
            <a href="?tab=accounts" class="<?= $tab === 'accounts' ? 'active' : '' ?>">🔐 帳號密碼與切換</a>
            <a href="?tab=database" class="<?= $tab === 'database' ? 'active' : '' ?>">🗄️ MySQL 資料庫終端</a>
            <a href="?tab=info" class="<?= $tab === 'info' ? 'active' : '' ?>">🖥️ Byethost 主機參數</a>
            <a href="http://<?= htmlspecialchars($HOST_INFO['domain']) ?>" target="_blank">🚀 預覽前台網站 ↗</a>
        </nav>
        <div class="host-card">
            <strong>📋 cPanel 主機摘要</strong>
            <div>帳號：<span><?= htmlspecialchars($HOST_INFO['current_user']) ?></span></div>
            <div>主機卷：<span><?= htmlspecialchars($HOST_INFO['volume']) ?></span></div>
            <div>FTP：<span><?= htmlspecialchars($HOST_INFO['ftp_host']) ?></span></div>
            <div>MySQL：<span><?= htmlspecialchars($HOST_INFO['mysql_host']) ?></span></div>
            <div>PHP 版本：<span><?= phpversion() ?></span></div>
        </div>
    </aside>

    <!-- 右側主工作區 -->
    <div class="main">
        <header class="topbar">
            <div style="font-weight:600; color:#e2e8f0;">
                目前路徑：<code>/htdocs<?= $currentRelDir ? '/' . htmlspecialchars($currentRelDir) : '' ?></code>
            </div>
            <div class="user-switcher">
                <span class="badge-role"><?= htmlspecialchars($_SESSION['pk_role']) ?>：<?= htmlspecialchars($_SESSION['pk_user']) ?></span>
                
                <!-- 快速無縫切換帳號下拉選單 -->
                <form method="POST" style="display:inline-flex; gap:6px; align-items:center;">
                    <input type="hidden" name="auth_action" value="switch_account">
                    <select name="switch_to_user" onchange="this.form.submit()" title="快速切換管理帳號">
                        <?php foreach ($authData['accounts'] as $acc => $info): ?>
                            <option value="<?= htmlspecialchars($acc) ?>" <?= $acc === $_SESSION['pk_user'] ? 'selected' : '' ?>>
                                切換帳號：<?= htmlspecialchars($acc) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <a href="?action=logout" class="btn btn-outline btn-sm">登出</a>
            </div>
        </header>

        <div class="content">
            <?php if ($msg): ?>
                <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <!-- ============================================================ -->
            <!-- 分頁 1：線上程式碼編輯器 -->
            <!-- ============================================================ -->
            <?php if ($tab === 'editor' && $isEditing): ?>
                <div class="card">
                    <div class="card-header">
                        <div>
                            <span class="card-title">📝 線上編輯器：<?= htmlspecialchars($editRelPath) ?></span>
                            <span style="color:var(--muted); font-size:0.8rem; margin-left:10px;">
                                大小：<?= formatBytes(filesize($editFullPath)) ?> | 權限：<?= substr(sprintf('%o', fileperms($editFullPath)), -4) ?>
                            </span>
                        </div>
                        <div style="display:flex; gap:8px;">
                            <a href="?tab=files&dir=<?= urlencode(dirname($editRelPath) === '.' ? '' : dirname($editRelPath)) ?>" class="btn btn-outline">← 返回目錄</a>
                            <button type="submit" form="editorForm" class="btn btn-success">💾 儲存變更 (Ctrl+S)</button>
                        </div>
                    </div>
                    <form id="editorForm" method="POST">
                        <input type="hidden" name="file_action" value="save_file">
                        <input type="hidden" name="file_path" value="<?= htmlspecialchars($editRelPath) ?>">
                        <div class="editor-container">
                            <div class="line-numbers" id="lineNumbers">1</div>
                            <textarea id="codeEditor" name="file_content" spellcheck="false"><?= htmlspecialchars($editContent) ?></textarea>
                        </div>
                    </form>
                </div>

            <!-- ============================================================ -->
            <!-- 分頁 2：網站檔案總管 -->
            <!-- ============================================================ -->
            <?php elseif ($tab === 'files'): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">📁 網站檔案與目錄管理</div>
                        <div class="breadcrumb">
                            <a href="?tab=files">🏠 htdocs (根目錄)</a>
                            <?php
                            if ($currentRelDir !== '') {
                                $parts = explode('/', $currentRelDir);
                                $accPath = '';
                                foreach ($parts as $p) {
                                    $accPath .= ($accPath === '' ? '' : '/') . $p;
                                    echo ' / <a href="?tab=files&dir=' . urlencode($accPath) . '">' . htmlspecialchars($p) . '</a>';
                                }
                            }
                            ?>
                        </div>
                    </div>

                    <!-- 建立檔案 / 資料夾 / 上傳工具列 -->
                    <div class="toolbar">
                        <form method="POST">
                            <input type="hidden" name="file_action" value="create_file">
                            <input type="text" name="new_name" placeholder="新檔案名稱 (如 index.php)" required>
                            <button type="submit" class="btn btn-primary">+ 新增檔案</button>
                        </form>
                        <form method="POST">
                            <input type="hidden" name="file_action" value="create_folder">
                            <input type="text" name="folder_name" placeholder="新資料夾名稱" required>
                            <button type="submit" class="btn btn-outline">+ 新增資料夾</button>
                        </form>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="file_action" value="upload_file">
                            <input type="file" name="upload_item" required style="color:var(--muted); font-size:0.82rem;">
                            <button type="submit" class="btn btn-success">⬆ 上傳檔案</button>
                        </form>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>名稱</th>
                                <th>類型 / 大小</th>
                                <th>權限</th>
                                <th>最後修改時間</th>
                                <th style="text-align:right;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($currentRelDir !== ''): 
                                $parentDir = dirname($currentRelDir);
                                if ($parentDir === '.') $parentDir = '';
                            ?>
                            <tr>
                                <td colspan="5">
                                    <a href="?tab=files&dir=<?= urlencode($parentDir) ?>" class="file-link">⬆️ .. (返回上一層目錄)</a>
                                </td>
                            </tr>
                            <?php endif; ?>

                            <!-- 列出資料夾 -->
                            <?php foreach ($folders as $folder): 
                                $rel = ($currentRelDir !== '' ? $currentRelDir . '/' : '') . $folder;
                                $full = $currentFullDir . DIRECTORY_SEPARATOR . $folder;
                            ?>
                            <tr>
                                <td>
                                    <a href="?tab=files&dir=<?= urlencode($rel) ?>" class="file-link">
                                        <span class="dir-icon">📁</span> <?= htmlspecialchars($folder) ?>
                                    </a>
                                </td>
                                <td>資料夾</td>
                                <td><code><?= substr(sprintf('%o', fileperms($full)), -4) ?></code></td>
                                <td><?= date('Y-m-d H:i', filemtime($full)) ?></td>
                                <td style="text-align:right;">
                                    <button type="button" class="btn btn-outline btn-sm" onclick="renamePrompt('<?= htmlspecialchars($folder, ENT_QUOTES) ?>')">重命名</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('確定要刪除此空資料夾嗎？');">
                                        <input type="hidden" name="file_action" value="delete_item">
                                        <input type="hidden" name="del_name" value="<?= htmlspecialchars($folder) ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">刪除</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <!-- 列出檔案 -->
                            <?php foreach ($files as $file): 
                                $rel = ($currentRelDir !== '' ? $currentRelDir . '/' : '') . $file;
                                $full = $currentFullDir . DIRECTORY_SEPARATOR . $file;
                                $isProtected = (realpath($full) === realpath(AUTH_FILE));
                            ?>
                            <tr>
                                <td>
                                    <?php if (!$isProtected): ?>
                                        <a href="?edit=<?= urlencode($rel) ?>" class="file-link">
                                            <span class="file-icon">📄</span> <?= htmlspecialchars($file) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="file-link" style="color:var(--warning);">🔒 <?= htmlspecialchars($file) ?> (系統安全鎖定)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= formatBytes(filesize($full)) ?></td>
                                <td><code><?= substr(sprintf('%o', fileperms($full)), -4) ?></code></td>
                                <td><?= date('Y-m-d H:i', filemtime($full)) ?></td>
                                <td style="text-align:right;">
                                    <?php if (!$isProtected): ?>
                                        <a href="?edit=<?= urlencode($rel) ?>" class="btn btn-primary btn-sm">編輯</a>
                                        <button type="button" class="btn btn-outline btn-sm" onclick="renamePrompt('<?= htmlspecialchars($file, ENT_QUOTES) ?>')">重命名</button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('確定要永久刪除 <?= htmlspecialchars($file) ?> 嗎？');">
                                            <input type="hidden" name="file_action" value="delete_item">
                                            <input type="hidden" name="del_name" value="<?= htmlspecialchars($file) ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">刪除</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 隱藏的重命名表單 -->
                <form id="renameForm" method="POST" style="display:none;">
                    <input type="hidden" name="file_action" value="rename_item">
                    <input type="hidden" name="old_name" id="renameOld">
                    <input type="hidden" name="new_name" id="renameNew">
                </form>

            <!-- ============================================================ -->
            <!-- 分頁 3：帳號密碼設置與多帳號切換管理 -->
            <!-- ============================================================ -->
            <?php elseif ($tab === 'accounts'): ?>
                <div class="grid-2">
                    <!-- 現有帳號列表與修改密碼 -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">🔐 現有管理員帳號與密碼變更</div>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>帳號名稱</th>
                                    <th>角色權限</th>
                                    <th>最後更新</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($authData['accounts'] as $accName => $accInfo): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($accName) ?></strong>
                                        <?php if ($accName === $_SESSION['pk_user']): ?>
                                            <span class="badge-role" style="margin-left:5px;">目前登入</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($accInfo['role']) ?></td>
                                    <td style="font-size:0.8rem; color:var(--muted);"><?= htmlspecialchars($accInfo['updated']) ?></td>
                                    <td>
                                        <?php if ($accName !== $_SESSION['pk_user']): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="auth_action" value="switch_account">
                                                <input type="hidden" name="switch_to_user" value="<?= htmlspecialchars($accName) ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">切換登入</button>
                                            </form>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('確定刪除此帳號？');">
                                                <input type="hidden" name="auth_action" value="delete_account">
                                                <input type="hidden" name="del_user" value="<?= htmlspecialchars($accName) ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">刪除</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color:var(--muted); font-size:0.8rem;">使用中</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <hr style="border:0; border-top:1px solid var(--border); margin:20px 0;">
                        <h4 style="margin-bottom:12px; color:#fff;">修改指定帳號密碼 / 角色</h4>
                        <form method="POST" style="display:flex; flex-direction:column; gap:12px;">
                            <input type="hidden" name="auth_action" value="change_password">
                            <div>
                                <label style="display:block; margin-bottom:4px; color:var(--muted);">選擇要修改的帳號</label>
                                <select name="target_user" style="width:100%;">
                                    <?php foreach ($authData['accounts'] as $accName => $accInfo): ?>
                                        <option value="<?= htmlspecialchars($accName) ?>"><?= htmlspecialchars($accName) ?> (<?= htmlspecialchars($accInfo['role']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label style="display:block; margin-bottom:4px; color:var(--muted);">角色名稱</label>
                                <input type="text" name="new_role" value="主管理員" style="width:100%;" required>
                            </div>
                            <div>
                                <label style="display:block; margin-bottom:4px; color:var(--muted);">設定新密碼 (至少 4 碼)</label>
                                <input type="password" name="new_password" placeholder="輸入新密碼" style="width:100%;" required>
                            </div>
                            <button type="submit" class="btn btn-success">更新帳號密碼</button>
                        </form>
                    </div>

                    <!-- 新增協作帳號 -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">➕ 新增可切換管理帳號</div>
                        </div>
                        <p style="color:var(--muted); margin-bottom:16px; line-height:1.6;">
                            您可以為不同的開發人員、編輯人員或測試環境建立獨立帳號密碼，登入後可於右上角選單一鍵無縫切換身分。所有密碼皆採用 PHP <code>PASSWORD_DEFAULT</code> (Bcrypt) 單向雜湊加密儲存於 <code>pk_auth_config.json</code>。
                        </p>
                        <form method="POST" style="display:flex; flex-direction:column; gap:14px;">
                            <input type="hidden" name="auth_action" value="add_account">
                            <div>
                                <label style="display:block; margin-bottom:4px; color:var(--muted);">新帳號名稱 (限英數字與底線)</label>
                                <input type="text" name="new_username" placeholder="例如：dev_admin" style="width:100%;" required>
                            </div>
                            <div>
                                <label style="display:block; margin-bottom:4px; color:var(--muted);">角色標籤</label>
                                <input type="text" name="new_user_role" placeholder="例如：前端工程師 / 內容編輯" style="width:100%;" required>
                            </div>
                            <div>
                                <label style="display:block; margin-bottom:4px; color:var(--muted);">設定登入密碼</label>
                                <input type="password" name="new_user_password" placeholder="至少 4 個字元" style="width:100%;" required>
                            </div>
                            <button type="submit" class="btn btn-primary">建立新管理帳號</button>
                        </form>
                    </div>
                </div>

            <!-- ============================================================ -->
            <!-- 分頁 4：MySQL 資料庫連線與 SQL 測試終端 -->
            <!-- ============================================================ -->
            <?php elseif ($tab === 'database'): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">🗄️ Byethost MySQL 資料庫快速連線器 (<?= htmlspecialchars($HOST_INFO['mysql_host']) ?>)</div>
                    </div>
                    <?php if ($sqlError): ?>
                        <div class="alert alert-error"><?= htmlspecialchars($sqlError) ?></div>
                    <?php endif; ?>
                    <form method="POST" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px; margin-bottom:16px;">
                        <input type="hidden" name="db_action" value="query">
                        <div>
                            <label style="display:block; margin-bottom:4px; color:var(--muted);">MySQL 主機名稱</label>
                            <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? $HOST_INFO['mysql_host']) ?>" style="width:100%;" required>
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:4px; color:var(--muted);">資料庫帳號 (通常為 b18_43495342)</label>
                            <input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? $HOST_INFO['current_user']) ?>" style="width:100%;" required>
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:4px; color:var(--muted);">cPanel / MySQL 密碼</label>
                            <input type="password" name="db_pass" value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>" placeholder="輸入您的 VistaPanel 密碼" style="width:100%;" required>
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:4px; color:var(--muted);">資料庫名稱 (如 b18_43495342_wp)</label>
                            <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" placeholder="請先於 cPanel 建立資料庫" style="width:100%;" required>
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <label style="display:block; margin-bottom:4px; color:var(--muted);">執行 SQL 指令</label>
                            <textarea name="sql_query" rows="3" style="width:100%; font-family:monospace;"><?= htmlspecialchars($_POST['sql_query'] ?? 'SHOW TABLES;') ?></textarea>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary">⚡ 連線並執行 SQL</button>
                        </div>
                    </form>

                    <?php if (is_array($sqlResult)): ?>
                        <div style="overflow-x:auto; margin-top:16px;">
                            <table>
                                <?php if (count($sqlResult) > 0): ?>
                                    <thead>
                                        <tr>
                                            <?php foreach (array_keys($sqlResult[0]) as $col): ?>
                                                <th><?= htmlspecialchars($col) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sqlResult as $r): ?>
                                            <tr>
                                                <?php foreach ($r as $val): ?>
                                                    <td><?= htmlspecialchars((string)$val) ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                <?php else: ?>
                                    <tr><td>查詢成功，但資料表無回傳紀錄 (0 rows)。</td></tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    <?php elseif (is_string($sqlResult)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($sqlResult) ?></div>
                    <?php endif; ?>
                </div>

            <!-- ============================================================ -->
            <!-- 分頁 5：Byethost 主機資訊與部署參數總覽 -->
            <!-- ============================================================ -->
            <?php elseif ($tab === 'info'): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">🖥️ Byethost cPanel 主機配置與伺服器環境參數</div>
                    </div>
                    <table>
                        <tbody>
                            <tr><th style="width:240px;">Current User (cPanel 帳號)</th><td><code><?= htmlspecialchars($HOST_INFO['current_user']) ?></code></td></tr>
                            <tr><th>主域名 (Main Domain)</th><td><a href="http://<?= htmlspecialchars($HOST_INFO['domain']) ?>" target="_blank" style="color:#60a5fa;"><?= htmlspecialchars($HOST_INFO['domain']) ?></a></td></tr>
                            <tr><th>主目錄 (Home Directory)</th><td><code><?= htmlspecialchars($HOST_INFO['home_dir']) ?></code></td></tr>
                            <tr><th>網站網頁根目錄 (Document Root)</th><td><code><?= htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? $HOST_INFO['home_dir'] . '/htdocs') ?></code></td></tr>
                            <tr><th>FTP 主機名稱 (FTP Hostname)</th><td><code><?= htmlspecialchars($HOST_INFO['ftp_host']) ?></code> (Port: 21)</td></tr>
                            <tr><th>MySQL 主機名稱 (MySQL Hostname)</th><td><code><?= htmlspecialchars($HOST_INFO['mysql_host']) ?></code> (Port: 3306)</td></tr>
                            <tr><th>主機卷 (Hosting Volume)</th><td><code><?= htmlspecialchars($HOST_INFO['volume']) ?></code></td></tr>
                            <tr><th>記錄之上次登入 IP</th><td><code><?= htmlspecialchars($HOST_INFO['last_ip']) ?></code> (目前連線 IP: <code><?= htmlspecialchars($_SERVER['REMOTE_ADDR']) ?></code>)</td></tr>
                            <tr><th>PHP 執行版本與介面</th><td><code><?= phpversion() ?> (<?= php_sapi_name() ?>)</code></td></tr>
                            <tr><th>單檔上傳上限 / POST 上限</th><td><code><?= ini_get('upload_max_filesize') ?> / <?= ini_get('post_max_size') ?></code></td></tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // 檔案重命名提示框
        function renamePrompt(oldName) {
            const newName = prompt("請輸入新的檔案或資料夾名稱：", oldName);
            if (newName && newName.trim() !== "" && newName !== oldName) {
                document.getElementById('renameOld').value = oldName;
                document.getElementById('renameNew').value = newName.trim();
                document.getElementById('renameForm').submit();
            }
        }

        // 程式碼編輯器行號同步與 Tab 鍵 / Ctrl+S 支援
        const editor = document.getElementById('codeEditor');
        const lineNumbers = document.getElementById('lineNumbers');
        if (editor && lineNumbers) {
            function updateLineNumbers() {
                const lines = editor.value.split('\n').length;
                lineNumbers.innerHTML = Array.from({length: lines}, (_, i) => i + 1).join('<br>');
            }
            editor.addEventListener('input', updateLineNumbers);
            editor.addEventListener('scroll', () => { lineNumbers.scrollTop = editor.scrollTop; });
            editor.addEventListener('keydown', function(e) {
                if (e.key === 'Tab') {
                    e.preventDefault();
                    const start = this.selectionStart;
                    const end = this.selectionEnd;
                    this.value = this.value.substring(0, start) + "    " + this.value.substring(end);
                    this.selectionStart = this.selectionEnd = start + 4;
                    updateLineNumbers();
                }
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                    e.preventDefault();
                    document.getElementById('editorForm').submit();
                }
            });
            updateLineNumbers();
        }
    </script>
</body>
</html>
