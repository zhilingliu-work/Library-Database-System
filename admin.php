<?php
// admin.php
session_start();

// 引入資料庫連線
require_once 'config/db.php';

// 【資安防護】檢查是否有合法 Session，且必須是管理員 (admin)
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$login_user = $_SESSION['username'];
$msg = '';
$error_msg = '';

// -------------------------------------------------------------------------
// 🛠️ 動作 1：處理新增書籍 (POST)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $title    = trim($_POST['title'] ?? '');
    $author   = trim($_POST['author'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);

    if ($title === '' || $author === '') {
        $error_msg = "⚠️ 書名與作者為必填項目！";
    } elseif ($quantity < 0) {
        $error_msg = "⚠️ 庫存數量不能為負數！";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO book (title, author, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$title, $author, $quantity]);
            $msg = "🎉 成功上架新書：《{$title}》！";
        } catch (Exception $e) {
            $error_msg = "上架失敗：" . $e->getMessage();
        }
    }
}

// -------------------------------------------------------------------------
// 🛠️ 動作 2：處理編輯書籍 (POST)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $book_id  = intval($_POST['book_id'] ?? 0);
    $title    = trim($_POST['title'] ?? '');
    $author   = trim($_POST['author'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);

    if ($title === '' || $author === '') {
        $error_msg = "⚠️ 編輯失敗：書名與作者不可為空！";
    } elseif ($quantity < 0) {
        $error_msg = "⚠️ 編輯失敗：庫存數量不能為負數！";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE book SET title = ?, author = ?, quantity = ? WHERE book_id = ?");
            $stmt->execute([$title, $author, $quantity, $book_id]);
            $msg = "✏️ 成功更新書籍資料：《{$title}》！";
        } catch (Exception $e) {
            $error_msg = "更新失敗：" . $e->getMessage();
        }
    }
}

// -------------------------------------------------------------------------
// 🛠️ 動作 3：處理刪除書籍 (POST)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $book_id = intval($_POST['book_id'] ?? 0);

    try {
        $stmt = $pdo->prepare("DELETE FROM book WHERE book_id = ?");
        $stmt->execute([$book_id]);
        $msg = "🗑️ 書籍（編號 #{$book_id}）已成功從系統下架。";
    } catch (Exception $e) {
        $error_msg = "下架失敗，可能此書籍目前仍有學生借閱中：" . $e->getMessage();
    }
}

// -------------------------------------------------------------------------
// 🔍 動作 4：後端書籍查詢與列表獲取 (GET)
// -------------------------------------------------------------------------
$search = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM book WHERE title LIKE ? OR author LIKE ? ORDER BY book_id DESC");
    $stmt->execute(["%$search%", "%$search%"]);
    $books = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("SELECT * FROM book ORDER BY book_id DESC");
    $books = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>圖書借閱系統 - 管理員後台</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 30px 20px; }
        .container { max-width: 1140px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
        
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #343a40; padding-bottom: 15px; margin-bottom: 25px; }
        .header h2 { margin: 0; font-size: 24px; color: #333; }
        .nav-links a { margin-right: 15px; color: #007bff; text-decoration: none; font-weight: bold; }
        .nav-links a:hover { text-decoration: underline; }
        .logout-btn { color: red !important; }
        
        .top-panels { display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap; }
        .panel-add { flex: 2; min-width: 350px; background-color: #f8f9fa; padding: 20px; border-radius: 8px; border-top: 4px solid #007bff; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .panel-search { flex: 1; min-width: 280px; background-color: #f8f9fa; padding: 20px; border-radius: 8px; border-top: 4px solid #6c757d; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        
        .panel-title { font-weight: bold; font-size: 16px; color: #333; margin-bottom: 15px; display: flex; align-items: center; gap: 6px; }
        
        .form-inline { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .form-control { box-sizing: border-box; height: 42px; padding: 10px 12px; border: 1px solid #ced4da; border-radius: 6px; font-size: 14px; width: 100%; }
        .form-control:focus { border-color: #007bff; outline: 0; box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25); }
        
        .btn-primary { background-color: #007bff; color: white; border: none; padding: 0 20px; border-radius: 6px; cursor: pointer; font-weight: bold; height: 42px; line-height: 42px; }
        .btn-primary:hover { background-color: #0056b3; }
        .btn-secondary { background-color: #6c757d; color: white; border: none; padding: 0 20px; border-radius: 6px; cursor: pointer; font-weight: bold; height: 42px; line-height: 42px; }
        .btn-secondary:hover { background-color: #5a6268; }
        .btn-clear { display: inline-block; background-color: #e2e3e5; color: #383d41; text-decoration: none; padding: 0 15px; border-radius: 6px; font-weight: bold; height: 42px; line-height: 42px; text-align: center; border: 1px solid #d6d8db; box-sizing: border-box; }
        .btn-clear:hover { background-color: #d6d8db; }
        
        /* 表格按鈕 */
        .btn-edit { background-color: #fd7e14; color: white; border: none; padding: 0 12px; height: 34px; line-height: 34px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px; }
        .btn-edit:hover { background-color: #e06d0f; }
        .btn-danger { background-color: #dc3545; color: white; border: none; padding: 0 12px; height: 34px; line-height: 34px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px; }
        .btn-danger:hover { background-color: #bd2130; }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; border-radius: 8px; overflow: hidden; box-shadow: 0 0 5px rgba(0,0,0,0.02); }
        th, td { padding: 14px 18px; text-align: left; border-bottom: 1px solid #dee2e6; vertical-align: middle; }
        th { background-color: #343a40; color: white; font-weight: 600; }
        
        /* 放寬操作欄位，以容納編輯與刪除兩顆按鈕 */
        .col-id { width: 100px; }
        .col-author { width: 220px; }
        .col-qty { width: 140px; }
        .col-action { width: 160px; }

        .alert-success { background-color: #d4edda; color: #155724; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; border-left: 5px solid #28a745; }
        .alert-danger { background-color: #f8d7da; color: #721c24; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; border-left: 5px solid #dc3545; }
        h3 { color: #333; font-size: 18px; margin-top: 10px; margin-bottom: 15px; }

        /* 🌟 編輯互動視窗 (Modal) 的 CSS 樣式 */
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;
        }
        .modal-content {
            background-color: white; padding: 30px; border-radius: 8px; width: 100%; max-width: 400px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .modal-content h3 { margin-top: 0; border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-bottom: 20px; }
        .modal-form-group { margin-bottom: 15px; }
        .modal-form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #495057; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>🛠️ 圖書管理員後台控制面板</h2>
        <div class="nav-links">
            <span>歡迎，<?php echo htmlspecialchars($login_user); ?> (管理員)</span> | 
            <a href="admin.php" style="color: #6f42c1; border-bottom: 2px solid #6f42c1; padding-bottom: 2px;">館藏管理</a>
            <a href="manage_users.php">使用者管理</a>
            <a href="logout.php" class="logout-btn">登出系統</a>
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
            <div class="panel-title">➕ 快速上架全新圖書</div>
            <form method="POST" action="admin.php" class="form-inline">
                <input type="hidden" name="action" value="add">
                <input type="text" name="title" class="form-control" placeholder="輸入書籍名稱" style="flex: 2; min-width: 150px;" required autocomplete="off">
                <input type="text" name="author" class="form-control" placeholder="輸入作者名字" style="flex: 1; min-width: 120px;" required autocomplete="off">
                <input type="number" name="quantity" class="form-control" placeholder="初始庫存" style="width: 90px;" min="0" value="5" required>
                <button type="submit" class="btn-primary">確認上架</button>
            </form>
        </div>

        <div class="panel-search">
            <div class="panel-title">🔍 後台館藏快速檢索</div>
            <form method="GET" action="admin.php" class="form-inline">
                <input type="text" name="search" class="form-control" placeholder="請輸入書名或作者..." style="flex: 1;" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="btn-secondary">搜尋</button>
                <?php if ($search !== ''): ?>
                    <a href="admin.php" class="btn-clear">清除</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <h3>📋 全館現存圖書清單</h3>
    <table>
        <thead>
            <tr>
                <th class="col-id">書籍編號</th>
                <th>書籍名稱</th>
                <th class="col-author">書籍作者</th>
                <th class="col-qty">目前館藏數量</th>
                <th class="col-action">管理操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($books) > 0): ?>
                <?php foreach ($books as $book): ?>
                    <tr>
                        <td><code>#<?php echo htmlspecialchars($book['book_id']); ?></code></td>
                        <td><strong style="color: #343a40; font-size: 16px;"><?php echo htmlspecialchars($book['title']); ?></strong></td>
                        <td><span style="color: #495057;"><?php echo htmlspecialchars($book['author']); ?></span></td>
                        <td style="font-weight: bold; color: <?php echo $book['quantity'] > 0 ? '#28a745' : '#dc3545'; ?>">
                            <?php echo htmlspecialchars($book['quantity']); ?> 冊
                        </td>
                        <td style="display: flex; gap: 8px;">
                            <button type="button" class="btn-edit" 
                                    data-id="<?php echo $book['book_id']; ?>" 
                                    data-title="<?php echo htmlspecialchars($book['title']); ?>" 
                                    data-author="<?php echo htmlspecialchars($book['author']); ?>" 
                                    data-qty="<?php echo $book['quantity']; ?>" 
                                    onclick="openEditModal(this)">編輯</button>

                            <form action="admin.php" method="POST" style="margin:0;" onsubmit="return confirm('⚠️ 確定要將《<?php echo htmlspecialchars($book['title']); ?>》下架嗎？');">
                                <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn-danger">下架</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #721c24; background-color: #f8d7da; font-weight: bold; padding: 20px;">
                        <?php echo ($search !== '') ? "❌ 找不到符合該關鍵字的館藏書籍。" : "目前系統內無任何書籍資料，請先由上方表單新增。"; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="editModal" class="modal-overlay">
    <div class="modal-content">
        <h3>✏️ 修改書籍資料</h3>
        <form method="POST" action="admin.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="book_id" id="modal_book_id">
            
            <div class="modal-form-group">
                <label>書籍名稱</label>
                <input type="text" name="title" id="modal_title" class="form-control" required autocomplete="off">
            </div>
            <div class="modal-form-group">
                <label>書籍作者</label>
                <input type="text" name="author" id="modal_author" class="form-control" required autocomplete="off">
            </div>
            <div class="modal-form-group">
                <label>館藏數量</label>
                <input type="number" name="quantity" id="modal_quantity" class="form-control" min="0" required>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeEditModal()">取消</button>
                <button type="submit" class="btn-primary" style="background-color: #28a745;">儲存變更</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(button) {
        // 從按鈕的 data-* 屬性抓取該列的書籍資料
        let id = button.getAttribute('data-id');
        let title = button.getAttribute('data-title');
        let author = button.getAttribute('data-author');
        let qty = button.getAttribute('data-qty');
        
        // 將資料填入 Modal 表單內
        document.getElementById('modal_book_id').value = id;
        document.getElementById('modal_title').value = title;
        document.getElementById('modal_author').value = author;
        document.getElementById('modal_quantity').value = qty;
        
        // 顯示 Modal
        document.getElementById('editModal').style.display = 'flex';
    }

    function closeEditModal() {
        // 隱藏 Modal
        document.getElementById('editModal').style.display = 'none';
    }
</script>

</body>
</html>