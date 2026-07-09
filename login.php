<?php
// login.php
session_start();
require_once 'config/db.php';

// 如果已經登入，根據角色直接導向
if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'student') {
        header("Location: index.php");
    } else {
        header("Location: admin.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = $_POST['role'] ?? 'student'; // 預設學生

    if ($username === '' || $password === '') {
        $error = '請填寫帳號與密碼！';
    } else {
        // 因密碼尚未加密，目前採取明文比對 (若以後要改 password_hash 請再告訴我)
        $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ? AND password = ? AND role = ?");
        $stmt->execute([$username, $password, $role]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            if ($user['role'] === 'student') {
                header("Location: index.php");
            } else {
                header("Location: admin.php"); // 導向管理後台
            }
            exit;
        } else {
            $error = '帳號、密碼或身分錯誤，請重新確認！';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>圖書借閱系統 - 使用者登入</title>
    <style>
        /* 運用 Flexbox 讓登入卡片在各種螢幕上完美居中 */
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background-color: #f4f6f9; 
            margin: 0; 
            padding: 0; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
        }
        
        /* 精緻卡片式設計，寬度鎖定 420px，乾淨不空洞 */
        .login-card { 
            width: 100%; 
            max-width: 420px; 
            background: white; 
            padding: 40px; 
            border-radius: 12px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.08); 
            box-sizing: border-box;
        }
        
        .login-card h2 { 
            margin-top: 0; 
            margin-bottom: 8px; 
            color: #333; 
            text-align: center; 
            font-size: 26px;
        }
        
        .subtitle {
            text-align: center;
            color: #6c757d;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .form-group { 
            margin-bottom: 20px; 
        }
        
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: bold; 
            color: #495057; 
            font-size: 14px;
        }
        
        /* 輸入框元件高度與寬度同步優化 */
        .form-control { 
            width: 100%; 
            height: 44px; 
            padding: 10px 12px; 
            box-sizing: border-box; 
            border: 1px solid #ced4da; 
            border-radius: 6px; 
            font-size: 15px;
            transition: border-color 0.15s ease-in-out;
        }
        .form-control:focus { 
            border-color: #007bff; 
            outline: 0; 
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25); 
        }
        
        /* 登入按鈕 */
        .btn-login { 
            width: 100%; 
            height: 44px; 
            background-color: #007bff; 
            color: white; 
            border: none; 
            border-radius: 6px; 
            font-size: 16px; 
            font-weight: bold; 
            cursor: pointer; 
            margin-top: 10px;
            box-shadow: 0 4px 6px rgba(0,123,255,0.15);
            transition: background-color 0.15s;
        }
        .btn-login:hover { 
            background-color: #0056b3; 
        }
        
        /* 錯誤提示 */
        .alert-danger { 
            background-color: #f8d7da; 
            color: #721c24; 
            padding: 12px; 
            border-radius: 6px; 
            margin-bottom: 20px; 
            font-size: 14px; 
            font-weight: 500;
            text-align: center;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

<div class="login-card">
    <h2>📚 圖書借閱系統</h2>
    <div class="subtitle">歡迎使用，請輸入您的憑證登入</div>

    <?php if (!empty($error)): ?>
        <div class="alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label>使用者帳號</label>
            <input type="text" name="username" class="form-control" placeholder="請輸入帳號" required autocomplete="off">
        </div>

        <div class="form-group">
            <label>安全密碼</label>
            <input type="password" name="password" class="form-control" placeholder="請輸入密碼" required>
        </div>

        <div class="form-group">
            <label>登入身分</label>
            <select name="role" class="form-control">
                <option value="student">一般學生 (Student)</option>
                <option value="admin">系統管理員 (Admin)</option>
            </select>
        </div>

        <button type="submit" class="btn-login">安全登入</button>
    </form>
</div>

</body>
</html>