<?php
// manage_users.php
session_start();

// 引入資料庫連線
require_once 'config/db.php';

// 【資安防護】必須是管理員 (admin) 才能進入此頁面
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$login_user = $_SESSION['username'];
$msg = '';
$error_msg = '';

// -------------------------------------------------------------------------
// 👤 動作 1：處理新增使用者 (POST)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $new_username = trim($_POST['username'] ?? '');
    $new_password = trim($_POST['password'] ?? '');
    $new_role     = $_POST['role'] ?? 'student';

    if ($new_username === '' || $new_password === '') {
        $error_msg = "⚠️ 帳號與密碼不可為空！";
    } else {
        try {
            // 檢查帳號是否已存在
            $checkStmt = $pdo->prepare("SELECT username FROM user WHERE username = ?");
            $checkStmt->execute([$new_username]);
            if ($checkStmt->fetch()) {
                $error_msg = "⚠️ 帳號「{$new_username}」已經存在，請更換一個！";
            } else {
                $stmt = $pdo->prepare("INSERT INTO user (username, password, role) VALUES (?, ?, ?)");
                $stmt->execute([$new_username, $new_password, $new_role]);
                $role_name = ($new_role === 'admin') ? '管理員' : '學生';
                $msg = "🎉 成功新增 {$role_name} 帳號：「{$new_username}」！";
            }
        } catch (Exception $e) {
            $error_msg = "新增失敗：" . $e->getMessage();
        }
    }
}

// -------------------------------------------------------------------------
// 👤 動作 2：處理編輯使用者 (POST)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $target_user = trim($_POST['original_username'] ?? '');
    $edit_pass   = trim($_POST['password'] ?? '');
    $edit_role   = $_POST['role'] ?? 'student';

    if ($target_user === '' || $edit_pass === '') {
        $error_msg = "⚠️ 編輯失敗：密碼不可為空！";
    } else {
        // 防止管理員把自己降級成學生（防呆機制）
        if ($target_user === $login_user && $edit_role !== 'admin') {
            $error_msg = "⚠️ 警告：您不能更改自己的管理員權限！";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE user SET password = ?, role = ? WHERE username = ?");
                $stmt->execute([$edit_pass, $edit_role, $target_user]);
                $msg = "✏️ 成功更新帳號「{$target_user}」的資料！";
            } catch (Exception $e) {
                $error_msg = "更新失敗：" . $e->getMessage();
            }
        }
    }
}

// -------------------------------------------------------------------------
// 👤 動作 3：處理刪除使用者 (POST)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $target_user = trim($_POST['username'] ?? '');

    if ($target_user === $login_user) {
        $error_msg = "⚠️ 警告：您不能刪除自己的帳號！";
    } else {
        try {
            // 實務上這裡可能要連帶處理該學生的借閱紀錄，這裡示範直接刪除帳號
            $stmt = $pdo->prepare("DELETE FROM user WHERE username = ?");
            $stmt->execute([$target_user]);
            $msg = "🗑️ 帳號「{$target_user}」已成功移除。";
        } catch (Exception $e) {
            $error_msg = "刪除失敗（該帳號可能還有未歸還的借閱紀錄）：" . $e->getMessage();
        }
    }
}

// -------------------------------------------------------------------------
// 🔍 動作 4：獲取使用者列表 (GET 搜尋)
// -------------------------------------------------------------------------
$search = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM user WHERE username LIKE ? ORDER BY role ASC, username ASC");
    $stmt->execute(["%$search%"]);
    $users = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("SELECT * FROM user ORDER BY role ASC, username ASC");
    $users = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>系統管理 - 使用者清單</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 30px 20px; }
        .container { max-width: 1140px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
        
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #6f42c1; padding-bottom: 15px; margin-bottom: 25px; }
        .header h2 { margin: 0; font-size: 24px; color: #333; }
        .nav-links a { margin-right: 15px; color: #007bff; text-decoration: none; font-weight: bold; }
        .nav-links a.active { color: #6f42c1; border-bottom: 2px solid #6f42c1; padding-bottom: 2px; }
        .nav-links a:hover { text-decoration: underline; }
        .logout-btn { color: red !important; }
        
        .top-panels { display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap; }
        .panel-add { flex: 2; min-width: 350px; background-color: #f8f9fa; padding: 20px; border-radius: 8px; border-top: 4px solid #28a745; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .panel-search { flex: 1; min-width: 280px; background-color: #f8f9fa; padding: 20px; border-radius: 8px; border-top: 4px solid #6c757d; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        
        .panel-title { font-weight: bold; font-size: 16px; color: #333; margin-bottom: 15px; display: flex; align-items: center; gap: 6px; }
        
        .form-inline { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .form-control { box-sizing: border-box; height: 42px; padding: 10px 12px; border: 1px solid #ced4da; border-radius: 6px; font-size: 14px; width: 100%; }
        .form-control:focus { border-color: #007bff; outline: 0; box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25); }
        
        .btn-primary { background-color: #28a745; color: white; border: none; padding: 0 20px; border-radius: 6px; cursor: pointer; font-weight: bold; height: 42px; line-height: 42px; }
        .btn-primary:hover { background-color: #218838; }
        .btn-secondary { background-color: #6c757d; color: white; border: none; padding: 0 20px; border-radius: 6px; cursor: pointer; font-weight: bold; height: 42px; line-height: 42px; }
        .btn-secondary:hover { background-color: #5a6268; }
        .btn-clear { display: inline-block; background-color: #e2e3e5; color: #383d41; text-decoration: none; padding: 0 15px; border-radius: 6px; font-weight: bold; height: 42px; line-height: 42px; text-align: center; border: 1px solid #d6d8db; box-sizing: border-box; }
        
        .btn-edit { background-color: #fd7e14; color: white; border: none; padding: 0 12px; height: 34px; line-height: 34px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px; }
        .btn-edit:hover { background-color: #e06d0f; }
        .btn-danger { background-color: #dc3545; color: white; border: none; padding: 0 12px; height: 34px; line-height: 34px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px; }
        .btn-danger:hover { background-color: #bd2130; }
        .btn-disabled { background-color: #e9ecef; color: #6c757d; border: none; padding: 0 12px; height: 34px; line-height: 34px; border-radius: 6px; font-weight: bold; font-size: 14px; cursor: not-allowed; }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; border-radius: 8px; overflow: hidden; box-shadow: 0 0 5px rgba(0,0,0,0.02); }
        th, td { padding: 14px 18px; text-align: left; border-bottom: 1px solid #dee2e6; vertical-align: middle; }
        th { background-color: #6f42c1; color: white; font-weight: 600; }
        
        .badge-admin { background-color: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-student { background-color: #17a2b8; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }

        .alert-success { background-color: #d4edda; color: #155724; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; border-left: 5px solid #28a745; }
        .alert-danger { background-color: #f8d7da; color: #721c24; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; border-left: 5px solid #dc3545; }
        h3 { color: #333; font-size: 18px; margin-top: 10px; margin-bottom: 15px; }

        /* Modal 樣式 */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background-color: white; padding: 30px; border-radius: 8px; width: 100%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .modal-content h3 { margin-top: 0; border-bottom: 2px solid #6f42c1; padding-bottom: 10px; margin-bottom: 20px; color: #6f42c1; }
        .modal-form-group { margin-bottom: 15px; }
        .modal-form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #495057; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>👥 系統管理員 - 使用者帳號管理</h2>
        <div class="nav-links">
            <span>管理員：<?php echo htmlspecialchars($login_user); ?></span> | 
            <a href="admin.php">館藏管理</a>
            <a href="manage_users.php" class="active">使用者管理</a>
            <a href="logout.php" class="logout-btn">登出</a>
        </div>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="alert-success"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <?php if (!empty($error_msg)): ?>
        <div class="alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <div class="top-panels">
        <div class="panel-add">
            <div class="panel-title">➕ 建立新帳號</div>
            <form method="POST" action="manage_users.php" class="form-inline">
                <input type="hidden" name="action" value="add">
                <input type="text" name="username" class="form-control" placeholder="輸入登入帳號" style="flex: 2; min-width: 120px;" required autocomplete="off">
                <input type="text" name="password" class="form-control" placeholder="輸入登入密碼" style="flex: 2; min-width: 120px;" required autocomplete="off">
                <select name="role" class="form-control" style="width: 110px;">
                    <option value="student">學生</option>
                    <option value="admin">管理員</option>
                </select>
                <button type="submit" class="btn-primary">建立帳號</button>
            </form>
        </div>

        <div class="panel-search">
            <div class="panel-title">🔍 尋找帳號</div>
            <form method="GET" action="manage_users.php" class="form-inline">
                <input type="text" name="search" class="form-control" placeholder="輸入帳號關鍵字..." style="flex: 1;" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="btn-secondary">搜尋</button>
                <?php if ($search !== ''): ?>
                    <a href="manage_users.php" class="btn-clear">清除</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <h3>📋 系統使用者列表</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 250px;">使用者帳號</th>
                <th>目前密碼</th>
                <th style="width: 150px;">身分權限</th>
                <th style="width: 160px;">操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($users) > 0): ?>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <strong style="font-size: 16px; color: #343a40;">
                                <?php echo htmlspecialchars($u['username']); ?>
                                <?php if ($u['username'] === $login_user) echo " <span style='color: #28a745; font-size:12px;'>(您自己)</span>"; ?>
                            </strong>
                        </td>
                        <td><code><?php echo htmlspecialchars($u['password']); ?></code></td>
                        <td>
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="badge-admin">系統管理員</span>
                            <?php else: ?>
                                <span class="badge-student">一般學生</span>
                            <?php endif; ?>
                        </td>
                        <td style="display: flex; gap: 8px;">
                            <button type="button" class="btn-edit" 
                                    data-user="<?php echo htmlspecialchars($u['username']); ?>" 
                                    data-pass="<?php echo htmlspecialchars($u['password']); ?>" 
                                    data-role="<?php echo htmlspecialchars($u['role']); ?>" 
                                    onclick="openEditUserModal(this)">編輯</button>

                            <?php if ($u['username'] === $login_user): ?>
                                <button type="button" class="btn-disabled" disabled title="無法刪除自己的帳號">刪除</button>
                            <?php else: ?>
                                <form action="manage_users.php" method="POST" style="margin:0;" onsubmit="return confirm('⚠️ 確定要刪除帳號「<?php echo htmlspecialchars($u['username']); ?>」嗎？\n(注意：若該帳號有借閱紀錄可能無法直接刪除)');">
                                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($u['username']); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn-danger">刪除</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: #721c24; background-color: #f8d7da; font-weight: bold; padding: 20px;">找不到符合該關鍵字的帳號。</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="editUserModal" class="modal-overlay">
    <div class="modal-content">
        <h3>⚙️ 修改使用者資料</h3>
        <form method="POST" action="manage_users.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="original_username" id="modal_orig_user">
            
            <div class="modal-form-group">
                <label>使用者帳號 (不可修改)</label>
                <input type="text" id="modal_display_user" class="form-control" readonly style="background-color: #e9ecef;">
            </div>
            <div class="modal-form-group">
                <label>修改密碼</label>
                <input type="text" name="password" id="modal_password" class="form-control" required autocomplete="off">
            </div>
            <div class="modal-form-group">
                <label>身分權限</label>
                <select name="role" id="modal_role" class="form-control">
                    <option value="student">一般學生</option>
                    <option value="admin">系統管理員</option>
                </select>
                <small style="color: #6c757d; display: block; margin-top: 5px;">*管理員無法在此將自己降級。</small>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeEditUserModal()">取消</button>
                <button type="submit" class="btn-primary" style="background-color: #6f42c1;">儲存變更</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditUserModal(button) {
        let user = button.getAttribute('data-user');
        let pass = button.getAttribute('data-pass');
        let role = button.getAttribute('data-role');
        
        document.getElementById('modal_orig_user').value = user;
        document.getElementById('modal_display_user').value = user;
        document.getElementById('modal_password').value = pass;
        document.getElementById('modal_role').value = role;
        
        document.getElementById('editUserModal').style.display = 'flex';
    }

    function closeEditUserModal() {
        document.getElementById('editUserModal').style.display = 'none';
    }
</script>

</body>
</html>