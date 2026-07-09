<?php
// index.php
session_start();

// 引入資料庫連線
require_once 'config/db.php';

// 【資安防護】檢查是否有合法 Session，且必須是一般學生 (student)
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$login_user = $_SESSION['username'];
$msg = '';
$error_msg = '';

// 處理批次與單獨借閱
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'batch_borrow') {
    $book_ids = $_POST['book_ids'] ?? [];
    if (!empty($book_ids) && is_array($book_ids)) {
        try {
            $pdo->beginTransaction();
            $success_titles = [];
            $error_details = [];

            foreach ($book_ids as $b_id) {
                $b_id = intval($b_id);
                $stmt = $pdo->prepare("SELECT quantity, title FROM book WHERE book_id = ? FOR UPDATE");
                $stmt->execute([$b_id]);
                $book = $stmt->fetch();

                if ($book) {
                    $title = $book['title'];
                    if ($book['quantity'] <= 0) {
                        $error_details[] = "《{$title}》已無庫存";
                        continue;
                    }
                    $checkStmt = $pdo->prepare("SELECT * FROM borrow_record WHERE username = ? AND book_id = ?");
                    $checkStmt->execute([$login_user, $b_id]);
                    if ($checkStmt->fetch()) {
                        $error_details[] = "《{$title}》您已借過且尚未歸還";
                        continue;
                    }

                    $insertStmt = $pdo->prepare("INSERT INTO borrow_record (username, book_id, borrow_date) VALUES (?, ?, ?)");
                    $insertStmt->execute([$login_user, $b_id, date('Y-m-d')]);

                    $updateStmt = $pdo->prepare("UPDATE book SET quantity = quantity - 1 WHERE book_id = ?");
                    $updateStmt->execute([$b_id]);

                    $success_titles[] = "《{$title}》";
                }
            }

            $pdo->commit();
            if (count($success_titles) > 0) {
                $msg = "✅ 成功借閱：" . implode("、", $success_titles) . "！";
            }
            if (count($error_details) > 0) {
                $error_msg = "⚠️ 部分書籍無法借閱：" . implode("；", $error_details) . "。";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = "借閱處理過程中發生錯誤：" . $e->getMessage();
        }
    } else {
        $error_msg = "⚠️ 請先勾選至少一本要借閱的書籍！";
    }
}

// 🔍 後端查詢
$search = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $search = trim($_GET['search']);
}
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM book WHERE title LIKE ? OR author LIKE ?");
    $stmt->execute(["%$search%", "%$search%"]);
    $books = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("SELECT * FROM book");
    $books = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>線上圖書借閱系統 - 借書大廳</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 30px 20px; }
        
        /* 🌟 核心改動：將最大寬度從 900px 放寬至 1140px (現代網頁 RWD 標準黃金比例) */
        .container { max-width: 1140px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
        
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #007bff; padding-bottom: 15px; margin-bottom: 25px; }
        .header h2 { margin: 0; font-size: 24px; color: #333; }
        .nav-links a { margin-right: 15px; color: #007bff; text-decoration: none; font-weight: bold; }
        .nav-links a:hover { text-decoration: underline; }
        .logout-btn { color: red !important; }
        
        /* 🔍 搜尋區塊稍微加大 Padding，看起來大器而不空洞 */
        .search-section { background-color: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 5px solid #28a745; margin-bottom: 25px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.02); }
        .search-inline { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .search-inline input, .btn-search, .btn-clear { box-sizing: border-box; height: 42px; font-size: 15px; }
        .search-inline input { padding: 10px 15px; border: 1px solid #ced4da; border-radius: 6px; flex: 1; transition: border-color 0.15s ease-in-out; }
        .search-inline input:focus { border-color: #28a745; outline: 0; box-shadow: 0 0 0 0.2rem rgba(40,167,69,0.25); }
        
        .btn-search { background-color: #28a745; color: white; border: none; padding: 0 25px; border-radius: 6px; cursor: pointer; font-weight: bold; line-height: 42px; }
        .btn-search:hover { background-color: #218838; }
        .btn-clear { display: inline-block; background-color: #6c757d; color: white; text-decoration: none; padding: 0 18px; border-radius: 6px; font-weight: bold; line-height: 42px; text-align: center; }
        .btn-clear:hover { background-color: #5a6268; }
        
        /* 🛠️ 批次操作區塊配合加寬版，兩端對齊排版更舒服 */
        .batch-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding: 15px 20px; background-color: #e9ecef; border-radius: 8px; border: 1px solid #dee2e6; }
        .btn-batch { background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 15px; box-shadow: 0 2px 4px rgba(0,123,255,0.2); }
        .btn-batch:hover { background-color: #0056b3; }

        /* 🌟 表格優化：加寬後增加 padding，字體微調，讓空白變成舒適的留白 */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; border-radius: 8px; overflow: hidden; box-shadow: 0 0 5px rgba(0,0,0,0.02); }
        th, td { padding: 14px 18px; text-align: left; border-bottom: 1px solid #dee2e6; vertical-align: middle; }
        th { background-color: #007bff; color: white; font-weight: 600; }
        
        /* 欄位寬度控制：讓書名獲得最多的呼吸空間，其他欄位緊湊定位，這樣畫面就不會被扯散 */
        .col-cb { width: 50px; text-align: center; }
        .col-id { width: 100px; }
        .col-author { width: 180px; }
        .col-qty { width: 120px; }
        .col-action { width: 120px; }

        input[type="checkbox"] { transform: scale(1.3); cursor: pointer; }
        
        .btn-borrow, .btn-disabled {
            display: inline-block; height: 34px; line-height: 34px; padding: 0 16px; font-size: 14px;
            border-radius: 6px; border: none; cursor: pointer; font-weight: bold; box-sizing: border-box;
            text-align: center; white-space: nowrap;
        }
        .btn-borrow { background-color: #28a745; color: white; }
        .btn-borrow:hover { background-color: #218838; }
        .btn-disabled { background-color: #6c757d; color: white; cursor: not-allowed; }
        
        .alert-success { background-color: #d4edda; color: #155724; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; }
        .alert-danger { background-color: #f8d7da; color: #721c24; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; }
        h3 { color: #333; font-size: 18px; margin-top: 10px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>📚 線上借書大廳</h2>
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
        <form method="GET" action="index.php" class="search-inline">
            <label style="font-weight: bold; color: #495057;">書籍查詢：</label>
            <input type="text" name="search" placeholder="請輸入書名或作者關鍵字..." 
                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit" class="btn-search">搜尋書籍</button>
            <?php if ($search !== ''): ?>
                <a href="index.php" class="btn-clear">清除搜尋</a>
            <?php endif; ?>
        </form>
    </div>

    <h3>📖 目前館藏清單</h3>
    
    <form action="index.php" method="POST" id="mainBorrowForm">
        <input type="hidden" name="action" value="batch_borrow">

        <?php if (count($books) > 0): ?>
        <div class="batch-actions">
            <span style="color: #495057; font-weight: bold;">💡 提示：您可以勾選多本書籍，然後點擊右側按鈕進行批次借閱。</span>
            <button type="submit" class="btn-batch" onclick="return confirmBatchBorrow();">🛒 一鍵借閱勾選書籍</button>
        </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th class="col-cb" style="text-align: center;">
                        <input type="checkbox" id="selectAll" onclick="toggleAll(this)" title="全選/取消全選">
                    </th>
                    <th class="col-id">書籍編號</th>
                    <th>書名</th>
                    <th class="col-author">作者</th>
                    <th class="col-qty">剩餘庫存</th>
                    <th class="col-action">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($books) > 0): ?>
                    <?php foreach ($books as $book): ?>
                        <tr>
                            <td style="text-align: center;">
                                <?php if ($book['quantity'] > 0): ?>
                                    <input type="checkbox" name="book_ids[]" value="<?php echo $book['book_id']; ?>" class="book-cb" id="cb_<?php echo $book['book_id']; ?>">
                                <?php else: ?>
                                    <input type="checkbox" disabled title="已無庫存">
                                <?php endif; ?>
                            </td>
                            <td><code>#<?php echo htmlspecialchars($book['book_id']); ?></code></td>
                            <td>
                                <?php if ($book['quantity'] > 0): ?>
                                    <label for="cb_<?php echo $book['book_id']; ?>" style="cursor:pointer; font-size: 16px; color: #007bff;"><strong><?php echo htmlspecialchars($book['title']); ?></strong></label>
                                <?php else: ?>
                                    <span style="color:#6c757d; font-size: 16px;"><strong><?php echo htmlspecialchars($book['title']); ?></strong></span>
                                <?php endif; ?>
                            </td>
                            <td><span style="color: #495057;"><?php echo htmlspecialchars($book['author']); ?></span></td>
                            <td style="font-weight: bold; color: <?php echo $book['quantity'] > 0 ? '#28a745' : '#dc3545'; ?>">
                                <?php echo htmlspecialchars($book['quantity']); ?> 冊
                            </td>
                            <td>
                                <?php if ($book['quantity'] > 0): ?>
                                    <button type="button" class="btn-borrow" onclick="singleBorrow(<?php echo $book['book_id']; ?>)">單獨借閱</button>
                                <?php else: ?>
                                    <button class="btn-disabled" disabled>已無庫存</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #721c24; background-color: #f8d7da; font-weight: bold; padding: 20px;">❌ 找不到符合關鍵字的書籍。</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
</div>

<script>
    function toggleAll(source) {
        let checkboxes = document.querySelectorAll('.book-cb:not([disabled])');
        checkboxes.forEach(cb => cb.checked = source.checked);
    }
    function confirmBatchBorrow() {
        let checkedCount = document.querySelectorAll('.book-cb:checked').length;
        if (checkedCount === 0) { alert("請先在左側勾選至少一本要借閱的書籍！"); return false; }
        return confirm("確定要一次借出這 " + checkedCount + " 本書嗎？");
    }
    function singleBorrow(bookId) {
        if (confirm('確定要借閱這本書嗎？')) {
            document.querySelectorAll('.book-cb').forEach(cb => cb.checked = false);
            document.getElementById('cb_' + bookId).checked = true;
            document.getElementById('mainBorrowForm').submit();
        }
    }
</script>
</body>
</html>