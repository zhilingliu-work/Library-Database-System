<?php
// mybooks.php
session_start();

// 引入資料庫連線
require_once 'config/db.php';

// 【資安防護】檢查是否有合法 Session
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$login_user = $_SESSION['username'];
$msg = '';
$error_msg = '';

// 處理「批次歸還」核心邏輯
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'batch_return') {
    $book_ids = $_POST['book_ids'] ?? [];

    if (!empty($book_ids) && is_array($book_ids)) {
        try {
            $pdo->beginTransaction();
            $success_count = 0;

            foreach ($book_ids as $b_id) {
                $b_id = intval($b_id);
                
                $checkStmt = $pdo->prepare("SELECT * FROM borrow_record WHERE username = ? AND book_id = ?");
                $checkStmt->execute([$login_user, $b_id]);
                
                if ($checkStmt->fetch()) {
                    $delStmt = $pdo->prepare("DELETE FROM borrow_record WHERE username = ? AND book_id = ?");
                    $delStmt->execute([$login_user, $b_id]);

                    $updStmt = $pdo->prepare("UPDATE book SET quantity = quantity + 1 WHERE book_id = ?");
                    $updStmt->execute([$b_id]);
                    
                    $success_count++;
                }
            }

            $pdo->commit();
            if ($success_count > 0) {
                $msg = "✅ 成功歸還 {$success_count} 本書籍，庫存已自動回補！";
            } else {
                $error_msg = "沒有成功歸還任何書籍。";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = "歸還過程中發生錯誤：" . $e->getMessage();
        }
    } else {
        $error_msg = "⚠️ 請先勾選至少一本要歸還的書籍！";
    }
}

// 🔍 後端查詢
$search = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

if ($search !== '') {
    $sql = "SELECT r.*, b.title, b.author 
            FROM borrow_record r
            JOIN book b ON r.book_id = b.book_id
            WHERE r.username = ? AND (b.title LIKE ? OR b.author LIKE ?)
            ORDER BY r.borrow_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$login_user, "%$search%", "%$search%"]);
    $records = $stmt->fetchAll();
} else {
    $sql = "SELECT r.*, b.title, b.author 
            FROM borrow_record r
            JOIN book b ON r.book_id = b.book_id
            WHERE r.username = ?
            ORDER BY r.borrow_date DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$login_user]);
    $records = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>線上圖書借閱系統 - 個人借閱紀錄</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 30px 20px; }
        
        /* 🌟 同步放寬至 1140px */
        .container { max-width: 1140px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
        
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #007bff; padding-bottom: 15px; margin-bottom: 25px; }
        .header h2 { margin: 0; font-size: 24px; color: #333; }
        .nav-links a { margin-right: 15px; color: #007bff; text-decoration: none; font-weight: bold; }
        .nav-links a:hover { text-decoration: underline; }
        .logout-btn { color: red !important; }
        
        /* 🔍 搜尋區塊高度與色調調整 (還書頁改為質感青藍色) */
        .search-section { background-color: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 5px solid #17a2b8; margin-bottom: 25px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.02); }
        .search-inline { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .search-inline input, .btn-search, .btn-clear { box-sizing: border-box; height: 42px; font-size: 15px; }
        .search-inline input { padding: 10px 15px; border: 1px solid #ced4da; border-radius: 6px; flex: 1; }
        .search-inline input:focus { border-color: #17a2b8; outline: 0; box-shadow: 0 0 0 0.2rem rgba(23,162,184,0.25); }
        
        .btn-search { background-color: #17a2b8; color: white; border: none; padding: 0 25px; border-radius: 6px; cursor: pointer; font-weight: bold; line-height: 42px; }
        .btn-search:hover { background-color: #138496; }
        .btn-clear { display: inline-block; background-color: #6c757d; color: white; text-decoration: none; padding: 0 18px; border-radius: 6px; font-weight: bold; line-height: 42px; text-align: center; }
        .btn-clear:hover { background-color: #5a6268; }
        
        /* 🛠️ 批次操作區塊 */
        .batch-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding: 15px 20px; background-color: #fff3cd; border-radius: 8px; border: 1px solid #ffeeba; }
        .btn-batch { background-color: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 15px; box-shadow: 0 2px 4px rgba(40,167,69,0.2); }
        .btn-batch:hover { background-color: #218838; }

        /* 🌟 表格與寬度限制 */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; border-radius: 8px; overflow: hidden; box-shadow: 0 0 5px rgba(0,0,0,0.02); }
        th, td { padding: 14px 18px; text-align: left; border-bottom: 1px solid #dee2e6; vertical-align: middle; }
        th { background-color: #17a2b8; color: white; font-weight: 600; }
        
        .col-cb { width: 50px; text-align: center; }
        .col-author { width: 200px; }
        .col-date { width: 160px; }
        .col-action { width: 120px; }

        input[type="checkbox"] { transform: scale(1.3); cursor: pointer; }
        
        .btn-return {
            display: inline-block; height: 34px; line-height: 34px; padding: 0 16px; font-size: 14px;
            border-radius: 6px; border: none; cursor: pointer; font-weight: bold; box-sizing: border-box;
            background-color: #fd7e14; color: white;
        }
        .btn-return:hover { background-color: #e06d0f; }
        
        .alert-success { background-color: #d4edda; color: #155724; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; }
        .alert-danger { background-color: #f8d7da; color: #721c24; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; }
        h3 { color: #333; font-size: 18px; margin-top: 10px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>📚 個人借閱紀錄頁面</h2>
        <div class="nav-links">
            <span>歡迎，<?php echo htmlspecialchars($login_user); ?> (學生)</span> | 
            <a href="index.php">借書大廳</a>
            <a href="mybooks.php">我的借閱紀錄</a>
            <a href="logout.php" class="logout-btn">登出</a>
        </div>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="alert-success"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <?php if (!empty($error_msg)): ?>
        <div class="alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <div class="search-section">
        <form method="GET" action="mybooks.php" class="search-inline">
            <label style="font-weight: bold; color: #495057;">搜尋我的借閱：</label>
            <input type="text" name="search" placeholder="請輸入已借書籍的書名或作者..." 
                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit" class="btn-search">搜尋紀錄</button>
            <?php if ($search !== ''): ?>
                <a href="mybooks.php" class="btn-clear">清除搜尋</a>
            <?php endif; ?>
        </form>
    </div>

    <h3>📋 我目前借閱中的書籍</h3>
    
    <form action="mybooks.php" method="POST" id="mainReturnForm">
        <input type="hidden" name="action" value="batch_return">
        
        <?php if (count($records) > 0): ?>
        <div class="batch-actions">
            <span style="color: #856404; font-weight: bold;">💡 提示：您可以勾選多本書籍，然後點擊右側按鈕一次歸還。</span>
            <button type="submit" class="btn-batch" onclick="return confirmBatchReturn();">📚 一鍵歸還勾選書籍</button>
        </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th class="col-cb" style="text-align: center;">
                        <input type="checkbox" id="selectAll" onclick="toggleAll(this)" title="全選/取消全選">
                    </th>
                    <th>書名</th>
                    <th class="col-author">作者</th>
                    <th class="col-date">借閱日期</th>
                    <th class="col-action">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($records) > 0): ?>
                    <?php foreach ($records as $row): ?>
                        <tr>
                            <td style="text-align: center;">
                                <input type="checkbox" name="book_ids[]" value="<?php echo $row['book_id']; ?>" class="book-cb" id="cb_<?php echo $row['book_id']; ?>">
                            </td>
                            <td>
                                <label for="cb_<?php echo $row['book_id']; ?>" style="cursor:pointer; font-size: 16px; color: #17a2b8;"><strong><?php echo htmlspecialchars($row['title']); ?></strong></label>
                            </td>
                            <td><span style="color: #495057;"><?php echo htmlspecialchars($row['author']); ?></span></td>
                            <td><code><?php echo htmlspecialchars($row['borrow_date']); ?></code></td>
                            <td>
                                <button type="button" class="btn-return" onclick="singleReturn(<?php echo $row['book_id']; ?>)">單獨歸還</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #721c24; background-color: #f8d7da; font-weight: bold; padding: 20px;">
                            <?php echo ($search !== '') ? "❌ 找不到符合關鍵字的借閱紀錄。" : "您目前沒有借閱任何書籍。"; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
</div>

<script>
    function toggleAll(source) {
        let checkboxes = document.querySelectorAll('.book-cb');
        checkboxes.forEach(cb => cb.checked = source.checked);
    }
    function confirmBatchReturn() {
        let checkedCount = document.querySelectorAll('.book-cb:checked').length;
        if (checkedCount === 0) { alert("請先在左側勾選至少一本要歸還的書籍！"); return false; }
        return confirm("確定要一次歸還這 " + checkedCount + " 本書嗎？");
    }
    function singleReturn(bookId) {
        if (confirm('確定要歸還這本書嗎？')) {
            document.querySelectorAll('.book-cb').forEach(cb => cb.checked = false);
            document.getElementById('cb_' + bookId).checked = true;
            document.getElementById('mainReturnForm').submit();
        }
    }
</script>
</body>
</html>